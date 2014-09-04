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


class MS_Model_Rule_Page extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_PAGE;
	
	protected $start_date;
	
	/**
	 * Set initial protection.
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->start_date = $membership_relationship->start_date;
		$this->add_filter( 'get_pages', 'protect_pages', 99 );
	}
	
	/**
	 * Filters protected pages.
	 *
	 * @action get_pages
	 *
	 * @param array $pages The array of pages.
	 * @return array Filtered array which doesn't include prohibited pages.
	 */
	public function protect_pages( $pages ) {
		$rule_value = apply_filters( 'ms_model_rule_page_protect_pages_rule_value', $this->rule_value );
	
		foreach ( (array) $pages as $key => $page ) {
			if( ! self::has_access( $page->ID ) ) {
				unset( $pages[ $key ] );
			}
			/**
			 * Dripped content.
			 */
			if( $this->has_dripped_rules( $page->ID ) && ! $this->has_dripped_access( $this->start_date, $page->ID ) ) {
				unset( $pages[ $key ] );
			}
				
		}
		return $pages;
	}
	
	/**
	 * Get the current page id.
	 * @return int The page id, or null if it is not a page.
	 */
	private function get_current_page_id() {
		
		$post_id = null;
		$post = get_queried_object();

		if( is_a( $post, 'WP_Post' ) && $post->post_type == 'page' )  {
			$post_id = $post->ID;
		}
		
		return $post_id;
	}
	
	/**
	 * Verify access to the current page.
	 * @param $membership_relationship 
	 * @return boolean
	 */
	public function has_access( $page_id = null ) {
		
		$settings = MS_Plugin::instance()->settings;
		$has_access = false;
		if( empty( $page_id ) ) {
			$page_id = $this->get_current_page_id();
		}

		if( ! empty( $page_id ) ) {
			$has_access = parent::has_access( $page_id );
			
			/** Membership special pages has access */
			if( $settings->is_special_page( $page_id ) ) {
				$has_access = true;
			}
		}
				
		return apply_filters( 'ms_model_rule_page_has_access',  $has_access, $page_id );		
	}

	/**
	 * Verify if has dripped rules.
	 * @return boolean
	 */
	public function has_dripped_rules( $page_id = null ) {

		if( empty ( $page_id ) ) {
			$page_id = $this->get_current_page_id();
		}

		return array_key_exists( $page_id, $this->dripped );
	}
	
	/**
	 * Verify access to dripped content.
	 * @param $start_date The start date of the member membership.
	 */
	public function has_dripped_access( $start_date, $page_id = null ) {
	
		$has_access = false;
		
		if( empty ( $page_id ) ) {
			$page_id = $this->get_current_page_id();
		}

		$has_access = parent::has_dripped_access( $start_date, $page_id );
	
		return $has_access;
	}
	
	/**
	 * Get the total content count.
	 * For list table pagination.
	 * 
	 * @since 1.0.0
	 * 
	 * @param string $args The default query post args.
	 * @return number The total content count.
	 */
	public function get_content_count( $args = null ) {
		$count = 0;
		
		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );

		/** @todo verify why $query->found_posts != count( $contents )*/
		$pages = $query->get_posts();
		
		$count = count( $pages );
// 		MS_Helper_Debug::log("------------get_content_count args:");
// 		MS_Helper_Debug::log($args);
// 		MS_Helper_Debug::log("count: $count, found posts: $query->found_posts");
// 		MS_Helper_Debug::log($pages);
		return apply_filters( 'ms_model_rule_page_get_content_count', $count, $args );
	}
	
	/**
	 * Prepare content to be shown in list table.
	 * @param string $args The default query post args.
	 * @return array The content.
	 */
	public function get_content( $args = null ) {
		$args = self::get_query_args( $args );
		
		$query = new WP_Query( $args );
// 		MS_Helper_Debug::log("***************get_content args:");
// 		MS_Helper_Debug::log($args);
// 		MS_Helper_Debug::log($query);
// 		$pages = get_posts( $args );
// 		$count = count( $pages );
// 		MS_Helper_Debug::log($pages);
// 		MS_Helper_Debug::log("count: $count, found posts::: $query->found_posts");
		$contents = array();
		$pages = $query->get_posts();
		foreach( $pages as $content ) {
			$content->id = $content->ID;
			$content->type = MS_Model_RULE::RULE_TYPE_PAGE;
			
			$content->access = self::has_access( $content->id );
			
			if( array_key_exists( $content->id, $this->dripped ) ) {
				$content->delayed_period = $this->dripped[ $content->id ]['period_unit'] . ' ' . $this->dripped[ $content->id ]['period_type'];
				$content->dripped = $this->dripped[ $content->id ];
			}
			else {
				$content->delayed_period = '';
			}
			
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
				'post_type'   => 'page',
				'post_status' => array( 'publish', 'virtual' ), //Classifieds plugin uses a "virtual" status for some of it's pages
				'post__not_in'     => $this->get_excluded_content(),
		);
		$args = wp_parse_args( $args, $defaults );
		
		/** If not visitor membership, just show protected content */
		if( ! $this->rule_value_invert ) {
			$visitor_membership = MS_Model_Membership::get_visitor_membership();
			$rule = $visitor_membership->get_rule( MS_Model_Rule::RULE_TYPE_PAGE );
			$args['post__in'] = array_keys( $rule->rule_value );
		}
		
		/** Cannot use post__in and post_not_in at the same time.*/
		if( ! empty( $args['post__in'] ) && ! empty( $args['post__not_in'] ) ) {
			$include = $args['post__in'];
			$exclude = $args['post__not_in'];
			foreach( $exclude as $id ) {
				$key = array_search( $id, $include );
				unset( $include[ $key ] );
			}
			unset( $args['post__not_in'] );
		}
		
		return apply_filters( 'ms_model_rule_page_get_query_args', $args );
	}
	
	/**
	 * Get page content array.
	 * Used to show content in html select.
	 * 
	 * @since 1.0.0
	 * @return array of id => page title 
	 */
	public function get_content_array( $args = null ) {
		$cont = array();

		$args = self::get_query_args( $args );
		
		$query = new WP_Query($args);
		
		$contents = $query->get_posts();
		foreach( $contents as $content ) {
			$cont[ $content->ID ] = $content->post_title;
		}

		return apply_filters( 'ms_model_rule_page_get_content_array', $cont );
	}
	
	/**
	 * Get pages that should not be protected.
	 * Settings pages like protected, subscribe, etc.
	 * 
	 * @since 1.0.0
	 * 
	 * @return array The page ids.
	 */
	private function get_excluded_content() {
		$settings = MS_Plugin::instance()->settings;
		$special_page_types = MS_Model_Settings::get_special_page_types();
		$exclude = null;
		foreach ( $special_page_types as $type ) {
			$exclude[] = $settings->get_special_page( $type );
		}
		
		return apply_filters( 'ms_model_rule_page_get_excluded_content', $exclude );
	}
}