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
							do_action( 'ms_view_registration_payment_form', $membership, $this->data['member'], $this->data['move_from_id'] );
						?>
					</td>
				</tr>
				<tr class='ms-prices-column'>
					<td colspan='3'>
						<?php if( $membership->description ): ?>
							<div>
								<span class="ms-strong"><?php __( 'You will pay: ', MS_TEXT_DOMAIN ); ?><span> 
								<?php echo $membership->description; ?>
							</div>
						<?php endif;?>
						<?php if( ! empty( $this->data['pro_rate'] ) ): ?>
							<div>
								<span class="ms-strong"><?php _e( 'Pro rate discount: ', MS_TEXT_DOMAIN ); ?><span> 
								<?php echo $this->data['pro_rate']; ?>
							</div>
						<?php endif;?>
					</td>
				</tr>
			</table>
		</div>
		<?php $this->coupon_html(); ?>
		<div style='clear:both;'></div>
		<?php 
		$html = ob_get_clean();
		return $html;
	}
	
	private function coupon_html() {
		$coupon = $this->data['coupon'];
		$coupon_message = '';
		$fields = array();
		if( ! empty ( $this->data['coupon_valid'] ) ) {
			$fields = array(
				'coupon_code' => array(
						'id' => 'coupon_code',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $coupon->code,
				),
				'remove_coupon_code' => array(
						'id' => 'remove_coupon_code',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Remove Coupon', MS_TEXT_DOMAIN ),
				),
			);
		}
		else {
			$fields = array(
				'coupon_code' => array(
						'id' => 'coupon_code',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $coupon->code,
				),
				'apply_coupon_code' => array(
						'id' => 'apply_coupon_code',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Apply Coupon', MS_TEXT_DOMAIN ),
				),
			);
		}
		$coupon_message = $coupon->coupon_message;
		$have_coupon_message = __( 'Have a coupon code?', MS_TEXT_DOMAIN );
		?>
		<div class="membership-coupon">
			<div class="membership_coupon_form couponbar">
				<form method="post">
					<?php if( $coupon_message ):?>
						<p class="ms-alert-box <?php echo ( ! empty ( $this->data['coupon_valid'] ) ? 'ms-alert-success' : 'ms-alert-error' )?>">
							<?php echo $coupon_message; ?>
						</p>
					<?php endif;?>
					<div class="couponEntry">
						<?php 
							if( empty ( $this->data['coupon_valid'] ) ) {
								echo "<div class='couponQuestion'>$have_coupon_message</div>";
							}
							foreach( $fields as $field ){
								MS_Helper_Html::html_input( $field );
							}
						?>
					</div>
				</form>
			</div>
		</div>
	<?php
	}
	
}