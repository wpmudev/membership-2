<?php
	if( is_user_logged_in() ) {
		// We are logged in
		if( current_user_has_subscription() ) {
			// and have a subscription - hoorah

			$member = current_member();

			$signupgateway = get_user_meta( $user_id, 'membership_signup_gateway', true );
			$gatewayissingle = get_user_meta( $user_id, 'membership_signup_gateway_is_single', true );

			$rels = $member->get_relationships();
			foreach( (array) $rels as $rel ) {

				$sub = new M_Subscription( $rel->sub_id );

				$nextlevel = $sub->get_next_level($rel->level_id, $rel->order_instance);

				?>
					<div class="renew-form">
						<div class="formleft">
						<p>
							<?php echo __('<strong>You are currently on the subscription</strong> : ','membership') . $sub->sub_name(); ?>
						</p>
						<p>
						<?php 	echo __('Your membership is due to expire on : ', 'membership');
								echo date( "jS F Y", mysql2date("U", $rel->expirydate));
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