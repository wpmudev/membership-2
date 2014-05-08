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
 * Wrapper class to facilitate category and post rules integration.
 * Mainly for protection rules validation. 
 *
 */
class MS_Model_Rule_Post_Category extends MS_Model {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $post_rule;
	
	protected $category_rule;
	
	protected $membership_relationship;
	
	public function __construct( MS_Model_Rule_Post $post_rule, MS_Model_Rule_Category $category_rule ) {
		$this->post_rule = $post_rule;
		$this->category_rule = $category_rule;
	}
	
	/**
	 * Wrapper to verify access to the current post.
	 * 
	 * Checks both category rule and post rule.
	 * 
	 * @param $membership_relationship 
	 * @return boolean
	 */
	public function has_access( $membership_relationship ) {
	
		$this->membership_relationship = $membership_relationship;
		
		$post_by_post = MS_Plugin::instance()->addon->post_by_post;
		
		$has_access = false;
		if ( is_single() ) {
			$post_id = get_the_ID();
			if( $post_by_post ) {
				$has_access = $this->ruled_by_post_by_post( $post_id );	
			}
			else {
				$has_access = $this->ruled_by_categories( $post_id );
			}
		}
		elseif( is_category() ) {
			if( $post_by_post ) {
				$has_access = true;
			}
			else {
				$has_access = $this->category_rule->has_access( $this->membership_relationship );
			}
		}
		
		return $has_access;
	}
	/**
	 * Ruled by categories tab.
	 * Post dripped access has priority over categories.
	 * @param $post_id The post id to verify access.
	 */
	public function ruled_by_categories( $post_id ) {
		$has_access = false;
		/**
		 * Verify if has access accordinly to category rules.
		*/
		if( in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
			$categories = wp_get_post_categories( $post_id );
			$intersect = array_intersect( $categories, $this->category_rule->rule_value );
			$has_access = ! empty( $intersect );
			/**
			 * Verify dripped access for category.
			 * Has priority over general category rules.
			*/
			if( ! empty( $this->category_rule->dripped ) && is_array( $categories ) ) {
				foreach ( $categories as $category_id ) {
					$has_access = $this->category_rule->has_dripped_access( $category_id, $this->membership_relationship->start_date );
				}
			}
		}
		/**
		 * Verify if has access accodinly to post dripped rules.
		 */
		if( array_key_exists( $post_id, $this->post_rule->dripped ) ){
			$has_access = $this->post_rule->has_dripped_access( $post_id, $this->membership_relationship->start_date );
		}
		return $has_access;
	}
	
	/**
	 * Ruled by post by post tab.
	 * Post dripped access has priority.
	 * @param $post_id The post id to verify access.
	 */
	public function ruled_by_post_by_post( $post_id ) {
		$has_access = false;
		
		/**
		 * Verify if has access accordinly to category rules.
		 */
		if( in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
			$categories = wp_get_post_categories( $post_id );
			/**
			 * Verify dripped access for category.
			 * Has priority over general category rules.
			*/
			if( ! empty( $this->category_rule->dripped ) && is_array( $categories ) ) {
				foreach ( $categories as $category_id ) {
					$has_access = $this->category_rule->has_dripped_access( $category_id, $this->membership_relationship->start_date );
				}
			}
		}
		/**
		 * Verify if has access accodinly to post rules.
		 * Dripped rules has priority over post access rules.
		 */
		if( array_key_exists( $post_id, $this->post_rule->dripped ) ){
			$has_access = $this->post_rule->has_dripped_access( $post_id, $this->membership_relationship->start_date );
		}
		else {
			$has_access = $this->post_rule->has_access( $this->membership_relationship );
		}
		return $has_access;
	} 
}