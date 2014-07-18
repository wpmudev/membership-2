<?php

class MS_View_Gateway_Stripe_Card extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		ob_start();
		?>
			<div class='ms-wrap'>
				<h2><?php echo __( 'Credit card info', MS_TEXT_DOMAIN ); ?> </h2>
				<table class="form-table">
					<tbody>
						<tr>
							<td><?php _e( 'Card Number', MS_TEXT_DOMAIN ); ?></td>
							<td><?php _e( 'Expiration date', MS_TEXT_DOMAIN ); ?></td>
						</tr>
						<tr>
							<td><?php echo '**** **** **** '. $this->data['stripe']['card_num']; ?></td>
							<td><?php echo $this->data['stripe']['card_exp']; ?></td>
						</tr>
					</tbody>
				</table>
				<form action="" method="post">
					<?php wp_nonce_field( 'change_card' ); ?>
					<script
					    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
					    data-key="<?php echo $this->data['publishable_key']; ?>"
					    data-amount="0"
					    data-name="<?php echo bloginfo( 'name' ); ?>"
					    data-description="<?php echo __( 'Just change card', MS_TEXT_DOMAIN ); ?>"
					    data-panel-label="<?php echo __( 'Change credit card', MS_TEXT_DOMAIN ); ?>"
					    data-email="<?php echo $this->data['member']->email; ?>"
					    >
				  	</script>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
}