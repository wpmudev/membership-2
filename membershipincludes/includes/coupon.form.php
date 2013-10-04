<?php

$coupon = membership_get_current_coupon();
if ( isset( $_REQUEST['subscription'] ) ) {
	$sub_id = (int)$_REQUEST['subscription'];
}

// Check if there are any coupons and if there are any valid for this subscription
if ( $coupon != false ) {
	// Check the coupon is a valid one
	$sub_id = (int)$_REQUEST['subscription'];
	if ( is_numeric( $sub_id ) && method_exists( $coupon, 'valid_for_subscription' ) && $coupon->valid_for_subscription( $sub_id ) ) {
		// The coupon is valid for this subscription
		$msg = '';
		// Apply the coupon so that we can check if it was applied at a later date and change the count
		$coupon->record_coupon_application( $sub_id, $pricing );
	} else {
		// The coupon is not valid for this subscription
		$msg = $coupon->get_not_valid_message( $sub_id );
		// Remove the coupon as it isn't valid
		$coupon = false;
	}
} else {
	$msg = '';
}

if ( !empty( $msg ) ) {
	echo '<div class="alert alert-error">', $msg, '</div>';
}

?>
<div class="membership-coupon">
	<div class="membership_coupon_form couponbar">
		<form method="post">
			<input type="hidden" name="subscription" value="<?php if ( isset( $_REQUEST['subscription'] ) ) echo esc_attr( $_REQUEST['subscription'] ) ?>">
			<?php if (empty($coupon) || (method_exists($coupon, 'valid_for_subscription') && !$coupon->valid_for_subscription($sub_id))) { ?>
				<div class="couponQuestion"><?php _e('Have a coupon code?', 'membership'); ?></div>
				<div class="couponEntry">
					<input type="hidden" id="coupon_sub_id" name="coupon_sub_id" value="<?php echo esc_attr($_REQUEST['subscription']); ?>" />
					<input type="text" class="couponInput" id="coupon_code" name="coupon_code" value="" />
					<input type='submit' class="button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" id="submit_coupon_code" value = '<?php _e('Apply Coupon', 'membership'); ?>' />
				</div>
			<?php } elseif (method_exists($coupon, 'get_coupon_code') && $coupon->get_coupon_code() != false) { ?>
				<div class="couponEntry">
				<?php _e('Using Coupon Code: ', 'membership'); ?>
					<strong><?php echo $coupon->get_coupon_code(); ?></strong>
					<input type="hidden" id="coupon_sub_id" name="coupon_sub_id" value="<?php echo esc_attr($_REQUEST['subscription']); ?>" />
					<input type="hidden" class="couponInput" id="coupon_code" name="coupon_code" value="" />
					<input type='submit' class="button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" id="submit_coupon_code" value = '<?php _e('Remove Coupon', 'membership'); ?>' />
				</div>
			<?php } ?>
		</form>
	</div>
</div>
