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
	
	const RULE_VALUE_NO_ACCESS = 1;
	const RULE_VALUE_READ = 2;
	const RULE_VALUE_WRITE = 3;
	
	/**
	 * Verify access to the current asset.
	 *
	 * @since 1.0
	 *
	 * @param $id The item id to verify access.
	 * @return boolean True if has access, false otherwise.
	 */
	public function has_access( $id = null ) {
		return false;
	}
	
	public function get_rule_value( $id ) {
		$value = isset( $this->rule_value[ $id ] ) ? $this->rule_value[ $id ] : 0;
		return apply_filters( 'ms_model_rule_comment_get_rule_value', $value, $id, $this->rule_value );
	
	}
	
	/**
	 * Set initial protection.
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->add_filter( 'the_content', 'check_special_page' );
		
		$rule_value = $this->get_rule_value( self::CONTENT_ID );
		switch( $rule_value ) {
			case self::RULE_VALUE_WRITE:
				add_filter( 'comments_open', '__return_true', 99 );
				break;
			case self::RULE_VALUE_READ:
				$this->add_filter( 'comment_reply_link', 'comment_reply_link', 99 );
				$this->add_filter( 'comments_open', 'read_only_comments', 99 );
				break;
			case self::RULE_VALUE_NO_ACCESS:
				add_filter( 'comments_open', '__return_false', 99 );
				$this->add_filter( 'get_comments_number', 'get_comments_number' );
				break;
		}
	}
	
	/**
	 * Workaround to enable read only comments.
	 * 
	 * @todo find a better way to allow read only comments.
	 * 
	 * **Hooks Filters: **
	 *
	 * * comments_open
	 * 
	 * @since 1.0
	 * 
	 * @param bool $open 
	 * @return boolean
	 */
	public function read_only_comments( $open ) {
		$traces = MS_Helper_Debug::debug_trace( true );
		if( false !== strpos( $traces, 'function: comment_form' ) ) {
			$open = false;
		}
		return $open;
	}
	
	/**
	 * Workaround to hide reply link when in read only mode.
	 * 
	 * @since 1.0
	 * 
	 * @param string $link
	 * @return string
	 */
	public function comment_reply_link( $link ) {
		return '';
	}
	
	/**
	 * Workaround to hide existing comments.
	 *
	 * **Hooks Filters: **
	 *
	 * * get_comments_number
	 * 
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_comments_number() {
		return 0;
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
	
	public function get_contents( $args = null ) {
		$contents = array();
		return apply_filters( 'ms_model_rule_comment_get_content', $contents );
	}
	
	public function get_content_array( $args = null ) {
		
		$contents = array(
				self::RULE_VALUE_WRITE => __( 'Read and Write Access', MS_TEXT_DOMAIN ),
				self::RULE_VALUE_READ => __( 'Read Only Access', MS_TEXT_DOMAIN ),
				self::RULE_VALUE_NO_ACCESS => __( 'No Access to Comments', MS_TEXT_DOMAIN ),
		);
		
		return apply_filters( 'ms_model_rule_comment_get_content_array', $contents );
	}
	
	public function set_access( $id, $rule_value ) {
		MS_Helper_Debug::log("id: $id, rulevalue: $rule_value");
		$this->rule_value[ $id ] = $rule_value;
	}
}