<?php

class MS_View_Settings_Gateway_Paypal extends MS_View {

	const GATEWAY_SECTION = 'gateway_section';
	const GATEWAY_NONCE = 'gateway_nonce';
	
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
					<?php wp_nonce_field( self::GATEWAY_NONCE, self::GATEWAY_NONCE ); ?>
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
					<?php MS_Helper_Html::html_submit(); ?>
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
					'section' => self::GATEWAY_SECTION,
					'title' => __( 'Paypal Email', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->paypal_email,
			);
		}
		else {
			$merchant_id_field = array(
					'id' => 'merchant_id',
					'section' => self::GATEWAY_SECTION,
					'title' => __( 'PayPal Merchant Account ID', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->merchant_id,
			);
		}
		
		$this->fields = array(
			'description' => array(
					'id' => 'description',
					'section' => self::GATEWAY_SECTION,
					'title' => __( 'Description', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->description,
			),
			'merchant_id' => $merchant_id_field,
			'paypal_site' => array(
					'id' => 'paypal_site',
					'section' => self::GATEWAY_SECTION,
					'title' => __( 'PayPal Site', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $model->get_paypal_sites(),
					'value' => $model->paypal_site,
			),
			'paypal_status' => array(
					'id' => 'paypal_status',
					'section' => self::GATEWAY_SECTION,
					'title' => __( 'PayPal Mode', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $model->paypal_status,
					'field_options' => $model->get_status_types(),
			),
			'pay_button_url' => array(
					'id' => 'pay_button_url',
					'section' => self::GATEWAY_SECTION,
					'title' => __( 'Payment button', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->pay_button_url,
			),
			'upgrade_button_url' => array(
					'id' => 'upgrade_button_url',
					'section' => self::GATEWAY_SECTION,
					'title' => __( 'Upgrade button', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->upgrade_button_url,
			),
			'cancel_button_url' => array(
					'id' => 'cancel_button_url',
					'section' => self::GATEWAY_SECTION,
					'title' => __( 'Cancel button', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->cancel_button_url,
			),
			'action' => array(
					'id' => 'action',
					'section' => self::GATEWAY_SECTION,
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
			'gateway_id' => array(
					'id' => 'gateway_id',
					'section' => self::GATEWAY_SECTION,
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $model->id,
			),
		);
	}

}