<?php
/**
 * Add-On controller for: Addon advanced invoice
 *
 * @since 1.1.3
 *
 * @package Membership2
 * @subpackage Addon
 */
class MS_Addon_Invoice extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.4
	 */
	const ID = 'addon_invoice';

	/**
	 * Invoice generation type
	 *
	 * @since  1.1.2
	 *
	 * @var string $sequence_type
	 */
	const DEFAULT_SEQUENCE 		= 'sequence_type_default'; //default
	const PROGRESSIVE_SEQUENCE 	= 'sequence_type_progressive'; //sequence like 1,2,3,4
	const CUSTOM_SEQUENCE 		= 'sequence_type_custom'; //custom allows for prefix with or without sequence


	 /**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.4
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

    /**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.4
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}


    /**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.4
	 */
	public function init() {
		MS_Model_Addon::disable( self::ID );
	}


	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.4
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
	
		$list[ self::ID ] = (object) array(
			'name' 			=> __( 'Additional Invoice Settings', 'membership2' ),
			'description' 	=> __( 'Take full control of your invoices', 'membership2' ),
			'icon' 			=> 'wpmui-fa wpmui-fa-credit-card',
			'action' 		=> array( __( 'Pro Version', 'membership2' ) ),
		);

		return $list;
	}
}

?>