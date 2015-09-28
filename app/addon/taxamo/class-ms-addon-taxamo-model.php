<?php
/**
 * Taxamo settings model.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Addon_Taxamo_Model extends MS_Model_Option {

	/**
	 * Group name of the custom settings
	 *
	 * @since  1.0.0
	 * @type  string
	 */
	const GROUP = 'taxamo';

	/**
	 * Return current value of an addon setting
	 *
	 * @since  1.0.0
	 *
	 * @param  string $key
	 * @return string
	 */
	public function get( $key ) {
		switch ( $key ) {
			case 'public_key':
				if ( $this->get( 'is_live' ) ) {
					$key = 'live_public_key';
				} else {
					$key = 'test_public_key';
				}
				break;

			case 'private_key':
				if ( $this->get( 'is_live' ) ) {
					$key = 'live_private_key';
				} else {
					$key = 'test_private_key';
				}
				break;
		}

		return $this->get_custom_setting( self::GROUP, $key );
	}

	/**
	 * Change a setting of the addon
	 *
	 * @since  1.0.0
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function set( $key, $value ) {
		switch ( $key ) {
			case 'is_live':
				// This is a boolean value, not a string.
				$value = lib3()->is_true( $value );
				break;
		}

		return $this->set_custom_setting( self::GROUP, $key, $value );
	}

}