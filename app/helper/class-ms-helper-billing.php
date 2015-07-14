<?php
class MS_Helper_Billing extends MS_Helper {

	const BILLING_MSG_ADDED = 1;
	const BILLING_MSG_DELETED = 2;
	const BILLING_MSG_UPDATED = 3;
	const BILLING_MSG_BULK_DELETED = 4;
	const BILLING_MSG_NOT_ADDED = -1;
	const BILLING_MSG_NOT_DELETED = -2;
	const BILLING_MSG_NOT_UPDATED = -3;
	const BILLING_MSG_BULK_NOT_DELETED = -4;
	const BILLING_MSG_NOT_A_MEMBER = -5;

	public static function get_admin_message( $msg = 0 ) {
		$messages = apply_filters(
			'ms_helper_billing_get_admin_messages',
			array(
				self::BILLING_MSG_ADDED => __( 'Billing added.', MS_TEXT_DOMAIN ),
				self::BILLING_MSG_DELETED => __( 'Billing deleted.', MS_TEXT_DOMAIN ),
				self::BILLING_MSG_UPDATED => __( 'Billing updated.', MS_TEXT_DOMAIN ),
				self::BILLING_MSG_BULK_DELETED => __( 'Billing bulk deleted.', MS_TEXT_DOMAIN ),
				self::BILLING_MSG_NOT_ADDED => __( 'Billing not added.', MS_TEXT_DOMAIN ),
				self::BILLING_MSG_NOT_DELETED => __( 'Billing not deleted.', MS_TEXT_DOMAIN ),
				self::BILLING_MSG_NOT_UPDATED => __( 'Billing not updated.', MS_TEXT_DOMAIN ),
				self::BILLING_MSG_BULK_NOT_DELETED => __( 'Billing bulk not deleted.', MS_TEXT_DOMAIN ),
				self::BILLING_MSG_NOT_A_MEMBER => __( 'Billing not added. User not a member of selected Membership.', MS_TEXT_DOMAIN ),
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
		$contents = self::get_admin_message( $msg );

		if ( $contents ) {
			lib2()->ui->admin_message( $contents, $class );
		}
	}

	/**
	 * Formats a number to display a valid price.
	 *
	 * @since  1.0.0
	 * @param  numeric $price
	 * @return numeric
	 */
	static public function format_price( $price ) {
		$formatted = number_format( (float) $price, 2, '.', '' );

		return apply_filters(
			'ms_format_price',
			$formatted,
			$price
		);
	}

}