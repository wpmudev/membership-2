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
	
	public function on_protection() {
		
	}

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
	/**
	 * Get pages that shall not be protected.
	 * @return array The page ids.
	 */
	private function get_excluded_content() {
		$exclude = null;
		// 		foreach ( array( 'registration_page', 'account_page', 'subscriptions_page', 'nocontent_page', 'registrationcompleted_page' ) as $page ) {
		// 			if ( isset( $M_options[$page] ) && is_numeric( $M_options[$page] ) ) {
		// 				$exclude[] = $M_options[$page];
		// 			}
		// 		}
		return $exclude;
	}
}