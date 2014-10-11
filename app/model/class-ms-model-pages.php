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
 * @since 1.0.0
 * 
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Pages extends MS_Model_Option {

	public static $instance;
	
	const MS_PAGE_MEMBERSHIPS = 'memberships';
	const MS_PAGE_PROTECTED_CONTENT = 'protected-content';
	const MS_PAGE_ACCOUNT = 'account';
	const MS_PAGE_REGISTER = 'register';
	const MS_PAGE_REG_COMPLETE = 'registration-complete';
	
	protected $pages;
	
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
	
	public static function is_valid_ms_page_type( $type ) {
		
		$valid = false;
		
		if ( array_key_exists( $type, self::get_ms_page_types() ) ) {
			$valid = true;
		}
		
		return apply_filters( 'ms_model_page_is_valid_ms_page_type', $valid );
	}
	
	public function get_ms_pages() {
		
		$page_types = self::get_ms_page_types();
		
		foreach( $page_types as $page_type => $title ) {
			$this->get_ms_page( $page_type );
		}
		
		return apply_filters( 'ms_model_page_get_ms_page', $this->pages, $this );
	}
	
	public function get_ms_page( $page_type ) {
		
		$ms_page = null;
		
		if ( self::is_valid_ms_page_type( $page_type ) ) {
			if(  ! empty( $this->pages[ $page_type ] ) ) {
				$ms_page = $this->pages[ $page_type ];
			}
			else {
				$page_types = self::get_ms_page_types();
				$ms_page = MS_Factory::create( 'MS_Model_Page' );
				$ms_page->type = $page_type;
				$ms_page->title = $page_types[ $page_type ];
				$ms_page->slug = $page_type;
				$ms_page->create_wp_page();
				$this->pages[ $page_type ] = $ms_page;
				$this->save();
			}
		}
		else {
			MS_Helper_Debug::log( 'ms_model_pages_get_page error: invalid page type: ' . $page_type );
			$ms_page = MS_Factory::create( 'MS_Model_Page' );
		}
		
		return apply_filters( 'ms_model_page_get_ms_page', $ms_page, $this );
	}
	
	public function set_ms_page( $page_type, $ms_page ) {
		
		if ( self::is_valid_ms_page_type( $page_type ) ) {
			$this->pages[ $page_type ] = $ms_page;
		}
		
		do_action( 'ms_model_pages_set_ms_page', $page_type, $ms_page, $this );
	}
	
	public function is_ms_page( $page_id = null, $page_type = null ) {
	
		$is_ms_page = false;
	
		if ( empty( $page_id ) && is_page() ) {
			$page_id = get_the_ID();
		}
		
		if ( ! empty( $page_id ) ) {
			if ( ! empty( $page_type ) ) {
				
				$ms_page = $this->get_ms_page( $page_type );
				if( $page_id == $ms_page->id ) { 
					$is_ms_page = $page_type;
				}
			}
			else {
				$page_types = self::get_ms_page_types();

				foreach ( $page_types as $page_type => $title ) {
					
					$ms_page = $this->get_ms_page( $page_type );
					if ( $page_id == $ms_page->id ) {
						$is_ms_page = $page_type;
						break;
					}
				}
			}
		}
	
		return apply_filters( 'ms_model_page_is_ms_page', $is_ms_page, $this );
	}
	
	public function get_ms_page_slug( $page_type ) {
	
		$slug = $this->get_ms_page( $page_type )->slug;
	
		return apply_filters( 'ms_model_page_get_ms_page_slug', $slug );
	
	}
	
	public function get_ms_page_id( $page_type ) {
		
		$page_id = $this->get_ms_page( $page_type )->id;
		
		if ( ! empty( $page_id ) ) {
			$page = get_post( $page_id );
			if ( empty( $page->ID ) || 'trash' == $page->post_status ) {
				$page_id = 0;
			}
		}
		
		return apply_filters( 'ms_model_page_get_ms_page', $page_id, $this );
	}
	
	public function get_ms_page_url( $page_type, $ssl = false ) {
		
		$url = null;
		$page_id = $this->get_ms_page_id( $page_type );
		
		if( ! empty( $page_id ) ) {
			$url = get_permalink( $page_id );
			
			if ( $ssl ) {
				$url = MS_Helper_Utility::get_ssl_url( $url );
			}
		}
				
		return apply_filters( 'ms_model_page_get_ms_page_url', $url, $this );
	}
}