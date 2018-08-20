<?php

class ArticleAsJson {
	static $media = [ ];
	static $heroImage = null;
	static $mediaDetailConfig = [
		'imageMaxWidth' => false
	];

	const CACHE_VERSION = 3.38;

	const ICON_MAX_SIZE = 48;
	// Line height in Mercury
	const ICON_SCALE_TO_MAX_HEIGHT = 20;
	const MAX_MERCURY_CONTENT_WIDTH = 985;

	const MEDIA_CONTEXT_ARTICLE_IMAGE = 'article-image';
	const MEDIA_CONTEXT_ARTICLE_VIDEO = 'article-video';
	const MEDIA_CONTEXT_GALLERY_IMAGE = 'gallery-image';
	const MEDIA_CONTEXT_ICON = 'icon';

	const MEDIA_ICON_TEMPLATE = 'extensions/wikia/ArticleAsJson/templates/media-icon.mustache';
	const MEDIA_THUMBNAIL_TEMPLATE = 'extensions/wikia/ArticleAsJson/templates/media-thumbnail.mustache';
	const MEDIA_GALLERY_TEMPLATE = 'extensions/wikia/ArticleAsJson/templates/media-gallery.mustache';
	const MEDIA_GALLERY_TEMPLATE_OLD = 'extensions/wikia/ArticleAsJson/templates/media-gallery-old.mustache';
	const MEDIA_LINKED_GALLERY_TEMPLATE_OLD = 'extensions/wikia/ArticleAsJson/templates/media-linked-gallery-old.mustache';

	private static function renderIcon( $media ) {
		$scaledSize = self::scaleIconSize( $media['height'], $media['width'] );

		try {
			$thumbUrl = VignetteRequest::fromUrl( $media['url'] )
				->thumbnailDown()
				->height( $scaledSize['height'] )
				->width( $scaledSize['width'] )
				->url();
		} catch (InvalidArgumentException $e) {
			// Media URL isn't valid Vignette URL so we can't generate the thumbnail
			$thumbUrl = null;
		}

		return self::removeNewLines(
			\MustacheService::getInstance()->render(
				self::MEDIA_ICON_TEMPLATE,
				[
					'url' => $thumbUrl,
					'height' => $scaledSize['height'],
					'width' => $scaledSize['width'],
					'title' => $media['title'],
					'href' => $media['href'],
					'caption' => $media['caption'] ?? ''
				]
			)
		);
	}

	private static function renderImage( $media ) {
		return self::removeNewLines(
			\MustacheService::getInstance()->render(
				self::MEDIA_THUMBNAIL_TEMPLATE,
				[
					'media' => $media,
					'mediaAttrs' => json_encode( $media ),
					'downloadIcon' => DesignSystemHelper::renderSvg( 'wds-icons-download', 'wds-icon' ),
					'chevronIcon' => DesignSystemHelper::renderSvg('wds-icons-menu-control-tiny', 'wds-icon wds-icon-tiny chevron'),
					'hasFigcaption' => !empty( $media['caption'] ) || ( !empty( $media['title'] ) && ( $media['isVideo'] || $media['isOgg'] ) )
				]
			)
		);
	}

	private static function renderGallery( $media, $hasLinkedImages ) {
		// TODO: clean me when new galleries in mobile-wiki are released and cache expires
		$isNewGalleryLayout = !empty( RequestContext::getMain()->getRequest()->getVal( 'premiumGalleries', false ) );
		if ( $isNewGalleryLayout ) {
			$rows = self::prepareGalleryRows($media);

			return self::removeNewLines(
				\MustacheService::getInstance()->render(
					self::MEDIA_GALLERY_TEMPLATE,
					[
						'galleryAttrs' => json_encode( $media ),
						'rows' => $rows,
						'downloadIcon' => DesignSystemHelper::renderSvg( 'wds-icons-download', 'wds-icon' ),
						'viewMoreLabel' => count($media) > 20 ? wfMessage('communitypage-view-more')->escaped() : false, // TODO:  XW-4793
					]
				)
			);
		} elseif ( $hasLinkedImages ) {
			return self::removeNewLines(
				\MustacheService::getInstance()->render(
					self::MEDIA_LINKED_GALLERY_TEMPLATE_OLD,
					[
						'galleryAttrs' => json_encode( $media ),
						'media' => $media,
						'downloadIcon' => DesignSystemHelper::renderSvg( 'wds-icons-download', 'wds-icon' ),
						'viewMoreLabel' => wfMessage('communitypage-view-more')->escaped(), // TODO:  XW-4793
						'linkedGalleryViewMoreVisible' => $hasLinkedImages && count($media) > 4,
						'chevronIcon' => DesignSystemHelper::renderSvg('wds-icons-menu-control-tiny', 'wds-icon wds-icon-tiny chevron')
					]
				)
			);
		} else {
			return self::removeNewLines(
				\MustacheService::getInstance()->render(
					self::MEDIA_GALLERY_TEMPLATE_OLD,
					[
						'galleryAttrs' => json_encode( $media ),
						'media' => $media,
						'downloadIcon' => DesignSystemHelper::renderSvg( 'wds-icons-download', 'wds-icon' ),
					]
				)
			);
		}
	}

