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
	public function protect_content( $start_date ) {
		$this->start_date = $start_date;
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
		if( ! MS_Plugin::instance()->addon->post_by_post ) {
			
			if ( in_array( $wp_query->get( 'post_type' ), array( 'post', '' )  ) ) {
				$categories = array_keys( $this->rule_value );
				$wp_query->set('category__in', array_unique( array_merge( $wp_query->query_vars['category__in'], $this->rule_value ) ) );
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
	
		if ( ! in_array( 'category', $taxonomies) ) {
			// bail - not fetching category taxonomy
			return $terms;
		}
	
		foreach ( (array) $terms as $key => $term ) {
			if ( $term->taxonomy == 'category' ) { //still do this check here - could be fetching multiple taxonomies
				if ( in_array( $term->term_id, $this->rule_value ) ) {
					$new_terms[$key] = $term;
				}
			} else {
				// this taxonomy isn't category so add it so custom taxonomies don't break
				$new_terms[$key] = $term;
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
		if( ! MS_Plugin::instance()->addon->post_by_post ) {
			/**
			 * Verify post access accordinly to category rules.
			 */
			if( ! empty( $post_id ) || is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
				if( empty( $post_id ) ) {
					$post_id = get_the_ID();
				}
				$categories = wp_get_post_categories( $post_id );
				$intersect = array_intersect( $categories, $this->rule_value );
				$has_access = ! empty( $intersect );
			}
			/**
			 * Category page.
			 */
			elseif( is_category() ) {
				$has_access = in_array( get_queried_object_id(), $this->rule_value );
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
		if( ! MS_Plugin::instance()->addon->post_by_post ) {
			/**
			 * Verify post access accordinly to category rules.
			 */
			if( is_single() && in_array( 'category', get_object_taxonomies( get_post_type() ) ) ) {
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
	 * @param string $args The default query post args.
	 * @return array The content.
	 */
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
	 * Get content array( id => title ).
	 * Used to show content in html select.
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