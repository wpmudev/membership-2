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


class MS_Model_Rule_Bbpress extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = MS_Integration_BbPress::RULE_TYPE_BBPRESS;
	
	/**
	 * Verify access to the current post.
	 *
	 * @since 4.0.0
	 *
	 * @return boolean
	 */
	public function has_access( $post_id = null ) {
	
		$has_access = false;
		/**
		 * Only verify permission if ruled by cpt post by post.
		 */
		if( MS_Model_Addon::is_enabled( MS_Integration_Bbpress::ADDON_BBPRESS ) ) {
			if( empty( $post_id ) ) {
				$post_id  = $this->get_current_post_id();
			}
			
			if( ! empty( $post_id ) ) {
				$post_type = get_post_type( $post_id );
				if( in_array( $post_type, MS_Integration_Bbpress::get_bb_custom_post_types() ) ) {
					switch( $post_type ) {
						case MS_Integration_Bbpress::CPT_BB_FORUM:
							$has_access = parent::has_access( $post_id );
							break;
						case MS_Integration_Bbpress::CPT_BB_TOPIC:
							if( function_exists( 'bbp_get_topic_forum_id' ) ) {
								$forum_id = bbp_get_topic_forum_id( $post_id );
								$has_access = parent::has_access( $forum_id );
							}
							break;
						case MS_Integration_Bbpress::CPT_BB_REPLY:
							if( function_exists( 'bbp_get_reply_forum_id' ) ) {
								$forum_id = bbp_get_reply_forum_id( $post_id );
								$has_access = parent::has_access( $forum_id );
							}
							break;
					}
				}
			}
			else {
				global $wp_query;
				/** 
				 * If post type is forum and no post_id, it is the forum list page, give access. 
				 * @todo Find another way to find if the current page is the forum list page.
				 */
				if( MS_Integration_Bbpress::CPT_BB_FORUM == $wp_query->get( 'post_type' ) ) {
					$has_access = true;
				}
				
			}
		}
	
		return $has_access;
	}
		
	/**
	 * Set initial protection.
	 * 
	 * @since 4.0.0
	 * 
	 * @param optional $membership_relationship The membership relationship info. 
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->add_action( 'pre_get_posts', 'protect_posts', 98 );
	}
	
	/**
	 * Adds filter for posts query to remove all protected bbpress custom post types.
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
		 * Only protect if add-on is enabled.
		 * Restrict query to show only has_access cpt posts.
		 * @todo handle default rule value
		*/
		if( MS_Model_Addon::is_enabled( MS_Integration_Bbpress::ADDON_BBPRESS ) ) {
			if ( ! $wp_query->is_singular && empty( $wp_query->query_vars['pagename'] ) && 
				! empty( $post_type ) && MS_Integration_Bbpress::CPT_BB_FORUM == $post_type ) {
				
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
	 * Get the current post id.
	 * 
	 * @since 4.0.0
	 * 
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
		
		$defaults = array(
				'posts_per_page' => -1,
				'post_type'   => MS_Integration_Bbpress::CPT_BB_FORUM,
				'post_status' => 'publish',
		);
		$args = wp_parse_args( $args, $defaults );
	
		$query = new WP_Query($args);
		return $query->found_posts;
	}
	
	/**
	 * Prepare content to be shown in list table.
	 * 
	 * @since 4.0.0
	 * 
	 * @param string $args The default query post args.
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		
		$args = self::get_query_args( $args );
		
		$query = new WP_Query( $args );
		$posts = $query->get_posts();

		$contents = array();
		foreach( $posts as $content ) {
			$content->id = $content->ID;
			$content->name = $content->post_title;
			$content->type = $this->rule_type;
			$content->access = parent::has_access( $content->id  );
			
			$contents[ $content->id ] = $content;
		}

		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		return apply_filters( 'ms_model_rule_bbpress_get_contents', $contents );
	}
	
	public function get_query_args( $args = null ) {
	
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'ID',
				'order'       => 'DESC',
				'post_type'   => MS_Integration_Bbpress::CPT_BB_FORUM,
				'post_status' => 'publish',
		);
		
		$args = wp_parse_args( $args, $defaults );
		$args = parent::get_query_args( $args );
		
		return apply_filters( 'ms_model_rule_bbpress_get_query_args', $args );
	}	
}