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

	/**
	 * Verify access to the current category.
	 * @param $membership_relationship 
	 * @return boolean
	 */
	public function has_access( $membership_relationship ) {
	
		$has_access = false;
		
		if ( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
			$categories = wp_get_post_categories( get_the_ID() );
			$intersect = array_intersect( $categories, $this->rule_value );
			$has_access = !empty( $intersect );
			if( is_array( $categories ) ) {
				foreach ( $categories as $category_id ) {
					$has_access = $has_access || $this->has_dripped_access( $category_id, $membership_relationship->start_date );
				}
			}
		}
		elseif ( is_category() ) {
			$has_access = in_array( get_queried_object_id(), $this->rule_value );
			$has_access = $has_access || $this->has_dripped_access( get_queried_object_id(), $membership_relationship->start_date );
		}
		
		
		
		return $has_access;
	}
	
	public function get_content( $args = null ) {
		$contents = get_categories( 'get=all' );
// 		$contents = get_terms( array('category', 'product_category', 'product_tag', 'nav_menu', 'post_tag'), 'get=all' );

		foreach( $contents as $key => $content ) {
			$content->id = $content->term_id;
			$content->type = MS_Model_RULE::RULE_TYPE_CATEGORY;
			if( in_array( $content->id, $this->rule_value ) ) {
				$content->access = true;
			}
			else {
				$content->access = false;
			}
			if( array_key_exists( $content->id, $this->dripped ) ) {
				$content->delayed_period = $this->dripped[ $content->id ]['period_unit'] . ' ' . $this->dripped[ $content->id ]['period_type'];
				$content->dripped = $this->dripped[ $content->id ];
			}
			else {
				$content->delayed_period = '';
			}
		}
		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		return $contents;
	}
	
	/**
	 * Get content array( id => title )
	 */
	public function get_content_array() {
		$cont = array();
		$contents = $this->get_content();
		foreach( $contents as $content ) {
			$cont[ $content->id ] = $content->name;
		}
		return $cont;
	}
	
}