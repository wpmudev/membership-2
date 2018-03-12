<?php
/**
 * Abstract class for all Helpers.
 *
 * All Helpers will extend or inherit from the MS_Helper class.
 * Methods of this class will be used to identify the purpose and
 * and actions of a helper.
 *
 * Almost all functionality will be created with in an extended class.
 *
 * @since  1.0.0
 *
 * @uses MS_Model
 * @uses MS_View
 *
 * @package Membership2
 */
class MS_Helper extends MS_Hooker {

	/**
	 * Parent constuctor of all helpers.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		/**
		 * Actions to execute when constructing the parent helper.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Helper object.
		 */
		do_action( 'ms_helper_construct', $this );
	}



}