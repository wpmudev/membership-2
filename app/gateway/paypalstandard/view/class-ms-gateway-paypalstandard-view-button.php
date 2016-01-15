<?php

class MS_Gateway_Paypalstandard_View_Button extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$subscription = $this->data['ms_relationship'];
		$invoice = $subscription->get_current_invoice();
		$gateway = $this->data['gateway'];

		$action_url = apply_filters(
			'ms_gateway_paypalstandard_view_button_form_action_url',
			$this->data['action_url']
		);

		$row_class = 'gateway_' . $gateway->id;
		if ( ! $gateway->is_live_mode() ) {
			$row_class .= ' sandbox-mode';
		}

		ob_start();
		?>
		<form action="<?php echo esc_url( $action_url ); ?>" method="post" id="ms-paypal-form">
			<?php
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
			<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >
		</form>
		<?php
		$payment_form = apply_filters(
			'ms_gateway_form',
			ob_get_clean(),
			$gateway,
			$invoice,
			$this
		);

		ob_start();
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<td class="ms-buy-now-column" colspan="2">
				<?php echo $payment_form; ?>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();

		$html = apply_filters(
			'ms_gateway_button-' . $gateway->id,
			$html,
			$this
		);

		$html = apply_filters(
			'ms_gateway_button',
			$html,
			$gateway->id,
			$this
		);

		return $html;
	}

	/**
	 * Prepare the PayPal IPN fields
	 *
	 * Details here:
	 * https://developer.paypal.com/docs/classic/paypal-payments-standard/integration-guide/Appx_websitestandard_htmlvariables/
	 *
	 * @since  1.0.0
	 * @return array
	 */
	private function prepare_fields() {
		$subscription = $this->data['ms_relationship'];
		$membership = $subscription->get_membership();

		if ( 0 === $membership->price ) {
			return;
		}

		$gateway = $this->data['gateway'];

		$invoice = $subscription->get_current_invoice();
		$regular_invoice = null;
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		$nonce = wp_create_nonce(
			$gateway->id. '_' . $subscription->id
		);
		$cancel_url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );
		$return_url = esc_url_raw(
			add_query_arg(
				array( 'ms_relationship_id' => $subscription->id ),
				MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false )
			)
		);

		$fields = array(
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $nonce,
			),
			'charset' => array(
				'id' => 'charset',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'utf-8',
			),
			'business' => array(
				'id' => 'business',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->merchant_id,
			),
			'cmd' => array(
				'id' => 'cmd',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => '_xclick-subscriptions',
			),
			'bn' => array(
				'id' => 'bn',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'incsub_SP',
			),
			'item_name' => array(
				'id' => 'item_name',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership->name,
			),
			'item_number' => array(
				'id' => 'item_number',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership->id,
			),
			'currency_code' => array(
				'id' => 'currency_code',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => MS_Plugin::instance()->settings->currency,
			),
			'return' => array(
				'id' => 'return',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $return_url,
			),
			'cancel_return' => array(
				'id' => 'cancel_return',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $cancel_url,
			),
			'notify_url' => array(
				'id' => 'notify_url',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->get_return_url(),
			),
			'country' => array(
				'id' => 'country',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->paypal_site,
			),
			'no_note' => array(
				'id' => 'no_note',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 1,
			),
			'no_shipping' => array(
				'id' => 'no_shipping',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 1,
			),
			'invoice' => array(
				'id' => 'invoice',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $invoice->id,
			),
		);

		$fields['submit'] = array(
			'id' => 'submit',
			'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
			'value' => 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif',
			'alt' => __( 'PayPal - The safer, easier way to pay online', 'membership2' ),
		);

		// custom pay button defined in gateway settings
		$custom_label = $gateway->pay_button_url;
		if ( ! empty( $custom_label ) ) {
			if ( false !== strpos( $custom_label, '://' ) ) {
				$fields['submit']['value'] = $custom_label;
			} else {
				$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => $custom_label,
				);
			}
		}

		// Trial period
		if ( $subscription->is_trial_eligible() ) {
			$fields['a1'] = array(
				'id' => 'a1',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $invoice->trial_price,
			);

			$trial_type = MS_Helper_Period::get_period_value(
				$membership->trial_period,
				'period_type'
			);
			$trial_type = strtoupper( $trial_type[0] );
			$trial_value = MS_Helper_Period::get_period_value(
				$membership->trial_period,
				'period_unit'
			);
			$trial_value = MS_Helper_Period::validate_range(
				$trial_value,
				$trial_type
			);

			$fields['p1'] = array(
				'id' => 'p1',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $trial_value,
			);

			$fields['t1'] = array(
				'id' => 't1',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $trial_type,
			);
		}

		// Membership price
		$membership_price = $invoice->total;
		$membership_price = MS_Helper_Billing::format_price( $membership_price );

		$fields['a3'] = array(
			'id' => 'a3',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $membership_price,
		);
		$fields['amount'] = array(
			'id' => 'amount',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $membership_price,
		);

		$recurring = 0;
		switch ( $membership->payment_type ) {
			// == RECURRING PAYMENTS
			case MS_Model_Membership::PAYMENT_TYPE_RECURRING:
				$period_type = MS_Helper_Period::get_period_value(
					$membership->pay_cycle_period,
					'period_type'
				);
				$period_type = strtoupper( $period_type[0] );
				$period_value = MS_Helper_Period::get_period_value(
					$membership->pay_cycle_period,
					'period_unit'
				);
				$period_value = MS_Helper_Period::validate_range(
					$period_value,
					$period_type
				);

				$fields['p3'] = array(
					'id' => 'p3',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $period_value,
				);
				$fields['t3'] = array(
					'id' => 't3',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $period_type,
				);

				// This makes the payments recurring!
				$recurring = 1;
				break;

			// == FINITE END DATE
			case MS_Model_Membership::PAYMENT_TYPE_FINITE:
				$period_type = MS_Helper_Period::get_period_value(
					$membership->period,
					'period_type'
				);
				$period_type = strtoupper( $period_type[0] );
				$period_value = MS_Helper_Period::get_period_value(
					$membership->period,
					'period_unit'
				);
				$period_value = MS_Helper_Period::validate_range(
					$period_value,
					$period_type
				);

				$fields['p3'] = array(
					'id' => 'p3',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $period_value,
				);
				$fields['t3'] = array(
					'id' => 't3',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $period_type,
				);
				break;

			// == DATE RANGE
			case MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE:
				$period_value = MS_Helper_Period::subtract_dates(
					$membership->period_date_end,
					$membership->period_date_start
				);
				$period_value = MS_Helper_Period::validate_range(
					$period_value,
					'D'
				);

				$fields['p3'] = array(
					'id' => 'p3',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $period_value,
				);
				$fields['t3'] = array(
					'id' => 't3',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => 'D',
				);
				break;

			// == PERMANENT
			case MS_Model_Membership::PAYMENT_TYPE_PERMANENT:
				/*
				 * Permanent membership: Set the subscription range to 5 years!
				 * PayPal requires us to provide the subscription range and the
				 * maximum possible value is 5 years.
				 */
				$period_value = MS_Helper_Period::validate_range(
					5,
					'Y'
				);

				$fields['p3'] = array(
					'id' => 'p3',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $period_value,
				);
				$fields['t3'] = array(
					'id' => 't3',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => 'Y',
				);
				break;
		}

		if ( 1 == $recurring ) {
			if ( 1 == $membership->pay_cycle_repetitions ) {
				$recurring = 0;
			} elseif ( $membership->pay_cycle_repetitions > 1 ) {
				/**
				 * Recurring times.
				 * The number of times that a recurring payment is made.
				 */
				$fields['srt'] = array(
					'id' => 'srt',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $membership->pay_cycle_repetitions,
				);
			}
		}

		/**
		 * Recurring field.
		 * 0 - one time payment
		 * 1 - recurring payments
		 */
		$fields['src'] = array(
			'id' => 'src',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $recurring,
		);

		/**
		 * Modify current subscription field.
		 * value != 0 does not allow trial period.
		 * 0 - allows subscribers only to sign up for new subscriptions
		 * 1 - allows subscribers to sign up for new subscriptions and modify their current subscriptions
		 * 2 - allows subscribers to modify only their current subscriptions
		*/
		$modify = ! empty( $move_from_id );
		$fields['modify'] = array(
			'id' => 'modify',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $modify ? 2 : 0,
		);

		if ( $gateway->is_live_mode() ) {
			$this->data['action_url'] = 'https://www.paypal.com/cgi-bin/webscr';
		} else {
			$this->data['action_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}

		return apply_filters(
			'ms_gateway_paypalstandard_view_prepare_fields',
			$fields,
                        $invoice
		);
	}
}