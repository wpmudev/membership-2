<?php

class MS_Gateway_Stripeplan_View_Button extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$subscription = $this->data['ms_relationship'];
		$invoice = $subscription->get_current_invoice();
		$member = MS_Model_Member::get_current_member();
		$gateway = $this->data['gateway'];

		// Stripe is using Ajax, so the URL is empty.
		$action_url = apply_filters(
			'ms_gateway_stripeplan_view_button_form_action_url',
			''
		);

		$row_class = 'gateway_' . $gateway->id;
		if ( ! $gateway->is_live_mode() ) {
			$row_class .= ' sandbox-mode';
		}

		$stripe_data = array(
			'name' => get_bloginfo( 'name' ),
			'description' => strip_tags( $invoice->short_description ),
			'label' => $gateway->pay_button_url,
		);

		/**
		 * Users can change details (like the title or description) of the
		 * stripe checkout popup.
		 *
		 * @since  1.0.2.4
		 * @var array
		 */
		$stripe_data = apply_filters(
			'ms_gateway_stripe_form_details',
			$stripe_data,
			$invoice
		);

		$stripe_data['email'] = $member->email;
		$stripe_data['key'] = $gateway->get_publishable_key();
		$stripe_data['currency'] = $invoice->currency;
		$stripe_data['amount'] = ceil(abs( $invoice->total * 100 )); // Amount in cents.

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
				<?php
				foreach ( $stripe_data as $key => $value ) {
					printf(
						'data-%s="%s" ',
						esc_attr( $key ),
						esc_attr( $value )
					);
				}
				?>
			></script>
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