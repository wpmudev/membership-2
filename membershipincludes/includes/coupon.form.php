<?php
$coupon_code = membership_get_current_coupon();
?>
<div class="membership-coupon">
	<div class="membership_coupon_form couponbar">
		<?php if(!$coupon_code || empty($coupon_code)) : ?>
			<div class="couponQuestion"><?php _e('Have a coupon code?','membership'); ?></div>
			<div class="couponEntry">
				<input type="text" class="couponInput" name="coupon_code" value="" />
				<a class="button" id="submitCoupon" href="#"><?php _e('Apply Coupon','membership'); ?></a>
			</div>
		<?php else: ?>
			<div class="couponEntry">
				<?php _e('Using Coupon Code: ','membership'); ?>
				<strong><?php echo $coupon_code; ?></strong>
				<input type="hidden" class="couponInput" name="coupon_code" value="" />
				<a class="button" id="submitCoupon" href="#"><?php _e('Remove Coupon','membership'); ?></a>
			</div>
		<?php endif; ?>
	</div>
	<script type="text/javascript">
		jQuery(document).ready( function($) {

			function m_fire_coupon_update() {
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'm_set_coupon',
						coupon_code: jQuery('.membership_coupon_form .couponInput').val()
					},
					success: function(data) {

						if(data) {
							jQuery('.membership_coupon_form').replaceWith( jQuery(data).find('.membership_coupon_form'));
							bind_coupon_js();
						} else {
							alert('<?php echo __('There was an error applying your coupon.  Please contact an administrator if you think this is in error','membership'); ?>');
						}
					},
				});
				return false;
			}

			function bind_coupon_js() {
				jQuery('.membership_coupon_form #submitCoupon').click(m_fire_coupon_update);
			}

			// First Time
			bind_coupon_js();
		});
	</script>
</div>