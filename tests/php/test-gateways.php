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
		$gateway2->update_stripe_data();

		$this->assertTrue( class_exists( 'Stripe' ) );
	}

	/**
	 * Tests the Stripe Subscription gateway
	 * @test
	 */
	function stripeplan_subscription() {
	}

}