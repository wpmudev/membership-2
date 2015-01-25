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
 * Membership Member Roles Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_MemberRoles_Model extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.1.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_MEMBERROLES;

	/**
	 * List of capabilities that are effectively used for the current user
	 *
	 * @since 1.1.0
	 *
	 * @var array
	 */
	static protected $real_caps = null;

	/**
	 * The assigned user role
	 *
	 * @var string
	 */
	protected $user_role = null;

	/**
	 * Caches the get_content_array output
	 *
	 * @var array
	 */
	protected $_content_array = null;

	/**
	 * Set-up the Rule
	 *
	 * @since  1.1.0
	 */
	static public function prepare_class() {
		// Register the tab-output handler for the admin side
		MS_Factory::load( 'MS_Rule_MemberRoles_View' )->register();
	}

	/**
	 * Initializes the object as early as possible
	 *
	 * @since  1.1.0
	 */
	public function prepare_obj() {
		$this->_content_array = null;
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.1.0
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		$this->add_action( 'user_has_cap', 'prepare_caps', 1, 3 );
		$this->add_action( 'user_has_cap', 'modify_caps', 10, 3 );
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.1.0
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. The membership relationship.
	 */
	public function protect_admin_content( $ms_relationship = false ) {
		parent::protect_admin_content( $ms_relationship );

		$this->add_action( 'user_has_cap', 'prepare_caps', 1, 3 );
		$this->add_action( 'user_has_cap', 'modify_caps', 10, 3 );
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
	public function has_access( $id = null ) {
		return apply_filters(
			'ms_rule_memberroles_model_has_access',
			null,
			null,
			$this
		);
	}

	/**
	 * Prepares the list of effective capabilities to use
	 *
	 * Relevant Action Hooks:
	 * - user_has_cap
	 *
	 * @since 1.1.0
	 *
	 * @param array   $allcaps An array of all the role's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 */
	public function prepare_caps( $allcaps, $caps, $args ) {
		global $wp_roles;

		// Only run this filter once!
		$this->remove_filter( 'user_has_cap', 'prepare_caps', 1, 3 );

		$all_roles = $wp_roles->roles;

		if ( isset( $all_roles[ $this->user_role ] )
			&& is_array( $all_roles[ $this->user_role ]['capabilities'] )
		) {
			$caps = $all_roles[ $this->user_role ]['capabilities'];
		}
		$caps = WDev()->get_array( $caps );

		if ( null === self::$real_caps ) {
			// First get a list of the users default capabilities.
			self::$real_caps = $allcaps;

			// Use the permissions of the first rule without checking.
			foreach ( $caps as $key => $value ) {
				self::$real_caps[$key] = $value;
			}
		} else {
			// Only add additional capabilities from now on...
			foreach ( $caps as $key => $value ) {
				if ( $value ) { self::$real_caps[$key] = 1; }
			}
		}
	}

	/**
	 * Modify the users capabilities.
	 *
	 * Relevant Action Hooks:
	 * - user_has_cap
	 *
	 * @since 1.1.0
	 *
	 * @param array   $allcaps An array of all the role's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 */
	public function modify_caps( $allcaps, $caps, $args ) {
		return apply_filters(
			'ms_rule_memberroles_model_modify_caps',
			self::$real_caps,
			$caps,
			$args,
			$this
		);
	}

	/**
	 * Get a simple array of capabilties (e.g. for display in select lists)
	 *
	 * @since 1.1.0
	 * @global array $menu
	 *
	 * @return array {
	 *      @type string $id The id.
	 *      @type string $name The name.
	 * }
	 */
	public function get_roles( $args = null ) {
		global $wp_roles;

		if ( null === $this->_content_array ) {
			// User-Roles are only available in Accessible Content tab, so always display all roles.
			$this->_content_array = array();

			$exclude = apply_filters(
				'ms_rule_memberroles_model_exclude_roles',
				array( 'administrator' )
			);

			$all_roles = $wp_roles->roles;

			// Make sure the rule_value only contains valid items.
			$rule_value = array_intersect_key(
				$this->rule_value,
				$all_roles
			);
			$this->rule_value = WDev()->get_array( $rule_value );

			foreach ( $all_roles as $key => $role ) {
				if ( in_array( $key, $exclude ) ) { continue; }
				$this->_content_array[$key] = $role['name'];
			}

			$this->_content_array = apply_filters(
				'ms_rule_memberroles_model_get_content_array',
				$this->_content_array,
				$this
			);
		}

		$contents = $this->_content_array;

		// Search the shortcode-tag...
		if ( ! empty( $args['s'] ) ) {
			foreach ( $contents as $key => $name ) {
				if ( stripos( $name, $args['s'] ) === false ) {
					unset( $contents[$key] );
				}
			}
		}

		$filter = self::get_exclude_include( $args );
		if ( is_array( $filter->include ) ) {
			$contents = array_intersect_key( $contents, array_flip( $filter->include ) );
		} elseif ( is_array( $filter->exclude ) ) {
			$contents = array_diff_key( $contents, array_flip( $filter->exclude ) );
		}

		// Pagination
		if ( ! empty( $args['posts_per_page'] ) ) {
			$total = $args['posts_per_page'];
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;
			$contents = array_slice( $contents, $offset, $total );
		}

		return $contents;
	}

	/**
	 * Get content to protect. An array of objects is returned.
	 *
	 * @since 1.1.0
	 * @param $args The query post args
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		$contents = array();
		$roles = $this->get_roles( $args );

		foreach ( $roles as $key => $rolename ) {
			$content = (object) array();

			$content->id = $key;
			$content->title = $rolename;
			$content->name = $rolename;
			$content->post_title = $rolename;
			$content->type = $this->rule_type;
			$content->access = $this->get_rule_value( $key );

			$contents[ $key ] = $content;
		}

		return apply_filters(
			'ms_rule_memberroles_model_get_contents',
			$contents,
			$args,
			$this
		);
	}

	/**
	 * Get the total content count.
	 * Used in Dashboard to display how many special pages are protected.
	 *
	 * @since 1.0.4
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The total content count.
	 */
	public function get_content_count( $args = null ) {
		$args['posts_per_page'] = 0;
		$args['offset'] = false;
		$count = count( $this->get_contents( $args ) );

		return apply_filters(
			'ms_model_rule_memberroles_get_content_count',
			$count,
			$args
		);
	}

}