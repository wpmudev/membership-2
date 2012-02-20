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
					<div class="priceboxes">
					<?php
						$member = current_member();

						$rels = $member->get_relationships();
						foreach( (array) $rels as $rel ) {

							$sub = new M_Subscription( $rel->sub_id );

							$nextlevel = $sub->get_next_level( $rel->level_id, $rel->order_instance );
							$currentlevel = $sub->get_level_at( $rel->level_id, $rel->order_instance );

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
								<div class="pricebox">
									<div class="topbar"><span class='title'><?php echo $sub->sub_name(); ?></span></div>
									<div class="pricedetails"><?php
										if($member->is_marked_for_expire($rel->sub_id)) {
											echo __('Your membership has been cancelled and will expire on : ', 'membership');
											echo date( "jS F Y", mysql2date("U", $rel->expirydate));
										} else {
											if($gatewayissingle == 'yes') {
												switch($currentlevel->sub_type) {
													case 'serial':
													case 'finite':		echo __('Your membership is due to expire on : ', 'membership');
																		echo "<strong>" . date( "jS F Y", mysql2date("U", $rel->expirydate)) . "</strong>";
																		break;

													case 'indefinite':	echo __('You are on an <strong>indefinite</strong> membership.', 'membership');
																		break;

												}

											} elseif($gatewayissingle == 'admin') {
												echo __('Your membership is set to <strong>automatically renew</strong>', 'membership');
											} else {
												// Serial gateway
												echo __('Your membership is set to <strong>automatically renew</strong>', 'membership');
											}
											if($gatewayissingle != 'admin') {
												//$pricing = $sub->get_pricingarray();
												//$gateway->display_cancel_button( $sub, $pricing, $member->ID );
											}
										}
									?></div>
									<div class=""><span class='price' style='float:right; margin-right: 10px;'><?php
											if($gatewayissingle != 'admin') {
												$pricing = $sub->get_pricingarray();
												$gateway->display_cancel_button( $sub, $pricing, $member->ID );
											}
									?></span>
								</div>
							<?php

						}
					?>
					</div> <!-- price boxes -->
					<?php
					// Get the last upgrade time
					$upgradedat = get_user_meta( $member->ID, '_membership_last_upgraded', true);
					if(empty($upgradedat)) $upgradedat = strtotime('-1 year');
					$period = $M_options['upgradeperiod'];
					if(empty($period)) $period = 1;

					if(!$member->is_marked_for_expire($rel->sub_id)) {
						switch( $gatewayissingle ) {

							case 'no':		// Don't need to display a renewal for this gateway as it will automatically handle it for us
											break;

							case 'yes':		if(empty($M_options['renewalperiod'])) $M_options['renewalperiod'] = 7;
											$renewalperiod = strtotime('-' . $M_options['renewalperiod'] . ' days', mysql2date("U", $rel->expirydate));

											if($nextlevel && time() >= $renewalperiod ) {
												// we have a next level so we can display the details and form for it
												if( $member->has_active_payment( $rel->sub_id, $nextlevel->level_id, $nextlevel->level_order )) {
													?>
													<div class='renew-form'>
														<div class="formleft">
															<p><?php
																echo __('Renewal for the ','membership') . "<strong>" . $nextlevel->level_period;
																switch($nextlevel->level_period_unit) {
																	case 'd':	_e(' day(s)', 'membership');
																				break;
																	case 'w':	_e(' week(s)', 'membership');
																				break;
																	case 'm':	_e(' month(s)', 'membership');
																				break;
																	case 'y':	_e(' year(s)', 'membership');
																				break;
																}
																echo "</strong> " . __('following', 'membership') . " " . date( "jS F Y", mysql2date("U", $rel->expirydate)) . " ";
																_e('has been completed.', 'membership');
															?></p>
														</div>
													</div> <!-- renew-form -->
													<?php
												} else {
												?>
												<div class='renew-form'>
													<div class="formleft">
														<p><?php
															echo __('To renew your subscription for another ','membership') . "<strong>" . $nextlevel->level_period;
															switch($nextlevel->level_period_unit) {
																case 'd':	_e(' day(s)', 'membership');
																			break;
																case 'w':	_e(' week(s)', 'membership');
																			break;
																case 'm':	_e(' month(s)', 'membership');
																			break;
																case 'y':	_e(' year(s)', 'membership');
																			break;
															}
															echo "</strong> " . __('following', 'membership') . " " . date( "jS F Y", mysql2date("U", $rel->expirydate)) . " ";
															if( $nextlevel->level_price > 0 ) {
																_e('you will need to pay', 'membership');
																echo " <strong>" . $nextlevel->level_price . " " . apply_filters('membership_real_currency_display', $M_options['paymentcurrency'] ) . "</strong>";
															} else {
																_e('click on the button to the right.', 'membership');
															}
															$pricing = $sub->get_pricingarray();
															$gateway->display_subscribe_button($sub, $pricing, $member->ID, $nextlevel->level_order);
														?></p>
													</div>
												</div> <!-- renew-form -->
												<?php
												}
											}
											break;

						}
					}

					if( $upgradedat <= strtotime('-' . $period . ' days') ) {
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
											<h3><strong><?php _e('Move to subscription : ','membership'); ?></strong><?php echo $subscription->sub_name(); ?></h3>
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
									?>
									</div> <!-- subscription -->
								<?php
								}
						}
					}
					?>
				</div> <!-- membership wrapper -->
			<?php
		}
	}
?>