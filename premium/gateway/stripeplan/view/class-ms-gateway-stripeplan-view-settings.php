<?php

class MS_Gateway_Stripeplan_View_Settings extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		/** Render tabbed interface. */
		?>
		<form class="ms-gateway-settings-form ms-form">
			<?php
			$description = sprintf(
				'%1$s<br />%2$s<br />%3$s',
				__( 'Best used for recurring payments.', 'membership2' ),
				sprintf(
					__( 'You can find your Stripe API Keys in your %sAccount Settings%s.', 'membership2' ),
					'<a href="https://dashboard.stripe.com/account/apikeys" target="_blank">',
					'</a>'
				),
				sprintf(
					__( 'Set up the url <strong>%s</strong> in your %sStripe Webhook Settings%s', 'membership2' ),
					site_url( 'ms-web-hook/stripe' ),
					'<a href="https://dashboard.stripe.com/account/webhooks" target="_blank">',
					'</a>'
				)
			);

			MS_Helper_Html::settings_box_header( '', $description );
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			MS_Helper_Html::settings_box_footer();
			?>
		</form>
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
				'title' => __( 'Mode', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'test_secret_key' => array(
				'id' => 'test_secret_key',
				'title' => __( 'API Test Secret Key', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->test_secret_key,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'test_publishable_key' => array(
				'id' => 'test_publishable_key',
				'title' => __( 'API Test Publishable Key', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->test_publishable_key,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'secret_key' => array(
				'id' => 'secret_key',
				'title' => __( 'API Live Secret Key', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->secret_key,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'publishable_key' => array(
				'id' => 'publishable_key',
				'title' => __( 'API Live Publishable Key', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->publishable_key,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'vendor_logo' => array(
				'id' => 'vendor_logo',
				'title' => __( 'Vendor Logo (Must be at least 128px by 128px)', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->vendor_logo,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'pay_button_url' => array(
				'id' => 'pay_button_url',
				'title' => apply_filters(
					'ms_translation_flag',
					__( 'Payment button label', 'membership2' ),
					'gateway-button' . $gateway->id
				),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->pay_button_url,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),
		);

		// Process the fields and add missing default attributes.
		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['ajax_data'] ) ) {
				$fields[ $key ]['ajax_data']['field'] = $fields[ $key ]['id'];
				$fields[ $key ]['ajax_data']['_wpnonce'] = $nonce;
				$fields[ $key ]['ajax_data']['action'] = $action;
				$fields[ $key ]['ajax_data']['gateway_id'] = $gateway->id;
			}
		}

		return $fields;
	}

}