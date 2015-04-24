<?php

/**
 * Test some of the protection rules
 */
class MS_Test_Rules extends WP_UnitTestCase {

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
	 * Check member roles add-on.
	 * @test
	 */
	function member_roles_rule() {
		// We only test the addon as an isolated unit.

		$addon = MS_Factory::load( 'MS_Rule_MemberRoles_Model' );

		remove_all_filters( 'user_has_cap' );
		$addon->protect_admin_content();

		wp_set_current_user( TData::id( 'user', 'admin' ) );
		$this->assertTrue( current_user_can( 'manage_options' ), 'admin' );
		$this->assertTrue( current_user_can( 'edit_theme_options' ), 'admin' );

		wp_set_current_user( TData::id( 'user', 'editor' ) );
		$this->assertFalse( current_user_can( 'manage_options' ), 'editor' );
		$this->assertFalse( current_user_can( 'edit_theme_options' ), 'editor' );
		$this->assertTrue( current_user_can( 'edit_pages' ), 'editor' );
		$this->assertTrue( current_user_can( 'delete_pages' ), 'editor' );
	}

	/**
	 * Check member capability add-on.
	 * @test
	 */
	function member_capabilities_rule() {
		// We only test the addon as an isolated unit.

		$addon = MS_Factory::load( 'MS_Rule_MemberCaps_Model' );

		remove_all_filters( 'user_has_cap' );
		$addon->protect_admin_content();

		wp_set_current_user( TData::id( 'user', 'admin' ) );
		$this->assertTrue( current_user_can( 'manage_options' ), 'admin' );
		$this->assertTrue( current_user_can( 'edit_theme_options' ), 'admin' );

		wp_set_current_user( TData::id( 'user', 'editor' ) );
		$this->assertFalse( current_user_can( 'manage_options' ), 'editor' );
		$this->assertFalse( current_user_can( 'edit_theme_options' ), 'editor' );
		$this->assertTrue( current_user_can( 'edit_pages' ), 'editor' );
		$this->assertTrue( current_user_can( 'delete_pages' ), 'editor' );
	}

}