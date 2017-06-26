<?php
/**
 * Controller to manage billing and invoices.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Billing extends MS_Controller {

	/**
	 * Default action to open the invoice edit form.
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	const ACTION_EDIT = 'edit';

	/**
	 * Action used to quick-pay an invoice via a link in the billings list.
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	const ACTION_PAY_IT = 'pay_it';

	/**
	 * Ajax action used in the transaction log list.
	 * Sets the Manual-State flag of an transaction.
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	const AJAX_ACTION_TRANSACTION_UPDATE = 'transaction_update';

	/**
	 * Ajax action used in the transaction log list.
	 * Returns a form to link a transaction with an invoice.
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	const AJAX_ACTION_TRANSACTION_LINK = 'transaction_link';

	/**
	 * Ajax action used in the transaction log list.
	 * Returns a list of requested items.
	 *
	 * @since 1.0.1.0
	 * @var   string
	 */
	const AJAX_ACTION_TRANSACTION_LINK_DATA = 'transaction_link_data';

	/**
	 * Prepare the Billing manager.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->add_ajax_action(
			self::AJAX_ACTION_TRANSACTION_UPDATE,
			'ajax_change_transaction'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_TRANSACTION_LINK,
			'ajax_link_transaction'
		);

		$this->add_ajax_action(
			self::AJAX_ACTION_TRANSACTION_LINK_DATA,
			'ajax_link_data_transaction'
		);
	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$hook = MS_Controller_Plugin::admin_page_hook( 'billing' );

		$this->run_action( 'load-' . $hook, 'admin_billing_manager' );
		$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
		$this->run_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
	}

	/**
	 * Show admin notices.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 */
	public function admin_billing_manager() {
		$this->print_admin_message();
		$msg = 0;
		$redirect = false;

		if ( ! $this->is_admin_user() ) {
			return;
		}

		$fields_edit = array( 'user_id', 'membership_id' );
		$fields_pay = array( 'invoice_id' );
		$fields_bulk = array( 'action', 'action2', 'invoice_id' );

		// Save details of a single invoice.
		if ( $this->verify_nonce( self::ACTION_EDIT )
			&& self::validate_required( $fields_edit )
		) {
			$msg = $this->save_invoice( $_POST );

			$redirect = esc_url_raw(
				add_query_arg(
					array( 'msg' => $msg ),
					remove_query_arg( array( 'invoice_id') )
				)
			);
		}

		// Quick-Pay an invoice.
		elseif ( $this->verify_nonce( self::ACTION_PAY_IT, 'GET' )
			&& self::validate_required( $fields_pay, 'GET' )
		) {
			$msg = $this->billing_do_action( 'pay', $_GET['invoice_id'] );
			$redirect = esc_url_raw(
				add_query_arg(
					array( 'msg' => $msg ),
					remove_query_arg(
						array( 'action', '_wpnonce', 'invoice_id' )
					)
				)
			);
		}

		// Bulk edit invoices.
		elseif ( $this->verify_nonce( 'bulk' )
			&& self::validate_required( $fields_bulk )
		) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->billing_do_action( $action, $_POST['invoice_id'] );
			$redirect = esc_url_raw(
				add_query_arg( array( 'msg' => $msg ) )
			);
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
	}

	/**
	 * Sets up the 'Billing' navigation and list page.
	 *
	 * @since  1.0.0
	 */
	public function admin_page() {
		$this->print_admin_message();

		// Action view page request
		$isset = array( 'action', 'invoice_id' );
		if ( self::validate_required( $isset, 'GET', false ) && 'edit' == $_GET['action'] ) {
			$invoice_id = ! empty( $_GET['invoice_id'] ) ? $_GET['invoice_id'] : 0;
			$data['invoice'] = MS_Factory::load( 'MS_Model_Invoice', $_GET['invoice_id'] );
			$data['action'] = $_GET['action'];
			$data['memberships'] = MS_Model_Membership::get_membership_names(
				array( 'include_guest' => 0 )
			);
			$view = MS_Factory::create( 'MS_View_Billing_Edit' );
			$view->data = apply_filters( 'ms_view_billing_edit_data',  $data );
			$view->render();
		} else {
			$view = MS_Factory::create( 'MS_View_Billing_List' );
			$view->render();
		}
	}

	/**
	 * Ajax action handler used by the transaction logs list to change a
	 * transaction log entry.
	 *
	 * Sets the Manual-State flag of an transaction.
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_change_transaction() {
		$res = MS_Helper_Billing::BILLING_MSG_NOT_UPDATED;
		$fields_state = array( 'id', 'state' );
		$fields_link = array( 'id', 'link' );

		if ( $this->verify_nonce() ) {
			if ( self::validate_required( $fields_state ) ) {
				$id = intval( $_POST['id'] );
				$state = $_POST['state'];

				$log = MS_Factory::load( 'MS_Model_Transactionlog', $id );

				if ( $log->manual_state( $state ) ) {
					$log->save();
					$res = MS_Helper_Billing::BILLING_MSG_UPDATED;
				}
			} elseif ( self::validate_required( $fields_link ) ) {
				$id = intval( $_POST['id'] );
				$link = intval( $_POST['link'] );

				$log = MS_Factory::load( 'MS_Model_Transactionlog', $id );

				$log->invoice_id = $link;
				if ( $log->manual_state( 'ok' ) ) {
					$invoice = $log->get_invoice();
					if ( $invoice ) {
						$invoice->pay_it( $log->gateway_id, 'manual' );
					}
					$log->save();
					$res = MS_Helper_Billing::BILLING_MSG_UPDATED;
				}
			}
		}

		echo $res;
		exit;
	}

	/**
	 * Ajax action handler used by the transaction logs list to change a
	 * transaction log entry.
	 *
	 * Returns a form to link a transaction with an invoice.
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_link_transaction() {
		$data = array();
		$resp = '';
		$fields = array( 'id' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$id = intval( $_POST['id'] );

			$log = MS_Factory::load( 'MS_Model_Transactionlog', $id );
			if ( $log->member_id ) {
				$data['member'] = $log->get_member();
			} else {
				$data['member'] = false;
			}
			$data['log'] = $log;

			$view = MS_Factory::create( 'MS_View_Billing_Link' );
			$view->data = apply_filters( 'ms_view_billing_link_data', $data );
			$resp = $view->to_html();
		}

		echo $resp;
		exit;
	}

	/**
	 * Ajax action handler used by the transaction logs list to change a
	 * transaction log entry.
	 *
	 * Returns a list of requested items
	 *
	 * @since  1.0.1.0
	 */
	public function ajax_link_data_transaction() {
		$resp = array();
		$fields = array( 'get', 'for' );

		if ( self::validate_required( $fields ) && $this->verify_nonce() ) {
			$type = $_POST['get'];
			$id = intval( $_POST['for'] );
			$settings = MS_Plugin::instance()->settings;

			if ( 'subscriptions' == $type ) {
				$member = MS_Factory::load( 'MS_Model_Member', $id );

				$resp[0] = __( 'Select a subscription', 'membership2' );
				$active = array();
				$inactive = array();
				foreach ( $member->subscriptions as $subscription ) {
					if ( $subscription->is_system() ) { continue; }

					$membership = $subscription->get_membership();
					if ( $membership->is_free() ) {
						$price = __( 'Free', 'membership2' );
					} else {
						$price = sprintf(
							'%s %s',
							$settings->currency,
							MS_Helper_Billing::format_price( $membership->price )
						);
					}
					$line = sprintf(
						__( 'Membership: %s, Base price: %s', 'membership2' ),
						$membership->name,
						$price
					);
					if ( $subscription->is_expired() ) {
						$inactive[$subscription->id] = $line;
					} else {
						$active[$subscription->id] = $line;
					}
				}
				if ( ! count( $active ) && ! count( $inactive ) ) {
					$resp[0] = __( 'No subscriptions found', 'membership2' );
				} else {
					if ( count( $active ) ) {
						$resp[__( 'Active Subscriptions', 'membership2' )] = $active;
					}
					if ( count( $inactive ) ) {
						$resp[__( 'Expired Subscriptions', 'membership2' )] = $inactive;
					}
				}
			} elseif ( 'invoices' == $type ) {
				$subscription = MS_Factory::load( 'MS_Model_Relationship', $id );
				$invoices = $subscription->get_invoices();

				$resp[0] = __( 'Select an invoice', 'membership2' );
				$unpaid = array();
				$paid = array();
				foreach ( $invoices as $invoice ) {
					$line = sprintf(
						__( 'Invoice: %s from %s (%s)', 'membership2' ),
						$invoice->get_invoice_number(),
						$invoice->due_date,
						$invoice->currency . ' ' .
						MS_Helper_Billing::format_price( $invoice->total )
					);
					if ( $invoice->is_paid() ) {
						$paid[$invoice->id] = $line;
					} else {
						$unpaid[$invoice->id] = $line;
					}
				}
				if ( ! count( $unpaid ) && ! count( $paid ) ) {
					$resp[0] = __( 'No invoices found', 'membership2' );
				} else {
					if ( count( $unpaid ) ) {
						$resp[__( 'Unpaid Invoices', 'membership2' )] = $unpaid;
					}
					if ( count( $paid ) ) {
						$resp[__( 'Paid Invoices', 'membership2' )] = $paid;
					}
				}
			}
		}

		echo json_encode( $resp );
		exit;
	}

	/**
	 * Perform actions for each invoice.
	 *
	 * @since  1.0.0
	 * @param string $action The action to perform on selected invoices.
	 * @param int[] $invoice_ids The list of invoices ids to process.
	 */
	public function billing_do_action( $action, $invoice_ids ) {
		$msg = MS_Helper_Billing::BILLING_MSG_NOT_UPDATED;

		if ( ! is_array( $invoice_ids ) ) {
			$invoice_ids = array( $invoice_ids );
		}

		foreach ( $invoice_ids as $invoice_id ) {
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );

			switch ( $action ) {
				case 'pay':
					$invoice->status = MS_Model_Invoice::STATUS_PAID;
					$invoice->changed();
					$msg = MS_Helper_Billing::BILLING_MSG_UPDATED;
					break;

				case 'archive':
					$invoice->archive();
					$msg = MS_Helper_Billing::BILLING_MSG_DELETED;
					break;

				default:
					do_action(
						'ms_controller_billing_do_action_' . $action,
						$invoice
					);
					break;
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
	 * @since  1.0.0
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
			
			//Get all gateways that are active.
			//If its only one, set that as the default gateway
			$gateway_names = MS_Model_Gateway::get_gateway_names( true );
			if ( count ( $gateway_names ) == 1 ) {
				$gateway_id = key( $gateway_names );
			}

			$subscription = MS_Model_Relationship::get_subscription(
				$member->id,
				$membership_id
			);

			if ( empty( $subscription ) ) {
				$subscription = MS_Model_Relationship::create_ms_relationship(
					$membership_id,
					$member->id,
					$gateway_id
				);
			} else {
				$subscription->set_gateway( $gateway_id );
			}

			if ( ! isset( $fields['modify_date'] ) || ! $fields['modify_date'] ) {
				$subscription->set_recalculate_expire_date( false );
			}

			$invoice_id = intval( $fields['invoice_id'] );
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );
			$this->log( 'Manual invoice creation' );
			if ( ! $invoice->is_valid() ) {
				$invoice = $subscription->get_current_invoice();
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
	 * @since  1.0.0
	 */
	public function enqueue_styles() {
		if ( empty( $_GET['action'] ) ) {
			$action = '';
		} else {
			$action = $_GET['action'];
		}

		if ( 'edit' == $action ) {
			lib3()->ui->add( 'jquery-ui' );
		}
	}

	/**
	 * Load Billing specific scripts.
	 *
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {
		$data = array(
			'ms_init' => array(),
		);

		if ( isset( $_GET['action'] ) && 'edit' == $_GET['action'] ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-validate' );

			$data['ms_init'][] = 'view_billing_edit';
		} else {
			$module = '';
			if ( isset( $_GET['show'] ) ) {
				$module = $_GET['show'];
			}

			if ( 'logs' == $module || 'matching' == $module ) {
				$data['ms_init'][] = 'view_billing_transactions';
				$data['lang'] = array(
					'link_title' => __( 'Link Transaction', 'membership2' ),
				);
			}
		}

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

}