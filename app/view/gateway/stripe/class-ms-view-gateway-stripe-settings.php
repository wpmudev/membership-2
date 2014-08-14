<?php

class MS_View_Gateway_Stripe_Settings extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<div class='ms-settings'>
					<h2><?php echo $this->data['model']->name;?> settings</h2>
					<form action="<?php echo remove_query_arg( array( 'action', 'gateway_id' ) ); ?>" method="post" class="ms-form">
						<?php wp_nonce_field( $this->data['action'] ); ?>
						<?php
							MS_Helper_Html::settingsbox(
								$this->fields, 
								'', 
								'',
								array( 'label_element' => 'h3' ) 
							);
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
		$model = $this->data['model'];
		$this->fields = array(
			'mode' => array(
					'id' => 'mode',
					'title' => __( 'Mode', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $model->mode,
					'field_options' => $model->get_mode_types(),
			),
			'test_secret_key' => array(
					'id' => 'test_secret_key',
					'title' => __( 'API Test Secret Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->test_secret_key,
			),
			'test_publishable_key' => array(
					'id' => 'test_publishable_key',
					'title' => __( 'API Test Publishable Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->test_publishable_key,
			),
			'secret_key' => array(
					'id' => 'secret_key',
					'title' => __( 'API Live Secret Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->secret_key,
			),
			'publishable_key' => array(
					'id' => 'publishable_key',
					'title' => __( 'API Live Publishable Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->publishable_key,
			),
			'pay_button_url' => array(
					'id' => 'pay_button_url',
					'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->pay_button_url,
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
			'gateway_id' => array(
					'id' => 'gateway_id',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $model->id,
			),
			'cancel' => array(
					'id' => 'cancel',
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'title' => __('Cancel', MS_TEXT_DOMAIN ),
					'value' => __('Cancel', MS_TEXT_DOMAIN ),
					'url' => remove_query_arg( array( 'action', 'gateway_id' ) ),
					'class' => 'ms-link-button button',
			),
			'submit_gateway' => array(
					'id' => 'submit_gateway',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);
	}

}