<?php

class paypalexpress extends M_Gateway {

	var $gateway = 'paypalexpress';
	var $title = 'PayPal Express - with Subscriptions';

	function paypalexpress() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));

		// If I want to override the transactions output - then I can use this action
		//add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		if($this->is_active()) {
			// Subscription form gateway
			add_action('membership_purchase_button', array(&$this, 'display_subscribe_button'), 1, 3);

			// Payment return
			add_action('membership_handle_payment_return_' . $this->gateway, array(&$this, 'handle_paypal_return'));
		}

	}

	function mysettings() {

		global $M_options;

		?>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
		  <th scope="row"><?php _e('PayPal Email', 'membership') ?></th>
		  <td><input type="text" name="paypal_email" value="<?php esc_attr_e(get_option( $this->gateway . "_paypal_email" )); ?>" />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('PayPal Site', 'membership') ?></th>
		  <td><select name="paypal_site">
		  <?php
		      $paypal_site = get_option( $this->gateway . "_paypal_site" );
		      $sel_locale = empty($paypal_site) ? 'US' : $paypal_site;
		      $locales = array(
		          'AU'	=> 'Australia',
		          'AT'	=> 'Austria',
		          'BE'	=> 'Belgium',
		          'CA'	=> 'Canada',
		          'CN'	=> 'China',
		          'FR'	=> 'France',
		          'DE'	=> 'Germany',
		          'HK'	=> 'Hong Kong',
		          'IT'	=> 'Italy',
		          'MX'	=> 'Mexico',
		          'NL'	=> 'Netherlands',
				  'NZ'	=>	'New Zealand',
		          'PL'	=> 'Poland',
		          'SG'	=> 'Singapore',
		          'ES'	=> 'Spain',
		          'SE'	=> 'Sweden',
		          'CH'	=> 'Switzerland',
		          'GB'	=> 'United Kingdom',
		          'US'	=> 'United States'
		          );

		      foreach ($locales as $key => $value) {
					echo '<option value="' . esc_attr($key) . '"';
		 			if($key == $sel_locale) echo 'selected="selected"';
		 			echo '>' . esc_html($value) . '</option>' . "\n";
		      }
		  ?>
		  </select>
		  <br />
		  <?php //_e('Format: 00.00 - Ex: 1.25', 'supporter') ?></td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Paypal Currency', 'membership') ?></th>
		  <td><?php
			if(empty($M_options['paymentcurrency'])) {
				$M_options['paymentcurrency'] = 'USD';
			}
			echo esc_html($M_options['paymentcurrency']); ?></td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('PayPal Mode', 'membership') ?></th>
		  <td><select name="paypal_status">
		  <option value="live" <?php if (get_option( $this->gateway . "_paypal_status" ) == 'live') echo 'selected="selected"'; ?>><?php _e('Live Site', 'membership') ?></option>
		  <option value="test" <?php if (get_option( $this->gateway . "_paypal_status" ) == 'test') echo 'selected="selected"'; ?>><?php _e('Test Mode (Sandbox)', 'membership') ?></option>
		  </select>
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Subscription button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_paypal_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );
		  ?>
		  <td><input type="text" name="paypal_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Upgrade button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_paypal_upgrade_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );
		  ?>
		  <td><input type="text" name="_paypal_upgrade_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
		  <br />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Cancel button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_paypal_cancel_button", 'https://www.paypal.com/en_US/i/btn/btn_unsubscribe_LG.gif' );
		  ?>
		  <td><input type="text" name="_paypal_cancel_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
		  <br />
		  </td>
		  </tr>
		</tbody>
		</table>
		<?php
	}

	function build_custom($user_id, $sub_id, $amount, $fromsub_id = false) {

		$custom = '';

		//fake:user:sub:key

		$custom = time() . ':' . $user_id . ':' . $sub_id . ':';
		$key = md5('MEMBERSHIP' . $amount);

		$custom .= $key;

		if($fromsub_id !== false) {
			$custom .= ":" . $fromsub_id;
		}

		return $custom;

	}

	function single_sub_button($pricing, $subscription, $user_id, $norepeat = false) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '';

		//if($pricing[0]['type'] == 'indefinite') $pricing[0]['days'] = 365;

		if (get_option( $this->gateway . "_paypal_status" ) == 'live') {
			$form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
		} else {
			$form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
		}
		$form .= '<input type="hidden" name="business" value="' . esc_attr(get_option( $this->gateway . "_paypal_email" )) . '">';
		$form .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
		$form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
		$form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
		$form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] .'">';
		$form .= '<input type="hidden" name="a3" value="' . number_format($pricing[0]['amount'], 2) . '">';
		$form .= '<input type="hidden" name="p3" value="' . $pricing[0]['period'] . '">';
		$form .= '<input type="hidden" name="t3" value="' . strtoupper($pricing[0]['unit']) . '"> <!-- Set recurring payments until canceled. -->';

		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, number_format($pricing[0]['amount'], 2)) .'">';

		$form .= '<input type="hidden" name="return" value="' . get_option('home') . '">';
		$form .= '<input type="hidden" name="cancel_return" value="' . get_option('home') . '">';

		$form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option( $this->gateway . "_paypal_site" )) . '">';
		$form .= '<input type="hidden" name="notify_url" value="' . trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway) . '">';

		if($norepeat) {
			$form .= '<input type="hidden" name="src" value="0">';
		} else {
			$form .= '<input type="hidden" name="src" value="1">';
		}

		$button = get_option( $this->gateway . "_paypal_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );

		$form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
		$form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
		$form .= '</form>';

		return $form;

	}

	function complex_sub_button($pricing, $subscription, $user_id) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '';

		if (get_option( $this->gateway . "_paypal_status" ) == 'live') {
			$form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
		} else {
			$form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
		}
		$form .= '<input type="hidden" name="business" value="' . esc_attr(get_option( $this->gateway . "_paypal_email" )) . '">';
		$form .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
		$form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
		$form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
		$form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] .'">';

		// complex bits here
		$count = 1;
		$ff = array();
		foreach((array) $pricing as $key => $price) {

			switch($price['type']) {

				case 'finite':	if(empty($price['amount'])) $price['amount'] = '0';
								if($count < 3) {
									$ff['a' . $count] = number_format($price['amount'], 2);
									$ff['p' . $count] = $price['period'];
									$ff['t' . $count] = strtoupper($price['unit']);
								} else {
									// Or last finite is going to be the end of the subscription payments
									$ff['a3'] = number_format($price['amount'], 2);
									$ff['p3'] = $price['period'];
									$ff['t3'] = strtoupper($price['unit']);
									$ff['src'] = '0';
								}
								$count++;
								break;

				case 'indefinite':
								if(empty($price['amount'])) $price['amount'] = '0';

								if($price['amount'] == '0') {
									// The indefinite rule is free, we need to move any previous
									// steps up to this one as we can't have a free a3
									if( isset($ff['a2']) && $ff['a2'] != '0.00' ) {
										// we have some other earlier rule so move it up
										$ff['a3'] = $ff['a2'];
										$ff['p3'] = $ff['p2'];
										$ff['t3'] = $ff['t2'];
										unset($ff['a2']);
										unset($ff['p2']);
										unset($ff['t2']);
										$ff['src'] = '0';
									} elseif( isset($ff['a1']) && $ff['a1'] != '0.00' ) {
										$ff['a3'] = $ff['a1'];
										$ff['p3'] = $ff['p1'];
										$ff['t3'] = $ff['t1'];
										unset($ff['a1']);
										unset($ff['p1']);
										unset($ff['t1']);
										$ff['src'] = '0';
									}
								} else {
									$ff['a3'] = number_format($price['amount'], 2);
									$ff['p3'] = 1;
									$ff['t3'] = 'Y';
									$ff['src'] = '0';
								}
								break;
				case 'serial':
								if(empty($price['amount'])) $price['amount'] = '0';

								if($price['amount'] == '0') {
									// The serial rule is free, we need to move any previous
									// steps up to this one as we can't have a free a3
									if( isset($ff['a2']) && $ff['a2'] != '0.00' ) {
										// we have some other earlier rule so move it up
										$ff['a3'] = $ff['a2'];
										$ff['p3'] = $ff['p2'];
										$ff['t3'] = $ff['t2'];
										unset($ff['a2']);
										unset($ff['p2']);
										unset($ff['t2']);
										$ff['src'] = '1';
									} elseif( isset($ff['a1']) && $ff['a1'] != '0.00' ) {
										$ff['a3'] = $ff['a1'];
										$ff['p3'] = $ff['p1'];
										$ff['t3'] = $ff['t1'];
										unset($ff['a1']);
										unset($ff['p1']);
										unset($ff['t1']);
										$ff['src'] = '1';
									}
								} else {
									$ff['a3'] = number_format($price['amount'], 2);
									$ff['p3'] = $price['period'];
									$ff['t3'] = strtoupper($price['unit']);
									$ff['src'] = '1';
								}

								break;
			}
		}

		if(!empty($ff)) {
			foreach($ff as $key => $value) {
				$form .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
			}
		}

		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, $ff['a3']) .'">';

		// Remainder of the easy bits

		$form .= '<input type="hidden" name="return" value="' . get_option('home') . '">';
		$form .= '<input type="hidden" name="cancel_return" value="' . get_option('home') . '">';


		$form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option( $this->gateway . "_paypal_site" )) . '">';
		$form .= '<input type="hidden" name="notify_url" value="' . trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway) . '">';

		$button = get_option( $this->gateway . "_paypal_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );

		$form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
		$form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
		$form .= '</form>';

		return $form;

	}

	function build_subscribe_button($subscription, $pricing, $user_id) {

		if(!empty($pricing)) {

			// check to make sure there is a price in the subscription
			// we don't want to display free ones for a payment system
			$free = true;
			foreach($pricing as $key => $price) {
				if(!empty($price['amount']) && $price['amount'] > 0 ) {
					$free = false;
				}
			}

			if(!$free) {
				if(count($pricing) == 1) {
					// A basic price or a single subscription
					if(in_array($pricing[0]['type'], array('indefinite','finite'))) {
						// one-off payment
						return $this->single_sub_button($pricing, $subscription, $user_id, true);
					} else {
						// simple subscription
						return $this->single_sub_button($pricing, $subscription, $user_id);
					}
				} else {
					// something much more complex

					return $this->complex_sub_button($pricing, $subscription, $user_id);

				}
			}

		}

	}

	function single_upgrade_button($pricing, $subscription, $user_id, $norepeat = false, $fromsub_id = false) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '';

		//if($pricing[0]['type'] == 'indefinite') $pricing[0]['days'] = 365;

		if (get_option( $this->gateway . "_paypal_status" ) == 'live') {
			$form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
		} else {
			$form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
		}
		$form .= '<input type="hidden" name="business" value="' . esc_attr(get_option( $this->gateway . "_paypal_email" )) . '">';
		$form .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
		$form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
		$form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
		$form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] .'">';
		$form .= '<input type="hidden" name="a3" value="' . number_format($pricing[0]['amount'], 2) . '">';
		$form .= '<input type="hidden" name="p3" value="' . $pricing[0]['period'] . '">';
		$form .= '<input type="hidden" name="t3" value="' . strtoupper($pricing[0]['unit']) . '"> <!-- Set recurring payments until canceled. -->';

		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, number_format($pricing[0]['amount'], 2), $fromsub_id) .'">';

		$form .= '<input type="hidden" name="return" value="' . get_option('home') . '">';
		$form .= '<input type="hidden" name="cancel_return" value="' . get_option('home') . '">';

		$form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option( $this->gateway . "_paypal_site" )) . '">';
		$form .= '<input type="hidden" name="notify_url" value="' . trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway) . '">';

		if($norepeat) {
			$form .= '<input type="hidden" name="src" value="0">';
		} else {
			$form .= '<input type="hidden" name="src" value="1">';
		}

		$form .= '<input type="hidden" name="modify" value="2">';

		$button = get_option( $this->gateway . "_paypal_upgrade_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );

		$form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
		$form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
		$form .= '</form>';

		return $form;

	}

	function complex_upgrade_button($pricing, $subscription, $user_id, $fromsub_id = false) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '';

		if (get_option( $this->gateway . "_paypal_status" ) == 'live') {
			$form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
		} else {
			$form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
		}
		$form .= '<input type="hidden" name="business" value="' . esc_attr(get_option( $this->gateway . "_paypal_email" )) . '">';
		$form .= '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
		$form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
		$form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
		$form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] .'">';

		// complex bits here
		$count = 1;
		$ff = array();
		foreach((array) $pricing as $key => $price) {

			switch($price['type']) {

				case 'finite':	if(empty($price['amount'])) $price['amount'] = '0';
								if($count < 3) {
									$ff['a' . $count] = number_format($price['amount'], 2);
									$ff['p' . $count] = $price['period'];
									$ff['t' . $count] = strtoupper($price['unit']);
								} else {
									// Or last finite is going to be the end of the subscription payments
									$ff['a3'] = number_format($price['amount'], 2);
									$ff['p3'] = $price['period'];
									$ff['t3'] = strtoupper($price['unit']);
									$ff['src'] = '0';
								}
								$count++;
								break;

				case 'indefinite':
								if(empty($price['amount'])) $price['amount'] = '0';

								if($price['amount'] == '0') {
									// The indefinite rule is free, we need to move any previous
									// steps up to this one as we can't have a free a3
									if( isset($ff['a2']) && $ff['a2'] != '0.00' ) {
										// we have some other earlier rule so move it up
										$ff['a3'] = $ff['a2'];
										$ff['p3'] = $ff['p2'];
										$ff['t3'] = $ff['t2'];
										unset($ff['a2']);
										unset($ff['p2']);
										unset($ff['t2']);
										$ff['src'] = '0';
									} elseif( isset($ff['a1']) && $ff['a1'] != '0.00' ) {
										$ff['a3'] = $ff['a1'];
										$ff['p3'] = $ff['p1'];
										$ff['t3'] = $ff['t1'];
										unset($ff['a1']);
										unset($ff['p1']);
										unset($ff['t1']);
										$ff['src'] = '0';
									}
								} else {
									$ff['a3'] = number_format($price['amount'], 2);
									$ff['p3'] = 1;
									$ff['t3'] = 'Y';
									$ff['src'] = '0';
								}
								break;
				case 'serial':
								if(empty($price['amount'])) $price['amount'] = '0';

								if($price['amount'] == '0') {
									// The serial rule is free, we need to move any previous
									// steps up to this one as we can't have a free a3
									if( isset($ff['a2']) && $ff['a2'] != '0.00' ) {
										// we have some other earlier rule so move it up
										$ff['a3'] = $ff['a2'];
										$ff['p3'] = $ff['p2'];
										$ff['t3'] = $ff['t2'];
										unset($ff['a2']);
										unset($ff['p2']);
										unset($ff['t2']);
										$ff['src'] = '1';
									} elseif( isset($ff['a1']) && $ff['a1'] != '0.00' ) {
										$ff['a3'] = $ff['a1'];
										$ff['p3'] = $ff['p1'];
										$ff['t3'] = $ff['t1'];
										unset($ff['a1']);
										unset($ff['p1']);
										unset($ff['t1']);
										$ff['src'] = '1';
									}
								} else {
									$ff['a3'] = number_format($price['amount'], 2);
									$ff['p3'] = $price['period'];
									$ff['t3'] = strtoupper($price['unit']);
									$ff['src'] = '1';
								}

								break;
			}
		}

		if(!empty($ff)) {
			foreach($ff as $key => $value) {
				$form .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
			}
		}

		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, $ff['a3'], $fromsub_id) .'">';

		// Remainder of the easy bits

		$form .= '<input type="hidden" name="return" value="' . get_option('home') . '">';
		$form .= '<input type="hidden" name="cancel_return" value="' . get_option('home') . '">';


		$form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option( $this->gateway . "_paypal_site" )) . '">';
		$form .= '<input type="hidden" name="notify_url" value="' . trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway) . '">';

		$form .= '<input type="hidden" name="modify" value="2">';

		$button = get_option( $this->gateway . "_paypal_upgrade_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );

		$form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
		$form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
		$form .= '</form>';

		return $form;

	}

	function build_upgrade_button($subscription, $pricing, $user_id, $fromsub_id = false) {

		if(!empty($pricing)) {

			// check to make sure there is a price in the subscription
			// we don't want to display free ones for a payment system
			$free = true;
			foreach($pricing as $key => $price) {
				if(!empty($price['amount']) && $price['amount'] > 0 ) {
					$free = false;
				}
			}

			if(!$free) {
				if(count($pricing) == 1) {
					// A basic price or a single subscription
					if(in_array($pricing[0]['type'], array('indefinite','finite'))) {
						// one-off payment
						return $this->single_upgrade_button($pricing, $subscription, $user_id, true, $fromsub_id);
					} else {
						// simple subscription
						return $this->single_upgrade_button($pricing, $subscription, $user_id, false, $fromsub_id);
					}
				} else {
					// something much more complex
					return $this->complex_upgrade_button($pricing, $subscription, $user_id, $fromsub_id);

				}
			}

		}

	}

	function display_subscribe_button($subscription, $pricing, $user_id) {
		echo $this->build_subscribe_button($subscription, $pricing, $user_id);

	}

	function display_upgrade_button($subscription, $pricing, $user_id, $fromsub_id = false) {
		echo $this->build_upgrade_button($subscription, $pricing, $user_id, $fromsub_id);
	}

	function display_cancel_button($subscription, $pricing, $user_id) {

		$form = '';

		if (get_option( $this->gateway . "_paypal_status" ) == 'live') {
			$form .= '<a class="unsubbutton" href="https://www.paypal.com/cgi-bin/webscr';
		} else {
			$form .= '<a class="unsubbutton" href="https://www.sandbox.paypal.com/cgi-bin/webscr';
		}

		$form .= '?cmd=_subscr-find&alias=' . urlencode(esc_attr(get_option( $this->gateway . "_paypal_email" ))) . '">';

		$button = get_option( $this->gateway . "_paypal_cancel_button", 'https://www.paypal.com/en_US/i/btn/btn_unsubscribe_LG.gif' );
		$form .= '<img border="0" src="' . esc_attr($button) . '">';
		$form .= '</a>';

		echo $form;

	}

	function update() {

		if(isset($_POST['paypal_email'])) {
			update_option( $this->gateway . "_paypal_email", $_POST[ 'paypal_email' ] );
			update_option( $this->gateway . "_paypal_site", $_POST[ 'paypal_site' ] );
			update_option( $this->gateway . "_currency", $_POST[ 'currency' ] );
			update_option( $this->gateway . "_paypal_status", $_POST[ 'paypal_status' ] );
			update_option( $this->gateway . "_paypal_button", $_POST[ 'paypal_button' ] );
			update_option( $this->gateway . "_paypal_upgrade_button", $_POST[ '_paypal_upgrade_button' ] );
			update_option( $this->gateway . "_paypal_cancel_button", $_POST[ '_paypal_cancel_button' ] );
		}

		// default action is to return true
		return true;

	}

	// IPN stuff
	function handle_paypal_return() {
		// PayPal IPN handling code

		if ((isset($_POST['payment_status']) || isset($_POST['txn_type'])) && isset($_POST['custom'])) {

			if (get_option( $this->gateway . "_paypal_status" ) == 'live') {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			$req = 'cmd=_notify-validate';
			if (!isset($_POST)) $_POST = $HTTP_POST_VARS;
			foreach ($_POST as $k => $v) {
				if (get_magic_quotes_gpc()) $v = stripslashes($v);
				$req .= '&' . $k . '=' . $v;
			}

			$header = 'POST /cgi-bin/webscr HTTP/1.0' . "\r\n"
					. 'Content-Type: application/x-www-form-urlencoded' . "\r\n"
					. 'Content-Length: ' . strlen($req) . "\r\n"
					. "\r\n";

			@set_time_limit(60);
			if ($conn = @fsockopen($domain, 80, $errno, $errstr, 30)) {
				fputs($conn, $header . $req);
				socket_set_timeout($conn, 30);

				$response = '';
				$close_connection = false;
				while (true) {
					if (feof($conn) || $close_connection) {
						fclose($conn);
						break;
					}

					$st = @fgets($conn, 4096);
					if ($st === false) {
						$close_connection = true;
						continue;
					}

					$response .= $st;
				}

				$error = '';
				$lines = explode("\n", str_replace("\r\n", "\n", $response));
				// looking for: HTTP/1.1 200 OK
				if (count($lines) == 0) $error = 'Response Error: Header not found';
				else if (substr($lines[0], -7) != ' 200 OK') $error = 'Response Error: Unexpected HTTP response';
				else {
					// remove HTTP header
					while (count($lines) > 0 && trim($lines[0]) != '') array_shift($lines);

					// first line will be empty, second line will have the result
					if (count($lines) < 2) $error = 'Response Error: No content found in transaction response';
					else if (strtoupper(trim($lines[1])) != 'VERIFIED') $error = 'Response Error: Unexpected transaction response';
				}

				if ($error != '') {
					echo $error;
					exit;
				}
			}

			// handle cases that the system must ignore
			//if ($_POST['payment_status'] == 'In-Progress' || $_POST['payment_status'] == 'Partially-Refunded') exit;
			$new_status = false;
			// process PayPal response
			switch ($_POST['payment_status']) {
				case 'Partially-Refunded':
					break;

				case 'In-Progress':
					break;

				case 'Completed':
				case 'Processed':
					// case: successful payment
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					$this->record_transaction($user_id, $sub_id, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], '');

					// Added for affiliate system link
					do_action('membership_payment_processed', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
					break;

				case 'Reversed':
					// case: charge back
					$note = 'Last transaction has been reversed. Reason: Payment has been reversed (charge back)';
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					$this->record_transaction($user_id, $sub_id, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);

					$member = new M_Membership($user_id);
					if($member) {
						$member->expire_subscription($sub_id);
						$member->deactivate();
					}

					do_action('membership_payment_reversed', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
					break;

				case 'Refunded':
					// case: refund
					$note = 'Last transaction has been reversed. Reason: Payment has been refunded';
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					$this->record_transaction($user_id, $sub_id, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);

					$member = new M_Membership($user_id);
					if($member) {
						$member->expire_subscription($sub_id);
					}

					do_action('membership_payment_refunded', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
					break;

				case 'Denied':
					// case: denied
					$note = 'Last transaction has been reversed. Reason: Payment Denied';
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					$this->record_transaction($user_id, $sub_id, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);

					$member = new M_Membership($user_id);
					if($member) {
						$member->expire_subscription($sub_id);
						$member->deactivate();
					}

					do_action('membership_payment_denied', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
					break;

				case 'Pending':
					// case: payment is pending
					$pending_str = array(
						'address' => 'Customer did not include a confirmed shipping address',
						'authorization' => 'Funds not captured yet',
						'echeck' => 'eCheck that has not cleared yet',
						'intl' => 'Payment waiting for aproval by service provider',
						'multi-currency' => 'Payment waiting for service provider to handle multi-currency process',
						'unilateral' => 'Customer did not register or confirm his/her email yet',
						'upgrade' => 'Waiting for service provider to upgrade the PayPal account',
						'verify' => 'Waiting for service provider to verify his/her PayPal account',
						'*' => ''
						);
					$reason = @$_POST['pending_reason'];
					$note = 'Last transaction is pending. Reason: ' . (isset($pending_str[$reason]) ? $pending_str[$reason] : $pending_str['*']);
					$amount = $_POST['mc_gross'];
					$currency = $_POST['mc_currency'];
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					$this->record_transaction($user_id, $sub_id, $amount, $currency, $timestamp, $_POST['txn_id'], $_POST['payment_status'], $note);

					do_action('membership_payment_pending', $user_id, $sub_id, $amount, $currency, $_POST['txn_id']);
					break;

				default:
					// case: various error cases
			}

			//check for subscription details
			switch ($_POST['txn_type']) {
				case 'subscr_signup':
					// start the subscription
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					// create_subscription
					$member = new M_Membership($user_id);
					if($member) {
						$member->create_subscription($sub_id, $this->gateway);
					}

					do_action('membership_payment_subscr_signup', $user_id, $sub_id);
				  break;

				case 'subscr_modify':
					// modify the subscription
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					// create_subscription
					$member = new M_Membership($user_id);
					if($member) {
						// Remove the old subscription
						$member->drop_subscription($sub_id);
						// Join the new subscription
						$member->create_subscription((int) $_POST['item_number'], $this->gateway);
						// Timestamp the update
						update_user_meta( $user_id, '_membership_last_upgraded', time());
					}

					do_action('membership_payment_subscr_signup', $user_id, $sub_id);
				  break;

				case 'subscr_cancel':
					// mark for removal
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					$member = new M_Membership($user_id);
					if($member) {
						$member->mark_for_expire($sub_id);
					}

					do_action('membership_payment_subscr_cancel', $user_id, $sub_id);
				  break;

				case 'new_case':
					// a dispute
					if($_POST['case_type'] == 'dispute') {
						// immediately suspend the account
						$member = new M_Membership($user_id);
						if($member) {
							$member->deactivate();
						}
					}

					do_action('membership_payment_new_case', $user_id, $sub_id, $_POST['case_type']);
					break;
			}

		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('Status: 404 Not Found');
			echo 'Error: Missing POST variables. Identification is not possible.';
			exit;
		}
	}

}

M_register_gateway('paypalexpress', 'paypalexpress');

?>