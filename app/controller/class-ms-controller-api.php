<?php
/**
 * This file defines the MS_Controller_Api class.
 *
 * The API class exposes the official public API of the Membership2 plugin.
 */

/*
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
 */

/**
 * Exposes the public API.
 *
 * @since 2.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Api extends MS_Controller {

	/**
	 * Construct Settings manager.
	 *
	 * @since 1.0.4.5
	 * @internal
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * Simple check to allow other plugins to quickly find out if
		 * Membership2 is loaded and the API was initialized.
		 *
		 *
		 * Example:
		 *   if ( apply_filters( 'ms_active' ) ) { ... }
		 */
		add_filter( 'ms_active', '__return_true' );

		/**
		 * Make the API controller accessible via MS_Plugin::$api
		 */
		MS_Plugin::set_api( $this );

		/**
		 * Notify other plugins that Membership2 is ready.
		 */
		do_action( 'ms_init', $this );
	}

	/**
	 * Returns either the current member or the member with the specified id.
	 *
	 * If the specified user does not exist then false is returned
	 *
	 * Useful functions of the Member object:
	 * $member->has_membership( $membership_id )
	 * $member->get_subscription( $membership_id )
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  int $user_id User_id
	 * @return MS_Model_Member|false The Member model.
	 */
	public function get_member( $user_id ) {
		$user_id = absint( $user_id );
		$member = MS_Factory::load( 'MS_Model_Member', $user_id );

		if ( ! $member->is_valid() ) {
			$member = false;
		}

		return $member;
	}

	/**
	 * Returns the Member object of the current user.
	 *
	 * If the current user is not logged in or if not a member then false is
	 * returned.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @return MS_Model_Member|false The Member model.
	 */
	public function current_member() {
		$member = MS_Model_Member::get_current_member();

		if ( ! $member->is_valid() ) {
			$member = false;
		}

		return $member;
	}

	/**
	 * Returns a single membership object.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  int $membership_id A specific membership ID.
	 * @return MS_Model_Membership The membership object.
	 */
	public function get_membership( $membership_id ) {
		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		return $membership;
	}

	/**
	 * Returns a single subscription object of the specified user. If the user
	 * did not subscribe to the given membership then false is returned.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  int $user_id The user ID.
	 * @param  int $membership_id A specific membership ID.
	 * @return MS_Model_Relationship|false The subscription object.
	 */
	public function get_subscription( $user_id, $membership_id ) {
		$subscription = false;

		$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		if ( $member && $member->has_membership( $membership_id ) ) {
			$subscription = $member->get_subscription( $membership_id );
		}

		return $subscription;
	}

	/**
	 * Returns a list of all available Memberships.
	 * Note that special/internal Memberships such as the Guest-Membership
	 * are not included in the list.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @return MS_Model_Membership[] List of all available Memberships.
	 */
	public function list_memberships() {
		$args = array(
			'include_base' => false,
			'include_guest' => false,
		);
		$list = MS_Model_Membership::get_memberships( $args );
		return $list;
	}

	/**
	 * Membership2 has a nice integrated debugging feature. This feature can be
	 * helpful for other developers so this API offers a simple way to access
	 * the debugging feature.
	 *
	 * Also note that all membership objects come with the built-in debug
	 * function `$obj->dump()` to quickly analyze the object.
	 *
	 * For example try this:
	 *   $user = MS_Plugin::$api->get_member();
	 *   $user->dump();
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  mixed $data The value to dump to the output stream.
	 */
	public function debug( $data ) {
		lib2()->debug->enable();
		lib2()->debug->dump( $data );
	}

}
