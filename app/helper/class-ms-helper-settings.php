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

class MS_Helper_Settings extends MS_Helper {

	// Success response codes
	const SETTINGS_MSG_ADDED = 1;
	const SETTINGS_MSG_DELETED = 2;
	const SETTINGS_MSG_UPDATED = 3;
	const SETTINGS_MSG_ACTIVATION_TOGGLED = 4;
	const SETTINGS_MSG_STATUS_TOGGLED = 5;
	const SETTINGS_MSG_BULK_UPDATED = 6;

	// Error response codes
	const SETTINGS_MSG_NOT_ADDED = -1;
	const SETTINGS_MSG_NOT_DELETED = -2;
	const SETTINGS_MSG_NOT_UPDATED = -3;
	const SETTINGS_MSG_ACTIVATION_NOT_TOGGLED = -4;
	const SETTINGS_MSG_STATUS_NOT_TOGGLED = -5;
	const SETTINGS_MSG_BULK_NOT_UPDATED = -6;

	/**
	 * Returns the status messages for a given status code
	 *
	 * @since  1.0.0
	 * @param  int $msg Status code
	 * @return string Status message
	 */
	public static function get_admin_message( $msg = 0 ) {
		static $Messages = null;

		if ( null === $Messages ) {
			$Messages = apply_filters(
				'ms_helper_membership_get_admin_messages',
				array(
					// Success response codes
					self::SETTINGS_MSG_ADDED => __( 'Setting added.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_DELETED => __( 'Setting deleted.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_UPDATED => __( 'Setting updated.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_ACTIVATION_TOGGLED => __( 'Setting activation toggled.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_STATUS_TOGGLED => __( 'Setting status toggled.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_BULK_UPDATED => __( 'Bulk settings updated.', MS_TEXT_DOMAIN ),

					// Error response messages
					self::SETTINGS_MSG_NOT_ADDED => __( 'Setting not added.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_NOT_DELETED => __( 'Setting not deleted.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_NOT_UPDATED => __( 'Setting not updated.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_ACTIVATION_NOT_TOGGLED => __( 'Setting activation not toggled.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_STATUS_NOT_TOGGLED => __( 'Setting status not toggled.', MS_TEXT_DOMAIN ),
					self::SETTINGS_MSG_BULK_NOT_UPDATED => __( 'Bulk settings not updated.', MS_TEXT_DOMAIN ),
				)
			);
		}

		if ( array_key_exists( $msg, $Messages ) ) {
			return $Messages[ $msg ];
		}

		return '';
	}

	/**
	 * Displays a status message on the Admin screen.
	 *
	 * The message to display is determined by the URL param 'msg'
	 *
	 * @since  1.0.0
	 */
	public static function print_admin_message() {
		$msg = ! empty( $_GET['msg'] ) ? (int) $_GET['msg'] : 0;

		$class = ( $msg > 0 ) ? 'updated' : 'error';

		if ( $msg = self::get_admin_message( $msg ) ) {
			prinf(
				'<div id="admin_message" class="%1$s"><p>%2$s</p></div>',
				esc_attr( $class ),
				$msg
			);
		}

	}

}