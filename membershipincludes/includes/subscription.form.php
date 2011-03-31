<?php
?>
<div id="reg-form">
	<div class="formleft">

		<h2><?php _e('Step 2. Select a subscription','membership'); ?></h2>
		<p>
			<?php _e('Please select a subscription from the options below.','membership'); ?>
		</p>
		<?php
			do_action( 'membership_subscription_form_before_subscriptions', $user_id );

			$subs = $this->get_subscriptions();

			do_action( 'membership_subscription_form_before_paid_subscriptions', $user_id );

			$subs = apply_filters( 'membership_override_subscriptions', $subs );
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
		</div>
</div>
<?php
?>