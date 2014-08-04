<?php

class MS_View_Gateway_Stripe_Card extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		?>
			<div class='ms-wrap ms-card-info-wrapper'>
				<h2><?php echo __( 'Stripe credit card info', MS_TEXT_DOMAIN ); ?> </h2>
				<table class="form-table">
					<tbody>
						<tr>
							<th><?php _e( 'Card Number', MS_TEXT_DOMAIN ); ?></th>
							<th><?php _e( 'Card Expiration date', MS_TEXT_DOMAIN ); ?></th>
						</tr>
						<tr>
							<td><?php echo '**** **** **** '. $this->data['stripe']['card_num']; ?></td>
							<td><?php echo $this->data['stripe']['card_exp']; ?></td>
						</tr>
					</tbody>
				</table>
				<form action="" method="post">
					<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
					<?php
						foreach( $this->fields as $field) {
							MS_Helper_Html::html_input( $field );
						} 
					?>
					<script
					    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
					    data-key="<?php echo $this->data['publishable_key']; ?>"
					    data-amount="0"
					    data-name="<?php echo bloginfo( 'name' ); ?>"
					    data-description="<?php echo __( 'Just change card', MS_TEXT_DOMAIN ); ?>"
					    data-panel-label="<?php echo __( 'Change credit card', MS_TEXT_DOMAIN ); ?>"
					    data-email="<?php echo $this->data['member']->email; ?>"
					    data-label="<?php echo __( 'Change card number', MS_TEXT_DOMAIN ); ?>"
					    >
				  	</script>
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
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'update_card',
				),
		);
	}
}