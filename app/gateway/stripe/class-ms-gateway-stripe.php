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
 * Stripe Gateway Integration.
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Gateway_Stripe extends MS_Gateway {

	const ID = 'stripe';

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
	 * Gateway active status.
	 *
	 * @since 1.0.0
	 * @var string $active
	 */
	protected $active = false;

	/**
	 * Manual payment indicator.
	 *
	 * If the gateway does not allow automatic reccuring billing.
	 *
	 * @since 1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = false;

	/**
	 * Gateway allow Pro rating.
	 *
	 * @todo To be released in further versions.
	 * @since 1.0.0
	 * @var bool $pro_rate
	 */
	protected $pro_rate = true;

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
	 * Stripe test secret key (sandbox).
	 *
	 * @see https://support.stripe.com/questions/where-do-i-find-my-api-keys
	 *
	 * @since 1.0.0
	 * @var string $test_secret_key
	 */
	protected $test_secret_key;

	/**
	 * Stripe Secret key (live).
	 *
	 * @since 1.0.0
	 * @var string $secret_key
	 */
	protected $secret_key;

	/**
	 * Stripe test publishable key (sandbox).
	 *
	 * @since 1.0.0
	 * @var string $test_publishable_key
	 */
	protected $test_publishable_key;

	/**
	 * Stripe publishable key (live).
	 *
	 * @since 1.0.0
	 * @var string $publishable_key
	 */
	protected $publishable_key;


	/**
	 * Initialize the object.
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->name = __( 'Stripe Gateway', MS_TEXT_DOMAIN );
	}

	/**
	 * Processes purchase action.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $ms_relationship The related membership relationship.
	 */
	public function process_purchase( $ms_relationship ) {
		do_action(
			'ms_gateway_stripe_process_purchase_before',
			$ms_relationship,
			$this
		);

		$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );
		$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );

		if ( ! empty( $_POST['stripeToken'] ) ) {
			lib2()->array->strip_slashes( $_POST, 'stripeToken' );

			$token = $_POST['stripeToken'];
			$this->load_stripe_lib();
			$customer = $this->get_stripe_customer( $member );

			if ( empty( $customer ) ) {
				$customer = Stripe_Customer::create(
					array(
						'card' => $token,
						'email' => $member->email,
					)
				);
				$this->save_customer_id( $member, $customer->id );
			} else {
				$this->add_card( $member, $token );
				$customer->save();
			}

			if ( 0 == $invoice->total ) {
				// Free, just process.
				$invoice->changed();
			} else {
				// Send request to gateway.
				$charge = Stripe_Charge::create(
					array(
						'amount' => (int) $invoice->total * 100, // Amount in cents!
						'currency' => strtolower( $invoice->currency ),
						'customer' => $customer->id,
						'description' => $invoice->name,
					)
				);

				if ( true == $charge->paid ) {
					$invoice->pay_it( $this->id, $charge->id );
				}
			}
		} else {
			throw new Exception( __( 'Stripe gateway token not found.', MS_TEXT_DOMAIN ) );
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
	 * @since 1.0.0
	 * @param MS_Model_Relationship $ms_relationship The related membership relationship.
	 */
	public function request_payment( $ms_relationship ) {
		do_action(
			'ms_gateway_stripe_request_payment_before',
			$ms_relationship,
			$this
		);

		$member = $ms_relationship->get_member();
		$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );

		if ( MS_Model_Invoice::STATUS_PAID != $invoice->status ) {
			try {
				$this->load_stripe_lib();
				$customer = $this->get_stripe_customer( $member );

				if ( ! empty( $customer ) ) {
					if ( 0 == $invoice->total ) {
						$invoice->changed();
					} else {
						$charge = Stripe_Charge::create(
							array(
								'amount' => (int) $invoice->total * 100, // Amount in cents!
								'currency' => strtolower( $invoice->currency ),
								'customer' => $customer->id,
								'description' => $invoice->name,
							)
						);

						if ( true == $charge->paid ) {
							$invoice->pay_it( $this->id, $charge->id );
						}
					}
				} else {
					MS_Helper_Debug::log( "Stripe customer is empty for user $member->username" );
				}
			} catch ( Exception $e ) {
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $ms_relationship );
				MS_Helper_Debug::log( $e->getMessage() );
			}
		}

		do_action(
			'ms_gateway_stripe_request_payment_after',
			$ms_relationship,
			$this
		);
	}

	/**
	 * Get Member's Stripe Customer Object.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 * @param MS_Model_Member $member The member.
	 */
	protected function get_stripe_customer( $member ) {
		$customer_id = $this->get_customer_id( $member );
		$customer = null;

		if ( ! empty( $customer_id ) ) {
			$customer = Stripe_Customer::retrieve( $customer_id );
		}

		return apply_filters(
			'ms_gateway_stripe_get_stripe_customer',
			$customer,
			$member,
			$this
		);
	}

	/**
	 * Get Member's Stripe customer_id.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 * @param MS_Model_Member $member The member.
	 */
	protected function get_customer_id( $member ) {
		$customer_id = $member->get_gateway_profile( $this->id, 'customer_id' );

		return apply_filters(
			'ms_gateway_stripe_get_customer_id',
			$customer_id,
			$member,
			$this
		);
	}

	/**
	 * Save Stripe customer id.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Member $member The member.
	 * @param int $customer_id The stripe customer id to save.
	 */
	protected function save_customer_id( $member, $customer_id ) {
		$member->set_gateway_profile( $this->id, 'customer_id', $customer_id );
		$member->save();

		do_action(
			'ms_gateway_stripe_save_customer_id_after',
			$member,
			$this
		);
	}

	/**
	 * Save card info to user meta.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Member $member The member.
	 */
	protected function save_card_info( $member ) {
		$customer = $this->get_stripe_customer( $member );
		$card = $customer->cards->retrieve( $customer->default_card );

		$member->set_gateway_profile(
			$this->id,
			'card_exp',
			gmdate( 'Y-m-t', strtotime( "{$card->exp_year}-{$card->exp_month}-01" ) )
		);
		$member->set_gateway_profile( $this->id, 'card_num', $card->last4 );
		$member->save();

		do_action( 'ms_gateway_stripe_save_card_info_after', $member, $this );
	}

	/**
	 * Add card info to strip customer profile.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Member $member The member.
	 * @param string $token The stripe card token generated by the gateway.
	 */
	public function add_card( $member, $token ) {
		$this->load_stripe_lib();

		$customer = $this->get_stripe_customer( $member );
		$card = $customer->cards->create( array( 'card' => $token ) );
		$customer->default_card = $card->id;
		$customer->save();
		$this->save_card_info( $member );

		do_action(
			'ms_gateway_stripe_add_card_info_after',
			$member,
			$token,
			$this
		);
	}


	/**
	 * Load Stripe lib.
	 *
	 * @since 1.0.0
	 */
	protected function load_stripe_lib(){
		require_once MS_Plugin::instance()->dir . '/lib/stripe-php/lib/Stripe.php';

		$secret_key = $this->get_secret_key();
		Stripe::setApiKey( $secret_key );

		do_action( 'ms_gateway_stripe_load_stripe_lib_after', $this );
	}

	/**
	 * Get Stripe publishable key.
	 *
	 * @since 1.0.0
	 * @return string The Stripe API publishable key.
	 */
	public function get_publishable_key() {
		$publishable_key = null;

		if ( self::MODE_LIVE == $this->mode ) {
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
	 * @since 1.0.0
	 * @return string The Stripe API secret key.
	 */
	protected function get_secret_key() {
		$secret_key = null;

		if ( self::MODE_LIVE == $this->mode ) {
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
	 * @since 1.0.0
	 * @return boolean True if configured.
	 */
	public function is_configured() {
		$is_configured = true;

		if ( $this->is_live_mode() ) {
			$required = array( 'secret_key', 'publishable_key' );
		} else {
			$required = array( 'test_secret_key', 'test_publishable_key' );
		}

		foreach ( $required as $field ) {
			if ( empty( $this->$field ) ) {
				$is_configured = false;
				break;
			}
		}

		return apply_filters(
			'ms_gateway_stripe_is_configured',
			$is_configured
		);
	}
}
