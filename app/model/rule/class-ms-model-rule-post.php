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


class MS_Model_Rule_Post extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	public function on_protection() {
		
	}
	
	public function get_content_count( $args = null ) {
		$defaults = array(
				'posts_per_page' => -1,
				'post_type'   => 'post',
				'post_status' => 'publish',
		);
		$args = wp_parse_args( $args, $defaults );
	
		$query = new WP_Query($args);
		return $query->found_posts;
	}
	
	public function get_content( $args = null, MS_Model_Rule_Category $model_rule_category = null) {
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'post_date',
				'order'       => 'DESC',
				'post_type'   => 'post',
				'post_status' => 'publish',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$contents = get_posts( $args );
		
		foreach( $contents as $content ) {
			$content->id = $content->ID;
			$content->type = MS_Model_RULE::RULE_TYPE_POST;
			$content->categories = array();
			$categories = wp_get_post_categories( $content->id );
			/* To inherit category access, set default access to false */
			$content->access = false;
			if( ! empty( $categories ) && ! empty( $model_rule_category ) ) {
				foreach( $categories as $cat_id ) {
					$cat = get_category( $cat_id );
					$cats[] = $cat->name;
					/* Inherit category access */ 
					if( in_array( $cat_id, $model_rule_category->rule_value ) ) {
						$content->access = true;
					}
				}
				$content->categories = $cats;
			}
// 			/* post by post override */ 
// 			if( in_array( $content->id, $this->rule_value ) ) {
// 				$content->access = true;
// 			}
// 			else {
// 				$content->access = false;
// 			}
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
			$cont[ $content->id ] = $content->post_title;
		}
		return $cont;
	}
	
}