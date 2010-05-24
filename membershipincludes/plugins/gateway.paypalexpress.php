<?php

class paypalexpress extends M_Gateway {

	var $gateway = 'paypalexpress';
	var $title = 'PayPal Express';

	function paypalexpress() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));
		add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		if($this->is_active()) {
			// Subscription form gateway
			add_filter('membership_purchase_button', array(&$this, 'display_subscribe_button'), 1, 4);

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
			if(!empty($M_options['paymentcurrency'])) {
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
		</tbody>
		</table>
		<?php
	}

	function build_custom($user_id, $sub_id, $amount) {

		$custom = '';

		//fake:user:sub:key

		$custom = time() . ':' . $user_id . ':' . $sub_id . ':';
		$key = md5('MEMBERSHIP' . $amount);

		$custom .= $key;

		return $custom;

	}

	function single_button($pricing, $subscription, $user_id) {

		global $M_options;

		if(!empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '';

		if (get_option( $this->gateway . "_paypal_status" ) == 'live') {
			$form .= '<form action="https://www.paypal.com/cgi-bin/webscr" method="post">';
		} else {
			$form .= '<form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">';
		}
		$form .= '<input type="hidden" name="business" value="' . esc_attr(get_option( $this->gateway . "_paypal_email" )) . '">';
		$form .= '<input type="hidden" name="cmd" value="_xclick">';
		$form .= '<input type="hidden" name="item_number" value="' . $subscription->sub_id() . '">';
		$form .= '<input type="hidden" name="item_name" value="' . $subscription->sub_name() . '">';
		$form .= '<input type="hidden" name="amount" value="' . $pricing[0]['amount'] . '.00">';
		$form .= '<input type="hidden" name="currency_code" value="' . $M_options['paymentcurrency'] .'">';

		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, $pricing[0]['amount'] . '.00') .'">';

		$form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option( $this->gateway . "_paypal_site" )) . '">';
		$form .= '<input type="hidden" name="notify_url" value="' . trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway) . '">';
		$form .= '<input type="image" name="submit" border="0" src="https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif" alt="PayPal - The safer, easier way to pay online">';
		$form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
		$form .= '</form>';

		return $form;

	}

	function single_sub_button($pricing, $subscription, $user_id, $norepeat = false) {

		global $M_options;

		if(!empty($M_options['paymentcurrency'])) {
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
		$form .= '<input type="hidden" name="a3" value="' . $pricing[0]['amount'] . '.00">';
		$form .= '<input type="hidden" name="p3" value="' . $pricing[0]['days'] . '">';
		$form .= '<input type="hidden" name="t3" value="D"> <!-- Set recurring payments until canceled. -->';

		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, $pricing[0]['amount'] . '.00') .'">';

		$form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option( $this->gateway . "_paypal_site" )) . '">';
		$form .= '<input type="hidden" name="notify_url" value="' . trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway) . '">';

		if($norepeat) {
			$form .= '<input type="hidden" name="src" value="0">';
		} else {
			$form .= '<input type="hidden" name="src" value="1">';
		}

		$form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif" alt="PayPal - The safer, easier way to pay online">';
		$form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
		$form .= '</form>';

		return $form;

	}

	function complex_sub_button($pricing, $subscription, $user_id) {

		global $M_options;

		if(!empty($M_options['paymentcurrency'])) {
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
									$ff['a' . $count] = $price['amount'] . '.00';
									$ff['p' . $count] = $price['days'];
									$ff['t' . $count] = 'D';
								} else {
									// Or last finite is going to be the end of the subscription payments
									$ff['a3'] = $price['amount'] . '.00';
									$ff['p3'] = $price['days'];
									$ff['t3'] = 'D';
									$ff['src'] = '0';
								}
								$count++;
								break;

				case 'indefinite':
								if(empty($price['amount'])) $price['amount'] = '0';
								$ff['a3'] = $price['amount'] . '.00';
								$ff['p3'] = 1;
								$ff['t3'] = 'Y';
								$ff['src'] = '0';
								break;
				case 'serial':
								if(empty($price['amount'])) $price['amount'] = '0';
								$ff['a3'] = $price['amount'] . '.00';
								$ff['p3'] = $price['days'];
								$ff['t3'] = 'D';
								$ff['src'] = '1';
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
		$form .= '<input type="hidden" name="lc" value="' . esc_attr(get_option( $this->gateway . "_paypal_site" )) . '">';
		$form .= '<input type="hidden" name="notify_url" value="' . trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway) . '">';
		$form .= '<!-- Display the payment button. --> <input type="image" name="submit" border="0" src="https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif" alt="PayPal - The safer, easier way to pay online">';
		$form .= '<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >';
		$form .= '</form>';

		return $form;

	}

	function build_subscribe_button($subscription, $pricing, $user_id) {

		if(!empty($pricing)) {

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

	function display_subscribe_button($content, $subscription, $pricing, $user_id) {

		$content .= $this->build_subscribe_button($subscription, $pricing, $user_id);

		return $content;

	}

	function get_transactions($type, $startat, $num) {

		switch($type) {

			case 'past':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status NOT IN ('Pending', 'Future') ORDER BY transaction_ID DESC  LIMIT %d, %d", $startat, $num );
						break;
			case 'pending':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status IN ('Pending') ORDER BY transaction_ID DESC LIMIT %d, %d", $startat, $num );
						break;
			case 'future':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status IN ('Future') ORDER BY transaction_ID DESC LIMIT %d, %d", $startat, $num );
						break;

		}

		return $this->db->get_results( $sql );

	}

	function get_total() {
		return $this->db->get_var( "SELECT FOUND_ROWS();" );
	}

	function mytransactions($type = 'past') {

		if(empty($_GET['paged'])) {
			$paged = 1;
		} else {
			$paged = ((int) $_GET['paged']);
		}

		$startat = ($paged - 1) * 50;

		$transactions = $this->get_transactions($type, $startat, 50);
		$total = $this->get_total();

		$columns = array();

		$columns['subscription'] = __('Subscription','membership');
		$columns['user'] = __('User','membership');
		$columns['date'] = __('Date','membership');
		$columns['amount'] = __('Amount','membership');
		$columns['transid'] = __('Transaction id','membership');

		$trans_navigation = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'total' => ceil($total / 50),
			'current' => $paged
		));

		echo '<div class="tablenav">';
		if ( $trans_navigation ) echo "<div class='tablenav-pages'>$trans_navigation</div>";
		echo '</div>';
		?>


			<table cellspacing="0" class="widefat fixed">
				<thead>
				<tr>
				<?php
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</thead>

				<tfoot>
				<tr>
				<?php
					reset($columns);
					foreach($columns as $key => $col) {
						?>
						<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col"><?php echo $col; ?></th>
						<?php
					}
				?>
				</tr>
				</tfoot>

				<tbody>
					<?php
					if($transactions) {
						foreach($transactions as $key => $transaction) {
							?>
							<tr valign="middle" class="alternate">
								<td class="column-subscription">
									<?php
										if(class_exists('M_Subscription')) {
											$subscription = new M_Subscription($transaction->transaction_subscription_ID);
											echo $subscription->sub_name();
										} else {
											echo __('Subscription not found','membership');
										}
									?>
								</td>
								<td class="column-user">
									<?php
										if(class_exists('M_Membership')) {
											$member = new M_Membership($transaction->transaction_user_ID);
											echo $member->user_login;
										} else {
											echo __('User not found','membership');
										}
									?>
								</td>
								<td class="column-date">
									<?php
										echo mysql2date("d-m-Y", $transaction->transaction_stamp);

									?>
								</td>
								<td class="column-amount">
									<?php
										$amount = $transaction->transaction_total_amount / 100;

										echo $transaction->transaction_currency;
										echo "&nbsp;" . number_format($amount, 2, '.', ',');
									?>
								</td>
								<td class="column-transid">
									<?php
										if(!empty($transaction->transaction_paypal_ID)) {
											echo $transaction->transaction_paypal_ID;
										} else {
											echo __('None yet','membership');
										}
									?>
								</td>
						    </tr>
							<?php
						}
					} else {
						$columncount = count($columns);
						?>
						<tr valign="middle" class="alternate" >
							<td colspan="<?php echo $columncount; ?>" scope="row"><?php _e('No Transactions have been found, patience is a virtue.','membership'); ?></td>
					    </tr>
						<?php
					}
					?>

				</tbody>
			</table>
		<?php
	}

	function update() {

		if(isset($_POST['paypal_email'])) {
			update_option( $this->gateway . "_paypal_email", $_POST[ 'paypal_email' ] );
			update_option( $this->gateway . "_paypal_site", $_POST[ 'paypal_site' ] );
			update_option( $this->gateway . "_currency", $_POST[ 'currency' ] );
			update_option( $this->gateway . "_paypal_status", $_POST[ 'paypal_status' ] );
		}

		// default action is to return true
		return true;

	}

	function record_transaction($user_id, $sub_id, $amount, $currency, $timestamp, $paypal_ID, $status, $note) {

		$data = array();
		$data['transaction_subscription_ID'] = $sub_id;
		$data['transaction_user_ID'] = $user_id;
		$data['transaction_paypal_ID'] = $paypal_ID;
		$data['transaction_stamp'] = $timestamp;
		$data['transaction_currency'] = $currency;
		$data['transaction_status'] = $status;
		$data['transaction_total_amount'] = (int) ($amount * 100);
		$data['transaction_note'] = $note;
		$data['transaction_gateway'] = $this->gateway;

		$existing_id = $this->db->get_var( $this->db->prepare( "SELECT transaction_ID FROM {$this->subscription_transaction} WHERE transaction_paypal_ID = %s", $paypal_ID ) );

		if(!empty($existing_id)) {
			// Update
			$this->db->update( $this->subscription_transaction, $data, array('transaction_ID' => $existing_id) );
		} else {
			// Insert
			$this->db->insert( $this->subscription_transaction, $data );
		}



	}

	// IPN stuff
	function handle_paypal_return() {
		// PayPal IPN handling code
		@wp_mail('barry@caffeinatedb.com', __('Transaction'), print_r($_POST, true));
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
					do_action('membership_payment_processed', $user_id, $amount, $sub_id);
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

					break;

				default:
					// case: various error cases
			}

			//check for subscription details
			switch ($_POST['txn_type']) {
				case 'subscr_signup':
					// start the subscription
				  	//$amount = $_POST['mc_gross'];
					//$currency = $_POST['mc_currency'];
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					// create_subscription
					$member = new M_Membership($user_id);
					if($member) {
						$member->create_subscription($sub_id);
					}

				  break;

				case 'subscr_cancel':
					// mark for removal
				  	//$amount = $_POST['mc_gross'];
					//$currency = $_POST['mc_currency'];
					list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);

					$member = new M_Membership($user_id);
					if($member) {
						$member->mark_for_expire();
					}

					//mark_for_expire
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