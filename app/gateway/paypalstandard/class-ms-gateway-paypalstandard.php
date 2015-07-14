<?php
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
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_Paypalstandard extends MS_Gateway {

	const ID = 'paypalstandard';

	/**
	 * Gateway singleton instance.
	 *
	 * @since  1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Paypal merchant ID.
	 *
	 * @since  1.0.0
	 * @var bool $merchant_id
	 */
	protected $merchant_id;

	/**
	 * Paypal country site.
	 *
	 * @since  1.0.0
	 * @var bool $paypal_site
	 */
	protected $paypal_site;


	/**
	 * Hook to add custom transaction status.
	 *
	 * @since  1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->id = self::ID;
		$this->name = __( 'PayPal Standard Gateway', MS_TEXT_DOMAIN );
		$this->group = 'PayPal';
		$this->manual_payment = false; // Recurring charged automatically
		$this->pro_rate = false;
	}

	/**
	 * Processes gateway IPN return.
	 *
	 * @since  1.0.0
	 */
	public function handle_return() {
		$success = false;
		$exit = false;
		$redirect = false;
		$notes = '';
		$status = null;
		$notes_pay = '';
		$notes_txn = '';
		$external_id = null;
		$invoice_id = 0;
		$subscription_id = 0;
		$amount = 0;

		if ( ( isset( $_POST['payment_status'] ) || isset( $_POST[ 'txn_type'] ) )
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

			$invoice_id = intval( $_POST['invoice'] );
			$currency = $_POST['mc_currency'];
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );

			if ( ! is_wp_error( $response )
				&& 200 == $response['response']['code']
				&& ! empty( $response['body'] )
				&& 'VERIFIED' == $response['body']
				&& $invoice->id == $invoice_id
			) {
				$subscription = $invoice->get_subscription();
				$membership = $subscription->get_membership();
				$member = $subscription->get_member();
				$subscription_id = $subscription->id;

				// Process PayPal payment status
				if ( ! empty( $_POST['payment_status'] ) ) {
					$amount = (float) $_POST['mc_gross'];
					$external_id = $_POST['txn_id'];

					switch ( $_POST['payment_status'] ) {
						// Successful payment
						case 'Completed':
						case 'Processed':
							if ( $amount == $invoice->total ) {
								$success = true;
								$notes .= __( 'Payment successful', MS_TEXT_DOMAIN );
							} else {
								$notes_pay = __( 'Payment amount differs from invoice total.', MS_TEXT_DOMAIN );
								$status = MS_Model_Invoice::STATUS_DENIED;
							}
							break;

						case 'Reversed':
							$notes_pay = __( 'Last transaction has been reversed. Reason: Payment has been reversed (charge back).', MS_TEXT_DOMAIN );
							$status = MS_Model_Invoice::STATUS_DENIED;
							break;

						case 'Refunded':
							$notes_pay = __( 'Last transaction has been reversed. Reason: Payment has been refunded.', MS_TEXT_DOMAIN );
							$status = MS_Model_Invoice::STATUS_DENIED;
							break;

						case 'Denied':
							$notes_pay = __( 'Last transaction has been reversed. Reason: Payment Denied.', MS_TEXT_DOMAIN );
							$status = MS_Model_Invoice::STATUS_DENIED;
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

							lib2()->array->strip_slashes( $_POST, 'pending_reason' );
							$reason = $_POST['pending_reason'];
							$notes_pay = __( 'Last transaction is pending. Reason: ', MS_TEXT_DOMAIN ) .
								( isset($pending_str[$reason] ) ? $pending_str[$reason] : $pending_str['*'] );
							$status = MS_Model_Invoice::STATUS_PENDING;
							break;

						default:
						case 'Partially-Refunded':
						case 'In-Progress':
							$notes_pay = __( 'Not handling payment_status: ', MS_TEXT_DOMAIN ) .
								$_POST['payment_status'];
							MS_Helper_Debug::log( $notes_pay );
							$success = null;
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
								$success = true;
							} else {
								if ( ! $success ) { $success = null; }
							}
							break;

						case 'subscr_modify':
							// Payment profile was modified
							$notes_txn = __( 'Paypal subscription profile has been modified.', MS_TEXT_DOMAIN );
							if ( ! $success ) { $success = null; }
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
							$status = MS_Model_Invoice::STATUS_DENIED;
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
							if ( ! $success ) { $success = null; }
							break;

						default:
							// Other event that we do not have a case for...
							$notes_txn = __( 'Not handling txn_type: ', MS_TEXT_DOMAIN ) . $_POST['txn_type'];
							MS_Helper_Debug::log( $notes_txn );
							if ( ! $success ) { $success = null; }
							break;
					}
				}

				if ( ! empty( $notes_pay ) ) { $invoice->add_notes( $notes_pay ); }
				if ( ! empty( $notes_txn ) ) { $invoice->add_notes( $notes_txn ); }

				$notes .= $notes_pay . ' | ' . $notes_txn;

				$invoice->save();

				if ( $success ) {
					$invoice->pay_it( $this->id, $external_id );
				} elseif ( ! empty( $status ) ) {
					$invoice->status = $status;
					$invoice->save();
					$invoice->changed();
				}

				do_action(
					'ms_gateway_paypalstandard_payment_processed_' . $status,
					$invoice,
					$subscription
				);

			} else {
				$reason = 'Unexpected transaction response';
				switch ( true ) {
					case is_wp_error( $response ):
						$reason = 'Response is error';
						break;

					case 200 != $response['response']['code']:
						$reason = 'Response code is ' . $response['response']['code'];
						break;

					case empty( $response['body'] ):
						$reason = 'Response is empty';
						break;

					case 'VERIFIED' != $response['body']:
						$reason = sprintf(
							'Expected response "%s" but got "%s"',
							'VERIFIED',
							(string) $response['body']
						);
						break;

					case $invoice->id != $invoice_id:
						$reason = sprintf(
							'Expected invoice_id "%s" but got "%s"',
							$invoice->id,
							$invoice_id
						);
						break;
				}

				$notes = 'Response Error: ' . $reason;
				MS_Helper_Debug::log( $notes );
				MS_Helper_Debug::log( $response );
				MS_Helper_Debug::log( $_POST );
				$exit = true;
			}
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.

			$u_agent = $_SERVER['HTTP_USER_AGENT'];
			if ( false === strpos( $u_agent, 'PayPal' ) ) {
				// Very likely someone tried to open the URL manually. Redirect to home page
				$notes = 'Error: Missing POST variables. Redirect user to Home-URL.';
				MS_Helper_Debug::log( $notes );
				$redirect = home_url();
			} else {
				// PayPal did provide invalid details...
				status_header( 404 );
				$notes = 'Error: Missing POST variables. Identification is not possible.';
				MS_Helper_Debug::log( $notes );
			}
			$exit = true;
		}

		do_action(
			'ms_gateway_transaction_log',
			self::ID, // gateway ID
			'handle', // request|process|handle
			$success, // success flag
			$subscription_id, // subscription ID
			$invoice_id, // invoice ID
			$amount, // charged amount
			$notes // Descriptive text
		);

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
		if ( $exit ) {
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
	 * @since  1.0.0
	 * @return array
	 */
	public function get_paypal_sites() {
		return apply_filters(
			'ms_gateway_paylpaystandard_get_paypal_sites',
			self::get_country_codes()
		);
	}

	/**
	 * Verify required fields.
	 *
	 * @since  1.0.0
	 * @return boolean
	 */
	public function is_configured() {
		$is_configured = true;
		$required = array( 'merchant_id', 'paypal_site' );

		foreach ( $required as $field ) {
			$value = $this->$field;
			if ( empty( $value ) ) {
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
	 * @since  1.0.0
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