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
 * Membership Admin Side Protection Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.1
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Rule_Adminside extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.1
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_ADMINSIDE;

	/**
	 * An array of all available menu items.
	 *
	 * @since 1.1
	 *
	 * @var array
	 */
	protected $available_menus = array();

	/**
	 * Verify access to the current content.
	 *
	 * @since 1.1
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access() {
		if ( is_admin() ) {
			$allow = null;
		} else {
			$allwo = false;
		}

		wp_die( 'protect Admin Side' );

		return apply_filters(
			'ms_model_rule_adminside_has_access',
			$allow,
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
		$this->add_action( 'network_admin_menu', 'prepare_protection', 1 );
		$this->add_action( 'user_admin_menu', 'prepare_protection', 1 );
		$this->add_action( 'admin_menu', 'prepare_protection', 1 );

		/*
		 * Remove menu items that are not allowed.
		 */
		$this->add_action( 'network_admin_menu', 'protect_menus', 10 );
		$this->add_action( 'user_admin_menu', 'protect_menus', 10 );
		$this->add_action( 'admin_menu', 'protect_menus', 10 );
	}

	/**
	 * Checks if the specified menu-ID is allowed by this rule.
	 *
	 * @since  1.1
	 *
	 * @param  object $item The menu item object.
	 * @return bool
	 */
	protected function can_access_menu( $item ) {
		$result = false;

		if ( parent::has_access( $item->ID ) ) {
			$result = true;
		} else if ( ! empty( $item->post_parent ) ) {
			$parent = get_post( $item->post_parent );
			$result = $this->can_access_menu( $parent );
		}

		return $result;
	}

	/**
	 * Set the protection flag for each menu item.
	 *
	 * This function is called before function protect_menuitems() below.
	 * Here we evaluate each menu item to see if the user has access to the
	 * menu item and collect all accessible menu items in a static/shared array
	 * so we have correct information when evaluating multiple memberships.
	 *
	 * Relevant Action Hooks:
	 * - network_admin_menu
	 * - user_admin_menu
	 * - admin_menu
	 *
	 * @since 1.1
	 * @global array $menu
	 */
	public function prepare_protection() {
		global $menu;

		WDev()->debug( $menu );

		foreach ( $menu as $key => $item ) {
			if ( empty( $item ) ) { continue; }

			$has_access = parent::has_access( $key );
			if ( $this->can_access_menu( $item ) ) {
				self::$allowed_items[$key] = $key;
			}
		}
	}

	/**
	 * Remove menu items that are protected.
	 *
	 * Menu-Item protection is split into two steps to ensure correct
	 * menu-visibility when users are members of multiple memberships.
	 * http://premium.wpmudev.org/forums/topic/multiple-membership-types-defaults-to-less-access-protected-content
	 *
	 * Relevant Action Hooks:
	 * - network_admin_menu
	 * - user_admin_menu
	 * - admin_menu
	 *
	 * @since 1.1
	 * @global array $menu
	 */
	public function protect_menus() {
		global $menu;
		static $Done = false;

		// Only remove menu items once.
		if ( $Done ) { return; }
		$Done = true;

		$allowed = apply_filters(
			'ms_model_rule_adminside_allowed_items',
			self::$allowed_items,
			$this
		);

		foreach ( $menu as $key => $item ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				// Remove protected items from the global array.
				unset( $menu[ $key ] );
			}
		}
	}

	/**
	 * Get a simple array of menu items (e.g. for display in select lists)
	 *
	 * @since 1.1
	 *
	 * @return array {
	 *      @type string $menu_id The id.
	 *      @type string $name The name.
	 * }
	 */
	public function get_content_array() {
		$contents = array();
		$full_menu = MS_Plugin::instance()->controller->get_admin_menu();

		$main = __( 'Main Menu', MS_TEXT_DOMAIN );
		$contents[$main] = array();
		foreach ( $full_menu['main'] as $pos => $item ) {
			// Skip separators
			if ( empty( $item[0] ) ) { continue; }

			$contents[$main][$item[2]] = trim( array_shift( explode( '<', $item[0] ) ) );
		}

		foreach ( $full_menu['sub'] as $url => $items ) {
			$parent = $contents[$main][$url];
			if ( empty( $parent ) ) { continue; }

			$contents[$parent] = array();

			foreach ( $items as $pos => $item ) {
				$contents[$parent][$item[2]] = array_shift( explode( '<', $item[0] ) );
			}
		}

		return apply_filters(
			'ms_model_rule_adminside_get_content_array',
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

		if ( ! empty( $args['rule_status'] ) ) {
			$contents = $this->filter_content( $args['rule_status'], $contents );
		}

		return apply_filters(
			'ms_model_rule_adminside_get_contents',
			$contents,
			$args,
			$this
		);
	}

}