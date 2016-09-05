<?php
/**
 * Redirect settings model.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Addon_Redirect_Model extends MS_Model_Settings {

	/**
	 * Group name of the custom settings
	 *
	 * @since  1.0.0
	 * @type  string
	 */
	const GROUP = 'redirect';

	/**
	 * Return current value of an addon setting
	 *
	 * @since  1.0.0
	 *
	 * @param  string $key
	 * @return string
	 */
	public function get( $key ) {
		return $this->get_custom_setting( self::GROUP, $key );
	}

	/**
	 * Change a setting of the addon
	 *
	 * @since  1.0.0
	 *
	 * @param  string $key
	 * @param  string $value
	 */
	public function set( $key, $value ) {
		return $this->set_custom_setting( self::GROUP, $key, $value );
	}

}