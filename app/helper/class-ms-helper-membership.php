<?php
/**
 * Helper for the Membership class.
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
		$messages = apply_filters(
			'ms_helper_membership_get_admin_messages',
			array(
				self::MEMBERSHIP_MSG_ADDED => __( 'You have successfully set up your <b>%s</b> Membership.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_DELETED => __( 'Membership deleted.', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_MSG_UPDATED => __( 'Membership <b>%s</b> updated.', MS_TEXT_DOMAIN ),
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
		} else {
			return null;
		}
	}

	public static function print_admin_message() {
		$msg = self::get_msg_id();

		$class = ( $msg > 0 ) ? 'updated' : 'error';

		if ( $msg = self::get_admin_messages( $msg ) ) {
			lib3()->ui->admin_message( $msg, $class );
		}
	}

	public static function get_admin_message( $args = null, $membership = null ) {
		$msg = '';
		$msg_id = self::get_msg_id();

		if ( $msg = self::get_admin_messages( $msg_id ) ) {
			if ( ! empty( $args ) ) {
				$msg = vsprintf( $msg, $args );
			}

			// When the first membership was created show a popup to the user
			$is_first = true;
			if ( $is_first
				&& self::MEMBERSHIP_MSG_ADDED == $msg_id
				&& ! empty( $membership )
			) {
				$url = MS_Controller_Plugin::get_admin_settings_url();

				self::show_setup_note( $membership );
			}
		}

		return apply_filters(
			'ms_helper_membership_get_admin_message',
			$msg
		);
	}

	public static function get_admin_title() {
		$title = __( 'Memberships', MS_TEXT_DOMAIN );

		$msg = self::get_msg_id();
		if ( self::MEMBERSHIP_MSG_ADDED == $msg ) {
			$title = __( 'Congratulations!', MS_TEXT_DOMAIN );
		}
		return apply_filters( 'ms_helper_membership_get_admin_title', $title );
	}

	public static function get_msg_id() {
		$msg = ! empty( $_GET['msg'] ) ? (int) $_GET['msg'] : 0;
		return apply_filters( 'ms_helper_membership_get_msg_id', $msg );
	}

	/**
	 * Displays a PopUp to the user that shows a sumary of the setup wizard
	 * including possible next steps for configuration.
	 *
	 * @since  1.0.0
	 * @param  MS_Model_Membership $membership The membership that was created.
	 */
	public static function show_setup_note( $membership ) {
		$popup = array();

		$popup['title'] = sprintf(
			'<i class="dashicons dashicons-yes"></i> %1$s<div class="subtitle">%2$s</div>',
			__( 'Congratulations!', MS_TEXT_DOMAIN ),
			sprintf(
				__( 'You have successfully set up your <b>%1$s</b> Membership.', MS_TEXT_DOMAIN ),
				$membership->name
			)
		);

		$setup = MS_Factory::create( 'MS_View_Settings_Page_Setup' );

		$popup['modal'] = true;
		$popup['close'] = false;
		$popup['sticky'] = false;
		$popup['class'] = 'ms-setup-done';
		$popup['body'] = $setup->to_html();
		$popup['height'] = $setup->dialog_height();

		$popup['body'] .= sprintf(
			'<div class="buttons">' .
			'<a href="%s" class="button">%s</a> ' .
			'<button type="button" class="button-primary close">%s</button>' .
			'</div>',
			MS_Controller_Plugin::get_admin_url( 'protection' ),
			__( 'Set-up Access Levels', MS_TEXT_DOMAIN ),
			__( 'Finish', MS_TEXT_DOMAIN )
		);

		lib3()->html->popup( $popup );

		$settings = MS_Plugin::instance()->settings;
		$settings->is_first_membership = false;
		if ( ! $membership->is_free ) {
			$settings->is_first_paid_membership = false;
		}
		$settings->save();
	}
}