<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Taxamo settings model.
 *
 * @since 1.1.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Addon_Taxamo_Model extends MS_Model_Settings {

	/**
	 * Group name of the custom settings
	 *
	 * @since 1.1.0
	 * @type  string
	 */
	const GROUP = 'taxamo';

	/**
	 * Return current value of an addon setting
	 *
	 * @since  1.1.0
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
	 * @since 1.1.0
	 *
	 * @param string $key
	 * @param string $value
	 */
	public function set( $key, $value ) {
		switch ( $key ) {
			case 'is_live':
				// This is a boolean value, not a string.
				$value = lib2()->is_true( $value );
				break;
		}

		return $this->set_custom_setting( self::GROUP, $key, $value );
	}

}