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
	
	/**
	 * Get the current post id.
	 * @return int The post id, or null if it is not a post.
	 */
	private function get_current_post_id() {
		$post_id = null;
		$post = get_queried_object();
		if( is_a( $post, 'WP_Post' ) && $post->post_type == 'post' )  {
			$post_id = $post->ID;
		}
		return $post_id;
	}

	/**
	 * Verify access to the current post.
	 * @return boolean
	 */
	public function has_access( $post_id = null ) {
	
		$has_access = false;
		
		/**
		 * Only verify permission if ruled by post by post.
		 */
		if( MS_Plugin::instance()->addon->post_by_post ) {			
			$has_access = false;
			if( empty( $post_id ) ) {
				$post_id  = $this->get_current_post_id();
			}
			if( in_array( $post_id, $this->rule_value ) ) {
				$has_access = true;
			}
		}
		return $has_access;		
	}
	
	/**
	 * Verify if has dripped rules.
	 * @return boolean
	 */
	public function has_dripped_rules( $post_id = null ) {
		
		if( empty( $post_id ) ) {
			$post_id  = $this->get_current_post_id();
		}
		
		return array_key_exists( $post_id, $this->dripped );
		
// 		if( MS_Plugin::instance()->addon->post_by_post ) {
// 			$post_id  = $this->get_current_post_id();
// 			return array_key_exists( $post_id, $this->dripped );				
// 		}
// 		else {
// 			return false;
// 		}
	
	}
	
	/**
	 * Verify access to dripped content.
	 * @param $start_date The start date of the member membership.
	 */
	public function has_dripped_access( $start_date, $post_id = null ) {
	
		$has_access = false;
	
		if( empty( $post_id ) ) {
			$post_id  = $this->get_current_post_id();
		}
		
		$has_access = parent::has_dripped_access( $post_id, $start_date );
		
		return $has_access;
	}
	
	/**
	 * Get the total content count.
	 * For list table pagination. 
	 * @param string $args The default query post args.
	 * @return number The total content count.
	 */
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
	
	/**
	 * Prepare content to be shown in list table.
	 * @param string $args The default query post args.
	 * @return array The content.
	 */
	public function get_content( $args = null ) {
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
			$content->access = false;
				
			$content->categories = array();
			$categories = wp_get_post_categories( $content->id );
			if( ! empty( $categories ) ) {
				foreach( $categories as $cat_id ) {
					$cat = get_category( $cat_id );
					$cats[] = $cat->name;
				}
				$content->categories = $cats;
			}
			else {
				$content->categories = array();
			}
				
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
	 * Get content array( id => title ).
	 * Used to show content in html select.
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