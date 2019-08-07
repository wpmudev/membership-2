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

			//$this->add_filter(
			//	'ms_model_invoice_create_before_save',
			//	'add_discount'
			//);

			$this->add_filter(
				'ms_signup_payment_details',
				'add_discount',
				10, 2
			);

			$this->add_filter(
				'ms_model_relationship_get_payment_description/recurring',
				'payment_description_recurring',
				10, 6
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
			'name' => __( 'Pro-Rating', 'membership2' ),
			'description' => __( 'Pro-Rate previous payments when switching memberships.', 'membership2' ),
			'icon' => 'wpmui-fa wpmui-fa-money',
			'details' => array(
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'value' => __( 'Pro-Rating is applied when a user upgrades/downgrades a membership. Not when he cancels and subscribes in two steps.<br><br>Reason:<br>When a user cancels a membership he keeps access to the membership until the current period expires (exception: permanent access expires instantly)', 'membership2' ),
				),
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => '<b>' . __( 'When Multiple Memberships Add-on is disabled', 'membership2' ) . '</b>',
					'value' => __( 'Changing a membership always expires the old memberships and adds a subscription for the the new membership <em>in one step</em>. Pro Rating is always applied here.', 'membership2' ),
				),
				array(
					'type' => MS_Helper_Html::TYPE_HTML_TEXT,
					'title' => '<b>' . __( 'When Multiple Memberships Add-on is enabled', 'membership2' ) . '</b>',
					'value' => __( 'Only when you manually set the "Cancel and Pro-Rate" setting in the Upgrade Paths settings of the membership then the change is recognized as upgrade/downgrade. In this case the old membership is deactivated when the new subscription is created.<br>If you do not set this option the default logic applies: The user can access the old membership for the duration he paid, even when he cancels earlier. So no Pro-Rating then.', 'membership2' ),
				),
			),
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
	public function add_discount( $invoice, $subscription ) {
		
		// If memberships were already cancelled don't pro-rate again!
		if ( $subscription->cancelled_memberships ) { return $invoice; }

		$membership = $invoice->get_membership();

		if ( ! $subscription->move_from_id ) { return $invoice; }
		$ids = explode( ',', $subscription->move_from_id );

		if ( empty( $ids ) ) { return $invoice; }
		if ( $membership->is_free() ) { return $invoice; }

		// Calc pro rate discount if moving from another membership.
		$pro_rate = 0;
		foreach ( $ids as $id ) {
			if ( ! $id ) { continue; }

			$move_from = MS_Model_Relationship::get_subscription(
				$subscription->user_id,
				$id
			);

			if ( $move_from->is_valid() && $move_from->membership_id == $id ) {
				$pro_rate += $this->get_discount( $move_from );
			}
		}

		$pro_rate = floatval(
			apply_filters(
				'ms_addon_prorate_apply_discount',
				abs( $pro_rate ),
				$invoice
			)
		);

		if ( $pro_rate > $invoice->amount ) {
			$pro_rate = $invoice->amount;
		}

		if ( $pro_rate > 0 ) {
			$invoice->pro_rate = $pro_rate;
			$notes[] = sprintf(
				__( 'Pro-Rate Discount: %s.', 'membership2' ) . ' ',
				$invoice->currency . ' ' . $pro_rate
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

			if ( $invoice && $invoice->is_paid() ) {
				switch ( $subscription->status ) {
					case MS_Model_Relationship::STATUS_TRIAL:
						// No Pro-Rate given for trial memberships.
						break;

					case MS_Model_Relationship::STATUS_ACTIVE:
					case MS_Model_Relationship::STATUS_WAITING:
					case MS_Model_Relationship::STATUS_CANCELED:
						$remaining_days = $subscription->get_remaining_period( 0 );
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


	/**
	 * Sets the payment description on checkout page if a valid coupon is applied
	 *
	 * @since  1.1.6
	 * @param  String $desc
	 * @param  Boolean $short
	 * @param  String $currency
	 * @param  String $total_price Price where discount has already been applied
	 * @param  MS_Model_Membership $membership
	 * @param  MS_Model_Invoice $invoice
	 * @return String Payment description
	 */
	public function payment_description_recurring( $desc, $short, $currency, $total_price, $membership, $invoice ){

		if ( 1 == $membership->pay_cycle_repetitions ) return $desc;

		if ( ! empty( $invoice->pro_rate ) && $invoice->pro_rate > 0 && ! empty( $_REQUEST['membership_id'] ) ) {

			$lbl = '';
			if ( $membership->pay_cycle_repetitions > 1 ) {
				// Fixed number of payments (more than 1)
				if ( $short ) {
					$lbl = __( '<span class="price">%1$s %2$s</span> first time and then <span class="price">%1$s %3$s</span> (each %4$s)', 'membership2' );
				} else {
					$lbl = __( 'First payment <span class="price">%1$s %2$s</span> and then you will make %5$s payments of <span class="price">%1$s %3$s</span>, one each %4$s.', 'membership2' );
				}
			} else {
				// Indefinite number of payments
				if ( $short ) {
					$lbl = __( '<span class="price">%1$s %2$s</span> first time and then <span class="price">%1$s %3$s</span> (each %4$s)', 'membership2' );
				} else {
					$lbl = __( 'You will pay <span class="price">%1$s %2$s</span> first time and then <span class="price">%1$s %3$s</span> each %4$s.', 'membership2' );
				}
			}

			$desc = sprintf(
				$lbl,
				$currency,
				$total_price,
				$membership->price,				
				MS_Helper_Period::get_period_desc( $membership->pay_cycle_period ),
				$membership->pay_cycle_repetitions - 1
			);
			
		}

		return $desc;
	}

}
