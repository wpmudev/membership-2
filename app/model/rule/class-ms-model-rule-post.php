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
	
	protected $rule_type = self::RULE_TYPE_POST;
	
	private $start_date;
	
	/**
	 * Set initial protection.
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->start_date = $membership_relationship->start_date;
		$this->add_action( 'pre_get_posts', 'protect_posts', 99 );
		$this->add_filter( 'posts_where', 'include_dripped', 10, 2 );
	}
	
	public function protect_posts( $wp_query ) {
		/* List rather than on a single post */
		if ( ! $wp_query->is_singular && empty( $wp_query->query_vars['pagename'] )
			&& ( ! isset( $wp_query->query_vars['post_type'] ) || in_array( $wp_query->query_vars['post_type'], array( 'post', '' ) ) ) ) {
			/**
			 * Only verify permission if ruled by post by post.
			 */
			if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
				foreach( $this->rule_value as $id => $value ) {
					if( ! $this->has_access( $id ) ) {
						$wp_query->query_vars['post__not_in'][] = $id;
					}
				}
			}
			
			$membership = $this->get_membership();
			if( MS_Model_Membership::TYPE_DRIPPED == $membership->type ) {
				$dripped_type = $this->get_dripped_type();
				/**
				 * Exclude dripped content.
				 * Can't include posts, just exclude because of category clause conflict to post_in.
				 * Using filter 'posts_where' to include dripped content.
				 * * @todo handle default rule value.
				 */
				foreach( $this->dripped[ $dripped_type ] as $post_id => $period ) {
					if( ! $this->has_dripped_access( $this->start_date, $post_id ) ) {
						$wp_query->query_vars['post__not_in'][] = $post_id;
						if( $key = array_search( $post_id, $wp_query->query_vars['post__in'] ) ) {
							unset( $wp_query->query_vars['post__in'][ $key ] );
						}
					}
				}
			}
		}
	}
	
	/**
	 * Include dripped content.
	 * 
	 * Workaround to include dripped posts that not belongs to a accessible category.
	 * 
	 * @param string $where
	 * @param WP_Query $wp_query
	 * @return string
	 */
	public function include_dripped( $where, $wp_query ) {
		global $wpdb;
		if ( ! $wp_query->is_singular && empty( $wp_query->query_vars['pagename'] )
			&& ( ! isset( $wp_query->query_vars['post_type'] ) || in_array( $wp_query->query_vars['post_type'], array( 'post', '' ) ) ) ) {
			
			$membership = $this->get_membership();
			if( MS_Model_Membership::TYPE_DRIPPED == $membership->type ) {
				$dripped_type = $this->get_dripped_type();
						
				$posts = array();
				foreach( $this->dripped[ $dripped_type ] as $post_id => $period ) {
					if( $this->has_dripped_access( $this->start_date, $post_id ) ) {
						$posts[] = $post_id;
					}
				}
				if( ! empty( $posts ) ) {
					$post__in = join( ',', $posts );
					$where .= " OR {$wpdb->posts}.ID IN ($post__in)";
				}
			}
		}
		return $where;
	}
	
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

	public function get_rule_value( $id ) {
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$value = isset( $this->rule_value[ $id ] ) ? $this->rule_value[ $id ] : $this->rule_value_default;
		}
		else {
			$membership = $this->get_membership();
			$rule_category = $membership->get_rule( self::RULE_TYPE_CATEGORY );
			$value = isset( $this->rule_value[ $id ] ) ? $this->rule_value[ $id ] : $rule_category->has_access( $id );
		}
		return apply_filters( 'ms_model_rule_post_get_rule_value', $value, $id, $this->rule_value );
	
	}
	
	/**
	 * Verify access to the current post.
	 * @return boolean
	 */
	public function has_access( $post_id = null ) {
	
		$has_access = false;
		
		if( empty( $post_id ) ) {
			$post_id  = $this->get_current_post_id();
		}
		/**
		 * Only verify permission if ruled by post by post.
		 * @todo verify addon handling
		 */
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$has_access = parent::has_access( $post_id );
		}
		else {
			$has_access = $this->get_rule_value( $post_id );
		}

		/**
		 * Feed page request
		 */
		global $wp_query;
		if ( ! empty( $wp_query->query_vars['feed'] ) ) {
			$has_access = true;
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
		
		return apply_filters( 'ms_model_rule_post_has_dripped_rules', parent::has_dripped_rules( $post_id ) );	
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
		
		$has_access = parent::has_dripped_access( $start_date, $post_id );
		
		return $has_access;
	}
	
	/**
	 * Get the total content count.
	 * For list table pagination. 
	 * @param string $args The default query post args.
	 * @return number The total content count.
	 */
	public function get_content_count( $args = null ) {
		$args = self::get_query_args( $args );
	
		$query = new WP_Query( $args );
		return $query->found_posts;
	}
	
	/**
	 * Prepare content to be shown in list table.
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
			$content->type = MS_Model_RULE::RULE_TYPE_POST;
			$content->access = false;
			$content->name = $content->post_name;
				
			$content->categories = array();
			$cats = array();
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

			$content->access = $this->get_rule_value( $content->id );
			
			$content->delayed_period = $this->has_dripped_rules( $content->id );
			$content->avail_date = $this->get_dripped_avail_date( $content->id );
			
			$contents[ $content->id ] = $content;
		}
		
		if( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}
		return $contents;
		
	}
	
	public function get_query_args( $args = null ) {
	
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'post_date',
				'order'       => 'DESC',
				'post_type'   => 'post',
				'post_status' => 'publish',
		);
		
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$membership = $this->get_membership();
			$rule_category = $membership->get_rule( self::RULE_TYPE_CATEGORY );
				
			$args['category__in'] = array_keys( $rule_category->rule_value );
			$args['tax_query'] = array( 'relation' => 'OR' );			
		}
		
		$args = wp_parse_args( $args, $defaults );
		$args = parent::get_query_args( $args );

		return apply_filters( 'ms_model_rule_post_get_query_args', $args );
	}
	
	/**
	 * Get content array( id => title ).
	 * Used to show content in html select.
	 */
	public function get_content_array() {
		$cont = array();
		$contents = $this->get_contents();
		foreach( $contents as $content ) {
			$cont[ $content->id ] = $content->post_title;
		}
		return $cont;
	}
	
}