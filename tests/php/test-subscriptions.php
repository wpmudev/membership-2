<?php

/**
 * Test subscription logic.
 */
class MS_Test_Subscriptions extends WP_UnitTestCase {

	// Runs before the first test
	static function setUpBeforeClass() {
		WP_UnitTestCase::setUpBeforeClass();
		require_once 'shared-setup.php';
	}

	// Runs before the each test
	function setUp() {
		parent::setUp();
		TData::reset();
	}

	/**
	 * General check that simply determines if the plugin was loaded at all.
	 */
	function test_simple_subscription() {
		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		$sub_id = $subscription->id;
		$this->assertFalse( empty( $sub_id ) );
		$this->assertEquals( $membership_id, $subscription->membership_id );
		$this->assertEquals( $user_id, $subscription->user_id );

		// Not paid yet, so it is pending
		$this->assertEquals( MS_Helper_Period::current_date(), $subscription->start_date );
		$this->assertEquals( '', $subscription->expire_date );
		$this->assertEquals( '', $subscription->trial_expire_date );
		$this->assertEquals( MS_Model_Relationship::STATUS_PENDING, $subscription->status, 'Pending status' );

		$invoice = MS_Model_Invoice::get_current_invoice( $subscription );
		$this->assertEquals( MS_Model_Invoice::STATUS_BILLED, $invoice->status, 'Invoice status' );

		// Paying will change the status
		$invoice->pay_it( 'stripe', 'stripe_ext_id_100' );
		$subscription = $invoice->get_subscription();
		$this->assertEquals( MS_Model_Invoice::STATUS_PAID, $invoice->status, 'Invoice status' );
		$this->assertEquals( MS_Model_Relationship::STATUS_ACTIVE, $subscription->status, 'Active status' );
		$this->assertEquals( MS_Helper_Period::current_date(), $subscription->start_date );
		$this->assertEquals( '', $subscription->expire_date );
		$this->assertEquals( '', $subscription->trial_expire_date );
	}
}