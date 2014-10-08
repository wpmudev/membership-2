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
 * Membership Menu Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Menu extends MS_Model_Rule {
	
	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_MENU;
	
	/**
	 * Verify access to the current content.
	 * 
	 * @since 1.0.0
	 * 
	 * @param string $id The content id to verify access.
	 * @return boolean True if has access, false otherwise.
	 */
	public function has_access( $id = null ) {
		
		return apply_filters( 'ms_model_rule_menu_has_access', false, $id, $this );
	}
	
	/**
	 * Set initial protection.
	 * 
	 * @since 1.0.0
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship Optional. The membership relationship. 
	 */
	public function protect_content( $ms_relationship = false ) {
		
		parent::protect_content( $ms_relationship );
		
		$this->add_filter( 'wp_get_nav_menu_items', 'filter_menus', 10, 3 );
	}
	
	/**
	 * Set initial protection.
	 *
	 * **Hooks Actions/Filters: **
	 * 
	 * * filter_menus
	 * 
	 * @since 1.0.0
	 *
	 * @param array $items The menu items.
	 * @param object $menu The menu object. 
	 * @param mixed $args The menu select args.
	 */
	function filter_menus( $items, $menu, $args ) {

		if( ! empty( $items ) ) {
			foreach( $items as $key => $item ) {
				if( ! parent::has_access( $item->ID ) || ( ! empty( $item->menu_item_parent ) && ! parent::has_access( $item->menu_item_parent ) ) ) {
					unset( $items[ $key ] );
				}
			}
		}
		
		return apply_filters( 'ms_model_rule_menu_filter_menus', $items, $menu, $args, $this );
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 * 				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		
		$contents = array();

		if( ! empty( $args['protected_content'] ) ) {
			$menus = $this->get_menu_array();
			foreach( $menus as $menu_id => $menu ) {
				$contents = array_merge( $contents, $this->get_contents( array( 'menu_id' => $menu_id ) ) );
			}
			return $contents;
		}
		elseif( ! empty( $args['menu_id'] ) ) {
			$menu_id = $args['menu_id'];
			$items = wp_get_nav_menu_items( $menu_id );
			if( ! empty( $items ) ) {
				foreach( $items as $item ) {
					$item_id = $item->ID;
					$contents[ $item_id ] = $item;
					$contents[ $item_id ]->id = $item_id;
					$contents[ $item_id ]->title = esc_html( $item->title );
					$contents[ $item_id ]->name = esc_html( $item->title );
					$contents[ $item_id ]->parent_id = $menu_id;
					$contents[ $item_id ]->type = $this->rule_type;
					$contents[ $item_id ]->access = $this->get_rule_value( $contents[ $item_id ]->id );
				}
			}
		}
		
		/** If not visitor membership, just show protected content */
		if( ! $this->rule_value_invert ) {
			$contents = array_intersect_key( $contents,  $this->rule_value );
		}

		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		$ms = MS_Model_Membership::get_visitor_membership();
		
		return apply_filters( 'ms_model_rule_menu_get_contents', $contents, $args, $this );
	}
	
	/**
	 * Get menu array.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 * 		@type string $menu_id The menu id.
	 * 		@type string $name The menu name.
	 * }
	 */
	public function get_menu_array() {
		$contents = array( __( 'No menus found.', MS_TEXT_DOMAIN ) );
		$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );

		if( ! empty( $navs ) ) {
			$contents = array();
			foreach( $navs as $nav ) {
				$contents[ $nav->term_id ] = esc_html( $nav->name );
			}
		}
		
		return apply_filters( 'ms_model_rule_menu_get_menu_array', $contents, $this );
	}
}