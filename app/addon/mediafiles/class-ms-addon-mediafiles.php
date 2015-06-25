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


class MS_Addon_Mediafiles extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.1.0
	 */
	const ID = 'addon_mediafiles';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.1.0.8
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID ) &&
			MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA );
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0.8
	 */
	public function init() {
		// This Add-on has no real logic.
		// It is only a switch that is used in the MS_Rule_Category files...

		$this->add_filter(
			'ms_model_addon_is_enabled_' . self::ID,
			'is_enabled'
		);
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0.8
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		// This Add-on is controlled inside the Media Protection Add-on.
		return $list;
	}

	/**
	 * Add a dependency check to this add-on: It can only be enabled when the
	 * parent Add-on "Media" is also enabled.
	 *
	 * Filter: 'ms_model_addon_is_enabled_addon_mediafiles'
	 *
	 * @since  1.0.0.8
	 * @internal
	 *
	 * @param  bool $enabled State of this add-on
	 *         (without considering the parent add-on)
	 * @return bool The actual state of this add-on.
	 */
	public function is_enabled( $enabled ) {
		if ( $enabled ) {
			$enabled = MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA );
		}

		return $enabled;
	}

}