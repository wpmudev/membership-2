<?php global $M_options; ?>
<div id="membership-wrapper">
	<fieldset>
		<legend><?php _e( 'Select a Subscription', 'membership' ) ?></legend>
		<p class="help-block"><?php _e( 'We have the following subscriptions available for our site. To join, simply click on the <strong>Sign Up</strong> button and then complete the registration details.', 'membership' ) ?></p>

		<form class="form-membership" method="post">
			<div class="priceboxes">
				<?php do_action( 'membership_subscription_form_before_subscriptions' ) ?>
				<?php $facotry = Membership_Plugin::factory() ?>
				<?php foreach ( (array)apply_filters( 'membership_override_subscriptions', $this->get_subscriptions() ) as $sub ) : ?>
					<?php $subscription = $facotry->get_subscription( $sub->id ) ?>
					<div id="subscriptionbox-<?php echo $subscription->id ?>" class="pricebox subscriptionbox">
						<div class="topbar">
							<span class="title"><?php echo $subscription->sub_name() ?></span>
						</div>
						<div class="pricedetails"><?php echo $subscription->sub_description() ?></div>
						<div class="bottombar">
							<span class="price"><?php echo $subscription->sub_pricetext() ?></span>
							<?php if( $subscription->get_pricingarray() ) : ?>
								<span class="link"><?php
									$class = '';
									if ( isset( $M_options['formtype'] ) && $M_options['formtype'] == 'new' ) {
										// pop up form
										$link = add_query_arg( array( 'action' => 'buynow', 'subscription' => (int)$sub->id ), admin_url( 'admin-ajax.php' ) );
										$class = 'popover';
									} else {
										// original form
										$link = add_query_arg( array( 'action' => 'registeruser', 'subscription' => (int)$sub->id ) );
									}

									if ( empty( $linktext ) ) {
										$linktext = apply_filters( 'membership_subscription_signup_text', __( 'Sign Up', 'membership' ) );
									}

									?><a href="<?php echo esc_url( $link ) ?>" class="button <?php echo $class ?> <?php echo apply_filters( 'membership_subscription_button_color', 'blue' ) ?>"><?php echo esc_html( $linktext ) ?></a>
								</span>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
				<?php do_action( 'membership_subscription_form_after_subscriptions' ) ?>
			</div> <!-- price boxes -->
		</form>
	</fieldset>
</div>