<?php
?>
<div class="priceboxes">

<?php
	do_action( 'membership_subscription_form_before_subscriptions' );

	$subs = $this->get_subscriptions();

	$subs = apply_filters( 'membership_override_subscriptions', $subs );

	?>

		<div class="pricebox">
		<div class="topbar"><span class='title'>Solo</span><span class="price">$39.00</span></div>
		<ul class="pricedetails">
		<li><strong>Single</strong> site</li>
		<li><strong>Unlimited</strong> properties</li>
		<li><strong>Unlimited</strong> bookings</li>
		<li>Customisation library <strong>access</strong></li>
		<li><strong>12</strong> months of upgrades</li>
		<li><strong>12</strong> months of support</li>
		<li>Almost there <strong>discount</strong> price</li>
		</ul>
		</div>
		<div class="pricebox">
		<div class="topbar"><span class='title'>Premium</span><span class="price">$59.00</span></div>
		<ul class="pricedetails">
		<li><strong>Five</strong> sites</li>
		<li><strong>Unlimited</strong> properties</li>
		<li><strong>Unlimited</strong> bookings</li>
		<li>Customisation library <strong>access</strong></li>
		<li><strong>Advanced</strong> customisations library</li>
		<li><strong>12</strong> months of upgrades</li>
		<li><strong>12</strong> months of support</li>
		<li>Almost there <strong>discount</strong> price</li>
		</ul>
		</div>
		<div class="pricebox">
		<div class="topbar"><span class='title'>Developer</span><span class="price">$59.00</span></div>
		<ul class="pricedetails">
		<li><strong>Unlimited</strong> sites</li>
		<li><strong>Unlimited</strong> properties</li>
		<li><strong>Unlimited</strong> bookings</li>
		<li>Customisation library <strong>access</strong></li>
		<li><strong>Advanced</strong> customisations library</li>
		<li><strong>Beta</strong> release access</li>
		<li><strong>12</strong> months of upgrades</li>
		<li><strong>12</strong> months of support</li>
		<li>Early bird <strong>discount</strong> price</li>
		</ul>
		</div>

	<?php
	foreach((array) $subs as $key => $sub) {

		$subscription = new M_Subscription($sub->id);

		?>
		<div class="subscription">
			<div class="description">
				<h3><?php echo $subscription->sub_name(); ?></h3>
				<p><?php echo $subscription->sub_description(); ?></p>
			</div>

		<?php
			$pricing = $subscription->get_pricingarray();

			if($pricing) {
				?>
				<div class='priceforms'>
					<?php do_action('membership_purchase_button', $subscription, $pricing, $user_id); ?>
				</div>
				<?php
			}
		?>
		</div>
		<?php
	}

	do_action( 'membership_subscription_form_after_paid_subscriptions', $user_id );
	do_action( 'membership_subscription_form_after_subscriptions', $user_id );
	?>
</div> <!-- price boxes -->
<?php
?>