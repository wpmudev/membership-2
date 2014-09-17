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
	
	protected $rule_type = self::RULE_TYPE_CATEGORY;
	
	protected $start_date; 
	
	/**
	 * Set initial protection.
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->start_date = $membership_relationship->start_date;
		$this->add_action( 'pre_get_posts', 'protect_posts', 98 );
		$this->add_filter( 'get_terms', 'protect_categories', 99, 3 );
	}
	
	/**
	 * Adds category__in filter for posts query to remove all posts which not
	 * belong to allowed categories.
	 *
	 * @since 4.0
	 * @action pre_get_posts 
	 *
	 * @access public
	 * @param WP_Query $query The WP_Query object to filter.
	 */
	public function protect_posts( $wp_query ) {
		/**
		 * Only verify permission if ruled by categories.
		 */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			
			if( in_array( $wp_query->get( 'post_type' ), array( 'post', '' )  ) ) {

				$categories = array();
				$contents = $this->get_contents();
				foreach( $contents as $content ) {
					if( $content->access ) {
						$categories[] = $content->id;
					}
				}
				$wp_query->query_vars['category__in'] = $categories;
			}
		}
	}
	
	/**
	 * Filters categories and removes all not accessible categories.
	 *
	 * @sicne 4.0
	 *
	 * @access public
	 * @param array $terms The terms array.
	 * @return array Fitlered terms array.
	 */
	public function protect_categories( $terms, $taxonomies, $args ) {
		$new_terms = array();
	
		/** bail - not fetching category taxonomy */
		if( ! in_array( 'category', $taxonomies ) ) {
			
			return $terms;
		}
	
		foreach( (array) $terms as $key => $term ) {
			if( ! empty( $term->taxonomy ) && 'category' == $term->taxonomy ) { 
				if ( parent::has_access( $term->term_id ) ) {
					$new_terms[ $key ] = $term;
				}
			} 
			else {
				/** this taxonomy isn't category so add it so custom taxonomies don't break */
				$new_terms[ $key ] = $term;
			}
		}
	
		return $new_terms;
	}
	
	/**
	 * Verify access to the current category or post belonging to a catogory.
	 * @return boolean
	 */
	public function has_access( $post_id = null ) {
		
		$has_access = false;
		
		/**
		 * Only verify permissions if ruled by categories.
		 */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			/**
			 * Verify post access accordinly to category rules.
			 */
			if( ! empty( $post_id ) || ( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) ) {
				if( empty( $post_id ) ) {
					$post_id = get_the_ID();
				}
				
				$categories = wp_get_post_categories( $post_id );
				foreach( $categories as $category_id ) {
					$has_access = $has_access || parent::has_access( $category_id );
					if( $has_access ) {
						break;
					}
				}
			}
			/**
			 * Category page.
			 */
			elseif( is_category() ) {
				$has_access = parent::has_access( get_queried_object_id() );
			}
				
		}
		return $has_access;
	}
	
	/**
	 * Verify if has dripped rules.
	 * Only if ruled by categories.
	 * @return boolean
	 */
	public function has_dripped_rules( $post_id = null ) {
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			/**
			 * Verify post access accordinly to category rules.
			 */
			if( ! empty( $post_id ) || ( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) ) {
				if( empty( $post_id ) ) {
					$post_id = get_the_ID();
				}
				$categories = wp_get_post_categories( $post_id );
				$intersect = array_intersect( $categories, array_keys( $this->dripped ) );
				return ! empty( $intersect );
			}
			/**
			 * Category page.
			 */
			elseif( is_category() ) {
				return array_key_exists( get_queried_object_id(), $this->dripped );
			}
		}
		else {
			return false;
		}
		
	}
	
	/**
	 * Verify access to dripped content.
	 * @param $start_date The start date of the member membership.
	 */
	public function has_dripped_access( $start_date, $post_id = null ) {
		
		$has_access = false;
		
		/**
		 * Verify post access accordinly to category rules.
		 */
		if( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
			if( empty( $post_id ) ) {
				$post_id = get_the_ID();
			}
			$categories = wp_get_post_categories( $post_id );
			if( ! empty( $categories ) ) {
				foreach( $categories as $category_id ) {
					$has_access = $has_access || parent::has_dripped_access( $start_date, $category_id );
				}				
			}
		}
		/**
		 * Category page.
		 */
		elseif( is_category() ) {
			$has_access = parent::has_dripped_access( $start_date, get_queried_object_id() );
		}
		
		return $has_access;
	}
	
	/**
	 * Prepare content to be shown in list table.
	 * 
	 * @since 1.0.0
	 * 
	 * @param string $args The default query args.
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$contents = get_categories( 'get=all' );

		foreach( $contents as $key => $content ) {
			$content->id = $content->term_id;
			if( ! $this->has_rule( $content->id ) ) {
				unset( $contents[ $key ] );
				continue;
			}
			$content->type = MS_Model_RULE::RULE_TYPE_CATEGORY;

			$content->access = $this->get_rule_value( $content->id );
			
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
	 * Get category content array.
	 * Used to show content in html select.
	 *
	 * @since 1.0.0
	 * @return array of id => category name
	 */
	public function get_content_array() {
		$cont = array();
		$contents = get_categories( 'get=all' );
		
		foreach( $contents as $key => $content ) {
			$cont[ $content->term_id ] = $content->name;
		}
		return apply_filters( 'ms_model_rule_category_get_content_array', $cont );
	}
	
}