<?php

class MS_Gateway_Paypalsingle_View_Button extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$subscription = $this->data['ms_relationship'];
		$invoice = $subscription->get_current_invoice();
		$gateway = $this->data['gateway'];

		$action_url = apply_filters(
			'ms_gateway_paypalsingle_view_button_form_action_url',
			$this->data['action_url']
		);

		$row_class = 'gateway_' . $gateway->id;
		if ( ! $gateway->is_live_mode() ) {
			$row_class .= ' sandbox-mode';
		}

		ob_start();
		?>
		<form action="<?php echo esc_url( $action_url ); ?>" method="post">
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

		$fields = array(
			'business' => array(
				'id' => 'business',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->paypal_email,
			),
			'cmd' => array(
				'id' => 'cmd',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => '_xclick',
			),
			'bn' => array(
				'id' => 'bn',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'incsub_SP',
			),
			'item_number' => array(
				'id' => 'item_number',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $subscription->membership_id,
			),
			'item_name' => array(
				'id' => 'item_name',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership->name,
			),
			'amount' => array(
				'id' => 'amount',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => MS_Helper_Billing::format_price( $invoice->total ),
			),
			'currency_code' => array(
				'id' => 'currency_code',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $invoice->currency,
			),
			'return' => array(
				'id' => 'return',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => esc_url_raw(
					add_query_arg(
						array( 'ms_relationship_id' => $subscription->id ),
						MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false )
					)
				),
			),
			'cancel_return' => array(
				'id' => 'cancel_return',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER ),
			),
			'notify_url' => array(
				'id' => 'notify_url',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->get_return_url(),
			),
			'lc' => array(
				'id' => 'lc',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $gateway->paypal_site,
			),
			'invoice' => array(
				'id' => 'invoice',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $invoice->id,
			),
		);

		// Don't send to paypal if free
		if ( 0 === $invoice->total ) {
			$fields = array(
				'gateway' => array(
					'id' => 'gateway',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $gateway->id,
				),
				'ms_relationship_id' => array(
					'id' => 'ms_relationship_id',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $subscription->id,
				),
				'step' => array(
					'id' => 'step',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => MS_Controller_Frontend::STEP_PROCESS_PURCHASE,
				),
				'_wpnonce' => array(
					'id' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce(
						$gateway->id . '_' .$subscription->id
					),
				),
			);
			$this->data['action_url'] = null;
		} else {
			if ( $gateway->is_live_mode() ) {
				$this->data['action_url'] = 'https://www.paypal.com/cgi-bin/webscr';
			} else {
				$this->data['action_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}
		}

		$fields['submit'] = array(
			'id' => 'submit',
			'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
			'value' => 'https://www.paypalobjects.com/en_US/i/btn/x-click-but06.gif',
			'alt' => __( 'PayPal - The safer, easier way to pay online', MS_TEXT_DOMAIN ),
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

		return apply_filters(
			'ms_gateway_paypalsingle_view_prepare_fields',
			$fields
		);
	}
}