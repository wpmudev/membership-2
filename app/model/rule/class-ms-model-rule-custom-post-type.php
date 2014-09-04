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


class MS_Model_Rule_Custom_Post_Type extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_CUSTOM_POST_TYPE;
	
	/**
	 * Set initial protection.
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->add_action( 'pre_get_posts', 'protect_posts', 98 );
	}
	
	/**
	 * Adds filter for posts query to remove all protected custom post types.
	 *
	 * @since 4.0
	 * @action pre_get_posts 
	 *
	 * @access public
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( $wp_query ) {
		$post_type = $wp_query->get( 'post_type' );
		
		/**
		 * Only protect if not cpt group.
		 * Restrict query to show only has_access cpt posts.
		 * @todo handle default rule value.
		 */
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			if ( ! $wp_query->is_singular && empty( $wp_query->query_vars['pagename'] ) && ! empty( $post_type ) &&
			 ! in_array( $post_type, MS_Model_Rule_Custom_Post_Type_Group::get_excluded_content() ) )  {

				/** If default access is true, set which posts should be protected. */
				if( $this->rule_value_default ) {
					foreach( $this->rule_value as $id => $value ) {
						if( ! $value ) {
							$wp_query->query_vars['post__not_in'][] = $id;
						}
					}
				
				}
				/** If default is false, set which posts has access. */
				else {
					foreach( $this->rule_value as $id => $value ) {
						if( $value ) {
							$wp_query->query_vars['post__in'][] = $id;
						}
					}
				}
			}
		}
	}
	
	/**
	 * Verify access to the current post.
	 * @return boolean
	 */
	public function has_access( $post_id = null ) {
		
		$has_access = false;
		/**
		 * Only verify permission if ruled by cpt post by post.
		 */
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$has_access = false;
			if( empty( $post_id ) ) {
				$post_id  = $this->get_current_post_id();
			}
			if( ! empty( $post_id ) ) {
				if( in_array( get_post_type( $post_id ), MS_Model_Rule_Custom_Post_Type_Group::get_ms_post_types() ) ) {
					$has_access = true;
				}
				else {
					$has_access = parent::has_access( $post_id  );
				}
			}
		}

		return $has_access;
	}
	
	/**
	 * Get the current post id.
	 * @return int The post id, or null if it is not a post.
	 */
	private function get_current_post_id() {
		$post_id = null;
		$post = get_queried_object();
		
		if( is_a( $post, 'WP_Post' ) )  {
			$post_id = $post->ID;
		}
		return $post_id;
	}
	
	/**
	 * Get the total content count.
	 * For list table pagination.
	 * @param string $args The default query post args.
	 * @return number The total content count.
	 */
	public function get_content_count( $args = null ) {
		$cpts = MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types();
		
		$defaults = array(
				'posts_per_page' => -1,
				'post_type'   => $cpts,
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
	public function get_contents( $args = null ) {
		$cpts = MS_Model_Rule_Custom_Post_Type_Group::get_custom_post_types();

		if( empty( $cpts ) ) {
			return array();
		}
		
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'post_date',
				'order'       => 'DESC',
				'post_type'   => $cpts,
				'post_status' => 'publish',
		);
		$args = wp_parse_args( $args, $defaults );

		$contents = get_posts( $args );
		
		foreach( $contents as $content ) {
			$content->id = $content->ID;
			$content->type = $this->rule_type;
			$content->access = parent::has_access( $content->id  );
		}

		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		return $contents;
	}
}