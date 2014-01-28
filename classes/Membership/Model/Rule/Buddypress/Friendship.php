<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Rule class responsible for BuddyPress friendship protection.
 *
 * @since 3.5
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 * @subpackage Buddypress
 */
class Membership_Model_Rule_Buddypress_Friendship extends Membership_Model_Rule {

	/**
	 * Handles rule's stuff initialization.
	 *
	 * @access public
	 */
	public function on_creation() {
		parent::on_creation();

		$this->name = 'bpfriendship';
		$this->label = __( 'Friend Connections', 'membership' );
		$this->description = __( 'Allows the sending friendship requests to be limited to members.', 'membership' );
		$this->rulearea = 'public';
	}

	/**
	 * Renders rule settings at access level edit form.
	 *
	 * @access public
	 * @param mixed $data The data associated with this rule.
	 */
	public function admin_main( $data ) {
		?><div class="level-operation" id="main-bpfriendship">
			<h2 class="sidebar-name">
				<?php _e( 'Friend Connections', 'membership' ); ?>
				<span><a href="#remove" id="remove-bpfriendship" class="removelink" title="<?php _e( "Remove Private Messaging from this rules area.", 'membership' ); ?>">
					<?php _e( 'Remove', 'membership' ); ?>
				</a></span>
			</h2>
			<div class="inner-operation">
				<p><strong><?php _e( 'Positive:', 'membership' ); ?></strong> <?php _e( 'User can send friendship requests.', 'membership' ); ?></p>
				<p><strong><?php _e( 'Negative:', 'membership' ); ?></strong> <?php _e( 'User is unable to send friendship requests.', 'membership' ); ?></p>
				<input type="hidden" name="bpfriendship[]" value="yes">
			</div>
		</div><?php
	}

	/**
	 * Associates negative data with this rule.
	 *
	 * @access public
	 * @param mixed $data The negative data to associate with the rule.
	 */
	public function on_negative( $data ) {
		$this->data = $data;
		add_filter( 'bp_get_add_friend_button', array( $this, 'hide_add_friend_button' ) );
		add_filter( 'bp_get_template_part', array( $this, 'get_friends_template' ), 10, 2 );
	}

	/**
	 * Adds filter to prevent friendship button rendering.
	 *
	 * @since 3.5
	 * @filter bp_get_add_friend_button
	 *
	 * @access public
	 * @param array $button The button settings.
	 * @return array The current button settings.
	 */
	public function hide_add_friend_button( $button ) {
		add_filter( 'bp_get_button', array( $this, 'prevent_button_rendering' ) );
		return $button;
	}

	/**
	 * Prevents button rendering.
	 *
	 * @since 3.5
	 * @filter bp_get_button
	 *
	 * @access public
	 * @return boolean FALSE to prevent button rendering.
	 */
	public function prevent_button_rendering() {
		remove_filter( 'bp_get_button', array( $this, 'prevent_button_rendering' ) );
		return false;
	}

	/**
	 * Overrides friends template.
	 *
	 * @filter bp_get_template_part 10 2
	 *
	 * @access public
	 * @param array $templates Income templates.
	 * @param string $slug The template slug.
	 * @return array The new template for friends pages or original for else pages.
	 */
	public function get_friends_template( $templates, $slug ) {
		if ( $slug != 'members/single/friends' ) {
			return $templates;
		}

		add_action( 'bp_template_content', array( $this, 'render_protection_message' ) );
		return array( 'members/single/plugins.php' );
	}

	/**
	 * Renders protection message.
	 *
	 * @action bp_template_content
	 *
	 * @access public
	 */
	public function render_protection_message() {
		if ( defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && MEMBERSHIP_GLOBAL_TABLES === true ) {
			$MBP_options = function_exists( 'get_blog_option' )
				? get_blog_option( MEMBERSHIP_GLOBAL_MAINSITE, 'membership_bp_options', array() )
				: get_option( 'membership_bp_options', array() );
		} else {
			$MBP_options = get_option( 'membership_bp_options', array( ) );
		}

		echo '<div id="message" class="error"><p>' . stripslashes( $MBP_options['buddypressmessage'] ) . '</p></div>';
	}

}