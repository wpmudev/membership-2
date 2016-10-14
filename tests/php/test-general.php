<?php

/**
 * Test general plugin aspects
 */
class MS_Test_General extends WP_UnitTestCase {

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
	 * Checks if shared-setup was working.
	 * @test
	 */
	function staging_data_is_correct() {
		$empty = empty( TData::id( 'user', 'admin' ) );
		$this->assertFalse( $empty );
		wp_set_current_user( TData::id( 'user', 'admin' ) );
		$this->assertEquals( get_current_user_id(), TData::id( 'user', 'admin' ) );
		$empty = empty( TData::id( 'user', 'editor' ) );
		$this->assertFalse( $empty );
		
		$empty = empty( TData::id( 'post', 'sample-page' ) );
		$this->assertFalse( $empty );
		$this->assertEquals( 'page', get_post_type( TData::id( 'post', 'sample-page' ) ) );

		$ms_id = TData::id( 'membership', 'simple' );
		$empty = empty( $ms_id );
		$this->assertFalse( $empty );
		$membership = MS_Factory::load( 'MS_Model_Membership', $ms_id );
		$this->assertEquals( $ms_id, $membership->id );
		$this->assertEquals( 29, $membership->price );
	}
}