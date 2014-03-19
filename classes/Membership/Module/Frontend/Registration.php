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
 * The module responsible for front end registration rendering and processing.
 *
 * @category Membership
 * @package Module
 * @subpackage Frontend
 *
 * @since 3.5
 */
class Membership_Module_Frontend_Registration extends Membership_Module {

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

		$this->_add_filter( 'pre_user_first_name', 'get_user_first_name' );
		$this->_add_filter( 'pre_user_last_name', 'get_user_last_name' );
	}

	/**
	 * Returns first name if it has been already defined or searches first name
	 * in the post data and uses it.
	 *
	 * @since 3.5
	 * @filter pre_user_first_name
	 *
	 * @access public
	 * @param string $first_name The first name of an  user.
	 * @return string The first name.
	 */
	public function get_user_first_name( $first_name ) {
		return empty( $first_name )
			? trim( filter_input( INPUT_POST, 'first_name' ) )
			: $first_name;
	}

	/**
	 * Returns last name if it has been already defined or searches last name in
	 * the post data and uses it.
	 *
	 * @since 3.5
	 * @filter pre_user_last_name
	 *
	 * @access public
	 * @param string $last_name The last name of an  user.
	 * @return string The last name.
	 */
	public function get_user_last_name( $last_name ) {
		return empty( $last_name )
			? trim( filter_input( INPUT_POST, 'last_name' ) )
			: $last_name;
	}

}