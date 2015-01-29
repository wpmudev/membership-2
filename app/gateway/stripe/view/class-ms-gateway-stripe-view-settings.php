<?php

class MS_Gateway_Stripe_View_Settings extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		/** Render tabbed interface. */
		?>
		<div class="ms-wrap">
			<form class="ms-gateway-settings-form ms-form wpmui-ajax-update" data-ajax="<?php echo esc_attr( $gateway->id ); ?>">
				<?php
				$description = sprintf(
					__( 'You can find your Stripe API Keys in your <a href="%1$s">Account Settings</a>.', MS_TEXT_DOMAIN ),
					'https://dashboard.stripe.com/account/apikeys" target="_blank'
				);

				MS_Helper_Html::settings_box_header( '', $description );
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
			'mode' => array(
				'id' => 'mode',
				'title' => __( 'Mode', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class' => 'ms-text-large',
			),

			'test_secret_key' => array(
				'id' => 'test_secret_key',
				'title' => __( 'API Test Secret Key', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->test_secret_key,
				'class' => 'ms-text-large',
			),

			'test_publishable_key' => array(
				'id' => 'test_publishable_key',
				'title' => __( 'API Test Publishable Key', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->test_publishable_key,
				'class' => 'ms-text-large',
			),

			'secret_key' => array(
				'id' => 'secret_key',
				'title' => __( 'API Live Secret Key', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->secret_key,
				'class' => 'ms-text-large',
			),

			'publishable_key' => array(
				'id' => 'publishable_key',
				'title' => __( 'API Live Publishable Key', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->publishable_key,
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

		return $fields;
	}

}