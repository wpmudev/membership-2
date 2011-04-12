<?php
	if( is_user_logged_in() ) {
		// We are logged in
		if( current_user_has_subscription() ) {
			// and have a subscription - hoorah
			// Removes so we can do it on a sub by sub basis
			//$signupgateway = get_user_meta( $user_id, 'membership_signup_gateway', true );
			//$gatewayissingle = get_user_meta( $user_id, 'membership_signup_gateway_is_single', true );

			$member = current_member();
			$rels = $member->get_relationships();
			foreach( (array) $rels as $rel ) {

				$sub = new M_Subscription( $rel->sub_id );
				$nextlevel = $sub->get_next_level( $rel->level_id, $rel->order_instance );

				if( !empty( $rel->usinggateway ) && ($rel->usinggateway != 'admin') ) {
					$gateway = M_get_class_for_gateway( $rel->usinggateway );

					if( !empty( $gateway ) && $gateway->issingle ) {
						$gatewayissingle = 'yes';
					} else {
						$gatewayissingle = 'no';
					}
				} else {
					$gatewayissingle = 'admin';
				}

			?>
					<div class="renew-form">
						<div class="formleft">
						<p>
							<?php echo __('<strong>You are currently on the subscription</strong> : ','membership') . $sub->sub_name(); ?>
						</p>
						<p>
						<?php
								if($member->is_marked_for_expire($rel->sub_id)) {
									echo __('Your membership has been cancelled and will expire on : ', 'membership');
									echo date( "jS F Y", mysql2date("U", $rel->expirydate));
								} else {
									if($gatewayissingle == 'yes') {
										echo __('Your membership is due to expire on : ', 'membership');
										echo date( "jS F Y", mysql2date("U", $rel->expirydate));
									} elseif($gatewayissingle == 'admin') {
										echo __('Your membership is set to automatically renew', 'membership');
									} else {
										echo __('Your membership is set to automatically renew', 'membership');
									}
									$pricing = $sub->get_pricingarray();
									$gateway->display_cancel_button( $sub, $pricing, $member->ID );
								}
						?>
						</p>
						</div>
					</div>
				<?php
					if($gatewayissingle == 'no' && !$member->is_marked_for_expire($rel->sub_id)) {
						// If it exists display we'll display the gateways upgrade forms.
						$upgradesubs = $this->get_subscriptions();
						$upgradesubs = apply_filters( 'membership_override_upgrade_subscriptions', $upgradesubs );
						foreach((array) $upgradesubs as $key => $upgradesub) {
							$subscription = new M_Subscription($upgradesub->id);

								if($upgradesub->id == $rel->sub_id ) {
									// do a cancel button
								} else {
									// do an upgrade button
									?>
									<div class="subscription">
										<div class="description">
											<h3><strong><?php _e('Move to subscription : ','membership'); ?></strong><?php echo $subscription->sub_name(); ?></h3>
											<p><?php echo $subscription->sub_description(); ?></p>
										</div>

									<?php
									$pricing = $subscription->get_pricingarray();
									if($pricing) {
										?>
										<div class='priceforms'>
											<?php
												$gateway->display_upgrade_button( $subscription, $pricing, $member->ID, $rel->sub_id );
											?>
										</div>
										<?php
									}
								}
							?>
							</div>
							<?php
						}
					} elseif($gatewayissingle == 'yes' && !$member->is_marked_for_expire($rel->sub_id)) {
						// We are on a single pay gateway so need to show the form for the next payment due.


						// Show upgrades
						$upgradesubs = $this->get_subscriptions();
						$upgradesubs = apply_filters( 'membership_override_upgrade_subscriptions', $upgradesubs );
						foreach((array) $upgradesubs as $key => $upgradesub) {
								if($upgradesub->id == $rel->sub_id ) {
									// Don't want to show our current subscription as we will display this above.
								} else {
									$subscription = new M_Subscription($upgradesub->id);
									?>
									<div class="subscription">
										<div class="description">
											<h3><?php echo $subscription->sub_name(); ?></h3>
											<p><?php echo $subscription->sub_description(); ?></p>
										</div>

									<?php
									// do an upgrade button
									$pricing = $subscription->get_pricingarray();
									if($pricing) {
										?>
										<div class='priceforms'>
											<?php
												$gateway->display_upgrade_button( $subscription, $pricing, $member->ID, $rel->sub_id );
											?>
										</div>
										<?php
									}
								}
							?>
							</div>
							<?php
						}
					}
			}

		} else {
			// No subscriptions - so display a message
			?>
				<div class="renew-form">
					<div class="formleft">
					<p>
						<?php _e('You do not currently have any subscriptions in place.','membership'); ?>
					</p>
					</div>
				</div>
			<?php
		}
	} // is user logged in
?>