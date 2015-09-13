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
	 * @param  MS_Model_Transactionlog $log Optional. A transaction log item
	 *         that will be updated instead of creating a new log entry.
	 */
	public function handle_return( $log = false ) {
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
		$ext_type = false;

		if ( ! empty( $_POST[ 'txn_type'] ) ) {
			$transaction_type = strtolower( $_POST[ 'txn_type'] );
		}
		if ( isset( $_POST['mc_gross'] ) ) {
			$amount = (float) $_POST['mc_gross'];
		} elseif ( isset( $_POST['mc_amount3'] ) ) {
			// mc_amount1 and mc_amount2 are for trial period prices.
			$amount = (float) $_POST['mc_amount3'];
		}
		if ( ! empty( $_POST[ 'payment_status'] ) ) {
			$payment_status = strtolower( $_POST[ 'payment_status'] );
		}
		if ( ! empty( $_POST['txn_id'] ) ) {
			$external_id = $_POST['txn_id'];
		}
		if ( ! empty( $_POST['mc_currency'] ) ) {
			$currency = $_POST['mc_currency'];
		}

		// Step 1: Find the invoice_id and determine if payment is M2 or M1.
		if ( $payment_status || $transaction_type ) {
			if ( ! empty( $_POST['invoice'] ) ) {
				// BEST CASE:
				// 'invoice' is set in all regular M2 subscriptions!
				$invoice_id = intval( $_POST['invoice'] );
			} elseif ( ! empty( $_POST['custom'] ) ) {
				// FALLBACK A:
				// Maybe it's an imported M1 subscription.
				$infos = explode( ':', $_POST['custom'] );
				if ( count( $infos ) > 2 ) {
					// $infos should contain [timestamp, user_id, sub_id, key]

					$m1_user_id = intval( $infos[1] );
					$m1_sub_id = intval( $infos[2] ); // Roughtly equals M2 membership->id.

					// M1 payments use the following type/status values.
					$pay_types = array( 'subscr_signup', 'subscr_payment' );
					$pay_stati = array( 'completed', 'processed' );

					if ( $m1_user_id > 0 && $m1_sub_id > 0 ) {
						if ( in_array( $transaction_type, $pay_types ) ) {
							$ext_type = 'm1';
						} elseif ( in_array( $payment_status, $pay_stati ) ) {
							$ext_type = 'm1';
						}
					}

					if ( 'm1' == $ext_type ) {
						$is_linked = false;

						// Seems to be a valid M1 payment:
						// Find the associated imported subscription!
						$subscription = MS_Model_Import::find_subscription(
							$m1_user_id,
							$m1_sub_id,
							'source'
						);

						if ( ! $subscription ) {
							$membership = MS_Model_Import::membership_by_source(
								$m1_sub_id
							);

							if ( $membership ) {
								$is_linked = true;
								$notes = sprintf(
									'Error: User is not subscribed to Membership %s.',
									$membership->id
								);
							}
						}

						$invoice_id = MS_Model_Import::find_invoice_by_subscription(
							$subscription
						);

						if ( ! $is_linked && ! $invoice_id ) {
							MS_Model_Import::need_matching( $m1_sub_id, 'm1' );
						}
					}
					// end if: 'm1' == $ext_type
				}
			} elseif ( ! empty( $_POST['btn_id'] && ! empty( $_POST['payer_email'] ) ) ) {
				// FALLBACK B:
				// Payment was made by a custom PayPal Payment button.
				$user = get_user_by( 'email', $_POST['payer_email'] );

				if ( $user && $user->ID ) {
					$ext_type = 'pay_btn';
					$is_linked = false;

					$subscription = MS_Model_Import::find_subscription(
						$user->ID,
						$_POST['btn_id'],
						'pay_btn'
					);

					if ( ! $subscription ) {
						$membership = MS_Model_Import::membership_by_matching(
							'pay_btn',
							$_POST['btn_id']
						);

						if ( $membership ) {
							$is_linked = true;
							$notes = sprintf(
								'Error: User is not subscribed to Membership %s.',
								$membership->id
							);
						}
					}

					$invoice_id = MS_Model_Import::find_invoice_by_subscription(
						$subscription
					);

					if ( ! $is_linked && ! $invoice_id ) {
						MS_Model_Import::need_matching( $_POST['btn_id'], 'pay_btn' );
					}
				} else {
					$notes = sprintf(
						'Error: Could not find user "%s".',
						$_POST['payer_email']
					);
				}
				// end if: 'pay_btn' == $ext_type
			}
		}

		// Step 2: If we have an invoice_id then process the payment.
		if ( $invoice_id ) {
			if ( $this->is_live_mode() ) {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			// PayPal post authenticity verification.
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
					switch ( $payment_status ) {
						// Successful payment
						case 'completed':
						case 'processed':
							$success = true;
							if ( $amount == $invoice->total ) {
								$notes .= __( 'Payment successful', MS_TEXT_DOMAIN );
							} else {
								$notes .= __( 'Payment registered, though amount differs from invoice.', MS_TEXT_DOMAIN );
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
							$notes_txn = __( 'PayPal Subscripton has been created.', MS_TEXT_DOMAIN );
							if ( 0 == $invoice->total ) {
								$success = true;
							} else {
								$ignore = true;
							}
							break;

						case 'subscr_modify':
							// Payment profile was modified
							$notes_txn = __( 'PayPal Subscription has been modified.', MS_TEXT_DOMAIN );
							$ignore = true;
							break;

						case 'recurring_payment_profile_canceled':
						case 'subscr_cancel':
							// Subscription was manually cancelled.
							$notes_txn = __( 'PayPal Subscription has been canceled.', MS_TEXT_DOMAIN );
							$member->cancel_membership( $membership->id );
							$member->save();
							$ignore = true;
							break;

						case 'recurring_payment_suspended':
							// Recurring subscription was manually suspended.
							$notes_txn = __( 'PayPal Subscription has been suspended.', MS_TEXT_DOMAIN );
							$member->cancel_membership( $membership->id );
							$member->save();
							$ignore = true;
							break;

						case 'recurring_payment_suspended_due_to_max_failed_payment':
							// Recurring subscription was automatically suspended.
							$notes_txn = __( 'PayPal Subscription has failed.', MS_TEXT_DOMAIN );
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
							 *   - after a one-time payment was made
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

				if ( $notes_pay ) {
					$notes .= ($notes ? ' | ' : '') . $notes_pay;
				}
				if ( $notes_txn ) {
					$notes .= ($notes ? ' | ' : '') . $notes_txn;
				}

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

					case ! $invoice->id:
						$reason = sprintf(
							'Specified invoice does not exist: "%s"',
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
				if ( ! $notes ) {
					$notes = 'Ignored: Missing POST variables. Redirect to Home-URL.';
				}
				MS_Helper_Debug::log( $notes );
				$redirect = MS_Helper_Utility::home_url( '/' );
				$ignore = true;
				$success = false;
			} elseif ( 'm1' == $ext_type ) {
				/*
				 * The payment belongs to an imported M1 subscription and could
				 * not be auto-matched.
				 * Do not return an error code, but also do not modify any
				 * invoice/subscription.
				 */
				$notes = 'M1 Payment detected. Manual matching required.';
				$ignore = false;
				$success = false;
			} elseif ( 'pay_btn' == $ext_type ) {
				/*
				 * The payment was made by a PayPal Payment button that was
				 * created in the PayPal account and not by M1/M2.
				 */
				$notes = 'PayPal Payment button detected. Manual matching required.';
				$ignore = false;
				$success = false;
			} else {
				// PayPal sent us a IPN notice about a non-Membership payment:
				// Ignore it, but add it to the logs.

				if ( ! $payment_status && ! $transaction_type ) {
					$notes = 'Ignored: payment_status and txn_type not specified. Cannot process.';
				} elseif ( empty( $_POST['invoice'] ) && empty( $_POST['custom'] ) ) {
					$notes = 'Ignored: No invoice or custom data specified.';
				} else {
					$notes = 'Ignored: Missing POST variables. Identification is not possible.';
				}

				MS_Helper_Debug::log( $notes );
				$ignore = true;
				$success = false;
			}
			$exit = true;
		}

		if ( $ignore && ! $success ) {
			$success = null;
			$notes .= ' [Irrelevant IPN call]';
		}

		if ( ! $log ) {
			do_action(
				'ms_gateway_transaction_log',
				self::ID, // gateway ID
				'handle', // request|process|handle
				$success, // success flag
				$subscription_id, // subscription ID
				$invoice_id, // invoice ID
				$amount, // charged amount
				$notes, // Descriptive text
				$external_id // External ID
			);

			if ( $redirect ) {
				wp_safe_redirect( $redirect );
				exit;
			}
			if ( $exit ) {
				exit;
			}
		} else {
			$log->invoice_id = $invoice_id;
			$log->subscription_id = $subscription_id;
			$log->amount = $amount;
			$log->description = $notes;
			$log->external_id = $external_id;
			if ( $success ) {
				$log->manual_state( 'ok' );
			} elseif ( $ignore ) {
				$log->manual_state( 'ignore' );
			}
			$log->save();
		}

		do_action(
			'ms_gateway_paypalstandard_handle_return_after',
			$this
		);

		if ( $log ) {
			return $log;
		}
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