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

class MS_Helper_Member extends MS_Helper {

	const MSG_MEMBER_ADDED = 1;
	const MSG_MEMBER_DELETED = 2;
	const MSG_MEMBER_UPDATED = 3;
	const MSG_MEMBER_ACTIVATION_TOGGLED = 4;
	const MSG_MEMBER_BULK_UPDATED = 5;
	const MSG_MEMBER_NOT_ADDED = -1;
	const MSG_MEMBER_NOT_DELETED = -2;
	const MSG_MEMBER_NOT_UPDATED = -3;
	const MSG_MEMBER_ACTIVATION_NOT_TOGGLED = -4;
	const MSG_MEMBER_BULK_NOT_UPDATED = -5;

	public static function get_admin_message( $msg = 0 ) {
		$messages = apply_filters(
			'ms_helper_member_get_admin_messages',
			array(
				self::MSG_MEMBER_ADDED => __( 'Membership added.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_DELETED => __( 'Membership deleted.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_UPDATED => __( 'Member updated.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_ACTIVATION_TOGGLED => __( 'Member activation toggled.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_BULK_UPDATED => __( 'Members bulk updated.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_NOT_ADDED => __( 'Membership not added.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_NOT_DELETED => __( 'Membership not deleted.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_NOT_UPDATED => __( 'Member not updated.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_ACTIVATION_NOT_TOGGLED => __( 'Member activation not toggled.', MS_TEXT_DOMAIN ),
				self::MSG_MEMBER_BULK_NOT_UPDATED => __( 'Members bulk not updated.', MS_TEXT_DOMAIN ),
			)
		);

		if ( array_key_exists( $msg, $messages ) ) {
			return $messages[ $msg ];
		}

		return null;
	}

	public static function print_admin_message() {
		$msg = ! empty( $_GET['msg'] ) ? (int) $_GET['msg'] : 0;

		$class = ( $msg > 0 ) ? 'updated' : 'error';

		if ( $msg = self::get_admin_message( $msg ) ) {
			WDev()->message( $msg, $class );
		}

	}

}