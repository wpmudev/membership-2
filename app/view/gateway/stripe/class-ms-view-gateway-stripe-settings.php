<?php

class MS_View_Gateway_Stripe_Settings extends MS_View {

	protected $fields = array();

	protected $data;

	public function to_html() {
		$this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		/** Render tabbed interface. */
		?>
		<div class="ms-wrap">
			<form class="ms-gateway-setings-form ms-form ms-ajax-update" data-ms="<?php echo esc_attr( $gateway->id ); ?>">
				<?php MS_Helper_Html::settings_box( $this->fields ); ?>
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

		$this->fields = array(
			'mode' => array(
				'id' => 'mode',
				'title' => __( 'Mode', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
			),

			'test_secret_key' => array(
				'id' => 'test_secret_key',
				'title' => __( 'API Test Secret Key', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->test_secret_key,
				'class' => 'required',
			),

			'test_publishable_key' => array(
				'id' => 'test_publishable_key',
				'title' => __( 'API Test Publishable Key', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->test_publishable_key,
				'class' => 'required',
			),

			'secret_key' => array(
				'id' => 'secret_key',
				'title' => __( 'API Live Secret Key', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->secret_key,
				'class' => 'required',
			),

			'publishable_key' => array(
				'id' => 'publishable_key',
				'title' => __( 'API Live Publishable Key', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->publishable_key,
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

			'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'close' => array(
				'id' => 'close',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Close', MS_TEXT_DOMAIN ),
				'class' => 'ms-dlg-close',
			),

			'save' => array(
				'id' => 'save',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);
	}

}