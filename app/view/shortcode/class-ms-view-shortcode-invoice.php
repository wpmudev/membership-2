<?php

class MS_View_Shortcode_Invoice extends MS_View {
	
	protected $data;
	
	public function to_html() {
		$invoice = $this->data['invoice'];
		$member = $this->data['member'];
		$ms_relationship = $this->data['ms_relationship'];
		$membership = $this->data['membership'];
		$gateway = $this->data['gateway'];
		
		ob_start();
		?>
			<div class="entry-content ms-invoice-wrapper">
				<h2>
					<div class="ms-invoice-sender-name">
						<?php echo MS_Plugin::instance()->settings->invoice_sender_name; ?>
					</div>
				</h2>
				<h2><?php echo __( 'Invoice #', MS_TEXT_DOMAIN ) . $invoice->id; ?></h2>
				<div class="ms-invoice-details-wrapper">
					<table class='ms-purchase-table'>
						<tr>
							<td class='ms-title-column'>
								<?php _e( 'Invoice to', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class='ms-details-column'>
								<?php echo $member->username; ?>
							</td>
						</tr>
						<tr>
							<td class='ms-title-column'>
								<?php _e( 'Due date', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class='ms-details-column'>
								<?php echo $invoice->due_date; ?>
							</td>
						</tr>
						<tr>
							<td class='ms-title-column'>
								<?php _e( 'Name', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class='ms-details-column'>
								<?php echo $membership->name; ?>
							</td>
						</tr>
						<?php if( $invoice->description ): ?>
							<tr>
							<td class='ms-title-column'>
								<?php _e( 'Description', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class='ms-desc-column' colspan='2'>
									<span class="ms-membership-description"><?php echo $invoice->title; ?></span>
								</td>
							</tr>
						<?php endif;?>
						<tr>
							<td class='ms-title-column'>
								<?php _e( 'Amount', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class='ms-details-column'>
								<?php
									if ( $invoice->amount > 0 ) {
										echo $invoice->currency . ' '. number_format( $invoice->amount, 2 );
									} 
									else {
										echo __( 'Free', MS_TEXT_DOMAIN );
									}
								?>
							</td>
						</tr>
						<?php if( $invoice->discount ): ?>
							<tr>
								<td class='ms-title-column'>
									<?php _e( 'Discount', MS_TEXT_DOMAIN ); ?>
								</td>
								<td class='ms-price-column'>
									<?php echo sprintf( '%s -%s', $invoice->currency, number_format( $invoice->discount, 2 ) ); ?>
								</td>
							</tr>
						<?php endif;?>
						<?php if( $invoice->pro_rate ): ?>
							<tr>
								<td class='ms-title-column'>
									<?php _e( 'Pro rate discount', MS_TEXT_DOMAIN ); ?>
								</td>
								<td class='ms-price-column'>
									<?php echo sprintf( '%s -%s', $invoice->currency, number_format( $invoice->pro_rate, 2 ) ); ?>
								</td>
							</tr>
						<?php endif;?>
						<tr>
							<td class='ms-title-column'>
								<?php _e( 'Total', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class='ms-price-column ms-total'>
								<?php echo $invoice->currency . ' '. number_format( $invoice->total, 2 ); ?>
							</td>
						</tr>
						<?php if( $gateway->manual_payment && $this->data['display_pay_button'] && $invoice->status != MS_Model_Invoice::STATUS_PAID ): ?>
							<tr>
								<td class='ms-buy-now-column' colspan='2' >
									<?php
										$gateway->purchase_button( $ms_relationship );
									?>
								</td>
							</tr>
						<?php endif; ?>
					</table>			
				</div>
			</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}	
}