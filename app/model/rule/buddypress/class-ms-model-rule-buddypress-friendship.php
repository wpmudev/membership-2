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


class MS_Model_Rule_Buddypress_Friendship extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_COMMENT;
	
	/**
	 * Set initial protection.
	 */
	public function protect_content( $membership_relationship = false ) {
	}
	
	public function get_content( $args = null ) {
		$content = new StdClass();
		$content->id = 1;
		$content->name = __( 'User gets read and make comments of posts.', MS_TEXT_DOMAIN );

		if( in_array( $content->id, $this->rule_value ) ) {
			$content->access = true;
		}
		else {
			$content->access = false;
		}

		return array( $content );
	}
}