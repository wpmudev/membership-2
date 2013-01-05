<?php
	global $M_options;
?>
<div id='membership-wrapper'>

	<fieldset>
		<legend><?php _e( 'Select a Subscription', 'membership' ) ?></legend>
		<p class="help-block"><?php _e('We have the following subscriptions available for our site. To join, simply click on the <strong>Sign Up</strong> button and then complete the registration details.','membership'); ?></p>

	<form class="form-membership" action="<?php echo get_permalink(); ?>" method="post">
		<div class="priceboxes">
		<?php
			do_action( 'membership_subscription_form_before_subscriptions' );

			$subs = $this->get_subscriptions();

			$subs = apply_filters( 'membership_override_subscriptions', $subs );

			foreach((array) $subs as $key => $sub) {

				$subscription = new M_Subscription($sub->id);

				?>
				<div class="pricebox subscriptionbox" id='subscriptionbox-<?php echo $subscription->id; ?>'>
					<div class="topbar"><span class='title'><?php echo $subscription->sub_name(); ?></span></div>
					<div class="pricedetails"><?php echo $subscription->sub_description(); ?></div>
					<div class="bottombar"><span class='price'><?php echo $subscription->sub_pricetext(); ?></span>
					<?php
						$pricing = $subscription->get_pricingarray();

						if($pricing) {
							?>
							<span class='link'>
								<?php

									if($M_options['formtype'] == 'new') {
										// pop up form
										$link = admin_url( 'admin-ajax.php' );
										$link .= '?action=buynow&amp;subscription=' . (int) $sub->id;
										$class = 'popover';
									} else {
										// original form
										$link = '?action=registeruser&amp;subscription=' . (int) $sub->id;
										$class = '';
									}

									if(empty($linktext)) {
										$linktext = apply_filters('membership_subscription_signup_text', __('Sign Up', 'membership'));
									}

									$html = "<a href='" . $link . "' class='button " . $class . " " . apply_filters('membership_subscription_button_color', 'blue') . "'>" . $linktext . "</a>";
									echo $html;
								?>
							</span>
							<?php
						}
						?>
					</div>
				</div>


			<?php
			}
			do_action( 'membership_subscription_form_after_subscriptions' );
			?>
			</div> <!-- price boxes -->
		</form>
	</fieldset>
</div>
<?php
?>