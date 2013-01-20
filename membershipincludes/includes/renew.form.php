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
			$member = current_member();

			//Handle the processing if needed
			if(isset($_POST['action'])) {
				switch(addslashes($_POST['action'])) {
					case 'unsubscribe':	// Unsubscribe button has been clicked for solo gateways
										$sub_id = (int) $_POST['subscription'];
										$user = (int)	$_POST['user'];
										if( wp_verify_nonce($_REQUEST['_wpnonce'], 'cancel-sub_' . $sub_id) && $user == $member->ID ) {
											$member->mark_for_expire( $sub_id );
										}
										break;

					case 'renewfree':	// Renew a free level on this subscription
										$sub_id = (int) $_POST['subscription'];
										$user = (int)	$_POST['user'];
										$level = (int) $_POST['level'];
										if( wp_verify_nonce($_REQUEST['_wpnonce'], 'renew-sub_' . $sub_id) && $user == $member->ID ) {
											$member->record_active_payment( $sub_id, $level, time() );
										}
										//update_user_meta( $member->ID, '_membership_last_upgraded', time());
										break;

					case 'upgradesolo':	// Upgrade a solo subscription
										$sub_id = (int) $_POST['subscription'];
										$user = (int)	$_POST['user'];
										$fromsub_id = (int) $_POST['fromsub_id'];
										$gateway = $_POST['gateway'];
										if( wp_verify_nonce($_REQUEST['_wpnonce'], 'upgrade-sub_' . $sub_id) && $user == $member->ID ) {
											// Join the new subscription
											$member->create_subscription($sub_id, $gateway);
											// Remove the old subscription
											$member->drop_subscription($fromsub_id);
											// Timestamp the update
											update_user_meta( $user, '_membership_last_upgraded', time());
										}
										break;
					case 'upgradefromfree':
										$sub_id = (int) $_POST['subscription'];
										$user = (int)	$_POST['user'];
										$fromsub_id = (int) $_POST['fromsub_id'];
										$gateway = $_POST['gateway'];
										if( wp_verify_nonce($_REQUEST['_wpnonce'], 'upgrade-sub_' . $sub_id) && $user == $member->ID ) {
											// Join the new subscription
											$member->create_subscription($sub_id, $gateway);
											// Remove the old subscription
											$member->drop_subscription($fromsub_id);
											// Timestamp the update
											update_user_meta( $user, '_membership_last_upgraded', time());
										}
										break;

				}
			}

			?>
				<div id='membership-wrapper'>
					<legend><?php echo __('Your Subscriptions','membership'); ?></legend>
					<div class="alert alert-success">
					<?php echo __('Your current subscriptions are listed here. You can renew, cancel or upgrade your subscriptions by using the forms below.', 'membership'); ?>
					</div>
					<div class="priceboxes">
					<?php

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
								<div class="pricebox subscribedbox" id='subscribedbox-<?php echo $sub->id; ?>'>
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
												switch($currentlevel->sub_type) {
													case 'serial':		echo __('Your membership is set to <strong>automatically renew</strong>', 'membership');
																		break;

													case 'finite':		echo __('Your membership is due to expire on : ', 'membership');
																		echo "<strong>" . date( "jS F Y", mysql2date("U", $rel->expirydate)) . "</strong>";
																		break;

													case 'indefinite':	echo __('You are on an <strong>indefinite</strong> membership.', 'membership');
																		break;

												}
											} else {
												// Serial gateway
												switch($currentlevel->sub_type) {
													case 'serial':		echo __('Your membership is set to <strong>automatically renew</strong>', 'membership');
																		break;

													case 'finite':		echo __('Your membership is due to expire on : ', 'membership');
																		echo "<strong>" . date( "jS F Y", mysql2date("U", $rel->expirydate)) . "</strong>";
																		break;

													case 'indefinite':	echo __('You are on an <strong>indefinite</strong> membership.', 'membership');
																		break;

												}
											}
										}

										// Get the last upgrade time
										$upgradedat = get_user_meta( $member->ID, '_membership_last_upgraded', true);
										if(empty($upgradedat)) $upgradedat = strtotime('-1 year');
										$period = $M_options['upgradeperiod'];
										if(empty($period) && $period != 0) $period = 1;

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
																		<legend><?php echo __('Renewal your subscription','membership'); ?></legend>
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
																				// Need to put in coupon code bit here in case they have signed up with one
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

									?></div>
									<div class=""><span class='price' style='float:right; margin-right: 10px;'><?php
											if($gatewayissingle != 'admin' && method_exists( $gateway, 'display_cancel_button' ) && !$member->is_marked_for_expire($rel->sub_id)) {
												$pricing = $sub->get_pricingarray();
												$gateway->display_cancel_button( $sub, $pricing, $member->ID );
											}
									?></span>
								</div>
								</div> <!-- price box -->
								<?php
								if( $upgradedat <= strtotime('-' . $period . ' days') ) {
									// Show upgrades
									?>
									<legend class='upgradefrom-<?php echo $sub->id; ?>'><?php echo __('Upgrade from ','membership') . $sub->sub_name(); ?></legend>
									<?php
									$upgradesubs = $this->get_subscriptions();
									$upgradesubs = apply_filters( 'membership_override_upgrade_subscriptions', $upgradesubs );

									foreach((array) $upgradesubs as $key => $upgradesub) {
											if($upgradesub->id == $rel->sub_id ) {
												// Don't want to show our current subscription as we will display this above.
											} else {
												$subscription = new M_Subscription($upgradesub->id);
												?>
													<div class="pricebox upgradebox upgradefrom-<?php echo $sub->id; ?>" id='upgradebox-<?php echo $subscription->id; ?>'>
														<div class="topbar"><span class='title'><strong><?php _e('Move to : ','membership'); ?></strong><?php echo $subscription->sub_name(); ?></span></div>
														<div class="pricedetails">
															<?php echo $subscription->sub_description(); ?>
														</div>
														<div class=""><span class='price' style='float:right; margin-right: 10px;'><?php
																// do an upgrade button
																$pricing = $subscription->get_pricingarray();
																if($pricing) {
																	if($currentlevel->level_price < 1) {
																		// We are on a free level, so need to do an upgrade from free
																		if($gatewayissingle != 'admin' && method_exists($gateway, 'display_upgrade_from_free_button')) {
																			$gateway->display_upgrade_from_free_button( $subscription, $pricing, $member->ID, $rel->sub_id, $sub->id );
																		}

																	} else {
																		// We want a normal upgrade button
																		if($gatewayissingle != 'admin' && method_exists($gateway, 'display_upgrade_button')) {
																			$gateway->display_upgrade_button( $subscription, $pricing, $member->ID, $rel->sub_id );
																		}
																	}
																}
														?></span>
														</div>
													</div> <!-- pricebox -->
											<?php
											}
									}
								}
								?>
							<?php
						}
					?>
					</div> <!-- price boxes -->
				</div> <!-- membership wrapper -->
			<?php
		}
	}
?>