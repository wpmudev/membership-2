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
	 * An array of all menu items that are not allowed for the current user.
	 *
	 * @since 1.1
	 *
	 * @var array
	 */
	static protected $denied_items = null;

	/**
	 * Verify access to the current content.
	 *
	 * @since 1.1
	 *
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access() {
		if ( is_admin() ) {
			$allow = null;
		} else {
			$allow = false;
		}

		return apply_filters(
			'ms_model_rule_adminside_has_access',
			$allow,
			null,
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
	public function protect_admin_content( $ms_relationship = false ) {
		parent::protect_admin_content( $ms_relationship );

		/*
		 * Find out which menu items are allowed.
		 */
		$this->add_filter( 'custom_menu_order', 'prepare_protection', 1 );

		/*
		 * Remove menu items that are not allowed.
		 */
		$this->add_filter( 'custom_menu_order', 'protect_menus', 10 );
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
	public function prepare_protection( $ignore ) {
		if ( null === self::$denied_items ) {
			self::$denied_items = array_fill_keys( array_keys( $this->rule_value ), 0 );
		}

		foreach ( $this->rule_value as $url => $allowed ) {
			if ( ! $allowed ) { continue; }
			if ( ! isset( self::$denied_items[$url] ) ) { continue; }

			unset( self::$denied_items[$url] );
		}

		return $ignore;
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
	public function protect_menus( $ignore ) {
		global $menu, $submenu, $_wp_submenu_nopriv, $_wp_menu_nopriv;
		static $Done = false;

		// Only remove menu items once.
		if ( $Done ) { return; }
		$Done = true;

		$denied = apply_filters(
			'ms_model_rule_adminside_denied_items',
			self::$denied_items,
			$this
		);

		// Protect the main menu.
		foreach ( $menu as $main_key => $main_item ) {
			$main_url = $main_item[2];

			if ( isset( $denied[ $main_url ] ) ) {
				// Remove protected items from the global array.
				unset( $menu[ $main_key ] );
				unset( $submenu[ $main_key ] );
				$_wp_menu_nopriv[$main_url] = true;
				continue;
			}

			if ( ! isset( $submenu[$main_url] ) ) { continue; }

			// Protect sub menus.
			foreach ( $submenu[$main_url] as $child_key => $child_item ) {
				$child_url = $main_url . ':' . $child_item[2];
				if ( isset( $denied[ $child_url ] ) ) {
					// Remove protected items from the global array.
					unset( $submenu[$main_url][ $child_key ] );
					$_wp_submenu_nopriv[$main_url][$child_item[2]] = true;
				}
			}
		}

		// Remove submenu items that have same URL as the top-menu item
		foreach ( $submenu as $main_key => $children ) {
			foreach ( $children as $child_key => $child_item ) {
				if ( isset( $denied[ $child_item[2] ] ) ) {
					// Remove protected items from the global array.
					unset( $submenu[ $main_key ][ $child_key ] );
					$_wp_submenu_nopriv[ $main_key ][ $child_key ] = true;
					continue;
				}
			}
		}

		return $ignore;
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
			// Skip separators.
			if ( empty( $item[0] ) ) { continue; }

			// Don't show the Protected Content plugin menu.
			if ( MS_Controller_Plugin::MENU_SLUG === $item[2] ) { continue; }

			$parts = explode( '<', $item[0] );

			$contents[$main][$item[2]] = trim( array_shift( $parts ) );
		}

		foreach ( $full_menu['sub'] as $url => $items ) {
			if ( empty( $contents[$main][$url] ) ) { continue; }

			$parent = $contents[$main][$url];
			$contents[$parent] = array();

			foreach ( $items as $pos => $item ) {
				$parts = explode( '<', $item[0] );
				$contents[$parent][$url . ':' . $item[2]] = $parent . ' &rarr; ' . trim( array_shift( $parts ) );
			}
		}

		// If not visitor membership, just show protected content
		if ( ! $this->is_base_rule ) {
			$contents = array_intersect_key( $contents, $this->rule_value );
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
		$full_menu = MS_Plugin::instance()->controller->get_admin_menu();

		foreach ( $full_menu['main'] as $pos => $item ) {
			// Skip separators
			if ( empty( $item[0] ) ) { continue; }
			$parts = explode( '<', $item[0] );
			$parent_name = trim( array_shift( $parts ) );
			$skip_parent = false;

			// Search the submenu name...
			if ( ! empty( $args['s'] ) ) {
				if ( stripos( $parent_name, $args['s'] ) === false ) {
					$skip_parent = true;
				}
			}

			if ( ! $skip_parent ) {
				$contents[$item[2]] = (object) array(
					'name' => $parent_name,
					'parent_id' => 0,
				);
			}

			if ( isset( $full_menu['sub'][$item[2]] ) ) {
				$children = $full_menu['sub'][$item[2]];

				foreach ( $children as $pos => $child ) {
					$parts = explode( '<', $child[0] );
					$child_name = trim( array_shift( $parts ) );

					// Search the submenu name...
					if ( $skip_parent && ! empty( $args['s'] ) ) {
						if ( stripos( $child_name, $args['s'] ) === false ) {
							continue;
						}
					}

					$contents[$item[2] . ':' . $child[2]] = (object) array(
						'name' => $parent_name . ' &rarr; ' . $child_name,
						'parent_id' => $item[2],
					);
				}
			}
		}

		foreach ( $contents as $key => $item ) {
			$contents[ $key ] = $item;
			$contents[ $key ]->id = $key;
			$contents[ $key ]->title = $contents[ $key ]->name;
			$contents[ $key ]->post_title = $contents[ $key ]->name;
			$contents[ $key ]->type = $this->rule_type;
			$contents[ $key ]->access = $this->get_rule_value( $key );
		}

		// If not visitor membership, just show protected content
		if ( ! $this->is_base_rule ) {
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

		if ( ! empty( $args['posts_per_page'] ) ) {
			$total = $args['posts_per_page'];
			$offset = ! empty( $args['offset'] ) ? $args['offset'] : 0;
			$contents = array_slice( $contents, $offset, $total );
		}

		return apply_filters(
			'ms_model_rule_adminside_get_contents',
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
		$args['posts_per_page'] = 0;
		$args['offset'] = false;
		$count = count( $this->get_contents( $args ) );

		return apply_filters(
			'ms_model_rule_adminside_get_contents',
			$count,
			$args,
			$this
		);
	}
}