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
	public function protect_content( $start_date ) {
		$this->start_date = $start_date;
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
			if ( ! in_array( $page->ID, $rule_value ) ) {
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

		if( in_array( $page_id, $this->rule_value ) || in_array( $page_id, $settings->pages ) ) { 
			$has_access = true;
		}

		return $has_access;		
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
	 * @param string $args The default query post args.
	 * @return number The total content count.
	 */
	public function get_content_count( $args = null ) {
		$exclude = $this->get_excluded_content();
		$defaults = array(
				'posts_per_page' => -1,
				'post_type'   => 'page',
				'post_status' => array('publish', 'virtual'), 
				'exclude'     => $exclude,
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
		$exclude = $this->get_excluded_content();
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'post_date',
				'order'       => 'DESC',
				'post_type'   => 'page',
				'post_status' => array('publish', 'virtual'), //Classifieds plugin uses a "virtual" status for some of it's pages
				'exclude'     => $exclude,
			);
		$args = wp_parse_args( $args, $defaults );
		
		$contents = get_posts( $args );
		
		foreach( $contents as $content ) {
			$content->id = $content->ID;
			$content->type = MS_Model_RULE::RULE_TYPE_PAGE;
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
	
	/**
	 * Get pages that should not be protected.
	 * Settings pages like protected, subscribe, etc.
	 * @return array The page ids.
	 */
	private function get_excluded_content() {
		$settings = MS_Plugin::instance()->settings;
		$exclude = null;
		foreach ( $settings->pages as $page ) {
			if ( !empty ( $page ) ) {
				$exclude[] = $page;
			}
		}
		return $exclude;
	}
}