	private static function prepareGalleryRows( $media ): array {
		switch ( count( $media ) ) {
			case 0:
				$result = [];
				break;
			case 1:
				$result = [
					[
						'typeRow1' => true,
						'items' => $media,
					]
				];
				break;
			case 2:
				$result = [
					self::getGalleryRow2items( $media ),
				];
				break;
			case 3:
				$result = [
					self::getGalleryRow3ItemsLeft( $media ),
				];
				break;
			case 4:
				$result = [
					self::getGalleryRow2items( array_slice( $media, 0, 2 ) ),
					self::getGalleryRow2items( array_slice( $media, 2, 2 ) ),
				];
				break;
			case 7:
				$result = [
					self::getGalleryRow2items( array_slice( $media, 0, 2 ) ),
					self::getGalleryRow3ItemsLeft( array_slice( $media, 2, 3 ) ),
					self::getGalleryRow2items( array_slice( $media, 5, 2 ) ),
				];
				break;
			default:
				$result = self::getGalleryRows( $media );
		}

		return $result;
	}

	private static function getGalleryRow2items( $items, $hidden = false ) {
		$thumbsize = 220;
		$items[0]['thumbnailUrl'] = self::getGalleryThumbnail( $items[0], $thumbsize);
		$items[0]['thumbSize'] = $thumbsize;
		$items[1]['thumbnailUrl'] = self::getGalleryThumbnail( $items[1], $thumbsize);
		$items[1]['thumbSize'] = $thumbsize;

		return [
			'typeRow2' => true,
			'items' => $items,
			'rowHidden' => $hidden,
		];
	}

	private static function getGalleryRow3ItemsLeft( $items, $hidden = false ) {
		$items[0]['thumbnailUrl'] = self::getGalleryThumbnail( $items[0], 300);
		$items[0]['thumbSize'] = 300;
		$items[1]['thumbnailUrl'] = self::getGalleryThumbnail( $items[1], 150);
		$items[1]['thumbSize'] = 150;
		$items[2]['thumbnailUrl'] = self::getGalleryThumbnail( $items[2], 150);
		$items[2]['thumbSize'] = 150;

		return [
			'typeRow3' => true,
			'left' => true,
			'leftColumn' => [ $items[0] ],
			'rightColumn' => [ $items[1], $items[2] ],
			'rowHidden' => $hidden,
		];
	}

	private static function getGalleryRow3ItemsRight( $items, $hidden = false ) {
		$items[0]['thumbnailUrl'] = self::getGalleryThumbnail( $items[0], 150);
		$items[0]['thumbSize'] = 150;
		$items[1]['thumbnailUrl'] = self::getGalleryThumbnail( $items[1], 150);
		$items[1]['thumbSize'] = 150;
		$items[2]['thumbnailUrl'] = self::getGalleryThumbnail( $items[2], 300);
		$items[2]['thumbSize'] = 300;

		return [
			'typeRow3' => true,
			'right' => true,
			'leftColumn' => [ $items[0], $items[1] ],
			'rightColumn' => [ $items[2] ],
			'rowHidden' => $hidden,
		];
	}

	private static function getGalleryThumbnail( $item, int $width ): string {
		try {
			return VignetteRequest::fromUrl( $item['url'] )
				->topCrop()
				->width( $width )
				->height( $width )
				->url();
		} catch (InvalidArgumentException $e) {
			return '';
		}
	}

