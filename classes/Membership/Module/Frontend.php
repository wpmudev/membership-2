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
 * The module responsible for front end rendering and processing.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module_Frontend extends Membership_Module {

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
		$this->_add_filter( 'body_class', 'add_body_classes' );
	}

	/**
	 * Determines whether current page is a BuddyPress component or not.
	 *
	 * @since 3.5
	 * @filter bp_is_current_component 10 2
	 *
	 * @access public
	 * @param boolean $is_component Initial value of whether the page is component or not.
	 * @param string $component The component name.
	 * @return boolean TRUE if current page is BuddyPress component, otherwise FALSE.
	 */
	public function determine_bp_component( $is_component, $component ) {
		if ( $component == 'register' ) {
			return filter_input( INPUT_GET, 'action' ) == 'registeruser' && membership_is_registration_page();
		}

		return $is_component;
	}

	/**
	 * Adds corresponding body classes when need be.
	 *
	 * @since 3.5
	 * @filter body_class
	 *
	 * @param array $classes The array of body classes.
	 * @return array The extended array of body classes.
	 */
	public function add_body_classes( $classes ) {
		if ( class_exists( 'BuddyPress' ) && filter_input( INPUT_GET, 'action' ) == 'registeruser' && membership_is_registration_page() ) {
			add_filter( 'bp_is_blog_page', '__return_false' );
			$this->_add_filter( 'bp_is_current_component', 'determine_bp_component', 10, 2 );

			$classes = array_unique( bp_get_the_body_class( $classes ) );
		}

		return $classes;
	}

}