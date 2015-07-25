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

		if ( $this->active && $this->is_live_mode() && strpos( $this->merchant_id, '@' ) ) {
			$settings_url = MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => MS_Controller_Settings::TAB_PAYMENT )
			);
			lib2()->ui->admin_message(
				sprintf(
					__( 'Warning: You use your email address for the PayPal Standard gateway instead of your Merchant ID. Please check %syour payment settings%s and enter the Merchant ID instead', MS_TEXT_DOMAIN ),
					'<a href="' . $settings_url . '">',
					'</a>'
				),
				'err'
			);
		}
	}

	/**
	 * Processes gateway IPN return.
	 *
	 * @since  1.0.0
	 */
	public function handle_return() {
		$success = false;
		$ignore = false;
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
		$transaction_type = '';
		$payment_status = '';
		$is_m1 = false;

		$fields_set = false;

		if ( ! empty( $_POST[ 'txn_type'] ) ) {
			$transaction_type = strtolower( $_POST[ 'txn_type'] );
		}
		if ( ! empty( $_POST[ 'payment_status'] ) ) {
			$payment_status = strtolower( $_POST[ 'payment_status'] );
		}

		if ( $payment_status || $transaction_type ) {
			if ( ! empty( $_POST['invoice'] ) ) {
				// 'invoice' is set in all regular M2 subscriptions.
				$fields_set = true;
			} elseif ( ! empty( $_POST['custom'] ) ) {
				// First: We cannot process this payment.
				$fields_set = false;

				// But let's check if it is an M1 payment.
				$infos = explode( ':', $_POST['custom'] );
				if ( count( $infos ) > 2 ) {
					// $infos should contain [timestamp, user_id, sub_id, key]
					$pay_types = array( 'subscr_signup', 'subscr_payment' );
					$pay_stati = array( 'completed', 'processed' );

					if ( in_array( $transaction_type, $pay_types ) ) {
						$is_m1 = true;
					} elseif ( in_array( $payment_status, $pay_stati ) ) {
						$is_m1 = true;
					}
				}
			}
		}

		if ( $fields_set ) {
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
				if ( $payment_status ) {
					$amount = (float) $_POST['mc_gross'];
					$external_id = $_POST['txn_id'];

					switch ( $payment_status ) {
						// Successful payment
						case 'completed':
						case 'processed':
							if ( $amount == $invoice->total ) {
								$success = true;
								$notes .= __( 'Payment successful', MS_TEXT_DOMAIN );
							} else {
								$notes_pay = __( 'Payment amount differs from invoice total.', MS_TEXT_DOMAIN );
								$status = MS_Model_Invoice::STATUS_DENIED;
							}
							break;

						case 'reversed':
							$notes_pay = __( 'Last transaction has been reversed. Reason: Payment has been reversed (charge back).', MS_TEXT_DOMAIN );
							$status = MS_Model_Invoice::STATUS_DENIED;
							$ignore = true;
							break;

						case 'refunded':
							$notes_pay = __( 'Last transaction has been reversed. Reason: Payment has been refunded.', MS_TEXT_DOMAIN );
							$status = MS_Model_Invoice::STATUS_DENIED;
							$ignore = true;
							break;

						case 'denied':
							$notes_pay = __( 'Last transaction has been reversed. Reason: Payment Denied.', MS_TEXT_DOMAIN );
							$status = MS_Model_Invoice::STATUS_DENIED;
							$ignore = true;
							break;

						case 'pending':
							lib2()->array->strip_slashes( $_POST, 'pending_reason' );
							$notes_pay = __( 'Last transaction is pending.', MS_TEXT_DOMAIN ) . ' ';

							switch ( $_POST['pending_reason'] ) {
								case 'address':
									$notes_pay .= __( 'Customer did not include a confirmed shipping address', MS_TEXT_DOMAIN );
									break;

								case 'authorization':
									$notes_pay .= __( 'Funds not captured yet', MS_TEXT_DOMAIN );
									break;

								case 'echeck':
									$notes_pay .= __( 'The eCheck has not cleared yet', MS_TEXT_DOMAIN );
									break;

								case 'intl':
									$notes_pay .= __( 'Payment waiting for approval by service provider', MS_TEXT_DOMAIN );
									break;

								case 'multi-currency':
									$notes_pay .= __( 'Payment waiting for service provider to handle multi-currency process', MS_TEXT_DOMAIN );
									break;

								case 'unilateral':
									$notes_pay .= __( 'Customer did not register or confirm his/her email yet', MS_TEXT_DOMAIN );
									break;

								case 'upgrade':
									$notes_pay .= __( 'Waiting for service provider to upgrade the PayPal account', MS_TEXT_DOMAIN );
									break;

								case 'verify':
									$notes_pay .= __( 'Waiting for service provider to verify his/her PayPal account', MS_TEXT_DOMAIN );
									break;

								default:
									$notes_pay .= __( 'Unknown reason', MS_TEXT_DOMAIN );
									break;
							}

							$status = MS_Model_Invoice::STATUS_PENDING;
							$ignore = true;
							break;

						default:
						case 'partially-refunded':
						case 'in-progress':
							$notes_pay = sprintf(
								__( 'Not handling payment_status: %s', MS_TEXT_DOMAIN ),
								$payment_status
							);
							MS_Helper_Debug::log( $notes_pay );
							$ignore = true;
							break;
					}
				}

				// Check for subscription details
				if ( $transaction_type ) {
					switch ( $transaction_type ) {
						case 'subscr_signup':
						case 'subscr_payment':
							// Payment was received
							$notes_txn = __( 'Paypal subscripton profile has been created.', MS_TEXT_DOMAIN );
							if ( 0 == $invoice->total ) {
								$success = true;
							} else {
								$ignore = true;
							}
							break;

						case 'subscr_modify':
							// Payment profile was modified
							$notes_txn = __( 'Paypal subscription profile has been modified.', MS_TEXT_DOMAIN );
							$ignore = true;
							break;

						case 'recurring_payment_profile_canceled':
						case 'subscr_cancel':
							// Subscription was manually cancelled.
							$notes_txn = __( 'Paypal subscription profile has been canceled.', MS_TEXT_DOMAIN );
							$member->cancel_membership( $membership->id );
							$member->save();
							$ignore = true;
							break;

						case 'recurring_payment_suspended':
							// Recurring subscription was manually suspended.
							$notes_txn = __( 'Paypal subscription profile has been suspended.', MS_TEXT_DOMAIN );
							$member->cancel_membership( $membership->id );
							$member->save();
							$ignore = true;
							break;

						case 'recurring_payment_suspended_due_to_max_failed_payment':
							// Recurring subscription was automatically suspended.
							$notes_txn = __( 'Paypal subscription profile has failed.', MS_TEXT_DOMAIN );
							$member->cancel_membership( $membership->id );
							$member->save();
							$ignore = true;
							break;

						case 'new_case':
							// New Dispute was filed for a payment.
							$status = MS_Model_Invoice::STATUS_DENIED;
							$ignore = true;
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
							$ignore = true;
							break;

						default:
							// Other event that we do not have a case for...
							$notes_txn = sprintf(
								__( 'Not handling txn_type: %s', MS_TEXT_DOMAIN ),
								$transaction_type
							);
							MS_Helper_Debug::log( $notes_txn );
							$ignore = true;
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
						$reason = 'PayPal did not verify this transaction: Unknown error';
						break;

					case 200 != $response['response']['code']:
						$reason = sprintf(
							'PayPal did not verify the transaction: Code %s',
							$response['response']['code']
						);
						break;

					case empty( $response['body'] ):
						$reason = 'PayPal did not verify this transaction: Empty response';
						break;

					case 'VERIFIED' != $response['body']:
						$reason = sprintf(
							'PayPal did not verify this transaction: "%s"',
							$response['body']
						);
						break;

					case $invoice->id != $invoice_id:
						$reason = sprintf(
							'PayPal gave us an invalid invoice_id: "%s"',
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
			} elseif ( $is_m1 ) {
				/*
				 * The payment belongs to an imported M1 subscription and could
				 * not be auto-matched.
				 * Do not return an error code, but also do not modify any
				 * invoice/subscription.
				 */
				$notes = 'M1 Payment detected. Manual matching required.';
				$ignore = false;
				$success = false;
			} else {
				if ( ! $payment_status && ! $transaction_type ) {
					$notes = 'Error: payment_status and txn_type not specified. Cannot process.';
				} elseif ( empty( $_POST['invoice'] ) && empty( $_POST['custom'] ) ) {
					$notes = 'Error: No invoice or custom data specified.';
				} else {
					$notes = 'Error: Missing POST variables. Identification is not possible.';
				}

				// PayPal did provide invalid details...
				status_header( 404 );
				MS_Helper_Debug::log( $notes );
			}
			$exit = true;
		}

		if ( $ignore && ! $success ) { $success = null; }

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