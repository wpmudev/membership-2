<?php

/**
 * Test general plugin aspects
 */
class MS_Test_General extends WP_UnitTestCase {

	/**
	 * Check if all constants are defined.
	 */
	function test_constants() {
		lib2()->debug->disable();
		$this->assertTrue( false, 'Unit Testing works!' );
	}
}