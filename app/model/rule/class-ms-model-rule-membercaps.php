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
	 * List of capabilities that are effectively used for the current user
	 *
	 * @since 1.1
	 *
	 * @var array
	 */
	static protected $real_caps = null;

	/**
	 * Initializes the object as early as possible
	 *
	 * @since  1.1
	 */
	public function prepare_obj() {
		if ( $this->_prepared ) { return; }
		$this->_prepared = true;

		$value = array_intersect_key( $this->rule_value, $this->get_content_array() );
		$this->rule_value = WDev()->get_array( $value );

		/*
		 * Find out which menu items are allowed.
		 */
		$this->add_filter( 'user_has_cap', 'prepare_caps', 1, 3 );
		$this->add_filter( 'user_has_cap', 'modify_caps', 2, 3 );
	}

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
		return apply_filters(
			'ms_model_rule_membercaps_has_access',
			null,
			$id,
			$this
		);
	}

	/**
	 * Prepares the list of effective capabilities to use
	 *
	 * Relevant Action Hooks:
	 * - user_has_cap
	 *
	 * @since 1.1
	 *
	 * @param array   $allcaps An array of all the role's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 */
	public function prepare_caps( $allcaps, $caps, $args ) {
		// Only run this filter once!
		$this->remove_filter( 'user_has_cap', 'prepare_caps', 1, 3 );

		if ( null === self::$real_caps ) {
			// First get a list of all capabilities.
			self::$real_caps = $allcaps;

			// Use the permissions of the first rule without checking.
			foreach ( $this->rule_value as $key => $value ) {
				self::$real_caps[$key] = $value;
			}
		} else {
			// Only grant additional capabilities...
			foreach ( $this->rule_value as $key => $value ) {
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
	 * @since 1.1
	 *
	 * @param array   $allcaps An array of all the role's capabilities.
	 * @param array   $caps    Actual capabilities for meta capability.
	 * @param array   $args    Optional parameters passed to has_cap(), typically object ID.
	 */
	public function modify_caps( $allcaps, $caps, $args ) {
		return apply_filters(
			'ms_model_rule_membercaps_modify_caps',
			self::$real_caps,
			$caps,
			$args,
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

		// If not visitor membership, just show protected content
		if ( ! $this->rule_value_invert ) {
			$contents = array_intersect_key( $contents, $this->rule_value );
		}

		return apply_filters(
			'ms_model_rule_membercaps_get_content_array',
			$contents,
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
		$caps = $this->get_content_array( $args );

		foreach ( $caps as $key => $item ) {
			$content = (object) array();

			$content->id = $item;
			$content->title = $item;
			$content->name = $item;
			$content->post_title = $item;
			$content->type = $this->rule_type;
			$content->access = $this->get_rule_value( $key );

			$contents[ $key ] = $content;
		}

		// If not visitor membership, just show protected content
		if ( ! $this->rule_value_invert ) {
			$keys = $this->rule_value;
			if ( isset( $args['rule_status'] ) ) {
				switch ( $args['rule_status'] ) {
					case 'no_access': $keys = array_fill_keys( array_keys( $keys, false ), 0 ); break;
					case 'has_access': $keys = array_fill_keys( array_keys( $keys, true ), 1 ); break;
				}
			}

			$contents = array_intersect_key( $contents, $keys );
		}

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

	/**
	 * Get total content count.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The content count.
	 */
	public function get_content_count( $args = null ) {
		$count = count( $this->rule_value );

		return apply_filters(
			'ms_model_rule_membercaps_get_contents',
			$count,
			$args,
			$this
		);
	}

}