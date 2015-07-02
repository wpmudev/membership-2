<?php

class MS_Gateway_2checkout_View_Button extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$subscription = $this->data['ms_relationship'];
		$invoice = $subscription->get_current_invoice();
		$gateway = $this->data['gateway'];

		$action_url = apply_filters(
			'ms_gateway_2checkout_view_button_form_action_url',
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

	private function prepare_fields() {
		$gateway = $this->data['gateway'];
		$subscription = $this->data['ms_relationship'];
		$membership = $subscription->get_membership();
		$invoice = $subscription->get_current_invoice();
		$member = $subscription->get_member();

		$fields = array(
			'sid' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'sid',
				'value' => $gateway->seller_id,
			),
			'mode' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'mode',
				'value' => '2CO',
			),
			'type' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'li_0_type',
				'value' => 'product',
			),
			'name' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'li_0_name',
				'value' => $membership->name,
			),
			'price' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'li_0_price',
				'value' => $invoice->total,
			),
			'tangible' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'li_0_tangible',
				'value' => 'N',
			),
			'skip_landing' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'skip_landing',
				'value' => '1',
			),
			'user_id' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'user_id',
				'value' => $member->id,
			),
			'email' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'email',
				'value' => $member->email,
			),
			'currency' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'currency',
				'value' => $invoice->currency,
			),
			'merchant_order_id' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'merchant_order_id',
				'value' => $invoice->id,
			),
			'return_url' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'id' => 'x_receipt_link_url',
				'value' => esc_url_raw(
					add_query_arg(
						array( 'ms_relationship_id' => $subscription->id ),
						MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false )
					)
				),
			),
		);

		if ( MS_Model_Membership::PAYMENT_TYPE_RECURRING == $membership->payment_type ) {
			#'li_0_reccurance' = '2 days'   // Can use # Week / # Month / # Year
			#'li_0_duration' = 'Forever'    // Same as _recurrence, with additional "Forever" option
		}

		if ( false !== strpos( $gateway->pay_button_url, '://' ) ) {
			$fields['submit'] = array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
				'value' => $gateway->pay_button_url,
			);
		} else {
			$fields['submit'] = array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Signup', MS_TEXT_DOMAIN ),
			);

			if ( $gateway->pay_button_url ) {
				$fields['submit']['value'] = $gateway->pay_button_url;
			}
		}

		// Don't send to gateway if free
		if ( 0 === $invoice->total ) {
			$this->data['action_url'] = null;
		} else {
			if ( $gateway->is_live_mode() ) {
				$this->data['action_url'] = 'https://www.2checkout.com/checkout/purchase';
			} else {
				$this->data['action_url'] = 'https://sandbox.2checkout.com/checkout/purchase';
			}
		}

		return $fields;
	}
}