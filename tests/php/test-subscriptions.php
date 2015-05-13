<?php

/**
 * Test subscription logic.
 */
class MS_Test_Subscriptions extends WP_UnitTestCase {

	/**
	 * Runs before the first test.
	 * @beforeClass
	 */
	static function setup_once() {
		WP_UnitTestCase::setUpBeforeClass();
		require_once 'shared-setup.php';
	}

	/**
	 * Runs before the each test.
	 * @before
	 */
	function setup() {
		parent::setUp();
		TData::reset();
	}

	/**
	 * General check that simply determines if the plugin was loaded at all.
	 * @xxtest
	 */
	function simple_subscription() {
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
		$this->assertEquals( MS_Model_Relationship::STATUS_PENDING, $subscription->status, 'Pending status' );

		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( $invoice->ms_relationship_id, $subscription->id );

		$invoice_subscription = $invoice->get_subscription();
		$this->assertEquals( $subscription, $invoice_subscription );

		// Paying will change the status
		$invoice->pay_it( 'admin', '' );
		$this->assertEquals( MS_Model_Relationship::STATUS_ACTIVE, $subscription->status, 'Active status' );
		$this->assertEquals( MS_Helper_Period::current_date(), $subscription->start_date );
		$this->assertEquals( '', $subscription->expire_date );
	}

