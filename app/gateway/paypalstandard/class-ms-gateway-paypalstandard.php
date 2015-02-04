<?php
/**
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
 * Gateway: Paypal Standard
 *
 * Officially: PayPal Payments Standard
 * https://developer.paypal.com/docs/classic/paypal-payments-standard/gs_PayPalPaymentsStandard/
 *
 * Process single and recurring paypal purchases/payments.
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Gateway_Paypalstandard extends MS_Gateway {

	const ID = 'paypalstandard';

	/**
	 * Gateway transaction status constants.
	 *
	 * @since 1.0.0
	 * @var string $status
	 */
	const STATUS_FAILED = 'failed';
	const STATUS_REVERSED = 'reversed';
	const STATUS_REFUNDED = 'refunded';
	const STATUS_PENDING = 'pending';
	const STATUS_DISPUTE = 'dispute';
	const STATUS_DENIED = 'denied';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Gateway ID.
	 *
	 * @since 1.0.0
	 * @var int $id
	 */
	protected $id = self::ID;

	/**
	 * Gateway name.
	 *
	 * @since 1.0.0
	 * @var string $name
	 */
	protected $name = '';

	/**
	 * Gateway description.
	 *
	 * @since 1.0.0
	 * @var string $description
	 */
	protected $description = '';

	/**
	 * Manual payment indicator.
	 *
	 * If the gateway does not allow automatic reccuring billing.
	 *
	 * @since 1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = true;

	/**
	 * Gateway allow Pro rating.
	 *
	 * @todo To be released in further versions.
	 * @since 1.0.0
	 * @var bool $pro_rate
	 */
	protected $pro_rate = false;

	/**
	 * Gateway operation mode.
	 *
	 * Live or sandbox (test) mode.
	 *
	 * @since 1.0.0
	 * @var string $mode
	 */
	protected $mode;

	/**
	 * Paypal merchant ID.
	 *
	 * @since 1.0.0
	 * @var bool $merchant_id
	 */
	protected $merchant_id;

	/**
	 * Paypal country site.
	 *
	 * @since 1.0.0
	 * @var bool $paypal_site
	 */
	protected $paypal_site;


	/**
	 * Hook to add custom transaction status.
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->name = __( 'PayPal Standard Gateway', MS_TEXT_DOMAIN );

		if ( $this->active ) {
			$this->add_filter(
				'ms_model_invoice_get_status',
				'gateway_custom_status'
			);
		}
	}

	/**
	 * Add Gateway custom status.
	 *
	 * * Hooks Actions: *
	 * * ms_model_invoice_get_status
	 *
	 * @since 1.0.0
	 * @return array {
	 *     Array of ( $status_id => $status_name ).
	 *
	 *     @type string $status_id The status id.
	 *     @type string $status_name The status name.
	 * }
	 */
	public function gateway_custom_status( $status ) {
		$paypal_status = array(
			self::STATUS_FAILED => __( 'Failed', MS_TEXT_DOMAIN ),
			self::STATUS_REVERSED => __( 'Reversed', MS_TEXT_DOMAIN ),
			self::STATUS_REFUNDED => __( 'Refunded', MS_TEXT_DOMAIN ),
			self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
			self::STATUS_DISPUTE => __( 'Dispute', MS_TEXT_DOMAIN ),
			self::STATUS_DENIED => __( 'Denied', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_gateway_paypalstandard_gateway_custom_status',
			array_merge( $status, $paypal_status )
		);
	}

	/**
	 * Processes gateway IPN return.
	 *
	 * @since 1.0.0
	 */
	public function handle_return() {
		if ( ( isset($_POST['payment_status'] ) || isset( $_POST['txn_type'] ) )
			&& ! empty( $_POST['invoice'] )
		) {
			if ( $this->is_live_mode() ) {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			// Paypal post authenticity verification
			$ipn_data = (array) stripslashes_deep( $_POST );
			$ipn_data['cmd'] = '_notify-validate';
			$response = wp_remote_post(
				$domain . '/cgi-bin/webscr',
				array(
					'timeout' => 60,
					'sslverify' => false,
					'httpversion' => '1.1',
					'body' => $ipn_data,
				)
			);

			if ( ! is_wp_error( $response )
				&& 200 == $response['response']['code']
				&& ! empty( $response['body'] )
				&& 'VERIFIED' == $response['body']
			) {
				MS_Helper_Debug::log( 'PayPal Transaction Verified' );
			} else {
				$error = 'Response Error: Unexpected transaction response';
				MS_Helper_Debug::log( $error );
				MS_Helper_Debug::log( $response );
				wp_die( $error );
			}

			if ( empty( $_POST['invoice'] ) ) {
				$error = 'Response Error: No relationship identification found.';
				MS_Helper_Debug::log( $error );
				MS_Helper_Debug::log( $response );
				exit;
			}

			$invoice = MS_Factory::load( 'MS_Model_Invoice', $_POST['invoice'] );
			$ms_relationship = MS_Factory::load( 'MS_Model_Relationship', $invoice->ms_relationship_id );
			$membership = $ms_relationship->get_membership();
			$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );

			$currency = $_POST['mc_currency'];
			$status = null;
			$notes_pay = '';
			$notes_txn = '';
			$external_id = null;
			$amount = 0;

			// Process PayPal payment status
			if ( ! empty( $_POST['payment_status'] ) ) {
				$amount = (float) $_POST['mc_gross'];
				$external_id = $_POST['txn_id'];

				switch ( $_POST['payment_status'] ) {
					// Successful payment
					case 'Completed':
					case 'Processed':
						if ( $amount == $invoice->total ) {
							$status = MS_Model_Invoice::STATUS_PAID;
						} else {
							$notes_pay = __( 'Payment amount differs from invoice total.', MS_TEXT_DOMAIN );
							$status = self::STATUS_DENIED;
						}
						break;

					case 'Reversed':
						$notes_pay = __( 'Last transaction has been reversed. Reason: Payment has been reversed (charge back).', MS_TEXT_DOMAIN );
						$status = self::STATUS_REVERSED;
						break;

					case 'Refunded':
						$notes_pay = __( 'Last transaction has been reversed. Reason: Payment has been refunded.', MS_TEXT_DOMAIN );
						$status = self::STATUS_REFUNDED;
						break;

					case 'Denied':
						$notes_pay = __( 'Last transaction has been reversed. Reason: Payment Denied.', MS_TEXT_DOMAIN );
						$status = self::STATUS_DENIED;
						break;

					case 'Pending':
						$pending_str = array(
							'address' => __( 'Customer did not include a confirmed shipping address', MS_TEXT_DOMAIN ),
							'authorization' => __( 'Funds not captured yet', MS_TEXT_DOMAIN ),
							'echeck' => __( 'eCheck that has not cleared yet', MS_TEXT_DOMAIN ),
							'intl' => __( 'Payment waiting for aproval by service provider', MS_TEXT_DOMAIN ),
							'multi-currency' => __( 'Payment waiting for service provider to handle multi-currency process', MS_TEXT_DOMAIN ),
							'unilateral' => __( 'Customer did not register or confirm his/her email yet', MS_TEXT_DOMAIN ),
							'upgrade' => __( 'Waiting for service provider to upgrade the PayPal account', MS_TEXT_DOMAIN ),
							'verify' => __( 'Waiting for service provider to verify his/her PayPal account', MS_TEXT_DOMAIN ),
							'*' => '?',
						);

						$reason = $_POST['pending_reason'];
						$notes_pay = __( 'Last transaction is pending. Reason: ', MS_TEXT_DOMAIN ) .
							( isset($pending_str[$reason] ) ? $pending_str[$reason] : $pending_str['*'] );
						$status = self::STATUS_PENDING;
						break;

					default:
					case 'Partially-Refunded':
					case 'In-Progress':
						$notes_pay = __( 'Not handling payment_status: ', MS_TEXT_DOMAIN ) .
							$_POST['payment_status'];
						MS_Helper_Debug::log( $notes_pay );
						break;
				}
			}

			// Check for subscription details
			if ( ! empty( $_POST['txn_type'] ) ) {
				switch ( $_POST['txn_type'] ) {
					case 'subscr_signup':
					case 'subscr_payment':
						// Payment was received
						$notes_txn = __( 'Paypal subscripton profile has been created.', MS_TEXT_DOMAIN );
						if ( 0 == $invoice->total ) {
							$status = MS_Model_Invoice::STATUS_PAID;
						}
						break;

					case 'subscr_modify':
						// Payment profile was modified
						$notes_txn = __( 'Paypal subscription profile has been modified.', MS_TEXT_DOMAIN );
						break;

					case 'recurring_payment_profile_canceled':
					case 'subscr_cancel':
						// Subscription was manually cancelled.
						$notes_txn = __( 'Paypal subscription profile has been canceled.', MS_TEXT_DOMAIN );
						$member->cancel_membership( $membership->id );
						$member->save();
						break;

					case 'recurring_payment_suspended':
						// Recurring subscription was manually suspended.
						$notes_txn = __( 'Paypal subscription profile has been suspended.', MS_TEXT_DOMAIN );
						$member->cancel_membership( $membership->id );
						$member->save();
						break;

					case 'recurring_payment_suspended_due_to_max_failed_payment':
						// Recurring subscription was automatically suspended.
						$notes_txn = __( 'Paypal subscription profile has failed.', MS_TEXT_DOMAIN );
						$member->cancel_membership( $membership->id );
						$member->save();
						break;

					case 'new_case':
						// New Dispute was filed for a payment.
						$status = self::STATUS_DISPUTE;
						break;

					case 'subscr_eot':
						/*
						 * Meaning: Subscription expired.
						 *
						 *   - after a one-time payment was madeafter last
						 *   - after last transaction in a recurring subscription
						 *   - payment failed
						 *   - ...
						 *
						 * We do not handle this event...
						 *
						 * One time payment sends 3 messages:
						 *   1. subscr_start (new subscription starts)
						 *   2. subscr_payment (payment confirmed)
						 *   3. subscr_eot (subscription ends)
						 */
						$notes_txn = __( 'No more payments will be made for this subscription.', MS_TEXT_DOMAIN );
						break;

					default:
						// Other event that we do not have a case for...
						$notes_txn = __( 'Not handling txn_type: ', MS_TEXT_DOMAIN ) . $_POST['txn_type'];
						MS_Helper_Debug::log( $notes_txn );
						break;
				}
			}

			if ( empty( $invoice ) ) {
				$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
			}

			$invoice->external_id = $external_id;

			if ( ! empty( $notes_pay ) ) {
				$invoice->add_notes( $notes_pay );
			}

			if ( ! empty( $notes_txn ) ) {
				$invoice->add_notes( $notes_txn );
			}

			$invoice->gateway_id = $this->id;
			$invoice->save();

			if ( ! empty( $status ) ) {
				$invoice->status = $status;
				$invoice->save();

				$invoice = $this->invoice_changed( $invoice );

				if ( MS_Model_Invoice::STATUS_PAID == $invoice->status ) {
					/**
					 * Notify Add-ons that an invoice was paid.
					 *
					 * @since 1.1.0
					 */
					do_action( 'ms_invoice_paid', $invoice );
				}
			}

			do_action(
				'ms_gateway_paypalstandard_payment_processed_' . $status,
				$invoice,
				$ms_relationship
			);
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.

			$u_agent = $_SERVER['HTTP_USER_AGENT'];
			if ( false === strpos( $u_agent, 'PayPal' ) ) {
				// Very likely someone tried to open the URL manually. Redirect to home page
				$notes = 'Error: Missing POST variables. Redirect user to Home-URL.';
				MS_Helper_Debug::log( $notes );
				wp_safe_redirect( home_url() );
				exit;
			} else {
				// PayPal did provide invalid details...
				status_header( 404 );
				$notes = 'Error: Missing POST variables. Identification is not possible.';
				MS_Helper_Debug::log( $notes );
			}
			exit;
		}

		do_action(
			'ms_gateway_paypalstandard_handle_return_after',
			$this
		);
	}

	/**
	 * Get paypal country sites list.
	 *
	 * @see MS_Gateway::get_country_codes()
	 * @since 1.0.0
	 * @return array
	 */
	public function get_paypal_sites() {
		return apply_filters(
			'ms_gateway_paylpaystandard_get_paypal_sites',
			self::get_country_codes()
		);
	}

	/**
	 * Update the subscription after the invoice has changed.
	 *
	 * Process transaction status change related to this membership relationship.
	 * Change status accordinly to transaction status.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Invoice $invoice The Transaction.
	 * @return MS_Model_Invoice The processed invoice.
	 */
	public function invoice_changed( $invoice ) {
		$ms_relationship = MS_Factory::load( 'MS_Model_Relationship', $invoice->ms_relationship_id );
		$member = MS_Factory::load( 'MS_Model_Member', $invoice->user_id );

		switch ( $invoice->status ) {
			case self::STATUS_REVERSED:
			case self::STATUS_REFUNDED:
			case self::STATUS_DENIED:
			case self::STATUS_DISPUTE:
				MS_Model_Event::save_event(
					MS_Model_Event::TYPE_PAYMENT_DENIED,
					$ms_relationship
				);
				break;

			default:
				$ms_relationship = parent::invoice_changed( $invoice );
				break;
		}

		$member->save();
		$ms_relationship->gateway_id = $this->gateway_id;
		$ms_relationship->save();

		return apply_filters(
			'ms_gateway_paypalstandard_processed_transaction',
			$invoice,
			$this
		);
	}

	/**
	 * Verify required fields.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public function is_configured() {
		$is_configured = true;
		$required = array( 'merchant_id', 'paypal_site' );

		foreach ( $required as $field ) {
			if ( empty( $this->$field ) ) {
				$is_configured = false;
				break;
			}
		}

		return apply_filters(
			'ms_gateway_paypalstandard_is_configured',
			$is_configured
		);
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'paypal_site':
					if ( array_key_exists( $value, self::get_paypal_sites() ) ) {
						$this->$property = $value;
					}
					break;

				default:
					parent::__set( $property, $value );
					break;
			}
		}

		do_action(
			'ms_gateway_paypalstandard__set_after',
			$property,
			$value,
			$this
		);
	}

}