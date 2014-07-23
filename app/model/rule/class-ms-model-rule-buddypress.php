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


class MS_Model_Rule_Buddypress extends MS_Model_Rule {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $rule_type = MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS;
	
	/**
	 * Set initial protection.
	 * 
	 * @since 4.0.0
	 * 
	 * @param optional $membership_relationship The membership relationship info. 
	 */
	public function protect_content( $membership_relationship = false ) {
		$this->add_filter( 'bp_user_can_create_groups', 'protect_create_bp_group' );
		$this->protect_friendship_request();
	}
	
	/**
	 * Protect friendship request.
	 * 
	 * @since 4.0.0
	 * 
	 */
	protected function protect_friendship_request() {
		if( in_array( MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_FRIENDSHIP, $this->rule_value ) ) {
			$this->add_filter( 'bp_get_add_friend_button', 'hide_add_friend_button' );
		}
	}
	
	/**
	 * Adds filter to prevent friendship button rendering.
	 *
	 * @since 4.0.0
	 * @filter bp_get_add_friend_button
	 *
	 * @access public
	 * @param array $button The button settings.
	 * @return array The current button settings.
	 */
	public function hide_add_friend_button( $button ) {
		$this->add_filter( 'bp_get_button', 'prevent_button_rendering' );
		return $button;
	}
	
	/**
	 * Prevents button rendering.
	 *
	 * @since 4.0.0
	 * @filter bp_get_button
	 *
	 * @access public
	 * @return boolean false to prevent button rendering.
	 */
	public function prevent_button_rendering() {
		remove_filter( 'bp_get_button', array( $this, 'prevent_button_rendering' ) );
		return false;
	}
	
	/**
	 * Checks the ability to create groups.
	 *
	 * @since 4.0.0
	 * @filter bp_user_can_create_groups
	 *
	 * @param string $can_create The initial access.
	 * @return string The initial template if current user can create groups, otherwise blocking message.
	 */
	public function protect_create_bp_group( $can_create ) {
		$can_create = false;
		
		if( in_array( MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_GROUP_CREATION, $this->rule_value ) ) {
			$can_create = true;
		}
		
		return apply_filters( 'ms_model_rule_buddypress_protect_create_bp_group', $can_create );
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
		$contents = array(
				(object) array(
						'id' => MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_FRIENDSHIP,
						'name' => __( 'Friendship request', MS_TEXT_DOMAIN ),
						'description' => __( 'Allows the sending friendship requests to be limited to members.', MS_TEXT_DOMAIN ),
						'access' => ( in_array( MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_FRIENDSHIP, $this->rule_value ) ),
						
				),
				(object) array(
						'id' => MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_GROUP_CREATION,
						'name' => __( 'Group creation', MS_TEXT_DOMAIN ),
						'description' => __( 'Allows group creation to be allowed to members only.', MS_TEXT_DOMAIN ),
						'access' => ( in_array( MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_GROUP_CREATION, $this->rule_value ) ),
				),
				(object) array(
						'id' => MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_PRIVATE_MSG,
						'name' => __( 'Private messaging', MS_TEXT_DOMAIN ),
						'description' => __( 'Allows the sending of private messages to be limited to members.', MS_TEXT_DOMAIN ),
						'access' => ( in_array( MS_Integration_BuddyPress::RULE_TYPE_BUDDYPRESS_PRIVATE_MSG, $this->rule_value ) ),
				),
				
		);
		return apply_filters( 'ms_model_rule_buddypress_get_content', $contents );
	}
}