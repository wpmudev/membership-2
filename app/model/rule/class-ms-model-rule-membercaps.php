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
 * Membership Member Capabilities Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.1
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Membercaps extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.1
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_MEMBERCAPS;

	/**
	 * Verify access to the current content.
	 *
	 * Always returns null since this rule modifies the capabilities of the
	 * current user and does not directly block access to any page.
	 *
	 * @since 1.1
	 *
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access() {
		wp_die( 'protect Admin Side' );

		return apply_filters(
			'ms_model_rule_membercaps_has_access',
			null,
			$id,
			$this
		);
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.1
	 *
	 * @param MS_Model_Membership_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		/*
		 * Find out which menu items are allowed.
		 */
		$this->add_action( 'user_has_cap', 'modify_caps' );
	}

	/**
	 * Modify the users capabilities.
	 *
	 * Relevant Action Hooks:
	 * - user_has_cap
	 *
	 * @since 1.1
	 *
	 * @param array   $allcaps An array of all the role's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 * @param WP_User $user    The user object.
	 */
	public function modify_caps( $allcaps, $caps, $args, $user ) {
		$new_caps = $allcaps;

		return apply_filters(
			'ms_model_rule_membercaps_modify_caps',
			$new_caps,
			$caps,
			$args,
			$user,
			$this
		);
	}

	/**
	 * Get a simple array of capabilties (e.g. for display in select lists)
	 *
	 * @since 1.1
	 * @global array $menu
	 *
	 * @return array {
	 *      @type string $id The id.
	 *      @type string $name The name.
	 * }
	 */
	public function get_content_array() {
		$contents = array();
		$member = MS_Model_Member::get_current_member();
		$capslist = $member->wp_user->allcaps;

		$ignored_caps = array(
			'level_10' => 1,
			'level_9' => 1,
			'level_8' => 1,
			'level_7' => 1,
			'level_6' => 1,
			'level_5' => 1,
			'level_4' => 1,
			'level_3' => 1,
			'level_2' => 1,
			'level_1' => 1,
			'level_0' => 1,
			'administrator' => 1,
		);

		$capslist = array_diff_assoc( $capslist, $ignored_caps );
		$capslist = array_keys( $capslist );
		$contents = array_combine( $capslist, $capslist );

		return apply_filters(
			'ms_model_rule_membercaps_get_content_array',
			$capslist,
			$this
		);
	}

	/**
	 * Get content to protect. An array of objects is returned.
	 *
	 * @since 1.1
	 * @param $args The query post args
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$contents = array();

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		return apply_filters(
			'ms_model_rule_membercaps_get_contents',
			$contents,
			$args,
			$this
		);
	}

}