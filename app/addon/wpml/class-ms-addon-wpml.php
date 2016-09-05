<?php
/**
 * Integration for WPML translations
 *
 * @since  1.0.1.0
 */
class MS_Addon_Wpml extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'addon_wpml';

	/**
	 * Checks if the current Add-on is enabled.
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
	 * Registers the Add-On.
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'icon' => 'dashicons dashicons-translation',
			'name' => __( 'WPML Integration', 'membership2' ),
			'description' => __( 'Use WPML to translate plugin messages.', 'membership2' ),
			'action' => array( __( 'Pro Version', 'membership2' ) ),
		);

		return $list;
	}

}
