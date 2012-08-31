<?php

if($member->on_sub( $subscription )) {
		$sub =  new M_Subscription( $subscription );
		$coupon_code = (isset($_REQUEST['remove_coupon']) ? '' : $_REQUEST['coupon_code']);
		$pricing = $sub->get_pricingarray();
		$remove_coupon = sprintf('<input type="submit" name="remove_coupon" value="%s" href="#" id="membership_remove_coupon" />',__('Remove Coupon','membership'));
		
		if(!empty($pricing) && !empty($coupon_code) ) {
				$pricing = $sub->apply_coupon_pricing($coupon_code,$pricing);
		}
		
	?>
		<div id='membership-wrapper'>
		<form class="form-membership" action="<?php echo get_permalink(); ?>" method="post">
		<fieldset>
			<legend><?php echo __('Sign up for','membership') . " " . $sub->sub_name(); ?></legend>

			<div class="alert">
			<?php echo __('You currently have a subscription for the <strong>', 'membership') . $sub->sub_name() . __('</strong> subscription. If you wish to sign up a different subscription then you can do below.','membership'); ?>
			</div>

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
											if($sub->coupon_label) {
												echo sprintf('<p class="membership_coupon_label">%s</p>',$sub->coupon_label);
											}
										}
									?>
									</td>
									<td class='buynowcolumn'>
									<?php
									

									if(!empty($pricing)) {
										do_action('membership_purchase_button', $sub, $pricing, $member->ID);
									}
									?>
									</td>
								</tr>
							<?php
						}
				?>
			</table>
			<div class="membership-coupon">
					<?php if(empty($coupon_code)) : ?>
					<label><?php echo __('Have a coupon code?','membership'); ?>
					<input type="text" name="coupon_code" value="<?php echo (!empty($coupon_code) ? $_REQUEST['coupon_code'] : ''); ?>" /></label>
					<input type="submit" name="apply_coupon" value="<?php _e('Apply','membership'); ?>"/>
					<?php else: ?>
						<?php echo $remove_coupon; ?>
					<?php endif; ?>
			</div>
			</fieldset>
			</form>
		</div>

	<?php
} else {

	$sub =  new M_Subscription( $subscription );
	$coupon_code = (isset($_REQUEST['remove_coupon']) ? '' : $_REQUEST['coupon_code']);
	$pricing = $sub->get_pricingarray();
	$remove_coupon = sprintf('<input type="submit" name="remove_coupon" value="%s" href="#" id="membership_remove_coupon" />',__('Remove Coupon','membership'));

	if(!empty($pricing) && !empty($coupon_code) ) {
		$pricing = $sub->apply_coupon_pricing($coupon_code,$pricing);
	}
	?>
		<div id='membership-wrapper'>
			<legend><?php echo __('Sign up for','membership') . " " . $sub->sub_name(); ?></legend>

			<div class="alert alert-success">
			<?php echo __('Please check the details of your subscription below and click on the relevant button to complete the subscription.','membership'); ?>
			</div>

			<table class='purchasetable'>
			<tr>
				<td class='detailscolumn'>
				<?php echo $sub->sub_name(); ?>
				</td>
				<td class='pricecolumn'><?php
					
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
						if($sub->coupon_label) {
							echo sprintf('<p class="membership_coupon_label">%s</p>',$sub->coupon_label);
						}
					}
					?>
				</td>
				<td class='buynowcolumn'>
				<?php
				
				
				if(!empty($pricing)) {
					do_action('membership_purchase_button', $sub, $pricing, $member->ID);
				}
				?>
				</td>
			</tr>
			</table>
			<div class="membership-coupon">
				<form method="post">
					<?php if(empty($coupon_code)) : ?>
					<label><?php echo __('Have a coupon code?','membership'); ?>
					<input type="text" name="coupon_code" value="<?php echo (!empty($coupon_code) ? $_REQUEST['coupon_code'] : ''); ?>" /></label>
					<input type="submit" name="apply_coupon" value="<?php _e('Apply','membership'); ?>"/>
					<?php else: ?>
						<?php echo $remove_coupon; ?>
					<?php endif; ?>
				</form>
			</div>
		</div>
	<?php
}

?>