	private static function getGalleryRows( $items ) {
		$itemsLeft = count( $items );
		$evenRow = false;
		$rowSequence = [];

		while ( $itemsLeft > 2 ) {
			// every odd row should have 3 images and every even row should have 2 images
			if ( $evenRow ) {
				$rowSequence[] = 2;
				$itemsLeft -= 2;
			} else {
				$rowSequence[] = 3;
				$itemsLeft -= 3;
			}

			$evenRow = !$evenRow;
		}

		switch ( $itemsLeft ) {
			case 0:
				break;
			case 1:
				// if there is one image left, change the last row with two images to have 3 images
				if ( $rowSequence[count( $rowSequence ) - 1] === 2 ) {
					$rowSequence[count( $rowSequence ) - 1] = 3;
				} else {
					$rowSequence[count( $rowSequence ) - 2] = 3;
				}
				break;
			case 2:
				// if there are 2 images left:
				//      if the last row has 2 images then add an image to last two rows with 2 images
				//      if the last row has 3 images just add new row with two images
				if ( $rowSequence[count( $rowSequence ) - 1] === 2 ) {
					$rowSequence[count( $rowSequence ) - 1] = 3;
					$rowSequence[count( $rowSequence ) - 3] = 3;
				} else {
					$rowSequence[] = 2;
				}
				break;
		}

		$result = [];
		$itemsTaken = 0;
		foreach ( $rowSequence as $index => $value ) {
			// By default ~20 first images is shown in gallery (first 8 rows), rest is hidden
			$rowHidden = $index > 7;

			switch ( $value ) {
				case 2:
					$result[] = self::getGalleryRow2items( array_slice( $items, $itemsTaken, 2 ), $rowHidden );
					$itemsTaken += 2;

					break;
				case 3:
					if ( $index % 2 != 0 ) {
						$result[] = self::getGalleryRow3ItemsRight( array_slice( $items, $itemsTaken, 3 ), $rowHidden );
					} else {
						$result[] = self::getGalleryRow3ItemsLeft( array_slice( $items, $itemsTaken, 3 ), $rowHidden );
					}
					$itemsTaken += 3;

					break;
				default:
					Wikia\Logger\WikiaLogger::instance()->warning(
						'Error while generating gallery, unexpected number of images in row'
					);
					break;
			}
		}

		return $result;
	}

	private static function removeNewLines( $string ) {
		return trim( preg_replace( '/\s+/', ' ', $string ) );
	}

	private static function createMarker( $media, $isGallery = false ) {
		if ( $isGallery ) {
			$hasLinkedImages = false;

			// TODO: remove this when new galleries are released
			if ( count(
				array_filter(
					$media,
					function ( $item ) {
						return $item['isLinkedByUser'];
					}
				)
			) ) {
				$hasLinkedImages = true;
			}

			return self::renderGallery( $media, $hasLinkedImages );
		} else if ( $media['context'] === self::MEDIA_CONTEXT_ICON ) {
			return self::renderIcon( $media );
		} else {
			return self::renderImage( $media );
		}
	}

	public static function createMediaObject( $details, $imageName, $caption = null, $link = null ) {
		$context = '';
		$media = [
			'type' => $details['mediaType'],
			'url' => $details['rawImageUrl'],
			'fileUrl' => $details['fileUrl'],
			'fileName' => str_replace( ' ', '_', $imageName),
			'title' => $imageName,
			'user' => $details['userName'],
			'mime' => $details['mime'],
			'isVideo' => $details['mediaType'] === 'video',
			'isOgg' => $details['mime'] === 'application/ogg'
		];

		// Only images are allowed to be linked by user
		if ( is_string( $link ) && $link !== '' && $media['type'] === 'image' ) {
			$media['href'] = $link;
			$media['isLinkedByUser'] = true;
		} else {
			// There is no easy way to link directly to a video, so we link to its file page
			$media['href'] = $media['type'] === 'video' ? $media['fileUrl'] : $media['url'];
			$media['isLinkedByUser'] = false;
		}

		if ( !empty( $details['width'] ) ) {
			$media['width'] = (int) $details['width'];
		}

		if ( !empty( $details['height'] ) ) {
			$media['height'] = (int) $details['height'];
		}

		if ( is_string( $caption ) && $caption !== '' ) {
			$media['caption'] = $caption;
		}

		if ( $details['mediaType'] == 'video' ) {
			$media['context'] = self::MEDIA_CONTEXT_ARTICLE_VIDEO;
			$media['views'] = (int) $details['videoViews'];
			$media['embed'] = $details['videoEmbedCode'];
			$media['duration'] = $details['duration'];
			$media['provider'] = $details['providerName'];
		}

		if ( isset( $details['context'] ) ) {
			$context = $details['context'];
		}

		if ( is_string( $context ) && $context !== '' ) {
			$media['context'] = $context;
		}

		return $media;
	}

