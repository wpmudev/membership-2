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
 * Membership User-Role Memberships Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Rolemembers extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.1.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_ROLEMEMBERS;

	/**
	 * Caches the get_content_array output
	 *
	 * @var array
	 */
	protected $_content_array = null;

	/**
	 * Initializes the object as early as possible
	 *
	 * @since  1.1
	 */
	public function prepare_obj() {
		if ( $this->_prepared ) { return; }
		$this->_prepared = true;
		$this->_content_array = null;

		MS_Model_Membership::get_role_membership( '(Guest)' );
		MS_Model_Membership::get_role_membership( '(Logged in)' );
		$roles = $this->get_roles();
		foreach ( $roles as $id => $role ) {
			MS_Model_Membership::get_role_membership( $role );
		}
	}

	/**
	 * Verify access to the current content.
	 *
	 * Always returns null since this rule modifies the capabilities of the
	 * current user and does not directly block access to any page.
	 *
	 * @since 1.1.0
	 *
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access() {
		return apply_filters(
			'ms_model_rule_membercaps_has_access',
			null,
			null,
			$this
		);
	}

	/**
	 * Get a simple array of user roles (e.g. for display in select lists)
	 *
	 * @since 1.1.0
	 * @global array $menu
	 *
	 * @return array {
	 *      @type string $id The id.
	 *      @type string $name The name.
	 * }
	 */
	public function get_roles() {
		global $wp_roles;

		$contents = array();
		$all_roles = $wp_roles->roles;

		$exclude = apply_filters(
			'ms_model_rule_memberroles_exclude_roles',
			array()
		);

		foreach ( $all_roles as $key => $role ) {
			if ( in_array( $key, $exclude ) ) { continue; }
			$contents[$key] = $role['name'];
		}

		return $contents;
	}

}