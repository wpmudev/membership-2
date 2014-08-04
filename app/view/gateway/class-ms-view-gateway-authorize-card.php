<?php

class MS_View_Gateway_Authorize_Card extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$action_url = '';
		$this->prepare_fields();
		ob_start();
		?>
			<div class='ms-wrap ms-card-info-wrapper'>
				<h2><?php echo __( 'Authorize credit card info', MS_TEXT_DOMAIN ); ?> </h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th><?php _e( 'Card Number', MS_TEXT_DOMAIN ); ?></th>
							<th><?php _e( 'Card Expiration date', MS_TEXT_DOMAIN ); ?></th>
						</tr>
						<tr>
							<td><?php echo '**** **** **** '. $this->data['authorize']['card_num']; ?></td>
							<td><?php echo $this->data['authorize']['card_exp']; ?></td>
						</tr>
					</tbody>
				</table>
				<form action="<?php echo $action_url; ?>" method="post">
					<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
					<?php
						foreach( $this->fields as $field) {
							MS_Helper_Html::html_input( $field );
						} 
					?>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	
	private function prepare_fields() {
	
		$this->fields = array(
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['gateway']->id,
				),
				'ms_relationship_id' => array(
						'id' => 'ms_relationship_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['ms_relationship_id'],
				),
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'update_card',
				),
				'submit' => array(
						'id' => 'submit',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Change card number', MS_TEXT_DOMAIN ),
				),
		);
	}
}