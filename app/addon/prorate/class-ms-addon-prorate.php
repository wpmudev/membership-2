<?php
/**
 * Add-on: Enable the Pro-Rating function.
 *
 * @since  1.0.1.0
 */
class MS_Addon_Prorate extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.1.0
	 */
	const ID = 'addon_prorate';

	/**
	 * Checks if the current Add-on is enabled.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.1.0
	 */
	public function init() {
		if ( self::is_active() ) {
			$this->add_filter(
				'ms_model_invoice_create_before_save',
				'add_discount'
			);
		}
	}

	/**
	 * Registers the Add-On.
	 *
	 * @since  1.0.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name' => __( 'Pro-Rating', MS_TEXT_DOMAIN ),
			'description' => __( 'Pro-Rate previous payments when switching memberships.', MS_TEXT_DOMAIN ),
			'icon' => 'wpmui-fa wpmui-fa-money',
		);
		return $list;
	}

	/**
	 * Adds the Pro-Rating discount to an invoice.
	 *
	 * @since  1.0.1.0
	 * @param  MS_Model_Invoice $invoice
	 * @return MS_Model_Invoice Modified Invoice.
	 */
	public function add_discount( $invoice ) {
		$subscription = $invoice->get_subscription();
		$membership = $invoice->get_membership();
		$ids = explode( ',', $subscription->move_from_id );

		if ( empty( $ids ) ) { return $invoice; }
		if ( $membership->is_free() ) { return $invoice; }

		// Calc pro rate discount if moving from another membership.
		$pro_rate = 0;
		foreach ( $ids as $id ) {
			$move_from = MS_Model_Relationship::get_subscription(
				$subscription->user_id,
				$id
			);

			if ( $move_from->is_valid() ) {
				$pro_rate += $this->get_discount( $move_from );
			}
		}

		$pro_rate = floatval(
			apply_filters(
				'ms_addon_prorate_apply_discount',
				min( $pro_rate, $membership->price ),
				$invoice
			)
		);

		if ( $pro_rate > 0 ) {
			$invoice->pro_rate = $pro_rate;
			$notes[] = sprintf(
				__( 'Pro-Rate Discount: %s %s.', MS_TEXT_DOMAIN ) . ' ',
				$invoice->currency,
				$pro_rate
			);
		}

		return $invoice;
	}

	/**
	 * Calculate pro rate value.
	 *
	 * Pro rate using remaining membership days.
	 *
	 * @since  1.0.1.0
	 *
	 * @return float The pro rate value.
	 */
	protected function get_discount( $subscription ) {
		$value = 0;
		$membership = $subscription->get_membership();

		if ( MS_Model_Membership::PAYMENT_TYPE_PERMANENT !== $membership->payment_type ) {
			$invoice = $subscription->get_previous_invoice();

			if ( $invoice->is_paid() ) {
				switch ( $subscription->status ) {
					case MS_Model_Relationship::STATUS_TRIAL:
						// No Pro-Rate given for trial memberships.
						break;

					case MS_Model_Relationship::STATUS_ACTIVE:
					case MS_Model_Relationship::STATUS_WAITING:
					case MS_Model_Relationship::STATUS_CANCELED:
						$remaining_days = $subscription->get_remaining_period();
						$total_days = MS_Helper_Period::subtract_dates(
							$subscription->expire_date,
							$subscription->start_date
						);
						$value = $remaining_days / $total_days;
						$value *= $invoice->total;
						break;

					default:
						// No Pro-Rate for other subscription status.
						break;
				}
			}
		}

		return apply_filters(
			'ms_addon_prorate_get_discount',
			$value,
			$subscription
		);
	}

}