<?php

class MS_View_Settings_Gateway_Paypal extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<h2><?php echo $this->data['model']->name;?> settings</h2>
				<form action="<?php echo remove_query_arg( array( 'action', 'gateway_id' ) ); ?>" method="post" class="ms-form">
					<?php wp_nonce_field( $this->data['action'] ); ?>
					<table class="form-table">
						<tbody>
							<?php foreach( $this->fields as $field ): ?>
								<tr>
									<td>
										<?php MS_Helper_Html::html_input( $field ); ?>
									</td>
								</tr>
								<?php endforeach; ?>
						</tbody>
					</table>
					<?php MS_Helper_Html::html_submit( array( 'id' => 'submit_gateway') ); ?>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	function prepare_fields() {
		$model = $this->data['model'];
		if( $model->id == 'paypal_single_gateway' ) {
			$merchant_id_field = array(
					'id' => 'paypal_email',
					'title' => __( 'Paypal Email', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->paypal_email,
			);
		}
		else {
			$merchant_id_field = array(
					'id' => 'merchant_id',
					'title' => __( 'PayPal Merchant Account ID', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->merchant_id,
			);
		}
		
		$this->fields = array(
			'description' => array(
					'id' => 'description',
					'title' => __( 'Description', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->description,
			),
			'merchant_id' => $merchant_id_field,
			'paypal_site' => array(
					'id' => 'paypal_site',
					'title' => __( 'PayPal Site', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $model->get_paypal_sites(),
					'value' => $model->paypal_site,
			),
			'mode' => array(
					'id' => 'mode',
					'title' => __( 'PayPal Mode', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $model->mode,
					'field_options' => $model->get_mode_types(),
			),
			'pay_button_url' => array(
					'id' => 'pay_button_url',
					'title' => __( 'Payment button', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->pay_button_url,
			),
// 			'upgrade_button_url' => array(
// 					'id' => 'upgrade_button_url',
// 					'title' => __( 'Upgrade button', MS_TEXT_DOMAIN ),
// 					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
// 					'value' => $model->upgrade_button_url,
// 			),
// 			'cancel_button_url' => array(
// 					'id' => 'cancel_button_url',
// 					'title' => __( 'Cancel button', MS_TEXT_DOMAIN ),
// 					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
// 					'value' => $model->cancel_button_url,
// 			),
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
		);
	}

}