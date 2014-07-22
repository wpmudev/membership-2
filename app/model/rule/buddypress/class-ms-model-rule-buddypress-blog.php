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
	 */
	public function protect_content( $membership_relationship = false ) {
	}
	
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