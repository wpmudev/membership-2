<?php

class MS_Gateway_Paypalstandard_View_Settings extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		// Render tabbed interface.
		?>
		<form class="ms-gateway-settings-form ms-form">
			<?php
			$description = sprintf(
				'%s<br />&nbsp;<br />%s<br />&nbsp;<br />%s <strong>%s</strong><br /><a href="%s" target="_blank">%s</a>',
				__( 'This advanced PayPal gateway will handle all payment types, including trial periods and recurring payments. However, it should not be used for permanent type meberships, as here it will display "pay again after 5 years" during checkout.', MS_TEXT_DOMAIN ),
				__( 'In order for Membership 2 to function correctly you must setup an IPN listening URL with PayPal. Make sure to complete this step, otherwise we are not notified when a member cancels their subscription.', MS_TEXT_DOMAIN ),
				__( 'Your IPN listening URL is:', MS_TEXT_DOMAIN ),
				$this->data['model']->get_return_url(),
				'https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/',
				__( 'Instructions &raquo;', MS_TEXT_DOMAIN )
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
			'merchant_id' => array(
				'id' => 'merchant_id',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'PayPal Merchant Account ID', MS_TEXT_DOMAIN ),
				'value' => $gateway->merchant_id,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'paypal_site' => array(
				'id' => 'paypal_site',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'PayPal Site', MS_TEXT_DOMAIN ),
				'field_options' => $gateway->get_paypal_sites(),
				'value' => $gateway->paypal_site,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'mode' => array(
				'id' => 'mode',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'PayPal Mode', MS_TEXT_DOMAIN ),
				'value' => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'pay_button_url' => array(
				'id' => 'pay_button_url',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Payment button label or URL', MS_TEXT_DOMAIN ),
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