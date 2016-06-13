<?php

/**
 * Test payment gateways
 */
class MS_Test_Gateways extends WP_UnitTestCase {

	/**
	 * Runs before the first test
	 * @beforeClass
	 */
	static function setup_once() {
		WP_UnitTestCase::setUpBeforeClass();
		require_once 'shared-setup.php';
	}

	/**
	 * Runs before the each test
	 * @before
	 */
	function setup() {
		parent::setUp();
		TData::reset();
	}

	/**
	 * Make sure the gateway configurations are all set up correctly prior to
	 * running the other tests.
	 * @test
	 */
	function stripe_config() {
		// Stripe Single
		$gateway1 = MS_Model_Gateway::factory( MS_Gateway_Stripe::ID );

		$this->assertEquals( MS_Gateway::MODE_SANDBOX, $gateway1->mode );
		$this->assertTrue( $gateway1->active );
		$this->assertTrue( $gateway1->is_configured() );

		// Stripe Subscriptions
		$gateway2 = MS_Model_Gateway::factory( MS_Gateway_Stripeplan::ID );

		$this->assertEquals( MS_Gateway::MODE_SANDBOX, $gateway2->mode );
		$this->assertTrue( $gateway2->active );
		$this->assertTrue( $gateway2->is_configured() );

		$this->assertTrue( class_exists( 'M2_Stripe' ) );
	}

	/**
	 * Tests the Stripe Subscription gateway
	 * @test
	 */
	function stripeplan_subscription() {
		$gateway = MS_Model_Gateway::factory( MS_Gateway_Stripeplan::ID );
		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'recurring' );
		$subscription = TData::subscribe( $user_id, $membership_id );
		$controller = MS_Factory::load( 'MS_Controller_Gateway' );
		$gateway->update_stripe_data();

		$data = array(
			'card' => array(
				'number' => '4242424242424242',
				'exp_month' => 12,
				'exp_year' => date( 'Y' ) + 1,
				'cvc' => '314',
			),
		);
		$res = M2_Stripe_Token::create( $data );
		$token = $res->id;

		$form_data = array(
			'_wpnonce' => wp_create_nonce( $gateway->id . '_' . $subscription->id ),
			'gateway' => $gateway->id,
			'ms_relationship_id' => $subscription->id,
			'step' => 'process_purchase',
			'stripeToken' => $token,
			'stripeTokenType' => 'card',
			'stripeEmail' => 'editor@local.dev',
		);
		$_POST = $form_data;
		$_REQUEST = $_POST;

		// Right now the subscription must have status PENDING
		$this->assertEquals( MS_Model_Relationship::STATUS_PENDING, $subscription->status );

		/*
		 * This function processes the purchase and will set the subscription
		 * to active.
		 */
		$controller->process_purchase();

		// Check the subscription status.
		$this->assertEquals( MS_Model_Relationship::STATUS_ACTIVE, $subscription->status );
		$this->assertEquals( 1, count( $subscription->get_payments() ) );

		// Modify the expiration date: Expire date grants 1 extra day!
		$today = date( 'Y-m-d' );
		$yesterday = date( 'Y-m-d', time() - 86400 );
		$subscription->start_date = $yesterday;
		$subscription->expire_date = $today;
		$this->assertEquals( $today, $subscription->expire_date );
		$this->assertEquals( 1, $subscription->get_remaining_period() );

		// Make sure, no payment is collected!
		$subscription->check_membership_status();
		$this->assertEquals( 1, count( $subscription->get_payments() ) );

		// Modify the expiration date: Trigger next payment
		$subscription->expire_date = $yesterday;
		$this->assertEquals( $yesterday, $subscription->expire_date );
		$this->assertEquals( 0, $subscription->get_remaining_period() );

		// Trigger next payment and validate it.
		$subscription->check_membership_status();
		$this->assertEquals( 2, count( $subscription->get_payments() ) );

		// Modify the expiration date to trigger another payment.
		$subscription->expire_date = $yesterday;
		$this->assertEquals( $yesterday, $subscription->expire_date );
		$this->assertEquals( 0, $subscription->get_remaining_period() );

		// Trigger next payment and validate it.
		// THIS TIME NO PAYMENT SHOULD BE MADE because paycycle_repetitions = 2!
		$subscription->check_membership_status();
		$this->assertEquals( 2, count( $subscription->get_payments() ) );

		// Also the subscription should be cancelled at stripe now.
		$customer_id = $subscription->get_member()->get_gateway_profile(
			MS_Gateway_Stripe_Api::ID,
			'customer_id'
		);
		$customer = M2_Stripe_Customer::retrieve( $customer_id );
		$invoice = $subscription->get_previous_invoice();
		$stripe_sub_id = $invoice->external_id;
		$stripe_sub = $customer->subscriptions->retrieve( $stripe_sub_id );
		$this->assertEquals( 'active', $stripe_sub->status );
		$this->assertTrue( $stripe_sub->cancel_at_period_end );

		// Clean up.
		$customer->delete();
	}
}
