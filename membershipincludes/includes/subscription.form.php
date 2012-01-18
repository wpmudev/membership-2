<?php
?>
<div class="priceboxes">
<?php
	do_action( 'membership_subscription_form_before_subscriptions' );

	$subs = $this->get_subscriptions();

	$subs = apply_filters( 'membership_override_subscriptions', $subs );

	foreach((array) $subs as $key => $sub) {

		$subscription = new M_Subscription($sub->id);

		?>
		<div class="pricebox">
			<div class="topbar"><span class='title'><?php echo $subscription->sub_name(); ?></span><span class="price">$39.00</span></div>
			<div class="pricedetails"><?php echo $subscription->sub_description(); ?></div>
			<div class="bottombar">Hello;</div>
		</div><?php
			$pricing = $subscription->get_pricingarray();
			/*
			if($pricing) {
				?>
				<div class='priceforms'>
					<?php do_action('membership_purchase_button', $subscription, $pricing, $user_id); ?>
				</div>
				<?php
			}
			*/
	}

	do_action( 'membership_subscription_form_after_subscriptions' );
	?>
</div> <!-- price boxes -->
<?php
?>