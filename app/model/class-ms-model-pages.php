<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Plugin Pages model.
 *
 * Main MS Pages class, contains any Membership page functions.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Pages extends MS_Model_Option {

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @staticvar MS_Model_Settings
	 */
	public static $instance;

	/**
	 * Plugin pages constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const MS_PAGE_MEMBERSHIPS = 'memberships';
	const MS_PAGE_PROTECTED_CONTENT = 'protected-content';
	const MS_PAGE_ACCOUNT = 'account';
	const MS_PAGE_REGISTER = 'register';
	const MS_PAGE_REG_COMPLETE = 'registration-complete';

	/**
	 * Association between membership page-types and WordPress post_ids.
	 *
	 * @since  1.0.4.5
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Returns the singleton instance of the MS_Model_Pages object
	 *
	 * @since  1.1.0
	 * @return MS_Model_Pages
	 */
	static public function get_model() {
		static $Model = null;

		if ( null === $Model ) {
			$Model = MS_Factory::load( 'MS_Model_Pages' );
		}

		return $Model;
	}

	/**
	 * Returns a MS_Model_Pages setting value (these are the association between
	 * our Membership Page types and WordPress posts)
	 *
	 * @since  1.0.4.5
	 * @param  string $key The setting key.
	 * @return any The setting value. A post_id or 0.
	 */
	static public function get_setting( $key ) {
		$model = self::get_model();

		if ( ! isset( $model->settings[ $key ] ) ) {
			$model->settings[$key] = 0;
		}

		return apply_filters(
			'ms_model_pages_get_setting',
			$model->settings[$key],
			$key
		);
	}

	/**
	 * Saves a MS_Model_Pages setting value.
	 *
	 * @since  1.0.4.5
	 * @param  string $key The setting key.
	 * @param  any $value The new setting value.
	 */
	static public function set_setting( $key, $value ) {
		$model = self::get_model();

		$value = apply_filters(
			'ms_model_pages_set_setting',
			$value,
			$key
		);

		$model->settings[$key] = $value;
		$model->save();
	}

	/**
	 * Get MS Page types
	 *
	 * @since 1.0.0
	 *
	 * @return array{
	 *     @type string $page_type The ms page type.
	 *     @type string $title The page type title.
	 * }
	 */
	static public function get_page_types() {
		static $Page_types;

		if ( empty( $Page_types ) ) {
			$Page_types = array(
				self::MS_PAGE_MEMBERSHIPS => __( 'Memberships', MS_TEXT_DOMAIN ),
				self::MS_PAGE_PROTECTED_CONTENT => __( 'Protected Content', MS_TEXT_DOMAIN ),
				self::MS_PAGE_REGISTER => __( 'Register', MS_TEXT_DOMAIN ),
				self::MS_PAGE_REG_COMPLETE => __( 'Registration Complete', MS_TEXT_DOMAIN ),
				self::MS_PAGE_ACCOUNT => __( 'Account', MS_TEXT_DOMAIN ),
			);

			$Page_types = apply_filters(
				'ms_model_pages_get_page_types',
				$Page_types
			);
		}

		return $Page_types;
	}

	/**
	 * Returns a longer description for a page-type
	 *
	 * @since  1.1.0
	 * @param  string $type The page-type
	 * @return string The full description
	 */
	static public function get_description( $type ) {
		static $Description = null;

		if ( null === $Description ) {
			$Description = array(
				self::MS_PAGE_MEMBERSHIPS => __( 'A list with all public memberships.', MS_TEXT_DOMAIN ),
				self::MS_PAGE_PROTECTED_CONTENT => __( 'Displayed when a user cannot access the requested page.', MS_TEXT_DOMAIN ),
				self::MS_PAGE_REGISTER => __( 'Guests can register a new account here.', MS_TEXT_DOMAIN ),
				self::MS_PAGE_REG_COMPLETE => __( 'Thank you page after registration is completed.', MS_TEXT_DOMAIN ),
				self::MS_PAGE_ACCOUNT => __( 'Shows details about the current user.', MS_TEXT_DOMAIN ),
			);

			$Description = apply_filters(
				'ms_model_pages_get_description',
				$Description
			);
		}

		if ( ! isset( $Description[$type] ) ) {
			$Description[$type] = '';
		}

		return $Description[$type];
	}


	/**
	 * Validate ms page type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The page type to validate.
	 * @return boolean True if valid.
	 */
	static public function is_valid_type( $type ) {
		static $Res = array();

		if ( ! isset( $Res[$type] ) ) {
			$Res[$type] = array_key_exists( $type, MS_Model_Pages::get_page_types() );

			$Res[$type] = apply_filters(
				'ms_model_pages_is_valid_type',
				$Res[$type]
			);
		}

		return $Res[$type];
	}

	/**
	 * Get MS Pages.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_Post[] The page model objects.
	 */
	static public function get_pages() {
		static $Pages = null;

		if ( null === $Pages ) {
			$Pages = array();
			$page_types = MS_Model_Pages::get_page_types();

			foreach ( $page_types as $page_type => $title ) {
				$page_id = self::get_setting( $page_type );
				if ( empty( $page_id ) ) { continue; }

				$the_page = get_post( $page_id );
				if ( empty ( $the_page ) ) { continue; }

				$Pages[$page_type] = apply_filters(
					'ms_model_pages_get_pages_item',
					$the_page,
					$page_type,
					$page_id
				);
			}

			$Pages = apply_filters(
				'ms_model_pages_get_pages',
				$Pages
			);
		}

		return $Pages;
	}

	/**
	 * Get specific MS Page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type to retrieve the page.
	 * @return WP_Post The page model object.
	 */
	static public function get_page( $page_type ) {
		$result = null;

		if ( self::is_valid_type( $page_type ) ) {
			// Get a list of all WP_Post items.
			$pages = self::get_pages();

			if ( ! empty( $pages[ $page_type ] ) ) {
				$result = $pages[ $page_type ];
			}
		} else {
			MS_Helper_Debug::log( 'ms_model_pages_get_page error: invalid page type: ' . $page_type );
		}

		return apply_filters(
			'ms_model_pages_get_page',
			$result
		);
	}

	/**
	 * Get specific MS Page using either ID or slug information.
	 *
	 * @since 1.0.4.4
	 *
	 * @param string $field The field to check. [id|slug]
	 * @param string $value The field value
	 * @return null|WP_Post The page object.
	 */
	static public function get_page_by( $field, $value ) {
		static $Page_list = array();

		if ( ! isset( $Page_list[$field] ) ) {
			$Page_list[$field] = array();
		}

		if ( ! isset( $Page_list[$field][ $value ] ) ) {
			$page_found = null;

			switch ( $field ) {
				case 'id': $value = absint( $value ); break;
			}

			$ms_pages = self::get_pages();
			$found = false;

			foreach ( $ms_pages as $type => $page ) {
				switch ( $field ) {
					case 'id':   $found = ($value === absint( $page->ID ) ); break;
					case 'slug': $found = ($value === $page->post_name ); break;
				}

				if ( $found ) {
					$page_found = $page;
					break;
				}
			}

			$Page_list[$field][ $value ] = apply_filters(
				'ms_model_pages_get_page_by_id',
				$page_found,
				$field,
				$value
			);
		}

		return $Page_list[$field][ $value ];
	}

	/**
	 * Returns the page_id that is identified by the specified filter.
	 * Filter can be either a type-name, post-ID or WP_Post (in that order)
	 *
	 * @since  1.1.0
	 * @param  mixed $filter The filter to translate into a post_id
	 * @return int
	 */
	static public function get_page_id( $filter ) {
		$page_id = 0;

		if ( is_string( $filter ) ) {
			$filter = self::get_page( $filter );
		} elseif ( is_numeric( $filter ) ) {
			$page_id = $filter;
		}

		if ( is_a( $filter, 'WP_Post' ) ) {
			$page_id = $filter->ID;
		}

		return apply_filters(
			'ms_model_pages_get_page_id',
			$page_id,
			$filter
		);
	}

	/**
	 * Checks if the current URL is a MS Page.
	 * If yes, then some basic information on this page are returned.
	 *
	 * @since  1.0.4.4
	 * @param  int $page_id Optional. The page_id to fetch.
	 * @return WP_Post|null
	 */
	static public function current_page( $page_id = false, $page_type = null ) {
		static $Res = array();
		$key = json_encode( $page_id ) . json_encode( $page_type );

		if ( ! isset( $Res[$key] ) ) {
			$this_page = null;

			if ( ! empty( $page_type ) ) {
				/*
				 * We have a page_type:
				 * Get infos of that page!
				 */
				$expected_page = self::get_page( $page_type );

				if ( $page_id == $expected_page->ID ) {
					$this_page = $expected_page;
				}
			} else {
				/*
				 * We don't have the page_type:
				 * Use current page_id or the specified page_id/slug!
				 */
				if ( empty( $page_id ) ) { $page_id = get_the_ID(); }
				if ( empty( $page_id ) ) { $page_id = get_queried_object_id(); }
				if ( empty( $page_id ) && did_action( 'setup_theme' ) ) {
					$url = lib2()->net->current_url();
					$page_id = url_to_postid( $url );
				}

				if ( ! empty( $page_id ) ) {
					if ( is_numeric( $page_id ) ) {
						$this_page = self::get_page_by( 'id', $page_id );
					} else {
						$this_page = self::get_page_by( 'slug', $page_id );
					}
				}
			}

			$Res[$key] = apply_filters(
				'ms_model_pages_current_page',
				$this_page
			);
		}

		return $Res[$key];
	}

	/**
	 * Verify if is a MS Page.
	 *
	 * Verify if current page, or passed page_id is a plugin special page.
	 *
	 * @since 1.0.0
	 *
	 * @param int $page_id Optional. The page id to verify. Default to current page.
	 * @param string $page_type Optional. The page type to verify. If null, test it against all ms pages.
	 */
	static public function is_membership_page( $page_id = null, $page_type = null ) {
		$ms_page_type = false;
		$page = self::current_page( $page_id );

		if ( empty( $page_type ) ) {
			if ( $page ) {
				$ms_page_type = self::get_page_type( $page->ID );
			}
		} else {
			if ( empty( $page_id ) && is_page() ) {
				$page_id = get_the_ID();
			}

			if ( ! empty( $page_id ) ) {
				$ms_page = self::get_page( $page_type );
				if ( $page_id == $ms_page->id ) {
					$ms_page_type = $page_type;
				}
			}
		}

		return apply_filters(
			'ms_model_pages_is_membership_page',
			$ms_page_type
		);
	}

	/**
	 * Get MS Page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string|WP_Post $page_type The page type name or a WP_Post object.
	 * @param boolean $ssl If wanted a SSL url. Set to null to use auto detection.
	 * @return string The MS Page URL.
	 */
	static public function get_page_url( $page_type, $ssl = null ) {
		static $Urls = array();

		$page_id = self::get_page_id( $page_type );

		if ( ! isset( $Urls[$page_id] ) ) {
			$url = get_permalink( $page_id );
			if ( null === $ssl ) { $ssl = is_ssl(); }

			if ( $ssl ) {
				$url = MS_Helper_Utility::get_ssl_url( $url );
			}

			$Urls[$page_id] = apply_filters(
				'ms_model_pages_get_ms_page_url',
				$url,
				$page_id
			);
		}

		return $Urls[$page_id];
	}

	/**
	 * Redirect the user the specified membership page.
	 *
	 * @since  1.1.1.4
	 * @param  string $page_type The page-type.
	 * @param  array $args Optional. Additional URL parameters.
	 */
	static public function redirect_to( $page_type, $args = array() ) {
		self::create_missing_pages();
		$url = self::get_page_url( $page_type );

		$url = esc_url_raw( add_query_arg( $args, $url ) );

		/**
		 * Opportunity for other plugins to redirect to a different page.
		 */
		$url = apply_filters(
			'ms_model_pages_redirect_to',
			$url,
			$page_type,
			$args
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Returns the URL to display after successful login.
	 *
	 * @since  1.1.0
	 *
	 * @param  bool $filter Optional. If set to false then the URL is not
	 *                      filtered and the default value is returned.
	 * @return string URL of the page to display after login.
	 */
	static public function get_url_after_login( $filter = true ) {
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$url = $_REQUEST['redirect_to'];
			$enforce = true; // This redirection was enforced via REUQEST param.
		} else {
			$url = self::get_page_url( self::MS_PAGE_ACCOUNT );
			$enforce = false; // This is the default redirection.
		}

		if ( $filter ) {
			$url = apply_filters(
				'ms_url_after_login',
				$url,
				$enforce
			);
		}

		return $url;
	}

	/**
	 * Returns the URL to display after successful logout.
	 *
	 * @since  1.1.0
	 *
	 * @param  bool $filter Optional. If set to false then the URL is not
	 *                      filtered and the default value is returned.
	 * @return string URL of the page to display after logout.
	 */
	static public function get_url_after_logout( $filter = true ) {
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$url = $_REQUEST['redirect_to'];
			$enforce = true; // This redirection was enforced via REUQEST param.
		} else {
			$url = home_url( '/' );
			$enforce = false; // This is the default redirection.
		}

		if ( $filter ) {
			$url = apply_filters(
				'ms_url_after_logout',
				$url,
				$enforce
			);
		}

		return $url;
	}

	/**
	 * Get MS Page type by ID.
	 *
	 * @since 1.1.0
	 *
	 * @param string|WP_Post $page_type The page type name or a WP_Post object.
	 * @return string The MS Page type name.
	 */
	static public function get_page_type( $page_id ) {
		static $Types = array();

		$page_id = self::get_page_id( $page_id );
		$pages = self::get_pages();

		if ( ! isset( $Types[$page_id] ) ) {
			$type = '';
			foreach ( $pages as $page_type => $page ) {
				if ( $page->ID === $page_id ) {
					$type = $page_type;
					break;
				}
			}

			$Types[$page_id] = apply_filters(
				'ms_model_pages_get_ms_page_type',
				$type,
				$page_id
			);
		}

		return $Types[$page_id];
	}

	/**
	 * Creates any missing Membership pages.
	 *
	 * @since  1.1.0
	 * @return array|false Titles of the created pages
	 */
	static public function create_missing_pages() {
		static $Done = false;
		$res = false;

		if ( $Done ) { return $res; }
		$Done = true;

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) { return $res; }

		$types = MS_Model_Pages::get_page_types();

		$res = array();
		foreach ( $types as $type => $title ) {
			$page_id = self::get_setting( $type );
			$status = get_post_status( $page_id );

			if ( ! $status || 'trash' == $status ) {
				// Page does not exist or was deleted. Create new page.
				$page_id = 0;
			} elseif ( 'publish' != $status ) {
				// The page exists but is not published. Publish now.
				wp_publish_post( $page_id );
			}

			// If the post_id does not exist then create a new page
			if ( empty( $page_id ) ) {
				$data = array(
					'post_title' => $title,
					'post_name' => $type,
					'post_content' => self::get_default_content( $type ),
					'post_type' => 'page',
					'post_status' => 'publish',
					'post_author' => $user_id,
				);
				$new_id = wp_insert_post( $data );

				/**
				 * Filter the new page_id
				 *
				 * @since 1.1.0
				 */
				$new_id = apply_filters(
					'ms_model_pages_create_missing_page',
					$new_id,
					$type,
					$data
				);

				if ( is_numeric( $new_id ) ) {
					self::set_setting( $type, $new_id );
					$res[$new_id] = $title;

					/**
					 * Trigger action to allow modifications to the page
					 *
					 * @since 1.1.0
					 */
					do_action(
						'ms_model_pages_create_wp_page',
						$new_id
					);
				}
			}
		}

		return apply_filters(
			'ms_model_pages_create_missing_page',
			$res
		);
	}

	/**
	 * Returns true only then, when the current user can edit menu items.
	 *
	 * Reasons why it might be denied:
	 * - There are no menus where items can be added to.
	 * - The user is no admin.
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function can_edit_menus() {
		$Can_Edit_Menus = null;

		if ( null === $Can_Edit_Menus ) {
			$Can_Edit_Menus = false;
			$menus = wp_get_nav_menus();

			if ( MS_Model_Member::is_admin_user() && ! empty( $menus ) ) {
				$Can_Edit_Menus = true;
			}

			$Can_Edit_Menus = apply_filters(
				'ms_model_pages_can_edit_menus',
				$Can_Edit_Menus
			);
		}

		return $Can_Edit_Menus;
	}

	/**
	 * Create MS Pages in Menus.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type to create menu.
	 * @param string $update_only Only used by the upgrade class.
	 * @param string $type Only used by the upgrade class.
	 * @return bool True means that at least one menu item was created.
	 */
	static public function create_menu( $page_type, $update_only = null, $update_type = null ) {
		$res = false;

		if ( self::is_valid_type( $page_type ) ) {
			if ( $update_only && empty( $update_type ) ) {
				self::create_menu( $page_type, true, 'page' );
				self::create_menu( $page_type, true, 'ms_page' );
			} else {
				$ms_page = self::get_page( $page_type, true );
				$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );

				if ( ! empty( $navs ) ) {
					$object_type = empty( $update_type ) ? 'page' : $update_type;
					$page_url = self::get_page_url( $ms_page );

					foreach ( $navs as $nav ) {
						$args['meta_query'] = array(
							array(
								'key' => '_menu_item_object_id',
								'value' => $ms_page->ID,
							),
							array(
								'key' => '_menu_item_object',
								'value' => $object_type,
							),
							array(
								'key' => '_menu_item_type',
								'value' => 'post_type',
							),
						);

						// Search for existing menu item and create it if not found
						$items = wp_get_nav_menu_items( $nav, $args );

						$menu_item = apply_filters(
							'ms_model_settings_create_menu_item',
							array(
								'menu-item-object-id' => $ms_page->ID,
								'menu-item-object' => 'page',
								'menu-item-parent-id' => 0,
								'menu-item-position' => 0,
								'menu-item-type' => 'post_type',
								'menu-item-title' => $ms_page->post_title,
								'menu-item-url' => $page_url,
								'menu-item-status' => 'publish',
							)
						);

						$item = ! is_array( $items ) ? false : array_shift( $items );
						$db_id = empty( $item ) ? 0 : $item->db_id;

						if ( $db_id || ! $update_only ) {
							wp_update_nav_menu_item( $nav->term_id, $db_id, $menu_item );
							self::set_setting( 'has_nav_' . $page_type, true );
							$res = true;
						}
					}
				} else {
					// No menus defined.
					$res = true;
				}
			}
		}

		return $res;
	}

	/**
	 * Remove MS Pages from Menus.
	 *
	 * @since 1.1.0
	 *
	 * @param string $page_type The page type to create menu.
	 * @return bool True means that at least one menu item was deleted.
	 */
	static public function drop_menu( $page_type ) {
		$res = false;

		if ( self::is_valid_type( $page_type ) ) {
			$ms_page = self::get_page( $page_type, true );
			$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );

			if ( ! empty( $navs ) ) {
				foreach ( $navs as $nav ) {
					$args['meta_query'] = array(
						array(
							'key' => '_menu_item_object_id',
							'value' => $ms_page->ID,
						),
						array(
							'key' => '_menu_item_object',
							'value' => 'page',
						),
						array(
							'key' => '_menu_item_type',
							'value' => 'post_type',
						),
					);

					// Search for existing menu item and create it if not found
					$items = wp_get_nav_menu_items( $nav, $args );

					$item = ! is_array( $items ) ? false : array_shift( $items );
					$db_id = empty( $item ) ? 0 : $item->db_id;

					if ( $db_id ) {
						if ( false !== wp_delete_post( $db_id ) ) {
							self::set_setting( 'has_nav_' . $page_type, false );
							$res = true;
						}
					}
				}
			} else {
				// No menus defined.
				$res = true;
			}
		}

		return $res;
	}

	/**
	 * Returns the current menu state: If a specific page is added to the menu,
	 * this state is saved in the settings. So when the user removes a menu item
	 * manually we still have the "inserted" flag in DB.
	 *
	 * We do this, because the menu items are added to all existing nav menus
	 * and the user might remove them from one nav menu but not from all...
	 *
	 * @since  1.1.0
	 * @param  string $page_type
	 * @return bool
	 */
	static public function has_menu( $page_type ) {
		$state = false;

		if ( self::is_valid_type( $page_type ) ) {
			$state = self::get_setting( 'has_nav_' . $page_type );
			$state = lib2()->is_true( $state );
		}

		return $state;
	}


	/**
	 * Get default content for membership pages.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $type The page type name.
	 * @return string The default content.
	 */
	static public function get_default_content( $type ) {
		$lines = array();

		switch ( $type ) {
			case self::MS_PAGE_MEMBERSHIPS:
				$lines[] = sprintf(
					'['. MS_Helper_Shortcode::SCODE_NOTE .' type="info"]%1$s[/'. MS_Helper_Shortcode::SCODE_NOTE .']',
					__( 'We have the following subscriptions available for our site. You can renew, cancel or upgrade your subscriptions by using the forms below.', MS_TEXT_DOMAIN )
				);
				$lines[] = '['. MS_Helper_Shortcode::SCODE_SIGNUP .']';
				break;

			case self::MS_PAGE_PROTECTED_CONTENT:
				$lines[] = '[' . MS_Helper_Shortcode::SCODE_PROTECTED . ']';
				break;

			case self::MS_PAGE_ACCOUNT:
				$lines[] = '['. MS_Helper_Shortcode::SCODE_MS_ACCOUNT .']<hr />';
				$lines[] = '['. MS_Helper_Shortcode::SCODE_LOGOUT .']';
				break;

			case self::MS_PAGE_REGISTER:
				$lines[] = sprintf(
					'['. MS_Helper_Shortcode::SCODE_NOTE .' type="info"]%1$s[/'. MS_Helper_Shortcode::SCODE_NOTE .']',
					__( 'We have the following subscriptions available for our site. To join, simply click on the Sign Up button and then complete the registration details.', MS_TEXT_DOMAIN )
				);
				$lines[] = '['. MS_Helper_Shortcode::SCODE_SIGNUP .']';
				break;

			case self::MS_PAGE_REG_COMPLETE:
				$lines[] = sprintf(
					'['. MS_Helper_Shortcode::SCODE_NOTE .' type="info"]%1$s<br/>%2$s[/'. MS_Helper_Shortcode::SCODE_NOTE .']',
					__( 'Your request to join the membership was successfully received!', MS_TEXT_DOMAIN ),
					__( 'The Payment Gateway could take a couple of minutes to process and return the payment status.', MS_TEXT_DOMAIN )
				);
				$lines[] = '['. MS_Helper_Shortcode::SCODE_MS_ACCOUNT_LINK .']';
				break;
		}

		$content = implode( "\n", $lines );

		return apply_filters(
			'ms_model_pages_get_default_content',
			$content
		);
	}

	/**
	 * Creates a new WordPress menu and adds all top level pages to this menu.
	 *
	 * @since  1.1.0
	 */
	static public function create_default_menu() {
		$menu_id = wp_create_nav_menu( __( 'Default Menu', MS_TEXT_DOMAIN ) );

		if ( ! is_numeric( $menu_id ) || $menu_id <= 0 ) {
			return;
		}

		// Use the new menu in the menu-location of the theme.
		$locations = get_theme_mod( 'nav_menu_locations' );
		if ( is_array( $locations ) && count( $locations ) > 0 ) {
			reset( $locations );
			$first = key( $locations );
			$locations[$first] = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
		}

		// Enable the Auto-Add-New-Pages option.
		// Code snippet from wp-admin/includes/nav-menu.php
		$nav_menu_option = (array) get_option( 'nav_menu_options' );
		if ( ! isset( $nav_menu_option['auto_add'] ) ) {
			$nav_menu_option['auto_add'] = array();
		}
		if ( ! in_array( $menu_id, $nav_menu_option['auto_add'] ) ) {
			$nav_menu_option['auto_add'][] = $menu_id;
		}
		update_option( 'nav_menu_options', $nav_menu_option );

		// Get a list of all published top-level pages.
		$top_pages = get_pages(
			array( 'parent' => 0 )
		);

		// List of pages that should not be displayed in the menu.
		$skip_pages = array(
			self::MS_PAGE_PROTECTED_CONTENT,
			self::MS_PAGE_REG_COMPLETE,
		);

		foreach ( $top_pages as $page ) {
			// Skip pages that should not appear in menu.
			$ms_type = self::is_membership_page( $page->ID );
			if ( in_array( $ms_type, $skip_pages ) ) {
				continue;
			}

			// Add the page to our new menu!
			$item = array(
				'menu-item-object-id' => $page->ID,
				'menu-item-object' => $page->post_type,
				'menu-item-type' => 'post_type',
				'menu-item-status' => $page->post_status,
			);
			wp_update_nav_menu_item( $menu_id, 0, $item );
		}
	}

}
