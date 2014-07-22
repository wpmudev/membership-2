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


class MS_Model_Rule_Buddypress_Blog extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_BLOG;
	
	/**
	 * Set initial protection.
	 * 
	 * @since 4.0.0
	 * 
	 * @param optional $membership_relationship The membership relationship info. 
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->add_filter( 'bp_blogs_get_blogs', 'protect_blogs', 10 );
		$this->add_filter( 'bp_has_blogs', 'protect_has_blogs', 10, 2 );
		$this->add_filter( 'bp_activity_get', 'protect_activity', 10 );
		$this->add_filter( 'bp_get_total_blog_count', 'fix_blog_count' );
	}
	
	/**
	 * Protect blogs from showing.
	 * 
	 * @since 4.0.0
	 * 
	 * @param mixed $blogs The available blogs.
	 * @return mixed The filtered blogs.
	 */
	public function protect_blogs( $blogs ) {
		if( ! empty( $blogs['blogs'] ) ) {
			foreach ( $blogs['blogs'] as $key => $blog ) {
				if ( ! in_array( $blog->blog_id, $this->rule_value ) ) {
					unset( $blogs['blogs'][ $key ] );
					$blogs['total']--;
				}
			}
			/** reset index keys */
			$blogs['blogs'] = array_values( $blogs['blogs'] );
		}
		
		return apply_filters( 'ms_model_rule_buddypress_blog_protect_blogs', $blogs );
	}
	
	/**
	 * Protect BP has blogs function.
	 * 
	 * @since 4.0.0
	 * 
	 * @param int $one
	 * @param mixed $blogs The available blogs.
	 * @return bool if has blogs after filtering.
	 */
	public function protect_has_blogs( $one, $blogs ) {
		if( ! empty( $blogs->blogs ) ) {
			foreach ( $blogs->blogs as $key => $blog ) {
				if ( ! in_array( $blog->blog_id, $this->rule_value ) ) {
					unset( $blogs->blogs[ $key ] );
					$blogs->total_blog_count--;
				}
			}
			/** reset index keys */
			$blogs->blogs = array_values( $blogs->blogs );
		}
		return apply_filters( 'ms_model_rule_buddypress_blog_protect_has_blogs', ! empty( $blogs->blogs ) );
	}
	
	/**
	 * Protect activities.
	 * 
	 * Filter activities from protected blogs.
	 * 
	 * @since 4.0.0
	 * 
	 * @param mixed $activities The BP activities to filter.
	 * @return mixed The filtered BP activities.
	 */
	public function protect_activity( $activities ) {

		if( ! empty( $activities['activities'] ) ) {
			foreach ( $activities['activities'] as $key => $activity ) {
				if ( ! in_array( $activity->item_id, $this->rule_value ) ) {
					unset( $activities['activities'][ $key ] );
					$activities['total']--;
				}
			}
			/** reset index keys */
			$activities['activities'] = array_values( $activities['activities'] );
		}
		
		return apply_filters( 'ms_model_rule_buddypress_blog_protect_activity', $activities );
	}
	
	/**
	 * Fixes BP count.
	 * 
	 * Returns BP blogs after filtering protected ones.
	 * 
	 * @since 4.0.0
	 * 
	 * @param int $count The count to filter.
	 * @return int The blog count.
	 */
	public function fix_blog_count( $count ) {
		$count = count( $this->rule_value );
		return apply_filters( 'ms_model_rule_buddypress_blog_fix_blog_count', $count );
	}
	
	/**
	 * Get content to protect.
	 * 
	 * @since 4.0.0
	 * 
	 * @param $args Not used, but kept due to method override.
	 * @return array The content eligible to protect by this rule domain.
	 */
	public function get_content( $args = null ) {
		$contents = array();
		if( function_exists( 'bp_blogs_get_blogs' ) ) {
			$blogs = bp_blogs_get_blogs( array( 'per_page' => 50 ) );
			if( ! empty( $blogs['blogs'] ) ) {
				foreach( $blogs['blogs'] as $blog ) {
					$content = new StdClass();
					$content->id = $blog->blog_id;
					$content->name = $blog->name;
					
					if( in_array( $content->id, $this->rule_value ) ) {
						$content->access = true;
					}
					else {
						$content->access = false;
					}
					$contents[] = $content;
				}
			}
		}

		return apply_filters( 'ms_model_rule_buddypress_blog_get_content', $contents );
	}
}