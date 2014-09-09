<?php

class MS_View_Gateway_Paypal_Standard_Button extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		
		$action_url = apply_filters( 'ms_view_gateway_paypal_standard_button_form_action_url', $this->data['action_url'] );
		
		ob_start();
		?>
			<tr>
				<td class='ms-buy-now-column' colspan='2' >
					<form $action_url="<?php echo $action_url; ?>" method="post" id="ms-paypal-form">
						<?php 
							foreach( $this->fields as $field ) {
								MS_Helper_Html::html_input( $field ); 
							}
						?>
						<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >
					</form>
				</td>
			</tr>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	
	private function prepare_fields() {
	
		$ms_relationship = $this->data['ms_relationship'];
		$membership = $ms_relationship->get_membership();
		if( 0 == $membership->price ) {
			return;
		}
		
		$membership = $ms_relationship->get_membership();
		if( 0 == $membership->price ) {
			return;
		}

		$gateway = $this->data['gateway'];
		
		$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
		$regular_invoice = null;
		
		$this->fields = array(
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => wp_create_nonce( "{$this->data['gateway']->id}_{$this->data['ms_relationship']->id}" ),
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
						'value' => get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) ),
				),
				'cancel_return' => array(
						'id' => 'cancel_return',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_SIGNUP ) ),
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
				'custom' => array(
						'id' => 'custom',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->id,
				),
		);
		
		$this->fields['submit'] = array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
				'value' => 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif',
				'alt' => __( 'PayPal - The safer, easier way to pay online', MS_TEXT_DOMAIN ),
		);
		/** custom pay button defined in gateway settings */
		if( ! empty( $gateway->pay_button_url ) ) {
			if( strpos( $gateway->pay_button_url, 'http' ) !== 0 ) {
				$this->fields['submit']['value'] = $gateway->pay_button_url;
			}
			else {
				$this->fields['submit'] = array(
						'id' => 'submit',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => $gateway->pay_button_url ? $gateway->pay_button_url : __( 'Paypal', MS_TEXT_DOMAIN ),
				);
			}
		}
				
		/** Trial period */
		if( $membership->trial_period_enabled && $invoice->trial_period ) {
			$regular_invoice = MS_Model_Invoice::get_next_invoice( $ms_relationship );
			$this->fields['a1'] = array(
					'id' => 'a1',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => ( $invoice->trial_period ) ? $invoice->total : $membership->trial_price,
			);
			$this->fields['p1'] = array(
					'id' => 'p1',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => ! empty( $membership->trial_period['period_unit'] ) ? $membership->trial_period['period_unit']: 1,
			);
			$this->fields['t1'] = array(
					'id' => 't1',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => ! empty( $membership->trial_period['period_type'] ) ? strtoupper( $membership->trial_period['period_type'][0] ) : 'D',
			);
		}
		
		/** Membership price */
		$this->fields['a3'] = array(
				'id' => 'a3',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => ( ! $invoice->trial_period ) ? $invoice->total : $regular_invoice->total,
		);
		
		$recurring = 0;
		switch( $membership->payment_type ) {
			case MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING:
				$this->fields['p3'] = array(
				'id' => 'p3',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => ! empty( $membership->pay_cycle_period['period_unit'] ) ? $membership->pay_cycle_period['period_unit']: 0,
				);
				$this->fields['t3'] = array(
						'id' => 't3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => ! empty( $membership->pay_cycle_period['period_type'] ) ? strtoupper( $membership->pay_cycle_period['period_type'][0] ) : 'D',
				);
				$recurring = 1;
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_FINITE:
				$this->fields['p3'] = array(
				'id' => 'p3',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => ! empty( $membership->period['period_unit'] ) ? $membership->period['period_unit']: 1,
				);
				$this->fields['t3'] = array(
						'id' => 't3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => ! empty( $membership->period['period_type'] ) ? strtoupper( $membership->period['period_type'][0] ) : 'D',
				);
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE:
				$this->fields['p3'] = array(
				'id' => 'p3',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => MS_Helper_Period::subtract_dates( $membership->period_date_end, $membership->period_date_start ),
				);
				$this->fields['t3'] = array(
						'id' => 't3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => ! empty( $membership->period['period_type'] ) ? strtoupper( $membership->period['period_type'][0] ) : 'D',
				);
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT:
				$this->fields['p3'] = array(
				'id' => 'p3',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 5,
				);
				$this->fields['t3'] = array(
						'id' => 't3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'Y',
				);
				break;
		}
		
		/**
		 * Recurring field.
		 * 0 – subscription payments do not recur
		 * 1 – subscription payments recur
		 */
		$this->fields['src'] = array(
				'id' => 'src',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $recurring,
		);
		
		/**
		 * Modify current subscription field.
		 * value != 0 does not allow trial period.
		 * 0 – allows subscribers only to sign up for new subscriptions
		 * 1 – allows subscribers to sign up for new subscriptions and modify their current subscriptions
		 * 2 – allows subscribers to modify only their current subscriptions
		*/
		$this->fields['modify'] = array(
				'id' => 'modify',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => empty( $move_from_id ) ? 0 : 2,
		);
			
		if( $gateway->is_live_mode() ) {
			$this->data['action_url'] = 'https://www.paypal.com/cgi-bin/webscr';
		}
		else {
			$this->data['action_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}
	}
}