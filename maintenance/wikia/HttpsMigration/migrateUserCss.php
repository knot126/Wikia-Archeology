<?php

/**
* Maintenance script to migrate urls included in custom CSS files to https/protocol relative
* @usage
* 	# this will migrate assets for wiki with ID 119:
*   run_maintenance --script='wikia/HttpsMigration/MigrateUserCssToHttps.php --dryRun' --id=119
*/

ini_set( 'display_errors', 'stderr' );
ini_set( 'error_reporting', E_NOTICE );

require_once( dirname( __FILE__ ) . '/../../Maintenance.php' );
//require_once('/usr/wikia/slot1/current/src/maintenance/Maintenance.php');

use \Wikia\Logger\WikiaLogger;

/**
 * Class MigrateUserCssToHttps
 */
class MigrateUserCssToHttps extends Maintenance {

	protected $dryRun  = false;
	protected $fh;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Migrates urls in custom CSS assets to HTTPS';
		$this->addOption( 'dryRun', 'Dry run mode', false, false, 'd' );
		$this->addOption( 'file', 'CSV file where to save values that are going to be altered', false, true, 'f' );
	}

	public function __destruct()  {
		if ( $this->fh ) {
			fclose( $this->fh );
		}
	}

	public function logUrlChange( $description, $oldValue, $newValue ) {
		global $wgCityId;
		if ( $oldValue !== $newValue ) {
			if ( $this->fh ) {
				fwrite( $this->fh,
					sprintf("%d, \"%s\", \"%s\", \"%s\", \"%s\"\n", $wgCityId, $this->currentTitle->getDBkey(),
						$description, $oldValue, $newValue) );
			}
		}
	}

	private function isHttpLink( $url ) {
		return startsWith( $url, 'http://' );
	}

	private function isHttpsLink( $url ) {
		return startsWith( $url, 'https://' );
	}

	private function isProtocolRelative( $url ) {
		return startsWith( $url, '//' );
	}

	private function isLocalLink($link) {

		if ( $this->isHttpLink( $link ) ||
			$this->isHttpsLink( $link ) ||
			$this->isProtocolRelative( $link ) ) {
			return false;
		}
		return true;
	}

	private function isWikiaComLink( $url ) {
		$host = parse_url( $url, PHP_URL_HOST );
		return endsWith( $host, '.wikia.com' );
	}

	private function isWikiaLink( $url ) {
		$host = parse_url( $url, PHP_URL_HOST );
		return endsWith( $host, '.wikia.com' ) || endsWith( $host, '.wikia.nocookie.net' );
	}

	private function isVignetteUrl( $url ) {
		$host = parse_url( $url, PHP_URL_HOST );
		return preg_match( '/^(vignette|images|img|static)\d*\.wikia.nocookie.net$/', $host ) || $host === 'images.wikia.com';
	}

	private function upgradeToHttps( $url ) {
		if ( $this->isHttpLink( $url ) ) {
			$url = http_build_url( $url, [ 'scheme' => 'https' ] );
		}
		return $url;
	}

	private function countSubdomains( $url ) {
		$host = parse_url( $url, PHP_URL_HOST );
		return substr_count( $host, '.' );
	}

	private function maybeProtolRelative( $url ) {
		if ( $this->isWikiaComLink( $url ) || $this->isHttpLink( $url ) || $this->countSubdomains( $url ) === 2 ) {
			$url = wfProtocolUrlToRelative( $url );
		}
		return $url;
	}

	private function upgradeThirdPartyLink( $url ) {
		$known_https_hosts = [ 'en.wikipedia.org', 'i.imgur.com', 'upload.wikimedia.org', 'fonts.googleapis.com' ];
		$host = parse_url( $url, PHP_URL_HOST );
		if ( in_array( $host, $known_https_hosts ) ) {
			$newUrl = $this->upgradeToHttps( $url );
			$this->logUrlChange( 'Upgraded third party url', $url, $newUrl );
			$url = $newUrl;
		} else {
			$this->output( "Found unrecognized third party http url {$url}\n" );
		}
		return $url;
	}

	private function replaceCssLinks( $matches ) {
		$url = $matches[ 1 ];
		if ( $this->isLocalLink( $url ) ) {
			//$this->output( "Skipping local link {$matches[1]}\n" );
		} elseif ( !$this->isWikiaLink( $url ) ) {
			if ( $this->isHttpLink( $matches[ 1 ] ) ) {
				$url = $this->upgradeThirdPartyLink( $url );
			}
		} elseif ( $this->isVignetteUrl( $url ) ) {
			$url = $this->upgradeToHttps( http_build_url( $url, [ 'host' => 'vignette.wikia.nocookie.net' ] ) );
			$this->logUrlChange( 'Replaced vignette url', $matches[1], $url );
		} else {
			if ( $this->isWikiaComLink( $url ) ) {
				$url = $this->maybeProtolRelative( $url );
				$this->logUrlChange( 'Converted url to protocol relative', $matches[1], $url );
			} else {
				$this->output( "Don't know to handle {$url}\n" );
			}
		}
		$matches[0] = str_replace( $matches[ 1 ], $url, $matches[0] );
		return $matches[0];
	}

	private function updateCSSContent($text) {
		$lines = explode("\n", $text);
		// check if we covered all CSS urls with our regex
		foreach($lines as $line) {
			if ( strpos( $line, 'http://' ) !== FALSE || strpos( $line, 'https://' ) !== FALSE) {
				if ( strpos( $line, 'url(' ) === FALSE &&
					strpos( $line, 'url (' ) === FALSE &&
					strpos( $line, '(src=\'' ) === FALSE && strpos( $line, '(src="' ) === FALSE &&
					strpos( $line, '@import' ) === FALSE) {
					$this->output( "Unexpected url usage in {$line}\n" );
				}
			}
		}
		$text =  preg_replace_callback( '/url\s*\([\'"]?(.*?)[\'"]?\)/', [ $this, 'replaceCssLinks' ], $text );
		$text = preg_replace_callback( '/@import\s+[\'"](.*?)[\'"]/', [ $this, 'replaceCssLinks' ], $text );
		return preg_replace_callback( '/\(src=[\'"](.*?)[\'"]/', [ $this, 'replaceCssLinks' ], $text );
	}

	private function migrateCSS( Title $title ) {
		$revId = $title->getLatestRevID();
		$this->currentTitle = $title;
		$revision = Revision::newFromId( $revId );
		if( !is_null( $revision ) ) {
			$text = $revision->getText();
			$updatedText = $this->updateCSSContent($text);
			if ($text !== $updatedText) {
				if ( !$this->dryRun ) {
					// TBD...
					/*
					$article = new Article( $title );
					$editPage = new EditPage( $article );
					$editPage->initialiseForm();
					//$editPage->edittime = $article->getTimestamp();
					$editPage->summary = ".....";

					$result = [ ];
					$status = $editPage->internalAttemptSave( $result, true );
					// $status->isGood()
					*/
				}
				// save changes, return true
				return true;
			}
		}
		return false;
	}

	public function execute() {
		global $wgUser, $wgCityId;

		$wgUser = User::newFromName( Wikia::BOT_USER );

		$this->dryRun = $this->hasOption( 'dryRun' );
		$fileName = $this->getOption( 'file', false );
		if ( $fileName ) {
			$this->fh = fopen( $fileName, "a" );
			if ( !$this->fh ) {
				$this->error( "Could not open file '$fileName' for write!'\n" );
				return false;
			}
		}

		$this->output( "Running on city " . $wgCityId . "\n" );

		$this->db = wfGetDB( DB_SLAVE );

		$where = [ 'page_namespace' => NS_MEDIAWIKI, 'page_is_redirect' => 0 ];
		$options = [ 'ORDER BY' => 'page_title ASC' ];
		$result = $this->db->select( 'page', [ 'page_id', 'page_title', 'page_is_redirect' ], $where, __METHOD__, $options );
		$migratedFiles = 0;
		foreach( $result as $row ) {
			$title = Title::makeTitle( NS_MEDIAWIKI, $row->page_title );
			if ( $title->isCssPage() ) {
				$this->output("Processing CSS file {$row->page_title}...\n");
				if ( $this->migrateCSS( $title ) ) {
					$migratedFiles += 1;
				}
			}
		}
		$result->free();
		$this->output("Migrated {$migratedFiles} CSS files.\n");
	}

}

$maintClass = "MigrateUserCssToHttps";
require_once( RUN_MAINTENANCE_IF_MAIN );