	public static function onGalleryBeforeProduceHTML( $data, &$out ) {
		global $wgArticleAsJson;

		if ( $wgArticleAsJson ) {
			// TODO: clean me when new galleries in mobile-wiki are released and cache expires
			$isNewGalleryLayout = !empty( RequestContext::getMain()->getRequest()->getVal( 'premiumGalleries', false ) );

			$parser = ParserPool::get();
			$parserOptions = new ParserOptions();
			$title = F::app()->wg->Title;
			$media = [ ];

			foreach ( $data['images'] as $index => $image ) {
				$details = self::getMediaDetailWithSizeFallback(
					Title::newFromText( $image['name'], NS_FILE ),
					self::$mediaDetailConfig
				);
				$details['context'] = self::MEDIA_CONTEXT_GALLERY_IMAGE;

				if ( $details['exists'] === false ) {
					continue;
				}

				$caption = $image['caption'];

				if ( !empty( $caption ) ) {
					$caption = $parser->parse( $caption, $title, $parserOptions, false )->getText();
					$caption = self::unwrapParsedTextFromParagraph( $caption );
				}

				$linkHref = isset( $image['linkhref'] ) ? $image['linkhref'] : null;
				$mediaObj = self::createMediaObject( $details, $image['name'], $caption, $linkHref );
				$mediaObj['mediaAttr'] = json_encode( $mediaObj );
				$mediaObj['galleryRef'] = $index;

				// TODO: clean me when new galleries in mobile-wiki are released and cache expires
				if (!$isNewGalleryLayout) {
					try {
						$mediaObj['thumbnailUrl'] = VignetteRequest::fromUrl( $mediaObj['url'] )
							->topCrop()
							->width( 300 )
							->height( 300 )
							->url();
					} catch (InvalidArgumentException $e) {
						$mediaObj['thumbnailUrl'] = '';
					}
				}

				$media[] = $mediaObj;
			}

			self::$media[] = $media;

			if ( !empty( $media ) ) {
				$out = self::createMarker( $media, true );
			} else {
				$out = '';
			}

			ParserPool::release( $parser );

			return false;
		}

		return true;
	}

	public static function onExtendPortableInfoboxImageData( $data, &$ref, &$dataAttrs ) {
		$title = Title::newFromText( $data['name'] );
		if ( $title ) {
			$details = self::getMediaDetailWithSizeFallback( $title, self::$mediaDetailConfig );
			$details['context'] = $data['context'];
			$mediaObj = self::createMediaObject( $details, $title->getText(), $data['caption'] );
			self::$media[] = $mediaObj;
			$dataAttrs = $mediaObj;

			if ( $details['context'] == 'infobox-hero-image' && empty( self::$heroImage ) ) {
				self::$heroImage = $mediaObj;

				try {
					$height = PortableInfoboxMobileRenderService::MOBILE_THUMBNAIL_WIDTH * 5 / 4;
					$thumbnail4by5 = VignetteRequest::fromUrl( $mediaObj['url'] )
						->topCrop()
						->width( PortableInfoboxMobileRenderService::MOBILE_THUMBNAIL_WIDTH )
						->height( $height )
						->url();

					$thumbnail4by5x2 = VignetteRequest::fromUrl( $mediaObj['url'] )
						->topCrop()
						->width( PortableInfoboxMobileRenderService::MOBILE_THUMBNAIL_WIDTH * 2 )
						->height( $height * 2)
						->url();

					$thumbnail1by1 = VignetteRequest::fromUrl( $mediaObj['url'] )
						->topCrop()
						->width( PortableInfoboxMobileRenderService::MOBILE_THUMBNAIL_WIDTH )
						->height( PortableInfoboxMobileRenderService::MOBILE_THUMBNAIL_WIDTH )
						->url();

				} catch(InvalidArgumentException $e) {
					$thumbnail4by5 = '';
					$thumbnail4by5x2 = '';
					$thumbnail1by1 = '';
				}

				self::$heroImage['thumbnail4by5'] = $thumbnail4by5;
				self::$heroImage['thumbnail4by52x'] = $thumbnail4by5x2;
				self::$heroImage['thumbnail4by5Width'] = PortableInfoboxMobileRenderService::MOBILE_THUMBNAIL_WIDTH;
				self::$heroImage['thumbnail4by5Height'] = $height;
				self::$heroImage['thumbnail1by1'] = $thumbnail1by1;
				self::$heroImage['thumbnail1by1Size'] = PortableInfoboxMobileRenderService::MOBILE_THUMBNAIL_WIDTH;
			}

			$ref = count( self::$media ) - 1;
		}

		return true;
	}

