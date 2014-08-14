<?php

class MS_View_Gateway_Manual_Settings extends MS_View {

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
								__( 'Please instruct how to proceed with manual payments, informing bank account number and email to send payment confirmation.', MS_TEXT_DOMAIN ),
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
			'payment_info' => array(
					'id' => 'payment_info',
					'title' => __( 'Payment Info', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'value' => $model->payment_info,
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
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