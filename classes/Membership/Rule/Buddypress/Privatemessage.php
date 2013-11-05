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
 * Rule class responsible for BuddyPress private messages protection.
 *
 * @category Membership
 * @package Rule
 * @subpackage Buddypress
 */
class Membership_Rule_Buddypress_Privatemessage extends Membership_Rule {

	var $name = 'bpprivatemessage';
	var $label = 'Private Messaging';
	var $description = 'Allows the sending of private messages to be limited to members.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpprivatemessage'>
			<h2 class='sidebar-name'><?php _e('Private Messaging', 'membership');?><span><a href='#remove' id='remove-bpprivatemessage' class='removelink' title='<?php _e("Remove Private Messaging from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User can send messages.','membership'); ?></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User is unable to send messages.','membership'); ?></p>
				<input type='hidden' name='bpprivatemessage[]' value='yes' />
			</div>
		</div>
		<?php
	}

	function on_positive( $data ) {
		$this->data = $data;
		add_filter( 'messages_template_compose', array( $this, 'pos_bp_messages_template' ) );
	}

	function on_negative( $data ) {
		$this->data = $data;
		add_filter( 'messages_template_compose', array( $this, 'neg_bp_messages_template' ) );
	}

	function pos_bp_messages_template( $template ) {
		return $template;
	}

	function neg_bp_messages_template( $template ) {
		add_action( 'bp_template_content', array( $this, 'neg_bp_message' ) );
		return 'members/single/plugins';
	}

	function neg_bp_message() {
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