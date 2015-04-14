<?php
/**
 * This file defines the MS_Controller_Billing class.
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
 * Controller to manage billing and invoices.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Billing extends MS_Controller {

	/**
	 * Prepare the Billing manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$hook = 'protect-content_page_protected-content-billing';
		$this->add_action( 'load-' . $hook, 'admin_billing_manager' );

		$this->add_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
	}

	/**
	 * Show admin notices.
	 *
	 * @since 1.0.0
	 *
	 */
	public function print_admin_message() {
		add_action( 'admin_notices', array( 'MS_Helper_Billing', 'print_admin_message' ) );
	}

	/**
	 * Manages billing actions.
	 *
	 * Verifies GET and POST requests to manage billing.
	 *
	 * @since 1.0.0
	 */
	public function admin_billing_manager() {
		$this->print_admin_message();
		$msg = 0;
		$redirect = false;

		if ( ! $this->is_admin_user() ) {
			return;
		}

		$fields = array( 'user_id', 'membership_id' );

		if ( self::validate_required( $fields )
			&& $this->verify_nonce()
		) {
			// Save billing add/edit
			$msg = $this->save_invoice( $_POST );

			$redirect = remove_query_arg( array( 'invoice_id') );
			$redirect = add_query_arg( array( 'msg' => $msg ), $redirect );
		} elseif ( self::validate_required( array( 'invoice_id' ) )
			&& $this->verify_nonce( 'bulk' )
		) {
			// Execute bulk actions.
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->billing_do_action( $action, $_POST['invoice_id'] );
			$redirect = add_query_arg( array( 'msg' => $msg ) );
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Sets up the 'Billing' navigation and list page.
	 *
	 * @since 1.0.0
	 */
	public function admin_billing() {
		$this->print_admin_message();

		// Action view page request
		$isset = array( 'action', 'invoice_id' );
		if ( self::validate_required( $isset, 'GET', false ) && 'edit' == $_GET['action'] ) {
			$invoice_id = ! empty( $_GET['invoice_id'] ) ? $_GET['invoice_id'] : 0;
			$data['invoice'] = MS_Factory::load( 'MS_Model_Invoice', $_GET['invoice_id'] );
			$data['action'] = $_GET['action'];
			$data['users'] = MS_Model_Member::get_usernames( null, MS_Model_Member::SEARCH_ALL_USERS );
			$data['memberships'] = MS_Model_Membership::get_membership_names( null );
			$view = MS_Factory::create( 'MS_View_Billing_Edit' );
			$view->data = apply_filters( 'ms_view_billing_edit_data',  $data );
			$view->render();
		} else {
			$view = MS_Factory::create( 'MS_View_Billing_List' );
			$view->render();
		}
	}

	/**
	 * Perform actions for each invoice.
	 *
	 * @since 1.0.0
	 * @param string $action The action to perform on selected invoices.
	 * @param int[] $invoice_ids The list of invoices ids to process.
	 */
	public function billing_do_action( $action, $invoice_ids ) {
		$msg = MS_Helper_Billing::BILLING_MSG_NOT_UPDATED;

		if ( $this->is_admin_user() && is_array( $invoice_ids ) ) {
			foreach ( $invoice_ids as $invoice_id ) {
				switch ( $action ) {
					case 'delete':
						$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );
						$invoice->delete();
						$msg = MS_Helper_Billing::BILLING_MSG_DELETED;
						break;

					default:
						do_action( 'ms_controller_billing_do_action_' . $action, $invoice_ids );
						break;
				}
			}
		}

		return apply_filters(
			'ms_controller_billing_billing_do_action',
			$msg,
			$action,
			$invoice_ids,
			$this
		);
	}

	/**
	 * Save invoices using the invoices model.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $fields Transaction fields
	 */
	private function save_invoice( $fields ) {
		$msg = MS_Helper_Billing::BILLING_MSG_NOT_UPDATED;

		if ( $this->is_admin_user()
			&& is_array( $fields )
			&& ! empty( $fields['user_id'] )
			&& ! empty( $fields['membership_id'] )
		) {
			$member = MS_Factory::load( 'MS_Model_Member', $fields['user_id'] );
			$membership_id = $fields['membership_id'];
			$gateway_id = 'admin';

			$ms_relationship = MS_Model_Relationship::get_subscription( $member->id, $membership_id );
			if ( empty( $ms_relationship ) ) {
				$ms_relationship = MS_Model_Relationship::create_ms_relationship(
					$membership_id,
					$member->id,
					$gateway_id
				);
			} else {
				$ms_relationship->gateway_id = $gateway_id;
				$ms_relationship->save();
			}

			$invoice = MS_Factory::load( 'MS_Model_Invoice', $fields['invoice_id'] );
			if ( ! $invoice->is_valid() ) {
				$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
				$msg = MS_Helper_Billing::BILLING_MSG_ADDED;
			} else {
				$msg = MS_Helper_Billing::BILLING_MSG_UPDATED;
			}

			foreach ( $fields as $field => $value ) {
				$invoice->$field = $value;
			}

			$invoice->save();

			if ( ! empty( $fields['execute'] ) ) {
				$invoice->changed();
			}
		}

		return apply_filters(
			'ms_controller_billing_save_invoice',
			$msg,
			$fields,
			$this
		);
	}

	/**
	 * Load Billing specific styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		if ( empty( $_GET['action'] ) ) {
			$action = '';
		} else {
			$action = $_GET['action'];
		}

		if ( 'edit' == $action ) {
			lib2()->ui->add( 'jquery-ui' );
		}
	}

	/**
	 * Load Billing specific scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-validate' );

			$data = array(
				'ms_init' => array( 'view_billing_edit' ),
			);

			lib2()->ui->data( 'ms_data', $data );
			wp_enqueue_script( 'ms-admin' );
		}
	}

}