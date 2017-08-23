<?php
/**
 * Stripe Gateway Integration for repeated payments (payment plans).
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_Stripeplan extends MS_Gateway {

	const ID = 'stripeplan';

	/**
	 * Gateway singleton instance.
	 *
	 * @since  1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Stripe test secret key (sandbox).
	 *
	 * @see https://support.stripe.com/questions/where-do-i-find-my-api-keys
	 *
	 * @since  1.0.0
	 * @var string $test_secret_key
	 */
	protected $test_secret_key = false;

	/**
	 * Stripe Secret key (live).
	 *
	 * @since  1.0.0
	 * @var string $secret_key
	 */
	protected $secret_key = false;

	/**
	 * Stripe test publishable key (sandbox).
	 *
	 * @since  1.0.0
	 * @var string $test_publishable_key
	 */
	protected $test_publishable_key = false;

	/**
	 * Stripe publishable key (live).
	 *
	 * @since  1.0.0
	 * @var string $publishable_key
	 */
	protected $publishable_key = false;

	/**
	 * Stripe Vendor Logo.
	 *
	 * @since  1.0.3.4
	 * @var string $vendor_logo
	 */
	protected $vendor_logo = '';

	/**
	 * Instance of the shared stripe API integration
	 *
	 * @since  1.0.0
	 * @var MS_Gateway_Stripe_Api $api
	 */
	protected $_api;

	/**
	 * Initialize the object.
	 *
	 * @since  1.0.0
	 */
	public function after_load() {
		parent::after_load();
		$this->_api = MS_Factory::load( 'MS_Gateway_Stripe_Api' );

		// If the gateway is initialized for the first time then copy settings
		// from the Stripe Single gateway.
		if ( false === $this->test_secret_key ) {
			$single = MS_Factory::load( 'MS_Gateway_Stripe' );
			$this->test_secret_key = $single->test_secret_key;
			$this->secret_key = $single->secret_key;
			$this->test_publishable_key = $single->test_publishable_key;
			$this->publishable_key = $single->publishable_key;
			$this->save();
		}

		$this->id = self::ID;
		$this->name = __( 'Stripe Subscriptions Gateway', 'membership2' );
		$this->group = 'Stripe';
		$this->manual_payment = false; // Recurring charged automatically
		$this->pro_rate = true;
		$this->unsupported_payment_types = array(
			MS_Model_Membership::PAYMENT_TYPE_PERMANENT,
			MS_Model_Membership::PAYMENT_TYPE_FINITE,
			MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE,
		);

		// Update all payment plans and coupons.
		$this->add_action(
			'ms_gateway_toggle_stripeplan',
			'update_stripe_data'
		);

		// Update a single payment plan.
		$this->add_action(
			'ms_saved_MS_Model_Membership',
			'update_stripe_data_membership'
		);

		// Update a single coupon.
		$this->add_action(
			'ms_saved_MS_Addon_Coupon_Model',
			'update_stripe_data_coupon'
		);

		$this->add_filter(
				'ms_model_pages_get_ms_page_url',
				'ms_model_pages_get_ms_page_url_cb',
				99, 4
		);
	}

	/**
	 * Force SSL when Stripe in Live mode
	 *
	 * @since  1.0.2.5
	 *
	 * @param String $url The modified or raw URL
	 * @param String $page_type Check if this is a membership page
	 * @param Bool $ssl If SSL enabled or not
	 * @param Int $site_id The ID of site
	 *
	 * @return String $url Modified or raw URL
	 */
	public function ms_model_pages_get_ms_page_url_cb( $url, $page_type, $ssl, $site_id ) {
		/**
		* Constant M2_FORCE_NO_SSL
		*
		* It's needed, if :
		*      - the user has no SSL
		*      - the user has SSL but doesn't want to force
		*      - The user has multiple gateways like Paypal and Stripe and doesn't want to force
		*
		* If the user has SSL certificate, this rule won't work
		*/
		if( ! defined( 'M2_FORCE_NO_SSL' ) ){
			if ( $this->active && $this->is_live_mode() ) {
				if( $page_type == MS_Model_Pages::MS_PAGE_MEMBERSHIPS || $page_type == MS_Model_Pages::MS_PAGE_REGISTER ) {
					$url = MS_Helper_Utility::get_ssl_url( $url );
				}
			}
		}

		return $url;
	}

	/**
	 * Creates the external Stripe-ID of the specified item.
	 *
	 * This ID takes the current WordPress Site-URL into account to avoid
	 * collissions when several Membership2 sites use the same stripe account.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  int $id The internal ID.
	 * @param  string $type The item type, e.g. 'plan' or 'coupon'.
	 * @return string The external Stripe-ID.
	 */
	static public function get_the_id( $id, $type = 'item' ) {
		static $Base = null;
		if ( null === $Base ) {
			$Base = get_option( 'site_url' );
		}

		$hash = strtolower( md5( $Base . $type . $id ) );
		$hash = lib3()->convert(
			$hash,
			'0123456789abcdef',
			'0123456789ABCDEFGHIJKLMNOPQRSTUVXXYZabcdefghijklmnopqrstuvxxyz'
		);
		$result = 'ms-' . $type . '-' . $id . '-' . $hash;
		return $result;
	}

	/**
	 * Checks all Memberships and creates/updates the payment plan on stripe if
	 * the membership changed since the plan was last changed.
	 *
	 * This function is called when the gateway is activated and after a
	 * membership was saved to database.
	 *
	 * @since  1.0.0
	 */
	public function update_stripe_data() {
		if ( ! $this->active ) { return false; }
		$this->_api->set_gateway( $this );

		// 1. Update all playment plans.
		$memberships = MS_Model_Membership::get_memberships();
		foreach ( $memberships as $membership ) {
			$this->update_stripe_data_membership( $membership );
		}

		// 2. Update all coupons (if Add-on is enabled)
		if ( MS_Addon_Coupon::is_active() ) {
			$coupons = MS_Addon_Coupon_Model::get_coupons();
			foreach ( $coupons as $coupon ) {
				$this->update_stripe_data_coupon( $coupon );
			}
		}
	}

	/**
	 * Creates or updates a single payment plan on Stripe.
	 *
	 * This function is called when the gateway is activated and after a
	 * membership was saved to database.
	 *
	 * @since  1.0.0
	 */
	public function update_stripe_data_membership( $membership ) {
		if ( ! $this->active ) { return false; }
		$this->_api->set_gateway( $this );

		$plan_data = array(
			'id' => self::get_the_id( $membership->id, 'plan' ),
			'amount' => 0,
		);

		if ( ! $membership->is_free()
			&& $membership->payment_type == MS_Model_Membership::PAYMENT_TYPE_RECURRING
		) {
			// Prepare the plan-data for Stripe.
			$trial_days = null;
			if ( $membership->has_trial() ) {
				$trial_days = MS_Helper_Period::get_period_in_days(
					$membership->trial_period_unit,
					$membership->trial_period_type
				);
			}

			$interval = 'day';
			$max_count = 365;
			switch ( $membership->pay_cycle_period_type ) {
				case MS_Helper_Period::PERIOD_TYPE_WEEKS:
					$interval = 'week';
					$max_count = 52;
					break;

				case MS_Helper_Period::PERIOD_TYPE_MONTHS:
					$interval = 'month';
					$max_count = 12;
					break;

				case MS_Helper_Period::PERIOD_TYPE_YEARS:
					$interval = 'year';
					$max_count = 1;
					break;
			}

			$interval_count = min(
				$max_count,
				$membership->pay_cycle_period_unit
			);

			$settings = MS_Plugin::instance()->settings;
			$plan_data['amount'] = absint( $membership->price * 100 );
			$plan_data['currency'] = $settings->currency;
			$plan_data['name'] = $membership->name;
			$plan_data['interval'] = $interval;
			$plan_data['interval_count'] = $interval_count;
			$plan_data['trial_period_days'] = $trial_days;

			// Check if the plan needs to be updated.
			$serialized_data = json_encode( $plan_data );
			$temp_key = substr( 'ms-stripe-' . $plan_data['id'], 0, 45 );
			$temp_data = MS_Factory::get_transient( $temp_key );

			if ( $temp_data != $serialized_data ) {
				MS_Factory::set_transient(
					$temp_key,
					$serialized_data,
					HOUR_IN_SECONDS
				);

				$this->_api->create_or_update_plan( $plan_data );
			}
		}
	}

	/**
	 * Creates or updates a single coupon on Stripe.
	 *
	 * This function is called when the gateway is activated and after a
	 * coupon was saved to database.
	 *
	 * @since  1.0.0
	 */
	public function update_stripe_data_coupon( $coupon ) {
		if ( ! $this->active ) { return false; }
		$this->_api->set_gateway( $this );

		$settings = MS_Plugin::instance()->settings;
		$duration = 'forever';
		$percent_off = null;
		$amount_off = null;

		if ( MS_Addon_Coupon_Model::TYPE_VALUE == $coupon->discount_type ) {
			$amount_off = absint( $coupon->discount * 100 );
		} else {
			$percent_off = $coupon->discount;
		}

		$coupon_data = array(
			'id' => self::get_the_id( $coupon->id, 'coupon' ),
			'duration' => $duration,
			'amount_off' => $amount_off,
			'percent_off' => $percent_off,
			'currency' => $settings->currency,
		);

		// Check if the plan needs to be updated.
		$serialized_data = json_encode( $coupon_data );
		$temp_key = substr( 'ms-stripe-' . $coupon_data['id'], 0, 45 );
		$temp_data = MS_Factory::get_transient( $temp_key );

		if ( $temp_data != $serialized_data ) {
			MS_Factory::set_transient(
				$temp_key,
				$serialized_data,
				HOUR_IN_SECONDS
			);

			$this->_api->create_or_update_coupon( $coupon_data );
		}
	}

	/**
	 * Process Stripe WebHook requests
	 *
	 * @since 1.0.4
	 */
	public function handle_webhook() {
		do_action(
			'ms_gateway_handle_stripe_webhook_before',
			$this
		);

		$this->_api->set_gateway( $this );

		$secret_key = $this->get_secret_key();
		Stripe::setApiKey( $secret_key );

		// retrieve the request's body and parse it as JSON
		$body = @file_get_contents( 'php://input' );

		$this->log( $body );
		// grab the event information
		$event_json = json_decode( $body );

		if( isset( $event_json->id ) ) {
			try {
				$event_id 	= $event_json->id;
				$event 		= Stripe_Event::retrieve( $event_id );
				$log 		= false;
				$invoice 	= false;
				if ( $event ) {
					if ( isset( $event->data->object->id ) && $stripe_invoice ) {
						$stripe_customer 	= Stripe_Customer::retrieve( $stripe_invoice->customer );
						if ( $stripe_customer ) {
							$email 	= $stripe_customer->email;
							
							if ( !function_exists( 'get_user_by' ) ) {
								include_once( ABSPATH . 'wp-includes/pluggable.php' );
							}
	
							$user 	= get_user_by( 'email', $email );
							$member = MS_Factory::load( 'MS_Model_Member', $user->ID );
							$success = false;
							if ( $member ) {
								
								foreach ( $member->subscriptions as $subscription ){
									if ( $subscription ) {
										$membership = $subscription->get_membership();
										switch ( $event->type ){
											case 'invoice.payment_succeeded' :
												$invoice_id = $subscription->first_unpaid_invoice();
												if ( $invoice_id ) {
													$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );
													$invoice->ms_relationship_id 	= $subscription->id;
													$invoice->membership_id 		= $membership->id;
													if ( 0 == $invoice->total ) {
														// Free, just process.
														$invoice->changed();
														$success = true;
														$notes = __( 'No payment required for free membership', 'membership2' );
													} else {
														$stripe_sub = $this->_api->get_subscription(
															$stripe_customer,
															$membership
														);
														$reference = $event_id;
														if ( $stripe_sub ) {
															$reference = $stripe_sub->id;
															$this->cancel_if_done( $subscription, $stripe_sub );
														}
														$notes = $this->get_description_for_sub( $stripe_sub );
														$notes .= __( 'Payment successful', 'membership2' );
														$success = true;
														$invoice->status = MS_Model_Invoice::STATUS_PAID;
														$invoice->pay_it( self::ID, $reference );
														
														$log = true;
													}
													$invoice->add_notes( $notes );
													$invoice->save();
												}else {
													$this->log( 'Did not get invoice');
												}
											break;
											case 'customer.subscription.deleted' :
											case 'invoice.payment_failed' :
												$notes .= __( 'Membership cancelled via webhook', 'membership2' );
												$success = false;
												$member->cancel_membership( $membership->id );
												$member->save();
											break;
											default : 
												$notes = sprintf( __( 'Stripe webhook "%s" received', 'membership2' ), $event->type );
											break;
										}
									}
									
									
									if ( $log && $invoice ) {
										do_action(
											'ms_gateway_transaction_log',
											self::ID, // gateway ID
											'handle', // request|process|handle
											$success, // success flag
											$subscription->id, // subscription ID
											$invoice->id, // invoice ID
											$invoice->total, // charged amount
											$notes, // Descriptive text
											'' // External ID
										);
									}
								}
							} else {
								$this->log( 'Did not get member');
							}
						} else {
							$this->log( 'Did not get customer');
						}
					} else {
						$this->log( 'Did not find stripe invoice' );
					}
				}
			} catch (Exception $e) {
				$note = 'Stripe error webhook: '. $e->getMessage();
				$this->log( $note );
				MS_Helper_Debug::debug_log( $note );
				$error = $e;
			}
		}

		do_action(
			'ms_gateway_handle_stripe_webhook_after',
			$this
		);
	}

	/**
	 * Processes purchase action.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		$success = false;
		$note = '';
		$external_id = '';
		$error = false;

		do_action(
			'ms_gateway_stripeplan_process_purchase_before',
			$subscription,
			$this
		);
		$this->_api->set_gateway( $this );

		$member = $subscription->get_member();
		$invoice = $subscription->get_current_invoice();
		$token = '-';

		if ( ! empty( $_POST['stripeToken'] ) ) {
			lib3()->array->strip_slashes( $_POST, 'stripeToken' );

			$token = $_POST['stripeToken'];
			$external_id = $token;
			try {
				$customer = $this->_api->get_stripe_customer( $member, $token );

				if ( 0 == $invoice->total ) {
					// Free, just process.
					$invoice->changed();
					$success = true;
					$note = __( 'No payment required for free membership', 'membership2' );
				} else {
					// Get or create the subscription.
					$stripe_sub = $this->_api->subscribe(
						$customer,
						$invoice
					);

					$note = $this->get_description_for_sub( $stripe_sub );

					if ( 'active' == $stripe_sub->status || 'trialing' == $stripe_sub->status ) {
						$success = true;
						$invoice->pay_it( self::ID, $stripe_sub->id );
						$this->cancel_if_done( $subscription, $stripe_sub );
					}
				}
			} catch ( Exception $e ) {
				$note = 'Stripe error: '. $e->getMessage();
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $subscription );
				MS_Helper_Debug::debug_log( $note );
				$error = $e;
			}
		} else {
			$note = 'Stripe gateway token not found.';
			MS_Helper_Debug::debug_log( $note );
		}

		do_action(
			'ms_gateway_transaction_log',
			self::ID, // gateway ID
			'process', // request|process|handle
			$success, // success flag
			$subscription->id, // subscription ID
			$invoice->id, // invoice ID
			$invoice->total, // charged amount
			$note, // Descriptive text
			$external_id // External ID
		);

		if ( $error ) {
			throw $e;
		}

		return apply_filters(
			'ms_gateway_stripeplan_process_purchase',
			$invoice,
			$this
		);
	}

	/**
	 * Check if the subscription is still active.
	 * Only used in tests
	 *
	 * @since  1.0.0
	 * @since 1.0.4
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 * @return bool True on success.
	 */
	public function request_payment( $subscription ) {
		if ( defined( 'IS_UNIT_TEST' ) && IS_UNIT_TEST ) {
			$was_paid = false;
			$note = '';
			$external_id = '';
	
			do_action(
				'ms_gateway_stripeplan_request_payment_before',
				$subscription,
				$this
			);
			$this->_api->set_gateway( $this );
	
			$member = $subscription->get_member();
			$invoice = $subscription->get_current_invoice();
	
			if ( ! $invoice->is_paid() ) {
				try {
					$customer = $this->_api->find_customer( $member );
	
					if ( ! empty( $customer ) ) {
						if ( 0 == $invoice->total ) {
							$invoice->changed();
							$success = true;
							$note = __( 'No payment required for free membership', 'membership2' );
						} else {
							// Get or create the subscription.
							$stripe_sub = $this->_api->subscribe(
								$customer,
								$invoice
							);
							$external_id = $stripe_sub->id;
	
							$note = $this->get_description_for_sub( $stripe_sub );
	
							if ( 'active' == $stripe_sub->status || 'trialing' == $stripe_sub->status ) {
								$was_paid = true;
								$invoice->pay_it( self::ID, $external_id );
								$this->cancel_if_done( $subscription, $stripe_sub );
							}
						}
					} else {
						MS_Helper_Debug::debug_log( "Stripe customer is empty for user $member->username" );
					}
				} catch ( Exception $e ) {
					$note = 'Stripe error: '. $e->getMessage();
					MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $subscription );
					MS_Helper_Debug::debug_log( $note );
				}
			} else {
				// Invoice was already paid earlier.
				$was_paid = true;
			}
	
			do_action(
				'ms_gateway_stripeplan_request_payment_after',
				$subscription,
				$was_paid,
				$this
			);
	
			do_action(
				'ms_gateway_transaction_log',
				self::ID, // gateway ID
				'request', // request|process|handle
				$was_paid, // success flag
				$subscription->id, // subscription ID
				$invoice->id, // invoice ID
				$invoice->total, // charged amount
				$note, // Descriptive text
				$external_id // External ID
			);
	
			return $was_paid;
		} else {
			do_action(
				'ms_gateway_request_payment',
				$subscription,
				$this
			);
	
			// Default to "Payment successful"
			return true;
		}
	}

	/**
	 * Returns a description for the specified stripe subscription.
	 * Also populates some $_POST fields to store additional details in the
	 * transaction logs.
	 *
	 * @since  1.0.2.4
	 * @param  StripeSubscription $stripe_sub
	 * @return string
	 */
	protected function get_description_for_sub( $stripe_sub ) {
		$note = '';

		switch ( $stripe_sub->status ) {
			case 'trialing': // During trial period.
			case 'active':
				$note = __( 'Payment successful', 'membership2' );
				break;

			case 'past_due':
				$note = __( 'Stripe payment failed (payment is past due)', 'membership2' );
				break;

			case 'canceled':
				$note = __( 'Stripe subscription canceled', 'membership2' );
				break;

			case 'unpaid':
				$note = __( 'Payment failed, retry-attempts exhausted', 'membership2' );
				break;

			default:
				$note = sprintf(
					__( 'Stripe subscription is "%s"', 'membership2' ),
					$stripe_sub->status
				);
				break;
		}

		$_POST['API Response: id'] = $stripe_sub->id;
		$_POST['API Response: status'] = $stripe_sub->status;
		$_POST['API Response: canceled_at'] = $stripe_sub->canceled_at;
		$_POST['API Response: current_period_start'] = $stripe_sub->current_period_start;
		$_POST['API Response: current_period_end'] = $stripe_sub->current_period_end;
		$_POST['API Response: ended_at'] = $stripe_sub->ended_at;
		$_POST['API Response: start'] = $stripe_sub->start;
		$_POST['API Response: plan-id'] = $stripe_sub->plan->id;
		$_POST['API Response: plan-name'] = $stripe_sub->plan->name;

		return $note;
	}

	/**
	 * Checks if a subscription has reached the maximum paycycle repetitions.
	 * If the last paycycle was paid then the subscription is cancelled.
	 *
	 * @since  1.0.0
	 * @internal Called by process_purchase() and request_payment()
	 *
	 * @param  MS_Model_Relationship $subscription
	 * @param  M2_Stripe_Subscription $stripe_sub
	 */
	protected function cancel_if_done( $subscription, $stripe_sub ) {
		$membership = $subscription->get_membership();

		if ( $membership->pay_cycle_repetitions < 1 ) {
			return;
		}

		$payments = $subscription->get_payments();
		if ( count( $payments ) < $membership->pay_cycle_repetitions ) {
			return;
		}

		$stripe_sub->cancel(
			array( 'at_period_end' => true )
		);
	}

	/**
	 * When a member cancels a subscription we need to notify Stripe to also
	 * cancel the Stripe subscription.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 */
	public function cancel_membership( $subscription ) {
		parent::cancel_membership( $subscription );
		$this->_api->set_gateway( $this );

		$customer 	= $this->_api->find_customer( $subscription->get_member() );
		$membership = $subscription->get_membership();
		$stripe_sub = false;

		if ( $customer ) {
			$stripe_sub = $this->_api->get_subscription(
				$customer,
				$membership
			);
		}

		if ( $stripe_sub ) {
			$stripe_sub->cancel(
				array( 'at_period_end' => true )
			);
		}
	}

	/**
	 * Get Stripe publishable key.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return string The Stripe API publishable key.
	 */
	public function get_publishable_key() {
		$publishable_key = null;

		if ( $this->is_live_mode() ) {
			$publishable_key = $this->publishable_key;
		} else {
			$publishable_key = $this->test_publishable_key;
		}

		return apply_filters(
			'ms_gateway_stripeplan_get_publishable_key',
			$publishable_key
		);
	}

	/**
	 * Get Stripe secret key.
	 *
	 * @since  1.0.0
	 * @internal The secret key should not be used outside this object!
	 *
	 * @return string The Stripe API secret key.
	 */
	public function get_secret_key() {
		$secret_key = null;

		if ( $this->is_live_mode() ) {
			$secret_key = $this->secret_key;
		} else {
			$secret_key = $this->test_secret_key;
		}

		return apply_filters(
			'ms_gateway_stripeplan_get_secret_key',
			$secret_key
		);
	}

	/**
	 * Get Stripe Vendor Logo.
	 *
	 * @since  1.0.3.4
	 * @api
	 *
	 * @return string The Stripe Vendor Logo.
	 */

	public function get_vendor_logo() {
		$vendor_logo = null;

		$vendor_logo = $this->vendor_logo;

		return apply_filters(
			'ms_gateway_stripe_get_vendor_logo',
			$vendor_logo
		);
	}	

	/**
	 * Verify required fields.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return boolean True if configured.
	 */
	public function is_configured() {
		$key_pub = $this->get_publishable_key();
		$key_sec = $this->get_secret_key();

		$is_configured = ! ( empty( $key_pub ) || empty( $key_sec ) );

		return apply_filters(
			'ms_gateway_stripeplan_is_configured',
			$is_configured
		);
	}

	/**
	 * Auto-update some fields of the _api instance if required.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param string $key Field name.
	 * @param mixed $value Field value.
	 */
	public function __set( $key, $value ) {
		switch ( $key ) {
			case 'test_secret_key':
			case 'test_publishable_key':
			case 'secret_key':
			case 'publishable_key':
				$this->_api->$key = $value;
				break;
		}

		if ( property_exists( $this, $key ) ) {
			$this->$key = $value;
		}
	}

}
