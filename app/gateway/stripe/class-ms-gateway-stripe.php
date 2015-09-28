<?php
/**
 * Stripe Gateway Integration.
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_Stripe extends MS_Gateway {

	const ID = 'stripe';

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
	protected $test_secret_key = '';

	/**
	 * Stripe Secret key (live).
	 *
	 * @since  1.0.0
	 * @var string $secret_key
	 */
	protected $secret_key = '';

	/**
	 * Stripe test publishable key (sandbox).
	 *
	 * @since  1.0.0
	 * @var string $test_publishable_key
	 */
	protected $test_publishable_key = '';

	/**
	 * Stripe publishable key (live).
	 *
	 * @since  1.0.0
	 * @var string $publishable_key
	 */
	protected $publishable_key = '';

	/**
	 * Instance of the shared stripe API integration
	 *
	 * @since  1.0.0
	 * @var MS_Gateway_Stripe_Api $api
	 */
	protected $_api = null;

	/**
	 * Initialize the object.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function after_load() {
		parent::after_load();
		$this->_api = MS_Factory::load( 'MS_Gateway_Stripe_Api' );

		$this->id = self::ID;
		$this->name = __( 'Stripe Single Gateway', MS_TEXT_DOMAIN );
		$this->group = 'Stripe';
		$this->manual_payment = true; // Recurring billed/paid manually
		$this->pro_rate = true;
	}

	/**
	 * Processes purchase action.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		$success = false;
		$note = '';
		$token = '';
		$external_id = '';
		$error = false;

		do_action(
			'ms_gateway_stripe_process_purchase_before',
			$subscription,
			$this
		);
		$this->_api->set_gateway( $this );

		$member = $subscription->get_member();
		$invoice = $subscription->get_current_invoice();

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
					$note = __( 'No payment for free membership', MS_TEXT_DOMAIN );
				} else {
					// Send request to gateway.
					$charge = $this->_api->charge(
						$customer,
						$invoice->total,
						$invoice->currency,
						$invoice->name
					);

					if ( true == $charge->paid ) {
						$invoice->pay_it( $this->id, $charge->id );
						$note = __( 'Payment successful', MS_TEXT_DOMAIN );
						$note .= ' - Token: ' . $token;
						$success = true;
					} else {
						$note = __( 'Stripe payment failed', MS_TEXT_DOMAIN );
					}
				}
			} catch ( Exception $e ) {
				$note = 'Stripe error: '. $e->getMessage();
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $subscription );
				MS_Helper_Debug::log( $note );
				$error = $e;
			}
		} else {
			$note = 'Stripe gateway token not found.';
			MS_Helper_Debug::log( $note );
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
			'ms_gateway_stripe_process_purchase',
			$invoice,
			$this
		);
	}

	/**
	 * Request automatic payment to the gateway.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 * @return bool True on success.
	 */
	public function request_payment( $subscription ) {
		$was_paid = false;
		$note = '';
		$external_id = '';

		do_action(
			'ms_gateway_stripe_request_payment_before',
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
						$note = __( 'No payment for free membership', MS_TEXT_DOMAIN );
					} else {
						$charge = $this->_api->charge(
							$customer,
							$invoice->total,
							$invoice->currency,
							$invoice->name
						);
						$external_id = $charge->id;

						if ( true == $charge->paid ) {
							$was_paid = true;
							$invoice->pay_it( $this->id, $external_id );
							$note = __( 'Payment successful', MS_TEXT_DOMAIN );
						} else {
							$note = __( 'Stripe payment failed', MS_TEXT_DOMAIN );
						}
					}
				} else {
					$note = "Stripe customer is empty for user $member->username";
					MS_Helper_Debug::log( $note );
				}
			} catch ( Exception $e ) {
				$note = 'Stripe error: '. $e->getMessage();
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $subscription );
				MS_Helper_Debug::log( $note );
			}
		} else {
			// Invoice was already paid earlier.
			$was_paid = true;
			$note = __( 'Invoice already paid', MS_TEXT_DOMAIN );
		}

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

		do_action(
			'ms_gateway_stripe_request_payment_after',
			$subscription,
			$was_paid,
			$this
		);

		return $was_paid;
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

		if ( MS_Gateway::MODE_LIVE == $this->mode ) {
			$publishable_key = $this->publishable_key;
		} else {
			$publishable_key = $this->test_publishable_key;
		}

		return apply_filters(
			'ms_gateway_stripe_get_publishable_key',
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

		if ( MS_Gateway::MODE_LIVE == $this->mode ) {
			$secret_key = $this->secret_key;
		} else {
			$secret_key = $this->test_secret_key;
		}

		return apply_filters(
			'ms_gateway_stripe_get_secret_key',
			$secret_key
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
			'ms_gateway_stripe_is_configured',
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