	public static function onImageBeforeProduceHTML(
		&$dummy,
		Title &$title,
		&$file,
		&$frameParams,
		&$handlerParams,
		&$time,
		&$res
	) {
		global $wgArticleAsJson;

		if ( $wgArticleAsJson ) {
			$linkHref = '';

			if ( isset( $frameParams['link-title'] ) && $frameParams['link-title'] instanceof Title ) {
				$linkHref = $frameParams['link-title']->getLocalURL();
			} else if ( !empty( $frameParams['link-url'] ) ) {
				$linkHref = $frameParams['link-url'];
			}

			$details = self::getMediaDetailWithSizeFallback( $title, self::$mediaDetailConfig );

			if ( $details['exists'] === false ) {
				// Skip media when it doesn't exist

				$res = '';

				return false;
			}

			//information for mobile skins how they should display small icons
			$details['context'] = self::isIconImage( $details, $handlerParams ) ? self::MEDIA_CONTEXT_ICON :
				self::MEDIA_CONTEXT_ARTICLE_IMAGE;

			$caption = $frameParams['caption'] ?? null;
			$media = self::createMediaObject( $details, $title->getText(), $caption, $linkHref );
			$media['srcset'] = self::getSrcset( $media['url'], intval( $media['width'] ) );
			$media['thumbnail'] = self::getThumbnailUrlForWidth( $media['url'], 340 );

			self::$media[] = $media;

			$res = self::createMarker( $media );

			return false;
		}

		return true;
	}

	public static function getSrcset( string $url, int $originalWidth ): string {
		$widths = [ 284, 340, 732, 985 ];
		$srcSetItems = [];

		foreach ( $widths as $width ) {
			if ( $width <= $originalWidth ) {
				$thumb = self::getThumbnailUrlForWidth( $url, $width );
				$srcSetItems[] = "${thumb} ${width}w";
			}
		}

		return implode( ',', $srcSetItems );
	}

	public static function getThumbnailUrlForWidth( string $url, int $requestedWidth ) {
		try {
			$url = VignetteRequest::fromUrl( $url )
				->scaleToWidth( $requestedWidth )
				->url();
		} catch(InvalidArgumentException $e) {
			$url = '';
		}

		return $url;
	}

	public static function onPageRenderingHash( &$confstr ) {
		global $wgArticleAsJson;

		if ( $wgArticleAsJson ) {
			$confstr .= '!ArticleAsJson:' . self::CACHE_VERSION;

			// this is pseudo-versioning query param for collapsible sections (XW-4393)
			// should be removed after all App caches are invalidated
			if ( !empty( RequestContext::getMain()
				->getRequest()
				->getVal( 'collapsibleSections' ) )
			) {
				$confstr .= ':collapsibleSections';
			}
		}

		return true;
	}

	public static function onParserAfterTidy( Parser $parser, &$text ): bool {
		global $wgArticleAsJson;

		if ( $wgArticleAsJson && !is_null( $parser->getRevisionId() ) ) {
			foreach ( self::$media as &$media ) {
				self::linkifyMediaCaption( $parser, $media );
			}

			Hooks::run( 'ArticleAsJsonBeforeEncode', [ &$text ] );

			$text = json_encode(
				[
					'content' => $text,
					'heroImage' => self::$heroImage
				]
			);
		}

		return true;
	}

	public static function onShowEditLink( Parser $parser, &$showEditLink ): bool {
		global $wgArticleAsJson;

		//We don't have editing in this version
		if ( $wgArticleAsJson ) {
			$showEditLink = false;
		}

		return true;
	}

	/**
	 * Remove any limit report, we don't need that in json
	 *
	 * @param $parser Parser
	 * @param $report
	 *
	 * @return bool
	 */
	public static function reportLimits( $parser, &$report ) {
		global $wgArticleAsJson;

		if ( $wgArticleAsJson ) {
			$report = '';

			return false;
		}

		return true;
	}

	/**
	 * Because we take captions out of main parser flow we have to replace links manually
	 *
	 * @param Parser $parser
	 * @param $media
	 */
	private static function linkifyMediaCaption( Parser $parser, &$media ) {
		if ( array_key_exists( 'caption', $media ) ) {
			$caption = $media['caption'];

			if ( is_string( $caption ) &&
				( strpos( $caption, '<!--LINK' ) !== false || strpos( $caption, '<!--IWLINK' ) !== false )
			) {
				$parser->replaceLinkHolders( $media['caption'] );
			}
		}
	}

