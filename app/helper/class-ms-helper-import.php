<?php
/**
 * Helper class for import functions
 *
 * @since  1.0.4
 * @package Membership2
 * @subpackage Helper
 */
 class MS_Helper_Import extends MS_Helper {

	/**
	 * Membership to import view
	 * Converts the membrship objet to viewable data
	 *
	 * @return array
	 */
	public static function membership_to_view( $item, $ms_types, $ms_paytypes ) {
		if ( ! isset( $ms_types[ $item->type ] ) ) {
			$item->type = MS_Model_Membership::TYPE_STANDARD;
		}

		if ( empty( $item->payment_type ) ) {
			if ( ! empty( $item->pay_type ) ) {
				// Compatibility with bug in old M1 export files.
				$item->payment_type = $item->pay_type;
			} else {
				$item->payment_type = 'permanent';
			}
		}

		switch ( $item->payment_type ) {
			case 'recurring':
				$payment_type = MS_Model_Membership::PAYMENT_TYPE_RECURRING;
				break;

			case 'finite':
				$payment_type = MS_Model_Membership::PAYMENT_TYPE_FINITE;
				break;

			case 'date':
				$payment_type = MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE;
				break;

			default:
				$payment_type = MS_Model_Membership::PAYMENT_TYPE_PERMANENT;
				break;
		}

		return array(
			$item->name,
			$ms_types[ $item->type ],
			$ms_paytypes[ $payment_type ],
			$item->description,
		);
	}

	/**
	 * Member to view
	 * Converts the member data to viewable data
	 *
	 * @return array
	 */
	public static function member_to_view( $item ) {
		$inv_count = 0;
		if ( isset( $item->subscriptions )
			&& is_array( $item->subscriptions )
		) {
			foreach ( $item->subscriptions as $registration ) {
				$inv_count += count( $registration->invoices );
			}
		}

		return array(
			$item->username,
			$item->email,
			count( $item->subscriptions ),
			$inv_count,
		);
	}
 }
?>