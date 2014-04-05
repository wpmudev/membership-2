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
 * This module allows for customizing navigation menus.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module_Menu extends Membership_Module {

	const NAME = __CLASS__;

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param Membership_Plugin $plugin The instance of the plugin class.
	 */
	public function __construct( Membership_Plugin $plugin ) {
		parent::__construct( $plugin );

		if ( is_admin() ) {
			//bail if in admin
			return;
		}
		
		/*
		
		These aren't really required any more as we have a redirect in place on the
		registration page itself and can mess up people's menus if they have menu
		items nested under the registration menu item

		$this->_add_filter( 'wp_get_nav_menu_items', 'filter_nav_menu_items', 10, 3);
		$this->_add_filter( 'wp_list_pages_excludes', 'filter_wp_list_pages');
		
		*/
	}

	/**
	 * Removes registration page from menus that use wp_list_pages
	 *
	 * @since 3.5.0.6
	 *
	 * @access public
	 */
	function filter_wp_list_pages( $items ) {
		global $M_options;
		
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$member = Membership_Plugin::factory()->get_member( $current_user->ID );

			if ( $member->has_subscription() ) {
				$items[] = $M_options['registration_page'];
			}
		}
		
		return $items;
	}
	
	/**
	 * Removes registration page from menus that use wp_nav_menu
	 *
	 * @since 3.5.0.6
	 *
	 * @access public
	 */
	function filter_nav_menu_items( $items, $menu, $args ) {
		global $M_options;
		
		if ( ! is_user_logged_in() ) {
			//bail if not logged in
			return $items;
		}
		
		$current_user = wp_get_current_user();
		$member = Membership_Plugin::factory()->get_member( $current_user->ID );

		if ( ! $member->has_subscription() ) {
			//bail if member doesn't have subscription
			return $items;
		}
			
		foreach ( $items as $key => $item ) {
			if ( $item->object_id == $M_options['registration_page'] ) {
				unset($items[$key]);
			}
		}
		
		return $items;
	}
}