	/**
	 * Check simple_subscription with the Trial Add-on.
	 * @test
	 */
	function simple_subscription_addon_trial() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$this->simple_subscription();
	}

	/**
	 * Check simple_subscription with the Trial Add-on.
	 * @test
	 */
	function simple_subscription_compare_addon_trial() {
		TData::disable_addon( MS_Model_Addon::ADDON_TRIAL );

		// Both subscriptions and invoices should be identical

		$user_id = TData::id( 'user', 'editor' );

		$membership1_id = TData::id( 'membership', 'simple' );
		$subscription1 = TData::subscribe( $user_id, $membership1_id );
		$invoice1 = $subscription1->get_current_invoice();

		$membership2_id = TData::id( 'membership', 'simple-trial' );
		$subscription2 = TData::subscribe( $user_id, $membership2_id );
		$invoice2 = $subscription2->get_current_invoice();

		// Each object has some unique fields, for this test we manipulate these
		// data to see if all other properties are equal.
		$subscription2->_factory_id = $subscription1->_factory_id = 'demo';
		$subscription2->id = $subscription1->id = '0';
		$subscription2->membership_id = $subscription1->membership_id = '0';
		$subscription2->membership = $subscription1->membership = array();
		$subscription2->name = $subscription1->name = '...';
		$subscription2->description = $subscription1->description = '...';
		$subscription2->post_modified = $subscription1->post_modified = '0';

		$invoice2->_factory_id = $invoice1->_factory_id = 'demo';
		$invoice2->id = $invoice1->id = '0';
		$invoice2->membership_id = $invoice1->membership_id = '0';
		$invoice2->ms_relationship_id = $invoice1->ms_relationship_id = '0';
		$invoice2->name = $invoice1->name = '0';
		$invoice2->price_date = $invoice1->price_date = '0';
		$invoice2->post_modified = $invoice1->post_modified = '0';

		$this->assertEquals( $subscription1, $subscription2 );
		$this->assertEquals( $invoice1, $invoice2 );
	}

	/**
	 * Check simple membership with trial period.
	 * @test
	 */
	function simple_trial_subscription() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Not paid yet, so it is pending

		$trial_end = MS_Helper_Period::add_interval( 14, 'days' );
		$start_date = MS_Helper_Period::current_date();
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( '', $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );
		$this->assertEquals( MS_Model_Relationship::STATUS_PENDING, $subscription->status, 'Pending status' );

		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( $invoice->ms_relationship_id, $subscription->id );
		$this->assertTrue( $invoice->uses_trial );

		$invoice_subscription = $invoice->get_subscription();
		$this->assertEquals( $subscription, $invoice_subscription );

		// Paying a trial subscription with the FREE gateway will start the trial.

		$invoice->pay_it( 'free', '' );
		$this->assertEquals( MS_Model_Relationship::STATUS_TRIAL, $subscription->status, 'Trial status' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( $trial_end, $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );
	}

	/**
	 * Make sure access is granted even after cancellation.
	 * @test
	 */
	function simple_trial_cancellation_access() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );
		$trial_end = MS_Helper_Period::add_interval( 14, 'days' );
		$invoice = $subscription->get_current_invoice();
		$invoice->pay_it( 'free', '' );

		// Now our subscription is in active TRIAL status.

		$this->assertEquals( MS_Model_Relationship::STATUS_TRIAL, $subscription->status, 'Trial status' );
		$this->assertEquals( $trial_end, $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );

		// Now cancel the subscription and check if status and expire date are correct.

		$subscription->cancel_membership();
		$this->assertEquals( MS_Model_Relationship::STATUS_CANCELED, $subscription->status, 'Cancel status' );
		$this->assertEquals( $trial_end, $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );
	}

	/**
	 * Checks if the invoice-counter is correctly increased with each payment.
	 * @test
	 */
	function validate_invoice_counter() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// No invoice was paid yet, to the current invoice counter must be 1.
		$this->assertEquals( 1, $subscription->current_invoice_number );

		$invoice1 = $subscription->get_current_invoice();
		$this->assertEquals( 1, $invoice1->invoice_number );

		// The trial invoice is not yet paid (free gateway sets it to billed)
		// -> so the inovice counter must still be 1
		$invoice1->pay_it( 'free', '' );
		$this->assertEquals( 1, $invoice1->invoice_number );
		$this->assertEquals( 1, $subscription->current_invoice_number );
		$invoice2 = $subscription->get_current_invoice();
		$this->assertEquals( $invoice1, $invoice2 );

		// After the next (real) payment the invoice counter will be increased.
		$invoice2->pay_it( 'stripe', 'external_100' );
		$this->assertEquals( 2, $subscription->current_invoice_number );
		$invoice3 = $subscription->get_current_invoice();
		$this->assertNotEquals( $invoice1, $invoice3 );
		$this->assertEquals( 2, $invoice3->invoice_number );

		// After the next (real) payment the invoice counter will be increased.
		$invoice3->pay_it( 'stripe', 'external_200' );
		$this->assertEquals( 3, $subscription->current_invoice_number );
		$invoice4 = $subscription->get_current_invoice();
		$this->assertNotEquals( $invoice1, $invoice4 );
		$this->assertEquals( 3, $invoice4->invoice_number );
	}

	/**
	 * Create a trial subscription and activate it with a payment.
	 * @test
	 */
	function simple_trial_activation_access() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		$start_date = MS_Helper_Period::current_date();
		$trial_end = MS_Helper_Period::add_interval( 14, 'days' );

		// Activate the TRIAL status.

		$invoice1 = $subscription->get_current_invoice();
		$invoice1->pay_it( 'free', '' );
		$this->assertEquals( MS_Model_Relationship::STATUS_TRIAL, $subscription->status, 'Trial status' );

		// Paying with a non-free gateway activates the membership

		$invoice2 = $subscription->get_current_invoice();
		$invoice2->pay_it( 'stripe', 'external_123' );
		$this->assertEquals( MS_Model_Relationship::STATUS_ACTIVE, $subscription->status, 'Active status' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( '', $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );
	}

	/**
	 * Check limited (finite) membership with trial period.
	 * @test
	 */
	function limited_trial_subscription_date_test() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'limited-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// These are the expected start and expire dates.
		$start_date = MS_Helper_Period::current_date();
		$trial_end = MS_Helper_Period::add_interval( 14, 'days', $start_date );
		$limit_end1 = MS_Helper_Period::add_interval( 28, 'days', $trial_end );
		$limit_end2 = MS_Helper_Period::add_interval( 28, 'days', $limit_end1 );

		// Pending subscription has only start date.

		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( '', $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );

		// Activate trial status to see the changed dates

		$invoice = $subscription->get_current_invoice();
		$invoice->pay_it( 'free', '' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( $trial_end, $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );

		// Check the dates after the FIRST payment

		$invoice = $subscription->get_current_invoice();
		$invoice->pay_it( 'stripe', 'external_100' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( $limit_end1, $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );

		// Check the dates after the SECOND payment

		$invoice = $subscription->get_current_invoice();
		$invoice->pay_it( 'stripe', 'external_200' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( $limit_end2, $subscription->expire_date );
		$this->assertEquals( $trial_end, $subscription->trial_expire_date );
	}

	/**
	 * Check limited (finite) membership without trial period.
	 * @test
	 */
	function limited_subscription_date_test() {
		TData::disable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'limited-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// These are the expected start and expire dates.
		$start_date = MS_Helper_Period::current_date();
		$limit_end1 = MS_Helper_Period::add_interval( 28, 'days', $start_date );
		$limit_end2 = MS_Helper_Period::add_interval( 28, 'days', $limit_end1 );

		// Pending subscription has only start date.

		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( '', $subscription->expire_date );
		$this->assertEquals( '', $subscription->trial_expire_date );

		// Activate subscription and check the changed dates

		$invoice = $subscription->get_current_invoice();
		$invoice->pay_it( 'free', '' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( $limit_end1, $subscription->expire_date );
		$this->assertEquals( '', $subscription->trial_expire_date );

		// Check the dates after the SECOND payment

		$invoice = $subscription->get_current_invoice();
		$invoice->pay_it( 'stripe', 'external_100' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( $limit_end2, $subscription->expire_date );
		$this->assertEquals( '', $subscription->trial_expire_date );
	}

	/**
	 * Check the invoice trial flag.
	 * @test
	 */
	function invoice_trial_flag() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Trial flag must be TRUE

		$current_date = MS_Helper_Period::current_date();
		$invoice = $subscription->get_current_invoice();
		$this->assertTrue( $invoice->uses_trial );
	}

	/**
	 * Check the invoice trial flag when the trial-addon is disabled.
	 * @test
	 */
	function invoice_trial_flag_disabled() {
		TData::disable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Trial flag must be FALSE

		$current_date = MS_Helper_Period::current_date();
		$invoice = $subscription->get_current_invoice();
		$this->assertFalse( $invoice->uses_trial );
	}

	/**
	 * Check the invoice due date.
	 * @test
	 */
	function invoice_due_date_no_trial() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Instantly due

		$current_date = MS_Helper_Period::current_date();
		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( $current_date, $invoice->due_date );
		$this->assertEquals( $current_date, $invoice->invoice_date );
	}

	/**
	 * Check the invoice due date for a trial-membership.
	 * @test
	 */
	function invoice_due_date_with_trial() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Due on last day of the trial

		$current_date = MS_Helper_Period::current_date();
		$payment_date = MS_Helper_Period::add_interval( 14, 'days' );
		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( $payment_date, $invoice->due_date );
		$this->assertEquals( $current_date, $invoice->invoice_date );
	}

	/**
	 * Check the invoice due date for a trial-membership with type Date-Range.
	 * @test
	 */
	function invoice_due_date_with_trial_daterange() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'daterange-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// This checks if the payment date is on THE LAST DAY OF THE DATE-RANGE,
		// because the trial-period is overflowing the date-range and therefore
		// must be shortened by some condition.
		// Trial would last for 14-days, but payment date must be in 10-days!

		$current_date = MS_Helper_Period::current_date();
		$start_date = MS_Helper_Period::add_interval( 1, 'days' );
		$payment_date = MS_Helper_Period::add_interval( 10, 'days' );

		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( $payment_date, $invoice->due_date );
		$this->assertEquals( $current_date, $invoice->invoice_date );
	}

	/**
	 * Test the invoice status changes from new -> paid.
	 * @test
	 */
	function invoice_status_paid() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Brand new invoice, must be NEW

		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( MS_Model_Invoice::STATUS_NEW, $invoice->status );

		// After payment it must be PAID

		$invoice->pay_it( 'stripe', 'external_100' );
		$this->assertEquals( MS_Model_Invoice::STATUS_PAID, $invoice->status );
	}

	/**
	 * Test the invoice status changes from new -> paid with only free payments.
	 * @test
	 */
	function invoice_status_paid_for_free() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Brand new invoice, must be NEW

		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( MS_Model_Invoice::STATUS_NEW, $invoice->status );

		// Even after free payment it must be PAID

		$invoice->pay_it( 'free', '' );
		$this->assertEquals( MS_Model_Invoice::STATUS_PAID, $invoice->status );
	}

	/**
	 * Test the invoice status changes from new -> billed -> paid.
	 * @test
	 */
	function invoice_status_billed_paid() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Brand new invoice, must be NEW

		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( MS_Model_Invoice::STATUS_NEW, $invoice->status );

		// After the free payment it must be BILLED

		$invoice->pay_it( 'free', '' );
		$this->assertEquals( MS_Model_Invoice::STATUS_BILLED, $invoice->status );

		// After the real payment it must be PAID

		$invoice->pay_it( 'stripe', 'external_100' );
		$this->assertEquals( MS_Model_Invoice::STATUS_PAID, $invoice->status );
	}

	/**
	 * Test the invoice status changes from new -> billed -> paid with only free payments.
	 * @test
	 */
	function invoice_status_trial_paid_directly() {
		TData::enable_addon( MS_Model_Addon::ADDON_TRIAL );

		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'simple-trial' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Brand new invoice, must be NEW

		$invoice = $subscription->get_current_invoice();
		$this->assertEquals( MS_Model_Invoice::STATUS_NEW, $invoice->status );

		// After the stripe payment it must be PAID instantly

		$invoice->pay_it( 'stripe', 'external_123' );
		$this->assertEquals( MS_Model_Invoice::STATUS_PAID, $invoice->status );
	}

	/**
	 * Test activation of admin-assigned memberships.
	 * @test
	 */
	function admin_subscriptions() {
		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'limited' );
		$subscription = TData::subscribe( $user_id, $membership_id, 'admin' );

		// Because the 'admin' gateway was used to create the subscription we
		// should have an active subscription with correct expire data already.

		$start_date = MS_Helper_Period::current_date();
		$limit_end = MS_Helper_Period::add_interval( 28, 'days', $start_date );

		$this->assertEquals( MS_Model_Relationship::STATUS_ACTIVE, $subscription->status, 'Active status' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( $limit_end, $subscription->expire_date );
	}

	/**
	 * Test activation of free memberships.
	 * @test
	 */
	function free_subscriptions() {
		$user_id = TData::id( 'user', 'editor' );
		$membership_id = TData::id( 'membership', 'free-limited' );
		$subscription = TData::subscribe( $user_id, $membership_id );

		// Because the membership is free we should have an active subscription
		// with correct expire data already.

		$start_date = MS_Helper_Period::current_date();
		$limit_end = MS_Helper_Period::add_interval( 28, 'days', $start_date );

		$this->assertEquals( MS_Model_Relationship::STATUS_ACTIVE, $subscription->status, 'Active status' );
		$this->assertEquals( $start_date, $subscription->start_date );
		$this->assertEquals( $limit_end, $subscription->expire_date );
	}

}