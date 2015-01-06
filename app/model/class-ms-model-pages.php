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
	 * Returns a MS_Model_Pages setting value (these are the association between
	 * our Membership Page types and WordPress posts)
	 *
	 * @since  1.0.4.5
	 * @param  string $key The setting key.
	 * @return any The setting value. A post_id or 0.
	 */
	public function get_setting( $key ) {
		if ( ! isset( $this->settings[ $key ] ) ) {
			$this->settings[$key] = 0;
		}

		return apply_filters(
			'ms_model_pages_get_setting',
			$this->settings[$key],
			$key,
			$this
		);
	}

	/**
	 * Saves a MS_Model_Pages setting value.
	 *
	 * @since  1.0.4.5
	 * @param  string $key The setting key.
	 * @param  any $value The new setting value.
	 */
	public function set_setting( $key, $value ) {
		$value = apply_filters(
			'ms_model_pages_set_setting',
			$value,
			$key,
			$this
		);

		$this->settings[$key] = $value;
		$this->save();
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
	public function get_page_types() {
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
	public function get_description( $type ) {
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
	public function is_valid_type( $type ) {
		static $Res = array();

		if ( ! isset( $Res[$type] ) ) {
			$Res[$type] = array_key_exists( $type, $this->get_page_types() );

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
	public function get_pages() {
		static $Pages = null;

		if ( null === $Pages ) {
			$Pages = array();
			$page_types = $this->get_page_types();

			foreach ( $page_types as $page_type => $title ) {
				$page_id = $this->get_setting( $page_type );
				if ( empty( $page_id ) ) { continue; }

				$the_page = get_post( $page_id );
				if ( empty ( $the_page ) ) { continue; }

				$Pages[$page_type] = apply_filters(
					'ms_model_pages_get_pages_item',
					$the_page,
					$page_type,
					$page_id,
					$this
				);
			}

			$Pages = apply_filters(
				'ms_model_pages_get_pages',
				$Pages,
				$this
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
	public function get_page( $page_type ) {
		$result = null;

		if ( self::is_valid_type( $page_type ) ) {
			// Get a list of all WP_Post items.
			$pages = $this->get_pages();

			if ( ! empty( $pages[ $page_type ] ) ) {
				$result = $pages[ $page_type ];
			}
		} else {
			MS_Helper_Debug::log( 'ms_model_pages_get_page error: invalid page type: ' . $page_type );
		}

		return apply_filters(
			'ms_model_pages_get_page',
			$result,
			$this
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
	public function get_page_by( $field, $value ) {
		static $Page_list = array();

		if ( ! isset( $Page_list[$field] ) ) {
			$Page_list[$field] = array();
		}

		if ( ! isset( $Page_list[$field][ $value ] ) ) {
			$page_found = null;

			switch ( $field ) {
				case 'id': $value = absint( $value ); break;
			}

			$ms_pages = $this->get_pages();
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
				$value,
				$this
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
	public function get_page_id( $filter ) {
		$page_id = 0;

		if ( is_string( $filter ) ) {
			$filter = $this->get_page( $filter );
		} elseif ( is_numeric( $filter ) ) {
			$page_id = $filter;
		}

		if ( is_a( $filter, 'WP_Post' ) ) {
			$page_id = $filter->ID;
		}

		return apply_filters(
			'ms_model_pages_get_page_id',
			$page_id,
			$filter,
			$this
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
	public function current_page( $page_id = false, $page_type = null ) {
		static $Res = array();
		$key = json_encode( $page_id ) . json_encode( $page_type );

		if ( ! isset( $Res[$key] ) ) {
			$this_page = null;

			if ( ! empty( $page_type ) ) {
				/*
				 * We have a page_type:
				 * Get infos of that page!
				 */
				$expected_page = $this->get_page( $page_type );

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
					$url = WDev()->current_url();
					$page_id = url_to_postid( $url );
				}

				if ( ! empty( $page_id ) ) {
					if ( is_numeric( $page_id ) ) {
						$this_page = $this->get_page_by( 'id', $page_id );
					} else {
						$this_page = $this->get_page_by( 'slug', $page_id );
					}
				}
			}

			$Res[$key] = apply_filters(
				'ms_model_pages_current_page',
				$this_page,
				$this
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
	public function is_membership_page( $page_id = null, $page_type = null ) {
		$ms_page_type = false;
		$page = $this->current_page( $page_id );

		if ( empty( $page_type ) ) {
			if ( $page ) {
				$ms_page_type = $this->get_page_type( $page->ID );
			}
		} else {
			if ( empty( $page_id ) && is_page() ) {
				$page_id = get_the_ID();
			}

			if ( ! empty( $page_id ) ) {
				$ms_page = $this->get_page( $page_type );
				if ( $page_id == $ms_page->id ) {
					$ms_page_type = $page_type;
				}
			}
		}

		return apply_filters(
			'ms_model_pages_is_membership_page',
			$ms_page_type,
			$this
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
	public function get_page_url( $page_type, $ssl = null ) {
		static $Urls = array();

		$page_id = $this->get_page_id( $page_type );

		if ( ! isset( $Urls[$page_id] ) ) {
			$url = get_permalink( $page_id );
			if ( null === $ssl ) { $ssl = is_ssl(); }

			if ( $ssl ) {
				$url = MS_Helper_Utility::get_ssl_url( $url );
			}

			$Urls[$page_id] = apply_filters(
				'ms_model_pages_get_ms_page_url',
				$url,
				$page_id,
				$this
			);
		}

		return $Urls[$page_id];
	}

	/**
	 * Get MS Page type by ID.
	 *
	 * @since 1.1.0
	 *
	 * @param string|WP_Post $page_type The page type name or a WP_Post object.
	 * @return string The MS Page type name.
	 */
	public function get_page_type( $page_id ) {
		static $Types = array();

		$page_id = $this->get_page_id( $page_id );
		$pages = $this->get_pages();

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
				$page_id,
				$this
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
	public function create_missing_pages() {
		static $Done = false;
		$res = false;

		if ( $Done ) { return $res; }
		$Done = true;

		$user_id = get_current_user_id();
		if ( empty( $user_id ) ) { return $res; }

		$types = $this->get_page_types();

		$res = array();
		foreach ( $types as $type => $title ) {
			$page_id = $this->get_setting( $type );
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
					'post_content' => $this->get_default_content( $type ),
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
					$data,
					$this
				);

				if ( is_numeric( $new_id ) ) {
					$this->set_setting( $type, $new_id );
					$res[$new_id] = $title;

					/**
					 * Trigger action to allow modifications to the page
					 *
					 * @since 1.1.0
					 */
					do_action(
						'ms_model_pages_create_wp_page',
						$new_id,
						$this
					);
				}
			}
		}

		return apply_filters(
			'ms_model_pages_create_missing_page',
			$res,
			$this
		);
	}

	/**
	 * Create MS Pages in Menus.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type to create menu.
	 * @param string $update_only Only used by the upgrade class.
	 * @param string $type Only used by the upgrade class.
	 */
	public function create_menu( $page_type, $update_only = null, $update_type = null ) {
		if ( self::is_valid_type( $page_type ) ) {
			if ( $update_only && empty( $update_type ) ) {
				$this->create_menu( $page_type, true, 'page' );
				$this->create_menu( $page_type, true, 'ms_page' );
			} else {
				$ms_page = $this->get_page( $page_type, true );
				$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );
				$object_type = empty( $update_type ) ? 'page' : $update_type;
				$page_url = $this->get_page_url( $ms_page );

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
					}
				}
			}
		}
	}


	/**
	 * Get default content for membership pages.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $type The page type name.
	 * @return string The default content.
	 */
	public function get_default_content( $type ) {
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
				// The text in Settings > "Protection Messages" is added in
				// front end controller. This page has no default content.
				$lines = array();
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
			$content,
			$this
		);
	}

}
