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


class MS_Model_Rule_More extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = self::RULE_TYPE_MORE_TAG;
	
	protected $protection_message;
	
	/**
	 * Set initial protection.
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->protection_message = ! empty( MS_Plugin::instance()->settings->protection_message['more_tag'] ) 
			? MS_Plugin::instance()->settings->protection_message['more_tag'] 
			: '';
		if( parent::has_access( 1 ) ) {
			$this->add_filter( 'the_content_more_link', 'show_moretag_protection', 99, 2 );
			$this->add_filter( 'the_content', 'replace_moretag_content', 1 );
			$this->add_filter( 'the_content_feed', 'replace_moretag_content', 1 );
		}
	}
	
	function show_moretag_protection( $more_tag_link, $more_tag ) {

		return stripslashes( $this->protection_message );
	}
	
	function replace_moretag_content( $the_content ) {

		$more_starts_at = strpos( $the_content, '<span id="more-' );
		if ( false !== $more_starts_at ) {
			$the_content = substr( $the_content, 0, $more_starts_at );
			$the_content .= stripslashes( $this->protection_message );
		}
	
		return $the_content;
	}
	
	public function get_content( $args = null ) {
		$content = new StdClass();
		$content->id = 1;
		$content->name = __( 'User can read full post content beyond the More tag.', MS_TEXT_DOMAIN );

		$content->access = parent::has_access( $content->id );
		
		return array( $content );
	}
}