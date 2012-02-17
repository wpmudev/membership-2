<?php
	if(!is_user_logged_in()) {
		// The user isn't logged in so display a message here
		?>
		<div id='membership-wrapper'>
			<form class="form-membership" action="<?php echo get_permalink(); ?>" method="post">
				<fieldset>
				<legend><?php echo __('Your Subscriptions','membership'); ?></legend>
				<div class="alert alert-error">
				<?php echo __('You are not currently logged in. Please login to view your subscriptions.', 'membership'); ?>
				</div>

				</fieldset>
			</form>
		</div>
		<?php
	} else {
		// The user is logged in so go forward to the next check
		if(!current_user_has_subscription()) {
			// The user doesn't have a subscription so we should display a nice message and then the subscription form(?)
			?>
			<div id='membership-wrapper'>
				<form class="form-membership" action="<?php echo get_permalink(); ?>" method="post">
					<fieldset>
					<legend><?php echo __('Your Subscriptions','membership'); ?></legend>
					<div class="alert alert-error">
					<?php echo __('You do not currently have any subscriptions in place. You can sign up for a new subscription by selecting one below', 'membership'); ?>
					</div>

						<div class="priceboxes">
						<?php
							do_action( 'membership_subscription_form_before_subscriptions' );

							$subs = $this->get_subscriptions();

							$subs = apply_filters( 'membership_override_subscriptions', $subs );

							foreach((array) $subs as $key => $sub) {

								$subscription = new M_Subscription($sub->id);

								?>
								<div class="pricebox">
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
														$link = get_permalink($M_options['registration_page']);
														$link .= '?action=registeruser&amp;subscription=' . (int) $sub->id;
														$class = '';
													}

													if(empty($linktext)) {
														$linktext = apply_filters('membership_subscription_signup_text', __('Sign Up', 'membership'));
													}

													$html = "<a href='" . $link . "' class='button " . $class . " " . apply_filters('membership_subscription_button_color', 'blue') . "'>" . $linktext . "</a>";
													echo $html;
												?>
												<?php //do_action('membership_purchase_button', $subscription, $pricing, $user_id); ?>
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

					</fieldset>
				</form>
				</div>
			<?php
		} else {
			// The user has a subscription so we can display it with the information
			?>
				<div id='membership-wrapper'>
					<legend><?php echo __('Your Subscriptions','membership'); ?></legend>
					<div class="alert alert-success">
					<?php echo __('Your current subscriptions are listed here. You can renew, cancel or updgrade your subscriptions by using the forms below.', 'membership'); ?>
					</div>
				</div>
			<?php
		}
	}
?>