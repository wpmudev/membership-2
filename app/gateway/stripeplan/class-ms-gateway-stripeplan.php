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
 * Stripe Gateway Integration for repeated payments (payment plans).
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 2.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_Stripeplan extends MS_Gateway {

	const ID = 'stripeplan';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 2.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Reference to the default Stripe gateway (we reuse existing functions here)
	 *
	 * @since 2.0.0
	 * @var MS_Gateway_Stripe $stripe
	 */
	protected $stripe;

	/**
	 * Initialize the object.
	 *
	 * @since 2.0.0
	 */
	public function after_load() {
		parent::after_load();
		$this->stripe = MS_Factory::load( 'MS_Gateway_Stripe' );

		$this->id = self::ID;
		$this->name = __( 'Stripe Subscriptions Gateway', MS_TEXT_DOMAIN );
		$this->group = 'Stripe';
		$this->manual_payment = false;
		$this->pro_rate = true;
		$this->unsupported_payment_types = array(
			MS_Model_Membership::PAYMENT_TYPE_PERMANENT,
			MS_Model_Membership::PAYMENT_TYPE_FINITE,
			MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE,
		);

		$this->add_action( 'ms_saved_MS_Model_Membership', 'update_plans' );
		$this->add_action( 'ms_gateway_toggle_stripeplan', 'update_plans' );
	}

	/**
	 * Creates or updates the payment plan specified by the function parameter.
	 *
	 * @since  2.0.0
	 * @param  array $plan_data The plan-object containing all details for Stripe.
	 */
	protected function create_or_update_plan( $plan_data ) {
		$this->stripe->load_stripe_lib();

		$plan_id = $plan_data['id'];
		$all_plans = MS_Factory::get_transient( 'ms_stripeplan_plans' );
		$all_plans = lib2()->array->get( $all_plans );

		if ( ! isset( $all_plans[$plan_id] ) ) {
			try {
				$plan = Stripe_Plan::retrieve( $plan_id );
			} catch( Exception $e ) {
				// If the plan does not exist then stripe will throw an Exception.
				$plan = false;
			}
			$all_plans[$plan_id] = $plan;
		} else {
			$plan = $all_plans[$plan_id];
		}

		/*
		 * Stripe can only update the plan-name, so we have to delete and
		 * recreate the plan manually.
		 */
		if ( $plan ) {
			$plan->delete();
			$all_plans[$plan_id] = false;
		}

		if ( $plan_data['amount'] > 0 ) {
			$plan = Stripe_Plan::create( $plan_data );
			$all_plans[$plan_id] = $plan;
		}

		lib2()->debug->dump( $all_plans );
		MS_Factory::set_transient( 'ms_stripeplan_plans', $all_plans, HOUR_IN_SECONDS );
	}

	/**
	 * Checks all Memberships and creates/updates the payment plan on stripe if
	 * the membership changed since the plan was last changed.
	 *
	 * This function is called when the gateway is activated and after a
	 * membership was saved to database.
	 *
	 * @since  2.0.0
	 */
	public function update_plans() {
		if ( ! $this->active ) { return false; }

		// Get a list of all Memberships.
		$memberships = MS_Model_Membership::get_memberships();
		$settings = MS_Plugin::instance()->settings;

		foreach ( $memberships as $membership ) {
			$plan_data = array(
				'id' => 'ms-' . $membership->id,
				'amount' => 0,
			);

			if ( ! $membership->is_free()
				&& $membership->payment_type == MS_Model_Membership::PAYMENT_TYPE_RECURRING
			) {
				// Prepare the plan-data for Stripe.
				$trial_days = null;
				if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL )
					&& $membership->trial_period_enabled
				) {
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

				$plan_data['amount'] = absint( $membership->price * 100 );
				$plan_data['currency'] = $settings->currency;
				$plan_data['name'] = $membership->name;
				$plan_data['interval'] = $interval;
				$plan_data['interval_count'] = $interval_count;
				$plan_data['trial_period_days'] = $trial_days;
			}

			$this->create_or_update_plan( $plan_data );
		}
	}

	/**
	 * Processes purchase action.
	 *
	 * @since 2.0.0
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		return $this->stripe->process_purchase( $subscription );
	}

	/**
	 * Request automatic payment to the gateway.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 * @return bool True on success.
	 */
	public function request_payment( $subscription ) {
		return $this->stripe->request_payment( $subscription );
	}

	/**
	 * Add card info to strip customer profile.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param MS_Model_Member $member The member.
	 * @param string $token The stripe card token generated by the gateway.
	 */
	public function add_card( $member, $token ) {
		return $this->stripe->add_card( $member, $token );
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
			$publishable_key = $this->stripe->publishable_key;
		} else {
			$publishable_key = $this->stripe->test_publishable_key;
		}

		return apply_filters(
			'ms_gateway_stripeplan_get_publishable_key',
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
			$secret_key = $this->stripe->secret_key;
		} else {
			$secret_key = $this->stripe->test_secret_key;
		}

		return apply_filters(
			'ms_gateway_stripeplan_get_secret_key',
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
		$key_pub = $this->get_publishable_key();
		$key_sec = $this->get_secret_key();

		$is_configured = ! ( empty( $key_pub ) || empty( $key_sec ) );

		return apply_filters(
			'ms_gateway_stripeplan_is_configured',
			$is_configured
		);
	}

}
