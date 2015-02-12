<?php
/**
 * This file defines the MS_Controller_Settings class.
 *
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
 * Controller for Automated Communications.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Communication extends MS_Controller {

	/**
	 * Ajax action name.
	 *
	 * @since 1.0.0
	 * @var string The ajax action name.
	 */
	const AJAX_ACTION_UPDATE_COMM = 'update_comm';

	/**
	 * Prepare Membership settings manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		do_action( 'ms_controller_communication_before', $this );

		$this->add_action(
			'wp_ajax_' . self::AJAX_ACTION_UPDATE_COMM,
			'ajax_action_update_communication'
		);

		$this->add_action(
			'ms_controller_membership_setup_completed',
			'auto_setup_communications'
		);

		do_action( 'ms_controller_communication_after', $this );
	}

	/**
	 * Handle Ajax update comm field action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_update_comm
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_communication() {
		do_action(
			'ms_controller_communication_ajax_action_update_communication_before',
			$this
		);

		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'type', 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			WDev()->array->strip_slashes( $_POST, 'value' );

			$comm = MS_Model_Communication::get_communication( $_POST['type'] );
			$field = $_POST['field'];
			$value = $_POST['value'];
			$comm->$field = $value;
			$comm->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		do_action(
			'ms_controller_communication_ajax_action_update_communication_after',
			$this
		);

		echo apply_filters(
			'ms_controller_commnucation_ajax_action_update_communication_msg',
			$msg,
			$this
		);
		exit;
	}

	/**
	 * Auto setup communications.
	 *
	 * Fires after a membership setup is completed.
	 *
	 * Related Action Hooks:
	 * - ms_controller_membership_setup_completed
	 *
	 * @since 1.0.0
	 * @param MS_Model_Membership $membership
	 */
	public function auto_setup_communications( $membership ) {
		$comms = MS_Model_Communication::load_communications( true );

		// Private memberships don't have communications enabled
		if ( ! $membership->is_private ) {
			foreach ( $comms as $comm ) {
				$comm->enabled = true;
				$comm->save();
			}
		}

		do_action(
			'ms_controller_communication_auto_setup_communications_after',
			$membership,
			$this
		);
	}

}