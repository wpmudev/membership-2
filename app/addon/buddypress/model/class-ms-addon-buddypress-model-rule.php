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

/**
 * Membership BuddyPress Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Addon_Buddypress_Model_Rule extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = MS_Addon_BuddyPress::RULE_ID;

	/**
	 * Verify access to the current content.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The content post ID to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id = null ) {
		global $bp;
		$has_access = null;

		if ( function_exists( 'bp_current_component' ) ) {
			$component = bp_current_component();

			if ( ! empty( $component ) ) {
				switch ( $component ) {
					// Private messaging direct access.
					case 'messages':
						$has_access = false;
						if ( 'compose' === $bp->current_action
							&& parent::has_access( MS_Addon_BuddyPress::RULE_ID_PRIVATE_MSG )
						) {
							$has_access = true;
						}
						break;

					// Don't modify, handled by MS_Model_Rule_Buddypress_Group
					case 'groups':
						break;

					// Other BP pages can be handled by other rules.
					default:
						$has_access = null;
						break;
				}
			}
		}

		return apply_filters(
			'ms_model_rule_buddypress_has_access',
			$has_access,
			$id,
			$this
		);
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership_Relationship $ms_relationship Optional. Not used.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->add_filter(
			'bp_user_can_create_groups',
			'protect_create_bp_group'
		);
		$this->protect_friendship_request();
		$this->protect_private_messaging();
	}

	/**
	 * Protect private messaging.
	 *
	 * @since 1.0.0
	 */
	protected function protect_private_messaging() {
		if ( parent::has_access( MS_Addon_BuddyPress::RULE_ID_PRIVATE_MSG ) ) {
			$this->add_filter(
				'bp_get_send_message_button',
				'hide_private_message_button'
			);
		}

		do_action(
			'ms_model_rule_buddypress_protect_private_messaging',
			$this
		);
	}

	/**
	 * Adds filter to prevent friendship button rendering.
	 *
	 * Related Action Hooks:
	 * - bp_get_send_message_button
	 *
	 * @since 1.0.0
	 *
	 * @param array $button The button settings.
	 * @return bool false to hide button.
	 */
	public function hide_private_message_button( $button ) {
		return apply_filters(
			'ms_model_rule_buddypress_hide_private_message_button',
			false,
			$button,
			$this
		);
	}

	/**
	 * Protect friendship request.
	 *
	 * @since 1.0
	 *
	 */
	protected function protect_friendship_request() {
		if ( parent::has_access( MS_Addon_BuddyPress::RULE_ID_FRIENDSHIP ) ) {
			$this->add_filter(
				'bp_get_add_friend_button',
				'hide_add_friend_button'
			);
		}

		do_action(
			'ms_model_rule_buddypress_protect_friendship_request',
			$this
		);
	}

	/**
	 * Adds filter to prevent friendship button rendering.
	 *
	 * Related Action Hooks:
	 * - bp_get_add_friend_button
	 *
	 * @since 1.0.0
	 *
	 * @param array $button The button settings.
	 * @return array The current button settings.
	 */
	public function hide_add_friend_button( $button ) {
		$this->add_filter( 'bp_get_button', 'prevent_button_rendering' );

		return apply_filters(
			'ms_model_rule_buddypress_hide_add_friend_button',
			$button,
			$this
		);
	}

	/**
	 * Prevents button rendering.
	 *
	 * Related Action Hooks:
	 * - bp_get_button
	 *
	 * @since 1.0.0
	 *
	 * @return boolean false to prevent button rendering.
	 */
	public function prevent_button_rendering() {
		$this->remove_filter( 'bp_get_button', 'prevent_button_rendering' );

		return apply_filters(
			'ms_model_rule_buddypress_prevent_button_rendering',
			false,
			$this
		);
	}

	/**
	 * Checks the ability to create groups.
	 *
	 * Related Action Hooks:
	 * - bp_user_can_create_groups
	 *
	 * @since 1.0.0
	 *
	 * @param string $can_create The initial access.
	 * @return string The initial template if current user can create groups, otherwise blocking message.
	 */
	public function protect_create_bp_group( $can_create ) {
		$can_create = false;

		if ( parent::has_access( MS_Addon_BuddyPress::RULE_ID_GROUP_CREATION ) ) {
			$can_create = true;
		}

		return apply_filters(
			'ms_model_rule_buddypress_protect_create_bp_group',
			$can_create,
			$this
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Not used, but kept due to method override.
	 * @return array The content eligible to protect by this rule domain.
	 */
	public function get_contents( $args = null ) {
		$contents = array();
		$contents[MS_Addon_BuddyPress::RULE_ID_FRIENDSHIP] = (object) array(
			'id' => MS_Addon_BuddyPress::RULE_ID_FRIENDSHIP,
			'name' => __( 'Friendship request', MS_TEXT_DOMAIN ),
			'type' => $this->rule_type,
			'description' => __( 'Allows the sending of friendship requests to be limited to members.', MS_TEXT_DOMAIN ),
			'access' => $this->get_rule_value( MS_Addon_BuddyPress::RULE_ID_FRIENDSHIP ),
		);

		$contents[MS_Addon_BuddyPress::RULE_ID_GROUP_CREATION] = (object) array(
			'id' => MS_Addon_BuddyPress::RULE_ID_GROUP_CREATION,
			'name' => __( 'Group creation', MS_TEXT_DOMAIN ),
			'type' => $this->rule_type,
			'description' => __( 'Allows group creation to be limited to members.', MS_TEXT_DOMAIN ),
			'access' => $this->get_rule_value( MS_Addon_BuddyPress::RULE_ID_GROUP_CREATION ),
		);

		$contents[MS_Addon_BuddyPress::RULE_ID_PRIVATE_MSG] = (object) array(
			'id' => MS_Addon_BuddyPress::RULE_ID_PRIVATE_MSG,
			'name' => __( 'Private messaging', MS_TEXT_DOMAIN ),
			'type' => $this->rule_type,
			'description' => __( 'Allows the sending of private messages to be limited to members.', MS_TEXT_DOMAIN ),
			'access' => $this->get_rule_value( MS_Addon_BuddyPress::RULE_ID_PRIVATE_MSG ),
		);

		// If not visitor membership, just show protected content
		if ( ! $this->is_base_rule ) {
			$contents = array_intersect_key( $contents,  $this->rule_value );
		}

		return apply_filters(
			'ms_model_rule_buddypress_get_content',
			$contents,
			$this
		);
	}
}