	/**
	 * Copied from \Message::toString()
	 *
	 * @param $text
	 *
	 * @return string
	 */
	private static function unwrapParsedTextFromParagraph( $text ) {
		$matches = [ ];

		if ( preg_match( '/^<p>(.*)\n?<\/p>\n?$/sU', $text, $matches ) ) {
			$text = $matches[1];
		}

		return $text;
	}

	/**
	 * Safely get property from an array with an optional default
	 *
	 * @param array $array
	 * @param string $key
	 * @param bool $default
	 *
	 * @return bool
	 */
	private static function getWithDefault( $array, $key, $default = false ) {
		if ( array_key_exists( $key, $array ) ) {
			return $array[$key];
		}

		return $default;
	}

	/**
	 * @desc Determines if image is a small image used by users on desktop
	 * as an icon. Users to it by explicitly adding
	 * '{width}px' or 'x{height}px' to image wikitext or uploading a small image.
	 *
	 * @param $details - media details
	 * @param $handlerParams
	 *
	 * @return bool true if one of the image sizes is smaller than ICON_MAX_SIZE
	 */
	private static function isIconImage( $details, $handlerParams ) {
		$smallFixedWidth = self::isIconSize( $handlerParams, 'width' );
		$smallFixedHeight = self::isIconSize( $handlerParams, 'height' );
		$smallWidth = self::isIconSize( $details, 'width' );
		$smallHeight = self::isIconSize( $details, 'height' );
		$isInfoIcon = self::isInfoIcon( self::getWithDefault( $handlerParams, 'template-type' ) );

		return $smallFixedWidth || $smallFixedHeight || $smallWidth || $smallHeight || $isInfoIcon;
	}

	/**
	 * @desc Checks if passed property is set and if it's value is smaller than ICON_MAX_SIZE
	 *
	 * @param array $param an array with data
	 * @param string $key
	 *
	 * @return bool true if size is smaller than ICON_MAX_SIZE
	 * and returns false if $param[$key] does not exist
	 */
	private static function isIconSize( $param, $key ) {
		$value = self::getWithDefault( $param, $key );

		return $value ? $value <= self::ICON_MAX_SIZE : false;
	}

	private static function isInfoIcon( $templateType ) {
		return $templateType == TemplateClassificationService::TEMPLATE_INFOICON;
	}

	/**
	 * @param $originalHeight
	 * @param $originalWidth
	 *
	 * @return array
	 */
	private static function scaleIconSize( $originalHeight, $originalWidth ) {
		$height = $originalHeight;
		$width = $originalWidth;
		$maxHeight = self::ICON_SCALE_TO_MAX_HEIGHT;

		if ( $originalHeight > $maxHeight ) {
			$height = $maxHeight;
			$width = intval( $maxHeight * $originalWidth / $originalHeight );
		}

		return [
			'height' => $height,
			'width' => $width
		];
	}

	/**
	 * For some media WikiaFileHelper::getMediaDetail returns size 0 (width or height).
	 * Instead of showing broken image we want to show the image
	 * and as the fallback size we use the maximum content width handled by Mercury
	 *
	 * @param Title $title
	 * @param array $mediaDetailConfig
	 * @param int $fallbackSize
	 *
	 * @return array
	 */
	private static function getMediaDetailWithSizeFallback(
		$title,
		$mediaDetailConfig,
		$fallbackSize = self::MAX_MERCURY_CONTENT_WIDTH
	) {
		$mediaDetail = WikiaFileHelper::getMediaDetail( $title, $mediaDetailConfig );

		if ( $mediaDetail['exists'] === true ) {
			if ( empty( $mediaDetail['width'] ) ) {
				$mediaDetail['width'] = $fallbackSize;

				\Wikia\Logger\WikiaLogger::instance()->notice(
					'ArticleAsJson - Media width was empty - fallback to fallbackSize',
					[
						'media_details' => $mediaDetail,
						'fallback_size' => $fallbackSize
					]
				);
			}

			if ( empty( $mediaDetail['height'] ) ) {
				$mediaDetail['height'] = $fallbackSize;

				\Wikia\Logger\WikiaLogger::instance()->notice(
					'ArticleAsJson - Media height was empty - fallback to fallbackSize',
					[
						'media_details' => $mediaDetail,
						'fallback_size' => $fallbackSize
					]
				);
			}
		}

		return $mediaDetail;
	}
}
