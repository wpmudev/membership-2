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
 * Redirect settings model.
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Addon_Redirect_Model extends MS_Model_Settings {

	/**
	 * Group name of the custom settings
	 *
	 * @since 1.1.0
	 * @type  string
	 */
	const GROUP = 'redirect';

	/**
	 * Return current value of an addon setting
	 *
	 * @since  1.1.0
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
	 * @since  1.1.0
	 *
	 * @param  string $key
	 * @param  string $value
	 */
	public function set( $key, $value ) {
		return $this->set_custom_setting( self::GROUP, $key, $value );
	}

}