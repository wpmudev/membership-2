<?php
if(!$user_id) {
	$user = wp_get_current_user();

	$spmemuserid = $user->ID;

	if(!empty($user->ID) && is_numeric($user->ID) ) {
		$member = new M_Membership( $user->ID);
	} else {
		$member = current_member();
	}
} else {
	$member = new M_Membership( $user_id );
}

$subscription = (int) $_REQUEST['subscription'];

if($member->on_sub( $subscription )) {
		$sub =  new M_Subscription( $subscription );
	?>
		<div class='header' style='width: 750px'>
		<h1><?php echo __('Sign up for','membership') . " " . $sub->sub_name(); ?></h1>
		</div>
		<div class='fullwidth'>
			<p class='alreadybought'><?php echo __('You currently have a subscription for the <strong>', 'membership') . $sub->sub_name() . __('</strong> subscription. If you wish to sign up a different subscription then you can do below.','membership'); ?></p>

			<table class='purchasetable'>
				<?php $subs = $this->get_subscriptions();

						foreach($subs as $s) {
							if($s->id == $subscription) {
								continue;
							}
							$sub =  new M_Subscription( $s->id );
							?>
								<tr>
									<td class='detailscolumn'>
									<?php echo $sub->sub_name(); ?>
									</td>
									<td class='pricecolumn'>
									<?php
										$amount = $sub->sub_pricetext();

										if(!empty($amount)) {
											echo $amount;
										} else {
											$first = $sub->get_level_at_position(1);

											if(!empty($first)) {
												$price = $first->level_price;
												if($price == 0) {
													$price = "Free";
												} else {

													$M_options = get_option('membership_options', array());

													switch( $M_options['paymentcurrency'] ) {
														case "USD": $price = "$" . $price;
																	break;

														case "GBP":	$price = "&pound;" . $price;
																	break;

														case "EUR":	$price = "&euro;" . $price;
																	break;
													}
												}
											}
											echo $price;
										}
									?>
									</td>
									<td class='buynowcolumn'>
									<?php
									$pricing = $sub->get_pricingarray();

									if($pricing) {
										do_action('membership_purchase_button', $sub, $pricing, $member->ID);
									}
									?>
									</td>
								</tr>
							<?php
						}
				?>
			</table>
		</div>

	<?php
} else {

	$sub =  new M_Subscription( $subscription );

	?>
		<div class='header' style='width: 750px'>
		<h1><?php echo __('Sign up for','membership') . " " . $sub->sub_name(); ?></h1>
		</div>
		<div class='fullwidth'>
			<p><?php echo __('Please check the details of your subscription below and click on the relevant button to complete the subscription.','membership'); ?></p>

			<table class='purchasetable'>
				<tr>
					<td class='detailscolumn'>
					<?php echo $sub->sub_name(); ?>
					</td>
					<td class='pricecolumn'>
					<?php
						$amount = $sub->sub_pricetext();

						if(!empty($amount)) {
							echo $amount;
						} else {
							$first = $sub->get_level_at_position(1);

							if(!empty($first)) {
								$price = $first->level_price;
								if($price == 0) {
									$price = "Free";
								} else {

									$M_options = get_option('membership_options', array());

									switch( $M_options['paymentcurrency'] ) {
										case "USD": $price = "$" . $price;
													break;

										case "GBP":	$price = "&pound;" . $price;
													break;

										case "EUR":	$price = "&euro;" . $price;
													break;
									}
								}
							}
							echo $price;
						}
					?>
					</td>
					<td class='buynowcolumn'>
					<?php
					$pricing = $sub->get_pricingarray();

					if($pricing) {
						do_action('membership_purchase_button', $sub, $pricing, $member->ID);
					}
					?>
					</td>
				</tr>
			</table>

		</div>
	<?php
}
?>