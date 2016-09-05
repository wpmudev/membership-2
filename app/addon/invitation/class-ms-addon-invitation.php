<?php
/**
 * Add-On controller for: Invitations
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Addon_Invitation extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'invitation';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
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
			'name' => __( 'Invitation Codes', 'membership2' ),
			'description' => __( 'Users need an invitation code to subscribe to a membership.', 'membership2' ),
			'icon' => 'wpmui-fa wpmui-fa-unlock-alt',
			'action' => array( __( 'Pro Version', 'membership2' ) ),
		);

		return $list;
	}

}
