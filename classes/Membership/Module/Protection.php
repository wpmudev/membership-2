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
 * The module responsible for membership protection.
 *
 * @category Membership
 * @package Module
 *
 * @since 3.5
 */
class Membership_Module_Protection extends Membership_Module {

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

		$this->_add_action( 'plugins_loaded', 'check_membership_status' );
		$this->_add_filter( 'wp_authenticate_user', 'check_membership_is_active_on_signin', 30 );
	}

	/**
	 * Checks whether curren member is active or not. If member is deactivated,
	 * then he has to be logged out immediately.
	 *
	 * @since 3.5
	 * @action plugins_loaded
	 *
	 * @access public
	 */
	public function check_membership_status() {
		if ( !is_user_logged_in() ) {
			return;
		}

		$member = new M_Membership( get_current_user_id() );
		if ( !$member->active_member() ) {
			// member is not active, then logout and refresh page
			wp_logout();
			wp_redirect( home_url( $_SERVER['REQUEST_URI'] ) );
			exit;
		}
	}

	/**
	 * Checks whether member is active or not before user signed in. If member
	 * is deactivated, then he won't be able to sign in.
	 *
	 * @since 3.5
	 * @filter wp_authenticate_user 30
	 *
	 * @access public
	 * @param WP_User $user User object which tries to authenticate into the site.
	 * @return WP_User|WP_Error Current user if member is active, otherwise WP_Error object.
	 */
	public function check_membership_is_active_on_signin( $user ) {
		if ( is_wp_error( $user ) || !is_a( $user, 'WP_User' ) ) {
			return $user;
		}

		$member = new M_Membership( $user->ID );
		if ( !$member->active_member() ) {
			return new WP_Error( 'member_inactive', __( 'Sorry, this account is deactivated.', 'membership' ) );
		}

		return $user;
	}

}