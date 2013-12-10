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

	var $name = 'bpgroupcreation';
	var $label = 'Group Creation';
	var $description = 'Allows group creation to be allowed to members only.';

	var $rulearea = 'public';

	function admin_main($data) {
		if(!$data) $data = array();
		?>
		<div class='level-operation' id='main-bpgroupcreation'>
			<h2 class='sidebar-name'><?php _e('Group Creation', 'membership');?><span><a href='#remove' id='remove-bpgroupcreation' class='removelink' title='<?php _e("Remove Group Creation from this rules area.",'membership'); ?>'><?php _e('Remove','membership'); ?></a></span></h2>
			<div class='inner-operation'>
				<p><strong><?php _e('Positive : ','membership'); ?></strong><?php _e('User can create ','membership'); ?><input type='text' name='bpgroupcreation[number]' value='<?php echo esc_attr($data['number']); ?>' /><?php _e(' groups.','membership'); ?><br/><em><?php _e('Leave blanks for unlimited groups.','membership'); ?></em></p>
				<p><strong><?php _e('Negative : ','membership'); ?></strong><?php _e('User is unable to create any groups.','membership'); ?></p>
				<input type='hidden' name='bpgroupcreation[]' value='yes' />
			</div>
		</div>
		<?php
	}

	function on_positive( $data ) {
		$this->data = $data;
		add_filter( 'groups_template_create_group', array( $this, 'pos_bp_groups_template' ) );
	}

	function on_negative( $data ) {
		$this->data = $data;
		add_filter( 'groups_template_create_group', array( $this, 'neg_bp_groups_template' ) );
	}

	function pos_bp_groups_template( $template ) {
		if ( !empty( $this->data['number'] ) ) {
			if ( !is_numeric( $this->data['number'] ) || (int)$this->data['number'] > $this->users_group_count() ) {
				return $this->neg_bp_groups_template( $template );
			}
		}

		return $template;
	}

	function users_group_count() {
		global $member, $wpdb, $bp;

		if ( !empty( $member ) && method_exists( $member, 'has_cap' ) ) {
			// We have a member and it is a correct object
			return (int)$wpdb->get_var( $wpdb->prepare(
				"SELECT count(*) FROM {$bp->activity->table_name} WHERE component = 'groups' AND type = 'created_group' AND user_id = %d",
				$member->ID
			) );
		}

		return 0;
	}

	function neg_bp_groups_template( $template ) {
		global $bp;

		//hack template steps to hide creation form elements
		$bp->action_variables[1] = 'disabled'; //nonsensical value, hide all group steps
		$bp->avatar_admin->step = 'crop-image'; //hides submit button
		add_action( 'template_notices', array( $this, 'neg_bp_message' ) );

		return $template;
	}

	function neg_bp_message() {
		if ( defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && MEMBERSHIP_GLOBAL_TABLES === true ) {
			$MBP_options = function_exists( 'get_blog_option' )
				? get_blog_option( MEMBERSHIP_GLOBAL_MAINSITE, 'membership_bp_options', array() )
				: get_option( 'membership_bp_options', array() );
		} else {
			$MBP_options = get_option( 'membership_bp_options', array() );
		}

		echo '<div id="message" class="error"><p>' . stripslashes( $MBP_options['buddypressmessage'] ) . '</p></div>';
	}

}