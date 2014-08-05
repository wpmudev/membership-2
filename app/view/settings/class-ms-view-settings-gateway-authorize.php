<?php

class MS_View_Settings_Gateway_Authorize extends MS_View {

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
		$this->fields = array(
			'mode' => array(
					'id' => 'mode',
					'title' => __( 'Mode', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $model->mode,
					'field_options' => $model->get_mode_types(),
			),
			'api_login_id' => array(
					'id' => 'api_login_id',
					'title' => __( 'API Login ID', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->api_login_id,
			),
			'api_transaction_key' => array(
					'id' => 'api_transaction_key',
					'title' => __( 'API Transaction Key', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->api_transaction_key,
			),
			'pay_button_url' => array(
					'id' => 'pay_button_url',
					'title' => __( 'Payment button', MS_TEXT_DOMAIN ),
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
		);
	}

}