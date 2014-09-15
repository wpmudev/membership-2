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
			<div class='ms-wrap'>
				<div class='ms-settings'>
					<h2><?php echo sprintf( __( '%s settings', MS_TEXT_DOMAIN ), $gateway->name ); ?></h2>
					<form class="ms-gateway-setings-form ms-form" data-ms="<?php echo $gateway->id;?>">
						<?php
							MS_Helper_Html::settings_box( $this->fields );
							MS_Helper_Html::settings_footer( null, null, true );
						?>
					</form>
					<div class="clear"></div>
				</div>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	function prepare_fields() {
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
					'class' => 'ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'mode',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'test_secret_key' => array(
					'id' => 'test_secret_key',
					'title' => __( 'API Test Secret Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $gateway->test_secret_key,
					'class' => 'required ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'test_secret_key',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'test_publishable_key' => array(
					'id' => 'test_publishable_key',
					'title' => __( 'API Test Publishable Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $gateway->test_publishable_key,
					'class' => 'required ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'test_publishable_key',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'secret_key' => array(
					'id' => 'secret_key',
					'title' => __( 'API Live Secret Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $gateway->secret_key,
					'class' => 'required ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'secret_key',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'publishable_key' => array(
					'id' => 'publishable_key',
					'title' => __( 'API Live Publishable Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $gateway->publishable_key,
					'class' => 'required ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'publishable_key',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'pay_button_url' => array(
					'id' => 'pay_button_url',
					'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $gateway->pay_button_url,
					'class' => 'ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'pay_button_url',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'separator' => array(
					'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'close' => array(
					'id' => 'close',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Close', MS_TEXT_DOMAIN ),
					'class' => 'ms-link-button ms-close-button',
			),
			'save' => array(
					'id' => 'save',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);
	}

}