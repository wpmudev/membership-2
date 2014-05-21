<?php

class MS_View_Registration_Payment extends MS_View {
	
	protected $data;
	
	public function to_html() {
		$membership = $this->data['membership'];
		
		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<legend><?php _e( 'Join Membership', MS_TEXT_DOMAIN ) ?></legend>
			<p class="ms-alert-box ms-alert-success">
				<?php _e( 'Please check the details of the membership below and click on the relevant button to complete the signup.', MS_TEXT_DOMAIN ); ?>
			</p>
			<table class='ms-purchase-table'>
				<tr>
					<td class='ms-details-column'>
						<?php echo $membership->name; ?>
					</td>
					<td class='ms-price-column'>
						<?php
							if ( $membership->price > 0 ) {
								echo $this->data['currency'] . ' '. $membership->price;
							} 
							else {
								echo __( 'Free', MS_TEXT_DOMAIN );
							}
						?>
					</td>
					<td class='ms-buy-now-column'>
						<?php
							do_action( 'ms_view_registration_payment_form', $membership, $this->data['member'] );
						?>
					</td>
				</tr>
				<tr class='ms-prices-column'>
					<td colspan='3'>
						<?php
							echo '<strong>' . __( 'You will pay : ', MS_TEXT_DOMAIN ) . '</strong> ' . $membership->description;
						?>
					</td>
				</tr>
			</table>
		</div>
		<div style='clear:both;'></div>
		<?php 
		$html = ob_get_clean();
		return $html;
	}
}