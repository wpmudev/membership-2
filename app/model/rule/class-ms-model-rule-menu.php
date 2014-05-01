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


class MS_Model_Rule_Menu extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	public function on_protection() {
		
	}
	
	public function get_content( $args = null ) {
		$contents = array();
		$navs = wp_get_nav_menus( array( 'orderby' => 'name' ) );
		if( ! empty( $navs ) ) {
			foreach( $navs as $nav ) {
				$contents[ $nav->term_id ] = $nav;
				$contents[ $nav->term_id ]->id = $nav->term_id;
				$contents[ $nav->term_id ]->title = esc_html( $nav->name );
				$contents[ $nav->term_id ]->parent_id = false;
				$contents[ $nav->term_id ]->ignore = true;
				$contents[ $nav->term_id ]->delayed_period = '';
				$items = wp_get_nav_menu_items( $nav->term_id );
				if( ! empty( $items ) ) {
					foreach( $items as $item ) {
						$item_id = $item->ID;
						$contents[ $item_id ] = $item;
						$contents[ $item_id ]->id = $item_id;
						$contents[ $item_id ]->title = esc_html( $item->title );
						$contents[ $item_id ]->parent_id = $nav->term_id;
						if( in_array( $contents[ $item_id ]->id, $this->rule_value ) ) {
							$contents[ $item_id ]->access = true;
						}
						else {
							$contents[ $item_id ]->access = false;
						}
						if( in_array( $item_id, $this->delayed_access_enabled ) ) {
							$contents[ $item_id ]->delayed_period = $this->delayed_period_unit[ $item_id ] . $this->delayed_period_type[ $item_id ];
						}
						else {
							$contents[ $item_id ]->delayed_period = '';
						}
						
					}
				}
			}
		}
		
		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		
		return $contents;
	}
	
}