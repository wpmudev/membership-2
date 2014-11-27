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
 * Main MS Pages class, composition of MS_Model_Page objects.
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
	 * Plugin pages composition.
	 *
	 * @since 1.0.0
	 *
	 * @var MS_Model_Page[]
	 */
	protected $pages;

	public function __construct() {
		// Hide the slug editor in MS Page editor.
		$this->add_filter( 'get_sample_permalink_html', 'no_permalink' );
	}

	/**
	 * Hides the Permalink box from the MS Page editor
	 *
	 * @since  1.0.4.4
	 */
	public function no_permalink( $return ){
		global $post;

		if ( $post->post_type === MS_Model_Page::$POST_TYPE ) {
		?><style type="text/css">
		#titlediv{margin-bottom: 10px;}#edit-slug-box{display: none;}
		</style><?php
		}

		return $return;
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
	public static function get_ms_page_types() {
		static $Page_types;

		if ( empty( $Page_types ) ) {
			$Page_types = array(
				self::MS_PAGE_MEMBERSHIPS => __( 'Memberships', MS_TEXT_DOMAIN ),
				self::MS_PAGE_PROTECTED_CONTENT => __( 'Protected Content', MS_TEXT_DOMAIN ),
				self::MS_PAGE_ACCOUNT => __( 'Account', MS_TEXT_DOMAIN ),
				self::MS_PAGE_REGISTER => __( 'Register', MS_TEXT_DOMAIN ),
				self::MS_PAGE_REG_COMPLETE => __( 'Registration Complete', MS_TEXT_DOMAIN ),
			);

			$Page_types = apply_filters(
				'ms_model_page_get_ms_page_types',
				$Page_types
			);
		}

		return $Page_types;
	}

	/**
	 * Validate ms page type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The page type to validate.
	 * @return boolean True if valid.
	 */
	public static function is_valid_ms_page_type( $type ) {
		$valid = array_key_exists( $type, self::get_ms_page_types() );

		return apply_filters( 'ms_model_page_is_valid_ms_page_type', $valid );
	}

	/**
	 * Get MS Pages.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $create_if_not_exists Optional. Flag to create a page if not exists.
	 * @return MS_Model_Page[] The page model objects.
	 */
	public function get_ms_pages( $create_if_not_exists = false ) {
		$page_types = self::get_ms_page_types();

		foreach ( $page_types as $page_type => $title ) {
			$this->get_ms_page( $page_type, $create_if_not_exists );
		}

		return apply_filters(
			'ms_model_page_get_ms_page',
			$this->pages,
			$this
		);
	}

	/**
	 * Get specific MS Page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type to retrieve the page.
	 * @param boolean $create_if_not_exists Optional. Flag to create a page if not exists.
	 * @return MS_Model_Page The page model object.
	 */
	public function get_ms_page( $page_type, $create_if_not_exists = false ) {
		$ms_page = null;

		if ( self::is_valid_ms_page_type( $page_type ) ) {
			if ( ! empty( $this->pages[ $page_type ] ) ) {
				$ms_page = $this->pages[ $page_type ];
			}

			if ( $create_if_not_exists && empty( $ms_page ) ) {
				$page_types = self::get_ms_page_types();
				$ms_page = MS_Factory::create( 'MS_Model_Page' );
				$ms_page->type = $page_type;
				$ms_page->title = $page_types[ $page_type ];
				$ms_page->slug = $page_type;
				$ms_page->create_wp_page();

				$this->pages[ $page_type ] = $ms_page;
				$this->save();

				flush_rewrite_rules();
			}
		} else {
			MS_Helper_Debug::log( 'ms_model_pages_get_page error: invalid page type: ' . $page_type );
			$ms_page = MS_Factory::create( 'MS_Model_Page' );
		}

		return apply_filters( 'ms_model_page_get_ms_page', $ms_page, $this );
	}

	/**
	 * Get specific MS Page using either ID or slug information.
	 *
	 * @since 1.0.4.4
	 *
	 * @param string $field The field to check. [id|slug]
	 * @param string $value The field value
	 * @return null|MS_Model_Page The page model object.
	 */
	public function get_ms_page_by( $field, $value ) {
		static $Page_list = array();

		if ( ! isset( $Page_list[$field] ) ) {
			$Page_list[$field] = array();
		}

		if ( ! isset( $Page_list[$field][ $value ] ) ) {
			$ms_page_found = null;

			switch ( $field ) {
				case 'id': $value = absint( $value ); break;
			}

			$ms_pages = $this->get_ms_pages();
			$found = false;

			foreach ( $ms_pages as $ms_page ) {
				switch ( $field ) {
					case 'id':   $found = ($value === absint( $ms_page->id ) ); break;
					case 'slug': $found = ($value === $ms_page->slug ); break;
				}

				if ( $found ) {
					$ms_page_found = $ms_page;
					break;
				}
			}

			$Page_list[$field][ $value ] = apply_filters(
				'ms_model_page_get_ms_page_by_id',
				$ms_page_found,
				$field,
				$value,
				$this
			);
		}

		return $Page_list[$field][ $value ];
	}

	/**
	 * Set specific MS Page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type to set.
	 * @param MS_Model_Page The page model object to set.
	 */
	public function set_ms_page( $page_type, $ms_page ) {
		if ( self::is_valid_ms_page_type( $page_type ) ) {
			$this->pages[ $page_type ] = $ms_page;
		}

		do_action(
			'ms_model_pages_set_ms_page',
			$page_type,
			$ms_page,
			$this
		);
	}

	/**
	 * Checks if the current URL is a MS Page.
	 * If yes, then some basic information on this page are returned.
	 *
	 * @since  1.0.4.4
	 * @return object|false
	 */
	public function current_page( $page_id = false, $page_type = null ) {
		global $wp_query;
		static $Res = array();
		$key = json_encode( $page_id ) . json_encode( $page_type );

		if ( ! isset( $Res[$key] ) ) {
			$this_page = false;

			if ( ! empty( $page_id ) || ! empty( $page_type ) ) {
				/*
				 * We have a page_type:
				 * Get infos of that page!
				 */
				if ( ! empty( $page_type ) ) {
					$ms_page = $this->get_ms_page( $page_type );
					$query_slug = $wp_query->query_vars['ms_page'];

					if ( $page_id == $ms_page->id
						|| $query_slug == $page_type
					) {
						$this_page = $ms_page;
					}
				} else {
					/*
					 * We don't have the page_type:
					 * Use current page_id or the specified page_id/slug!
					 */
					if ( empty( $page_id ) ) {
						$this_page = $this->get_ms_page_by( 'id', get_the_ID() );
					} else if ( is_numeric( $page_id ) ) {
						$this_page = $this->get_ms_page_by( 'id', $page_id );
					} else {
						$this_page = $this->get_ms_page_by( 'slug', $page_id );
					}
				}
			} else {
				/*
				 * No page_id provided:
				 * Get infos based on the current URL!
				 */
				$pages = self::get_ms_pages();
				$url_parts = explode( '?', WDev()->current_url() );
				$url = array_shift( $url_parts );

				foreach ( $pages as $ms_page ) {
					if ( $url === $ms_page->url ) {
						$this_page = $ms_page;
						break;
					}
				}
			}

			$Res[$key] = $this_page;
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
	public function is_ms_page( $page_id = null, $page_type = null ) {
		$ms_page_type = false;
		$ms_page = $this->current_page( $page_id );

		if ( empty( $page_type ) ) {
			if ( $ms_page ) {
				$ms_page_type = $ms_page->type;
			}
		} else {
			if ( empty( $page_id ) && is_page() ) {
				$page_id = get_the_ID();
			}

			if ( ! empty( $page_id ) ) {
				$ms_page->id = $this->get_ms_page( $page_type );
				if ( $page_id == $ms_page->id ) {
					$ms_page_type = $page_type;
				}
			} elseif ( $ms_page ) {
				$slug = $ms_page->slug;
				$ms_page_slug = $this->get_ms_page_slug( $page_type );

				if ( $slug == $ms_page_slug ) {
					$ms_page_type = $page_type;
				}
			}
		}

		return apply_filters(
			'ms_model_page_is_ms_page',
			$ms_page_type,
			$this
		);
	}

	/**
	 * Get MS Page slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type.
	 * @return string The page slug.
	 */
	public function get_ms_page_slug( $page_type ) {
		$slug = $this->get_ms_page( $page_type )->slug;

		return apply_filters( 'ms_model_page_get_ms_page_slug', $slug );

	}

	/**
	 * Get MS Page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type.
	 * @param boolean $ssl If wanted a SSL url. Set to null to use auto detection.
	 * @param boolean $create_if_not_exists Optional. Flag to create a page if not exists.
	 * @return string The MS Page URL.
	 */
	public function get_ms_page_url( $page_type, $ssl = null, $create_if_not_exists = false ) {
		$url = null;
		$page = $this->get_ms_page( $page_type, $create_if_not_exists );
		$page_id = $page->id;

		if ( ! empty( $page_id ) ) {
			$url = $page->url;

			if ( true === $ssl || ( null === $ssl && is_ssl() ) ) {
				$url = MS_Helper_Utility::get_ssl_url( $url );
			}
		}

		return apply_filters( 'ms_model_page_get_ms_page_url', $url, $this );
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
		if ( self::is_valid_ms_page_type( $page_type ) ) {
			if ( $update_only && empty( $update_type ) ) {
				$this->create_menu( $page_type, true, 'page' );
				$this->create_menu( $page_type, true, 'ms_page' );
			} else {
				$ms_page = $this->get_ms_page( $page_type, true );
				$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );
				$object_type = empty( $update_type ) ? 'ms_page' : $update_type;

				foreach ( $navs as $nav ) {
					$args['meta_query'] = array(
						array(
							'key' => '_menu_item_object_id',
							'value' => $ms_page->id,
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
							'menu-item-object-id' => $ms_page->id,
							'menu-item-object' => 'ms_page',
							'menu-item-parent-id' => 0,
							'menu-item-position' => 0,
							'menu-item-type' => 'post_type',
							'menu-item-title' => $ms_page->post_title,
							'menu-item-url' => $ms_page->url,
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

};