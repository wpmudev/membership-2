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
	
	/**
	 * Get MS Page types
	 *
	 * @since 1.0.0
	 *
	 * @return array{
	 * 		@type string $page_type The ms page type.
	 * 		@type string $title The page type title.
	 * }
	 */
	public static function get_ms_page_types() {
		
		static $page_types;
		
		if( empty( $page_types ) ) {
			$page_types = array(
					self::MS_PAGE_MEMBERSHIPS => __( 'Memberships', MS_TEXT_DOMAIN ),
					self::MS_PAGE_PROTECTED_CONTENT=> __( 'Protected Content', MS_TEXT_DOMAIN ),
					self::MS_PAGE_ACCOUNT=> __( 'Account', MS_TEXT_DOMAIN ),
					self::MS_PAGE_REGISTER=> __( 'Register', MS_TEXT_DOMAIN ),
					self::MS_PAGE_REG_COMPLETE=> __( 'Registration Complete', MS_TEXT_DOMAIN ),
			);
		}
		
		return apply_filters( 'ms_model_page_get_ms_page_types', $page_types );
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
		
		$valid = false;
		
		if ( array_key_exists( $type, self::get_ms_page_types() ) ) {
			$valid = true;
		}
		
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
		
		foreach( $page_types as $page_type => $title ) {
			$this->get_ms_page( $page_type, $create_if_not_exists );
		}
		
		return apply_filters( 'ms_model_page_get_ms_page', $this->pages, $this );
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

			if(  ! empty( $this->pages[ $page_type ] ) ) {

				$ms_page = $this->pages[ $page_type ];
			}

			if( $create_if_not_exists ) {
				/* Verify both ms_page model and the WP page */
				if ( empty( $ms_page ) || ! $ms_page->get_page() ) {
					
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
			}
		}
		else {

			MS_Helper_Debug::log( 'ms_model_pages_get_page error: invalid page type: ' . $page_type );
			$ms_page = MS_Factory::create( 'MS_Model_Page' );
		}
		
		return apply_filters( 'ms_model_page_get_ms_page', $ms_page, $this );
	}
	
	/**
	 * Get specific MS Page using slug information.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug The slug to find in ms pages.
	 * @return null|MS_Model_Page The page model object.
	 */
	public function get_ms_page_by_slug( $slug ) {
		$ms_page_found = null;
		
		$ms_pages = $this->get_ms_pages();
		foreach( $ms_pages as $ms_page ) {
			if( $slug == $ms_page->slug ) {
				$ms_page_found = $ms_page;
				break;
			}
		}
		
		return apply_filters( 'ms_model_page_get_ms_page_by_slug', $ms_page_found, $slug, $this );
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
		
		do_action( 'ms_model_pages_set_ms_page', $page_type, $ms_page, $this );
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
	
		global $wp_query;
		$is_ms_page = false;
	
		if ( empty( $page_id ) && is_page() ) {
			$page_id = get_the_ID();
		}

		if ( ! empty( $page_id ) ) {
			if ( ! empty( $page_type ) ) {
				
				$ms_page_id = $this->get_ms_page_id( $page_type );
				if( $page_id == $ms_page_id ) { 
					$is_ms_page = $page_type;
				}
			}
			else {
				$page_types = self::get_ms_page_types();

				foreach ( $page_types as $page_type => $title ) {
					
					$ms_page_id = $this->get_ms_page_id( $page_type );
					if ( $page_id == $ms_page_id ) {
						$is_ms_page = $page_type;
						break;
					}
				}
			}
		}
		elseif( isset( $wp_query->query_vars['ms_page'] ) ) {
			$slug = $wp_query->query_vars['ms_page'];

			if ( ! empty( $page_type ) ) {
			
				$ms_page_slug = $this->get_ms_page_slug( $page_type );
				if( $slug == $ms_page_slug ) {
					$is_ms_page = $page_type;
				}
			}
			else {
				$page_types = self::get_ms_page_types();
			
				foreach ( $page_types as $page_type => $title ) {
						
					$ms_page_slug = $this->get_ms_page_slug( $page_type );
					if ( $slug == $ms_page_slug ) {
						$is_ms_page = $page_type;
						break;
					}
				}
			}
		}
		
		return apply_filters( 'ms_model_page_is_ms_page', $is_ms_page, $this );
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
	 * Get MS Page ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type.
	 * @param boolean $create_if_not_exists Optional. Flag to create a page if not exists.
	 */
	public function get_ms_page_id( $page_type, $create_if_not_exists = false ) {
		
		$ms_page = $this->get_ms_page( $page_type, $create_if_not_exists );
		
		return apply_filters( 'ms_model_page_get_ms_page', $ms_page->id, $this );
	}
	
	/**
	 * Get MS Page URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page_type The page type.
	 * @param boolean $ssl If wanted a SSL url.
	 * @param boolean $create_if_not_exists Optional. Flag to create a page if not exists.
	 * @return string The MS Page URL.
	 */
	public function get_ms_page_url( $page_type, $ssl = false, $create_if_not_exists = false ) {
		
		$url = null;
		$page_id = $this->get_ms_page_id( $page_type, $create_if_not_exists );
		
		if( ! empty( $page_id ) ) {
			$url = get_permalink( $page_id );
			
			if ( $ssl ) {
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
	 */
	public function create_menu( $page_type ) {
		
		if ( self::is_valid_ms_page_type( $page_type ) ) {
			$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );
			foreach ( $navs as $nav ) {
				$args['meta_query'] = array(
						array(
								'key' => '_menu_item_object_id',
								'value' => $this->get_ms_page( $page_type, true )->id,
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
				/* Search for existing menu item and create it if not found*/
				$items = wp_get_nav_menu_items( $nav, $args );
				if ( empty( $items ) ) {
					$page = get_post( $this->get_ms_page( $page_type )->id );
	
					$menu_item = apply_filters(
							'ms_model_settings_create_menu_item',
							array(
									'menu-item-object-id' => $page->ID,
									'menu-item-object' => 'page',
									'menu-item-parent-id' => 0,
									'menu-item-position' => 0,
									'menu-item-type' => 'post_type',
									'menu-item-title' => $page->post_title,
									'menu-item-url' => get_permalink( $page->ID ),
									'menu-item-status' => 'publish',
							)
					);
					wp_update_nav_menu_item( $nav->term_id, 0, $menu_item );
				}
			}
		}
	}
}