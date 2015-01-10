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


class MS_Addon_Automessage extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.1.0
	 */
	const ID = 'addon_automessage';

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $addons ) {
		/*
		// Don't register: Not completed yet...

		$addons[ self::ID ] = (object) array(
			'name' => __( 'Automessage', MS_TEXT_DOMAIN ),
			'description' => __( 'Automessage integration.', MS_TEXT_DOMAIN ),
		);
		*/
		return $addons;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {
		$this->add_filter( 'automessage_custom_user_hooks', 'automessage_custom_user_hooks' );
	}

	/**
	 * wpmu.dev Automessage plugin integration.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param array $hooks The existing hooks.
	 * @return array The modified array of hooks.
	 */
	public function automessage_custom_user_hooks( $hooks ) {
		$comm_types = MS_Model_Communication::get_communication_type_titles();

		foreach ( $comm_types as $type => $desc ) {
			$action = "ms_communications_process_$type";
			$hooks[ $action ] = array( 'action_nicename' => $desc );
		}

		return $hooks;
	}

}