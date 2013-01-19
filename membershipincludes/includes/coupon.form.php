<?php
$coupon = membership_get_current_coupon();
if(isset($_GET['subscription'])) {
	$sub_id = (int) $_GET['subscription'];
}

if($coupon != false ) {
	// Check the coupon is a valid one
	$sub_id = (int) $_GET['subscription'];
	if(is_numeric($sub_id) && method_exists( $coupon, 'valid_for_subscription') && $coupon->valid_for_subscription( $sub_id )) {
		// The coupon is valid for this subscription
		$msg = '';
	} else {
		// The coupon is not valid for this subscription
		$msg = $coupon->get_not_valid_message( $sub_id );
	}

} else {
	$msg = '';
}

if(!empty($msg)) {
	$errormessages = "<div class='alert alert-error'>";
	$errormessages .= $msg;
	$errormessages .= "</div>";

	echo $errormessages;
}

?>
<div class="membership-coupon">
	<div class="membership_coupon_form couponbar">
		<form action='' method='POST'>
		<?php if(empty($coupon) || (method_exists( $coupon, 'valid_for_subscription') && !$coupon->valid_for_subscription( $sub_id ))) { ?>
			<div class="couponQuestion"><?php _e('Have a coupon code?','membership'); ?></div>
			<div class="couponEntry">
				<input type="text" class="couponInput" id="coupon_code" name="coupon_code" value="" />
				<input type='submit' class="button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" id="submit_coupon_code" value = '<?php _e('Apply Coupon','membership'); ?>' />
			</div>
		<?php } else { ?>
			<div class="couponEntry">
				<?php _e('Using Coupon Code: ','membership'); ?>
				<strong><?php //echo $coupon_code; ?></strong>
				<input type="hidden" class="couponInput" name="coupon_code" value="" />
				<a class="button <?php echo apply_filters('membership_subscription_button_color', 'blue'); ?>" id="submitCoupon" href="#"><?php _e('Remove Coupon','membership'); ?></a>
			</div>
		<?php } ?>
		</form>
	</div>
</div>