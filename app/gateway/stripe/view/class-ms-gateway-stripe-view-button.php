<?php

class MS_Gateway_Stripe_View_Button extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$subscription = $this->data['ms_relationship'];
		$invoice = $subscription->get_current_invoice();
		$member = MS_Model_Member::get_current_member();
		$gateway = $this->data['gateway'];

		// Stripe is using Ajax, so the URL is empty.
		$action_url = apply_filters(
			'ms_gateway_view_button_form_action_url',
			''
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
			<script
				src="https://checkout.stripe.com/checkout.js" class="stripe-button"
				data-key="<?php echo esc_attr( $gateway->get_publishable_key() ); ?>"
				data-amount="<?php echo esc_attr( absint( $invoice->total * 100 ) ); //amount in cents ?>"
				data-name="<?php echo esc_attr( bloginfo( 'name' ) ); ?>"
				data-description="<?php echo esc_attr( strip_tags( $invoice->short_description ) ); ?>"
				data-currency="<?php echo esc_attr( $invoice->currency ); ?>"
				data-panel-label="<?php echo esc_attr( $gateway->pay_button_url ); ?>"
				data-label="<?php echo esc_attr( $gateway->pay_button_url ); ?>"
				data-email="<?php echo esc_attr( $member->email ); ?>"
				>
			</script>
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

		return $html;
	}

	private function prepare_fields() {
		$gateway = $this->data['gateway'];
		$subscription = $this->data['ms_relationship'];

		$fields = array(
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce(
					$gateway->id . '_' . $subscription->id
				),
			),
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
				'value' => $this->data['step'],
			),
		);

		return $fields;
	}
}