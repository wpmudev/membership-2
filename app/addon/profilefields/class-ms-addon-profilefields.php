<?php
/**
 * Add-On controller for: User Profile Fields
 *
 * @since  1.0.1.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Profilefields extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.1.0
	 */
	const ID = 'profilefields';

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	static public function is_active() {
		return false;
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.1.0
	 */
	public function init() {
		MS_Model_Addon::disable( self::ID );
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Profile Fields', 'membership2' ),
			'description' => __( 'Customize fields in the user profile and registration form.', 'membership2' ),
			'icon' => 'dashicons dashicons-id',
			'action' => array( __( 'Pro Version', 'membership2' ) ),
		);

		return $list;
	}

}
