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

class MS_Helper_Membership extends MS_Helper {

	const MEMBERSHIP_ACTION_SIGNUP = 'membership_signup';
	const MEMBERSHIP_ACTION_MOVE = 'membership_move';
	const MEMBERSHIP_ACTION_CANCEL = 'membership_cancel';
	const MEMBERSHIP_ACTION_RENEW = 'membership_renew';
	const MEMBERSHIP_ACTION_PAY = 'membership_pay';

	const MEMBERSHIP_MSG_ADDED = 1;
	const MEMBERSHIP_MSG_DELETED = 2;
	const MEMBERSHIP_MSG_UPDATED = 3;
	const MEMBERSHIP_MSG_ACTIVATION_TOGGLED = 4;
	const MEMBERSHIP_MSG_STATUS_TOGGLED = 5;
	const MEMBERSHIP_MSG_BULK_UPDATED = 6;
	const MEMBERSHIP_MSG_NOT_ADDED = -1;
	const MEMBERSHIP_MSG_NOT_DELETED = -2;
	const MEMBERSHIP_MSG_NOT_UPDATED = -3;
	const MEMBERSHIP_MSG_ACTIVATION_NOT_TOGGLED = -4;
	const MEMBERSHIP_MSG_STATUS_NOT_TOGGLED = -5;
	const MEMBERSHIP_MSG_BULK_NOT_UPDATED = -6;
	const MEMBERSHIP_MSG_PARTIALLY_UPDATED = -8;

	public static function get_admin_messages( $msg = 0 ) {

		$messages = apply_filters( 'ms_helper_membership_get_admin_messages', array(
				self::MEMBERSHIP_MSG_ADDED => __( 'You have successfully set up <span class="ms-high">%s</span>.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_DELETED => __( 'Membership deleted.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_UPDATED => __( 'Membership updated.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_ACTIVATION_TOGGLED => __( 'Membership activation toggled.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_STATUS_TOGGLED => __( 'Membership status toggled.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_BULK_UPDATED => __( 'Memberships bulk updated.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_NOT_ADDED => __( 'Membership not added.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_NOT_DELETED => __( 'Membership not deleted.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_NOT_UPDATED => __( 'Membership not updated.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_ACTIVATION_NOT_TOGGLED => __( 'Membership activation not toggled.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_STATUS_NOT_TOGGLED => __( 'Membership status not toggled.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_BULK_NOT_UPDATED => __( 'Memberships bulk not updated.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_PARTIALLY_UPDATED => __( 'Memberships partially updated. Some fields could not be changed after members have signed up.', MS_TEXT_DOMAIN ),
			)
		);

		if ( array_key_exists( $msg, $messages ) ) {
			return $messages[ $msg ];
		}
		return null;
	}

	public static function print_admin_message() {
		$msg = self::get_msg_id();

		$class = ( $msg > 0 ) ? 'updated' : 'error';

		if ( $msg = self::get_admin_messages( $msg ) ) {
			echo "<div id='message' class='$class'><p>$msg</p></div>";
		}

	}

	public static function get_admin_message( $args = null, $membership = null ) {

		$msg = '';
		$msg_id = self::get_msg_id();
		if ( $msg = self::get_admin_messages( $msg_id ) ) {
			if( ! empty( $args ) && $count = substr_count( $msg, '%s' ) ) {
				$msg = array( vsprintf( $msg, $args ) );
			}
			if( self::MEMBERSHIP_MSG_ADDED == $msg_id && ! empty( $membership ) && empty( $membership->private ) ) {
				if( ! is_array( $msg ) ) {
					$msg = array( $msg );
				}
				$url = MS_Controller_Plugin::get_admin_settings_url();

				$msg[] = sprintf( 'We have automatically created %s, %s & %s for you.',
						sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'tab' => 'pages'), $url ), __( 'Membership Pages', MS_TEXT_DOMAIN ) ),
						sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'tab' => 'messages-protection'), $url ), __( 'Protection Messages', MS_TEXT_DOMAIN ) ),
						sprintf( '<a href="%s">%s</a>', add_query_arg( array( 'tab' => 'messages-automated'), $url ), __( 'E-mail Responses', MS_TEXT_DOMAIN ) )
				);
				$msg[] = sprintf( 'Please check & modify them %s, and once satisfied, activate your membership.',
						sprintf( '<a href="%s">%s</a>', $url, __( 'here', MS_TEXT_DOMAIN ) )
				);
			}
		}

		return apply_filters( 'ms_helper_membership_get_admin_message', $msg );
	}

	public static function get_admin_title() {
		$title = __( 'Memberships', MS_TEXT_DOMAIN );

		$msg = self::get_msg_id();
		if( self::MEMBERSHIP_MSG_ADDED == $msg ) {
			$title = __( 'Congratulations!', MS_TEXT_DOMAIN ) ;
		}
		return apply_filters( 'ms_helper_membership_get_admin_title', $title );
	}

	public static function get_msg_id() {
		$msg = ! empty( $_GET['msg'] ) ? (int) $_GET['msg'] : 0;
		return apply_filters( 'ms_helper_membership_get_msg_id', $msg );
	}
}