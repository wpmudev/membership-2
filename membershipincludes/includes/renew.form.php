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
				// try to find out if the gateway is single.
				$trans = get_last_transaction_for_user_and_sub( $member->ID, $rel->sub_id );

				if( !empty( $trans ) && !empty( $trans->transaction_gateway ) ) {
					$lastgateway = $trans->transaction_gateway;
					$gateway = M_get_class_for_gateway( $lastgateway );

					if( !empty( $gateway ) && $gateway->issingle ) {
						$gatewayissingle = 'yes';
					} else {
						$gatewayissingle = 'no';
					}

					$signupgateway = $lastgateway;
				}
			?>
					<div class="renew-form">
						<div class="formleft">
						<p>
							<?php echo __('<strong>You are currently on the subscription</strong> : ','membership') . $sub->sub_name(); ?>
						</p>
						<p>
						<?php
								if($gatewayissingle == 'yes') {
									echo __('Your membership is due to expire on : ', 'membership');
									echo date( "jS F Y", mysql2date("U", $rel->expirydate));
								} else {
									echo __('Your membership is set to automatically renew', 'membership');
								}
						?>

						</p>


						</div>
					</div>
				<?php
				print_r($nextlevel);
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