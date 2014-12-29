<?php

class MS_View_Gateway_Manual_Settings extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$gateway = $this->data['model'];

		$msg = __( 'Please instruct how to proceed with manual payments, informing bank account number and email to send payment confirmation.', MS_TEXT_DOMAIN ) .
			'<br /><em>' .
			__( 'When using this payment method then the user will only see the payment instructions but the membership will not be activated for the user. You have to manually check if the payment was made and set the members bill to "paid" to complete the payment.', MS_TEXT_DOMAIN ) .
			'</em>';

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap">
			<form class="ms-gateway-setings-form ms-form wpmui-ajax-update" data-ajax="<?php echo esc_attr( $gateway->id ); ?>">
				<?php
				MS_Helper_Html::settings_box_header(
					'',
					$msg
				);

				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				?>
			</form>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	protected function prepare_fields() {
		$gateway = $this->data['model'];
		$action = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'payment_info' => array(
				'id' => 'payment_info',
				'title' => __( 'Payment Info', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				'value' => $gateway->payment_info,
				'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				'class' => 'required',
			),

			'pay_button_url' => array(
				'id' => 'pay_button_url',
				'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->pay_button_url,
			),

			'dialog' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'dialog',
				'value' => 'Gateway_' . $gateway->id . '_Dialog',
			),

			'gateway_id' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'gateway_id',
				'value' => $gateway->id,
			),

			'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'close' => array(
				'id' => 'close',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Close', MS_TEXT_DOMAIN ),
				'class' => 'close',
			),

			'save' => array(
				'id' => 'save',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);

		return apply_filters(
			'ms_view_gateway_manual_settings_prepare_fields',
			$fields
		);
	}
}