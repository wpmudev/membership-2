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
 * Rule class responsible for BuddyPress group creation protection.
 *
 * @category Membership
 * @package Model
 * @subpackage Rule
 * @subpackage Buddypress
 */
class Membership_Model_Rule_Buddypress_Groupcreation extends Membership_Model_Rule {

	/**
	 * Handles rule's stuff initialization.
	 *
	 * @access public
	 */
	public function on_creation() {
		parent::on_creation();

		$this->name = 'bpgroupcreation';
		$this->label = _x( 'Group Creation', 'The rule title which restricts the ability to create BuddyPress rules.', 'membership' );
		$this->description = _x( 'Allows group creation to be allowed to members only.', 'The rule description which restricts the ability to create BuddyPress groups.', 'membership' );
		$this->rulearea = 'public';
	}

	/**
	 * Renders rule settings at access level edit form.
	 *
	 * @access public
	 * @param mixed $data The data associated with this rule.
	 */
	public function admin_main( $data ) {
		if ( !$data ) {
			$data = array();
		}

		?><div id="main-bpgroupcreation" class="level-operation">
			<h2 class='sidebar-name'>
				<?php _e( 'Group Creation', 'membership' ) ?>
				<span>
					<a href="#remove" id="remove-bpgroupcreation" class="removelink" title="<?php _e( "Remove Group Creation from this rules area.", 'membership' ) ?>">
						<?php _e( 'Remove', 'membership' ) ?>
					</a>
				</span>
			</h2>
			<div class="inner-operation">
				<p>
					<strong><?php _e( 'Positive:', 'membership' ); ?></strong>
					<?php printf( _x( 'User can create %s groups.', 'User can create {number input field} groups.', 'membership' ), '<input type="text" name="bpgroupcreation[number]" value="' . esc_attr( $data['number'] ) . '">' ) ?>
					<br/>
					<em><?php _e( 'Leave blanks for unlimited groups.', 'membership' ); ?></em>
				</p>
				<p><strong><?php _e( 'Negative:', 'membership' ); ?></strong> <?php _e( 'User is unable to create any groups.', 'membership' ); ?></p>
				<input type='hidden' name='bpgroupcreation[]' value='yes' />
			</div>
		</div><?php
	}

	/**
	 * Associates positive data with this rule.
	 *
	 * @access public
	 * @param mixed $data The positive data to associate with the rule.
	 */
	public function on_positive( $data ) {
		$this->data = $data;
		add_filter( 'groups_template_create_group', array( $this, 'check_ability_to_create_groups' ) );
	}

	/**
	 * Checks the ability to create groups.
	 *
	 * @since 3.5
	 * @filter groups_template_create_group
	 *
	 * @access public
	 * @param string $template The initial template.
	 * @return string The initial template if current user can create groups, otherwise blocking message.
	 */
	public function check_ability_to_create_groups( $template ) {
		if ( !empty( $this->data['number'] ) ) {
			if ( !is_numeric( $this->data['number'] ) || absint( $this->data['number'] ) <= $this->_get_users_group_count() ) {
				return $this->protect_group_creation( $template );
			}
		}

		return $template;
	}

	/**
	 * Returns the number of groups create by an user.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @global wpdb $wpdb The connection to database.
	 * @global BuddyPress $bp The BuddyPress instance.
	 * @return int The amount of groups created by an user.
	 */
	private function _get_users_group_count() {
		global $wpdb, $bp;

		if ( is_user_logged_in() ) {
			// We have a member and it is a correct object
			return (int)$wpdb->get_var( "SELECT COUNT(*) FROM {$bp->activity->table_name} WHERE component = 'groups' AND type = 'created_group' AND user_id = " . get_current_user_id() );
		}

		return 0;
	}

	/**
	 * Associates negative data with this rule.
	 *
	 * @access public
	 * @param mixed $data The negative data to associate with the rule.
	 */
	public function on_negative( $data ) {
		$this->data = $data;
		add_filter( 'groups_template_create_group', array( $this, 'neg_bp_groups_template' ) );
	}

	/**
	 * Protects BuddyPress group creation.
	 *
	 * @since 3.5
	 * @filter groups_template_create_group
	 *
	 * @access public
	 * @global BuddyPress $bp The BuddyPress instance.
	 * @param string $template The initial template.
	 * @return string The initial template.
	 */
	public function protect_group_creation( $template ) {
		global $bp;

		//hack template steps to hide creation form elements
		$bp->action_variables[1] = 'disabled'; //nonsensical value, hide all group steps
		if ( $bp->avatar_admin ) {
			$bp->avatar_admin->step = 'crop-image'; //hides submit button
		}

		add_action( 'template_notices', array( $this, 'render_protection_message' ) );

		return $template;
	}

	/**
	 * Renders protections message for BuddyPress group creation process.
	 *
	 * @since 3.5
	 * @action template_notices
	 *
	 * @access public
	 */
	public function render_protection_message() {
		$MBP_options = defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && MEMBERSHIP_GLOBAL_TABLES === true && function_exists( 'get_blog_option' )
			? get_blog_option( MEMBERSHIP_GLOBAL_MAINSITE, 'membership_bp_options', array() )
			: get_option( 'membership_bp_options', array() );

		$message = trim( stripslashes( $MBP_options['buddypressmessage'] ) );
		if ( empty( $message ) ) {
			$message = __( 'You can not create new group.', 'membership' );
		}

		echo '<div id="message" class="error"><p>' . $message . '</p></div>';
	}

}