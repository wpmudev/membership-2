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

if( isset($_REQUEST['gateway']) && isset($_REQUEST['extra_form']) ) {

	$gateway = M_get_class_for_gateway($_REQUEST['gateway']);
	if($gateway && is_object($gateway) && $gateway->haspaymentform == true) {
		$sub =  new M_Subscription( $subscription );
		$pricing = $sub->get_pricingarray();
		$coupon_code = membership_get_current_coupon();

		if(!empty($pricing) && !empty($coupon_code) ) {
			$pricing = $sub->apply_coupon_pricing($coupon_code,$pricing);
		}
		?>
		<div class='header' style='width: 750px'>
			<h1><?php echo __('Enter Your Credit Card Information','membership') . " " . $sub->sub_name(); ?></h1>
		</div>
		<div class='fullwidth'>
			<?php do_action('membership_payment_form', $sub, $pricing, $member->ID); ?>
		</div>
		<?php
	} else {
		?>
		<div class='fullwidth'>
			<h2><?php echo __('Misconfigured Custom Payment Gateway: haspaymentform must be true.','membership'); ?></h2>
		</div>
		<?php
	}
} else if($member->on_sub( $subscription )) {
		$sub =  new M_Subscription( $subscription );
		$coupon_code = membership_get_current_coupon();

		if(!empty($pricing) && !empty($coupon_code) ) {
			$pricing = $sub->apply_coupon_pricing($coupon_code,$pricing);
		}
	?>
		<div class='header' style='width: 750px'>
		<h1><?php echo __('Sign up for','membership') . " " . $sub->sub_name(); ?></h1>
		</div>
		<div class='fullwidth'>
			<p class='alreadybought'><?php echo __('You currently have a subscription for the <strong>', 'membership') . $sub->sub_name() . __('</strong> subscription. If you wish to sign up a different subscription then you can do below.','membership'); ?></p>

			<table class='purchasetable'>
				<?php $subs = $this->get_public_subscriptions();

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
											if($sub->coupon_label) {
												echo sprintf('<p class="membership_coupon_label">%s</p>',$sub->coupon_label);
											}
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
											if(isset($sub->coupon_label)) {
												echo sprintf('<p class="membership_coupon_label">%s</p>',$sub->coupon_label);
											}
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
	$pricing = $sub->get_pricingarray();
	$coupon_code = membership_get_current_coupon();
	if(!empty($pricing) && !empty($coupon_code) ) {
		$pricing = $sub->apply_coupon_pricing($coupon_code,$pricing);
	}
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
							if(isset($sub->coupon_label) && !empty($sub->coupon_label)) {
								echo sprintf('<p class="membership_coupon_label">%s</p>',$sub->coupon_label);
							}
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
							if(isset($sub->coupon_label) && !empty($sub->coupon_label)) {
								echo sprintf('<p class="membership_coupon_label">%s</p>',$sub->coupon_label);
							}
						}
					?>
					</td>
					<td class='buynowcolumn'>
					<?php
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