<?php

class MS_Gateway_Manual_View_Settings extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$gateway = $this->data['model'];

		$msg = __(
				'Instruct your users how to proceed with manual payments. This might include things like your bank account number and an email address where the payment confirmation should be sent.',
				MS_TEXT_DOMAIN
			) . '<br />&nbsp;<br /><em>' . __(
				'When using this payment method the user will see the following payment instructions. Since the payment cannot be confirmed automatically his membership will <b>not</b> be activated instantly! You have to manually check if the payment was made and set the members bill to "paid" to complete the payment.',
				MS_TEXT_DOMAIN
			) .
			'</em>';

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap">
			<form class="ms-gateway-settings-form ms-form wpmui-ajax-update" data-ajax="<?php echo esc_attr( $gateway->id ); ?>">
				<?php
				MS_Helper_Html::settings_box_header( '', $msg );
				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				MS_Helper_Html::settings_box_footer();
				?>
			</form>
			<div class="buttons">
				<?php
				MS_Helper_Html::html_element(
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'value' => __( 'Close', MS_TEXT_DOMAIN ),
						'class' => 'close',
					)
				);

				MS_Helper_Html::html_element(
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
						'class' => 'ms-submit-form',
						'data' => array(
							'form' => 'ms-gateway-settings-form',
						)
					)
				);
				?>
			</div>
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
				'class' => 'ms-text-large',
			),

			'pay_button_url' => array(
				'id' => 'pay_button_url',
				'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->pay_button_url,
				'class' => 'ms-text-large',
			),

			'dialog' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'dialog',
				'value' => 'Gateway_' . ucfirst( $gateway->id ) . '_View_Dialog',
			),

			'gateway_id' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'gateway_id',
				'value' => $gateway->id,
			),
		);

		return apply_filters(
			'ms_gateway_manual_view_settings_prepare_fields',
			$fields
		);
	}
}