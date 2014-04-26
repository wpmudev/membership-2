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


class MS_Model_Rule_Category extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
		
	public function get_content() {
		$contents = get_categories( 'get=all' );
// 		$contents = get_terms( array('category', 'product_category', 'product_tag', 'nav_menu', 'post_tag'), 'get=all' );

		foreach( $contents as $content ) {
			$content->id = $content->term_id;
			if( in_array( $content->id, $this->rule_value ) ) {
				$content->access = true;
			}
			else {
				$content->access = false;
			}
			if( in_array( $content->id, $this->delayed_access_enabled ) && ! empty( $this->delayed_period_unit[ $content->id ] ) && ! empty( $this->delayed_period_type[ $content->id ] ) ) {
				$content->delayed_period = $this->delayed_period_unit[ $content->id ] . $this->delayed_period_type[ $content->id ];
			}
			else {
				$content->delayed_period = '';
			}
		}
		
		return $contents;
	}
	
}