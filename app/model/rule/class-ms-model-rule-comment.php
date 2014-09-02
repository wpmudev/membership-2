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


class MS_Model_Rule_Comment extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_COMMENT;
	
	const CONTENT_ID = 'comment';
	
	const RULE_VALUE_NO_ACCESS = 0;
	const RULE_VALUE_READ = 1;
	const RULE_VALUE_WRITE = 2;
	
	/**
	 * Set initial protection.
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->add_filter( 'the_content', 'check_special_page' );
		
		if( parent::has_access( self::CONTENT_ID ) ) {
			add_filter( 'comments_open', '__return_true', 99 );
		}
		else {
			add_filter( 'comments_open', '__return_false', 99 );
		}
	}
	
	/**
	 * Close comments for membership special pages.
	 */
	public function check_special_page( $content ) {
		if ( MS_Plugin::instance()->settings->is_special_page() ) {
			add_filter( 'comments_open', '__return_false', 100 );
		}
		return $content;
	}
	
	public function get_content( $args = null ) {
		$contents = array();
// 		$content = new StdClass();
// 		$content->id = 'read';
// 		$content->name = __( 'Read Only Access', MS_TEXT_DOMAIN );
// 		$content->access = parent::has_access( $content->id );
// 		$contents[] = $content;
		
// 		$content = new StdClass();
// 		$content->id = 'write';
// 		$content->name = __( 'Read and Write Access', MS_TEXT_DOMAIN );
// 		$content->access = parent::has_access( $content->id );
// 		$contents[] = $content;
		
// 		$content = new StdClass();
// 		$content->id = 'no_access';
// 		$content->name = __( 'No Access to Comments', MS_TEXT_DOMAIN );
// 		$content->access = parent::has_access( $content->id );
// 		$contents[] = $content;
		
		return apply_filters( 'ms_model_rule_comment_get_content', $contents );
	}
	
	public function get_content_array( $args = null ) {
		
		$contents = array(
				self::RULE_VALUE_READ => __( 'Read Only Access', MS_TEXT_DOMAIN ),
				self::RULE_VALUE_WRITE => __( 'Read and Write Access', MS_TEXT_DOMAIN ),
				self::RULE_VALUE_NO_ACCESS => __( 'No Access to Comments', MS_TEXT_DOMAIN ),
		);
		
		return apply_filters( 'ms_model_rule_comment_get_content_array', $contents );
	}
	
	public function get_rule_value() {
		$value = null;
		if( isset( $this->rule_value[ self::CONTENT_ID ] ) ) {
			$value = $this->rule_value[ self::CONTENT_ID ];
		}
		return apply_filters( 'ms_model_rule_comment_get_rule_value', $value );
	}
	
	public function set_access( $id, $rule_value ) {
		$this->rule_value[ $id ] = $rule_value;
	}
}