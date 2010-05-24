<?php

class paypalexpress extends M_Gateway {

	var $gateway = 'paypalexpress';
	var $title = 'PayPal Express';

	function paypalexpress() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));
		add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		// Subscription form gateway
		add_filter('membership_purchase_button', array(&$this, 'display_subscribe_button'), 1, 3);
	}

	function mysettings() {

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
		  <td><select name="currency">
		  <?php
		  $currency = get_option( $this->gateway . "_currency" );
		      $sel_currency = empty($currency) ? 'USD' : $currency;
		      $currencies = array(
		          'AUD' => 'AUD - Australian Dollar',
		          'BRL' => 'BRL - Brazilian Real',
		          'CAD' => 'CAD - Canadian Dollar',
		          'CHF' => 'CHF - Swiss Franc',
		          'CZK' => 'CZK - Czech Koruna',
		          'DKK' => 'DKK - Danish Krone',
		          'EUR' => 'EUR - Euro',
		          'GBP' => 'GBP - Pound Sterling',
		          'ILS' => 'ILS - Israeli Shekel',
		          'HKD' => 'HKD - Hong Kong Dollar',
		          'HUF' => 'HUF - Hungarian Forint',
		          'JPY' => 'JPY - Japanese Yen',
		          'MYR' => 'MYR - Malaysian Ringgits',
		          'MXN' => 'MXN - Mexican Peso',
		          'NOK' => 'NOK - Norwegian Krone',
		          'NZD' => 'NZD - New Zealand Dollar',
		          'PHP' => 'PHP - Philippine Pesos',
		          'PLN' => 'PLN - Polish Zloty',
		          'SEK' => 'SEK - Swedish Krona',
		          'SGD' => 'SGD - Singapore Dollar',
		          'TWD' => 'TWD - Taiwan New Dollars',
		          'THB' => 'THB - Thai Baht',
		          'USD' => 'USD - U.S. Dollar'
		      );

		      foreach ($currencies as $key => $value) {
					echo '<option value="' . esc_attr($key) . '"';
					if($key == $sel_currency) echo 'selected="selected"';
					echo '>' . esc_html($value) . '</option>' . "\n";
		      }
		  ?>
		  </select>
		  <br /><?php //_e('') ?></td>
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

	function build_subscribe_button($subscription, $pricing) {

		if(!empty($pricing)) {

			if(count($pricing) == 1 && $pricing[0]['days'] == 0) {
				// A basic price
			} else {

			}


		}

	}

	function display_subscribe_button($content, $subscription, $pricing) {

	}

	function get_transactions($type, $startat, $num) {

		switch($type) {

			case 'past':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status IN ('Completed', 'Processed') ORDER BY transaction_stamp DESC LIMIT %d, %d", $startat, $num );
						break;
			case 'pending':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status IN ('Pending') ORDER BY transaction_stamp DESC LIMIT %d, %d", $startat, $num );
						break;
			case 'future':
						$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status IN ('Future') ORDER BY transaction_stamp DESC LIMIT %d, %d", $startat, $num );
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

}

M_register_gateway('paypalexpress', 'paypalexpress');

?>