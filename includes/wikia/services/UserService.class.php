<?php

class UserService {

	const CACHE_EXPIRATION = 86400;//1 day

	const SHOW_FEEDS_IF_REGISTERED_AFTER = '20190210000000';

	public static function getNameFromUrl( $url ) {
		$out = false;

		$userUrlParted = explode( ':', $url, 3 );
		if ( isset( $userUrlParted[2] ) ) {
			$user = User::newFromName( urldecode( $userUrlParted[2] ) );
			if ( $user instanceof User ) {
				$out = $user->getName();
			}
		}

		return $out;
	}

	/**
	 * get main page for current user respecting user preferences
	 *
	 * @param User $user
	 *
	 * @return Title
	 * @throws MWException
	 */
	public static function getLandingPage( User $user ): Title {
		global $wgEnableFeedsAndPostsExt, $wgEnableDiscussions;

		$value = self::getLandingPagePreference( $user );

		switch ( $value ) {
			case UserPreferencesV2::LANDING_PAGE_FEEDS:
				if ( $wgEnableFeedsAndPostsExt && $wgEnableDiscussions ) {
					return new class extends Title {
						public function getFullURL( $query = '', $query2 = false ) {
							global $wgScriptPath;

							return wfExpandUrl( "$wgScriptPath/f" );
						}
					};
				}

				return Title::newMainPage();

				break;
			case UserPreferencesV2::LANDING_PAGE_RECENT_CHANGES:
				return SpecialPage::getTitleFor( 'RecentChanges' );

				break;
			case UserPreferencesV2::LANDING_PAGE_WIKI_ACTIVITY:
				return SpecialPage::getTitleFor( 'WikiActivity' );

				break;
			case UserPreferencesV2::LANDING_PAGE_MAIN_PAGE:
			default:
				return Title::newMainPage();
		}
	}

	public static function getLandingPagePreference( User $user ) {
		$value = $user->getGlobalPreference( UserPreferencesV2::LANDING_PAGE_PROP_NAME );

		if ( $user->getRegistration() >= static::SHOW_FEEDS_IF_REGISTERED_AFTER ) {
			return $value ?? UserPreferencesV2::LANDING_PAGE_FEEDS;
		}

		return $value ?? UserPreferencesV2::LANDING_PAGE_MAIN_PAGE;
	}

	/**
	 * Method for acquiring the list of users from database as User class objects.
	 *
	 * @param $ids array|string list of ids or names for users, should be specified as
	 *             array( 'user_id' => array(ids)|id [, 'user_name' => array(names)|name ]) or array( ids and names )
	 *
	 * @return User[] list of User class objects
	 */
	public function getUsers( $ids ) {
		wfProfileIn( __METHOD__ );

		$where = $this->parseIds( $ids );
		$result = $this->getUsersObjects( $where );

		wfProfileOut( __METHOD__ );

		return array_unique( $result );
	}

	/** Helper methods for getUsers */

	/**
	 * Methods builds User object depending on Ids and Names in ids array
	 *
	 * @param $ids array list of user ids and names to look for
	 *
	 * @return array with User objects
	 */
	private function getUsersObjects( $ids ) {
		wfProfileIn( __METHOD__ );
		$result = [];

		if ( isset( $ids['user_id'] ) ) {
			foreach ( $ids['user_id'] as $id ) {
				$user = User::newFromId( $id );
				//skip default user
				if ( $user && $user->getTouched() != 0 ) {
					$result[] = $user;
				}
			}
		}
		if ( isset( $ids['user_name'] ) ) {
			foreach ( $ids['user_name'] as $name ) {
				$user = User::newFromName( $name );
				//skip default user
				if ( $user && $user->getTouched() != 0 ) {
					$result[] = $user;
				}
			}
		}
		wfProfileOut( __METHOD__ );

		return array_unique( $result );
	}


	/**
	 * The method parse ids so they can be used in sql query and cache
	 *
	 * @param $ids array|string ids and names to parse
	 *
	 * @return array
	 */
	private function parseIds( $ids ) {

		if ( !isset( $ids['user_id'] ) && !isset( $ids['user_name'] ) ) {
			$conds = [];
			//make it array, so we can filter it using array_filter
			if ( !is_array( $ids ) ) {
				$ids = [ $ids ];
			}
			foreach ( $ids as $id ) {
				if ( is_numeric( $id ) ) {
					$numeric[] = $id;
				} elseif ( !empty( $id ) ) {
					$text[] = $id;
				}
			}
			if ( !empty( $numeric ) ) {
				$conds['user_id'] = $numeric;
			}
			if ( !empty( $text ) ) {
				$conds['user_name'] = $text;
			}

			return $conds;
		}

		return $ids;
	}
}
