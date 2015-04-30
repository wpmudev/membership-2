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
	 */
	public function __construct() {
		parent::__construct();

		/**
		 * API
		 * Use this to check if the Membership2 plugin is installed and active.
		 *
		 * Example:
		 *   if ( apply_filters( 'ms:active' ) ) { ... }
		 */
		add_filter( 'ms:active', '__return_true' );

		/**
		 * API
		 * Returns either the current Member or the Member with the
		 * specified user_id.
		 *
		 * Example:
		 *   $current = apply_filters( 'ms:member' );
		 *   $user_10 = apply_filters( 'ms:member', 10 );
		 */
		$this->add_filter( 'ms:member', 'get_member' );
	}

	/**
	 * Returns either the current Member or the Member with the
	 * specified user_id.
	 *
	 * Handles filter: 'ms:member'
	 *
	 * @since  2.0.0
	 * @param  int $user_id Optional. User_id
	 * @return MS_Model_Member
	 */
	public function get_member( $user_id = null ) {
		$member = null;

		if ( empty( $user_id ) ) {
			$user_id = absint( $user_id );
			$member = MS_Factory::load( 'MS_Model_Member', $user_id );
		} else {
			$member = MS_Model_Member::get_current_member();
		}

		return $member;
	}

}
