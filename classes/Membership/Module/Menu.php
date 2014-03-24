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

		$this->_add_filter( 'wp_nav_menu_args', 'remove_register_menu' );
	}

	/**
	 * Removes registration menu item from Members with subscriptions.
	 *
	 * @since 3.5
	 * @action widgets_init
	 *
	 * @access public
	 */
	function remove_register_menu( $original ) {
	     $original['walker'] = new Membership_Menu_Walker();
	     return $original;
	}

}



/**
 * The class responsible for the menu walker.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Menu_Walker extends Walker_Nav_Menu {
	/**
	* Start the element output.
	*
	* @see Walker::start_el()
	*
	* @since 3.0.0
	*
	* @param string $output Passed by reference. Used to append additional content.
	* @param object $item   Menu item data object.
	* @param int    $depth  Depth of menu item. Used for padding.
	* @param array  $args   An array of arguments. @see wp_nav_menu()
	* @param int    $id     Current item ID.
	*/
	function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		global $M_options;
		
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$member = Membership_Plugin::factory()->get_member( $current_user->ID );

			if ( $member->has_subscription() && $item->object_id == $M_options['registration_page'] ) {
				return;
			}
		}
		
		parent::start_el($output, $item, $depth, $args, $id);
	}

	/**
	* Ends the element output, if needed.
	*
	* @see Walker::end_el()
	*
	* @since 3.0.0
	*
	* @param string $output Passed by reference. Used to append additional content.
	* @param object $item   Page data object. Not used.
	* @param int    $depth  Depth of page. Not Used.
	* @param array  $args   An array of arguments. @see wp_nav_menu()
	*/
	function end_el( &$output, $item, $depth = 0, $args = array() ) {
		global $M_options;
		
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$member = Membership_Plugin::factory()->get_member( $current_user->ID );

			if ( $member->has_subscription() && $item->object_id == $M_options['registration_page'] ) {
				return;
			}
		}
		
		parent::end_el($output, $item, $depth, $args);
	}

}