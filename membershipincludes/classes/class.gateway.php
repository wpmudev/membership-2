<?php

if(!class_exists('M_Gateway')) {

	class M_Gateway {

		var $db;

		// Class Identification
		var $gateway = 'Not Set';
		var $title = 'Not Set';
		var $issingle = false;

		// Tables
		var $tables = array('subscription_transaction');
		var $subscription_transaction;

		function M_Gateway() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			// Actions and Filters
			add_filter('M_gateways_list', array(&$this, 'gateways_list'));

			add_action( 'membership_process_payment_return', array(&$this, 'process_payment_return') );
			add_action( 'membership_record_user_gateway', array(&$this, 'record_user_gateway') );

		}

		function gateways_list($gateways) {

			$gateways[$this->gateway] = $this->title;

			return $gateways;

		}

		function toggleactivation() {

			$active = get_option('M_active_gateways', array());

			if(array_key_exists($this->gateway, $active)) {
				unset($active[$this->gateway]);

				update_option('M_active_gateways', $active);

				return true;
			} else {
				$active[$this->gateway] = true;

				update_option('M_active_gateways', $active);

				return true;
			}

		}

		function activate() {

			$active = get_option('M_active_gateways', array());

			if(array_key_exists($this->gateway, $active)) {
				return true;
			} else {
				$active[$this->gateway] = true;

				update_option('M_active_gateways', $active);

				return true;
			}

		}

		function deactivate() {

			$active = get_option('M_active_gateways', array());

			if(array_key_exists($this->gateway, $active)) {
				unset($active[$this->gateway]);

				update_option('M_active_gateways', $active);

				return true;
			} else {
				return true;
			}

		}

		function is_active() {

			$active = get_option('M_active_gateways', array());
			if(array_key_exists($this->gateway, $active)) {
				return true;
			} else {
				return false;
			}
		}

		function settings() {

			global $page, $action;

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php echo __('Edit &quot;','membership') . esc_html($this->title) . __('&quot; settings','membership'); ?></h2>

				<form action='?page=<?php echo $page; ?>' method='post' name='gatewaysettingsform'>

					<input type='hidden' name='action' id='action' value='updated' />
					<input type='hidden' name='gateway' id='gateway' value='<?php echo $this->gateway; ?>' />
					<?php
					wp_nonce_field('updated-' . $this->gateway);

					do_action('M_gateways_settings_' . $this->gateway);

					?>

					<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
					</p>
				</form>

			</div> <!-- wrap -->
			<?php

		}

		function update() {

			// default action is to return true
			return true;

		}

		function get_transactions($type, $startat, $num) {

			switch($type) {

				case 'past':
							$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status NOT IN ('Pending', 'Future') AND transaction_gateway = %s ORDER BY transaction_ID DESC  LIMIT %d, %d", $this->gateway, $startat, $num );
							break;
				case 'pending':
							$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status IN ('Pending') AND transaction_gateway = %s ORDER BY transaction_ID DESC LIMIT %d, %d", $this->gateway, $startat, $num );
							break;
				case 'future':
							$sql = $this->db->prepare( "SELECT SQL_CALC_FOUND_ROWS * FROM {$this->subscription_transaction} WHERE transaction_status IN ('Future') AND transaction_gateway = %s ORDER BY transaction_ID DESC LIMIT %d, %d", $this->gateway, $startat, $num );
							break;

			}

			return $this->db->get_results( $sql );

		}

		function duplicate_transaction($user_id, $sub_id, $amount, $currency, $timestamp, $paypal_ID, $status, $note) {
			$sql = $this->db->prepare( "SELECT transaction_ID FROM {$this->subscription_transaction} WHERE transaction_subscription_ID = %d AND transaction_user_ID = %d AND transaction_paypal_ID = %s AND transaction_stamp = %d LIMIT 1 ", $sub_id, $user_id, $paypal_ID, $timestamp );

			$trans = $this->db->get_var( $sql );
			if(!empty($trans)) {
				return true;
			} else {
				return false;
			}
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

			$existing_id = $this->db->get_var( $this->db->prepare( "SELECT transaction_ID FROM {$this->subscription_transaction} WHERE transaction_paypal_ID = %s LIMIT 1", $paypal_ID ) );

			if(!empty($existing_id)) {
				// Update
				$this->db->update( $this->subscription_transaction, $data, array('transaction_ID' => $existing_id) );
			} else {
				// Insert
				$this->db->insert( $this->subscription_transaction, $data );
			}

		}

		function get_total() {
			return $this->db->get_var( "SELECT FOUND_ROWS();" );
		}

		function transactions() {

			global $page, $action, $type;

			wp_reset_vars( array('type') );

			if(empty($type)) $type = 'past';

			?>
			<div class='wrap'>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php echo esc_html($this->title) . __(' transactions','membership'); ?></h2>

				<ul class="subsubsub">
					<li><a href="<?php echo add_query_arg('type', 'past'); ?>" class="rbutton <?php if($type == 'past') echo 'current'; ?>"><?php  _e('Recent transactions', 'membership'); ?></a> | </li>
					<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php  _e('Pending transactions', 'membership'); ?></a> | </li>
					<li><a href="<?php echo add_query_arg('type', 'future'); ?>" class="rbutton <?php if($type == 'future') echo 'current'; ?>"><?php  _e('Future transactions', 'membership'); ?></a></li>
				</ul>

				<?php
					if(has_action('M_gateways_transactions_' . $this->gateway)) {
						do_action('M_gateways_transactions_' . $this->gateway, $type);
					} else {
						$this->mytransactions($type);
					}

				?>
			</div> <!-- wrap -->
			<?php

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
			$columns['status'] = __('Status','membership');
			$columns['note'] = __('Notes','membership');

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
									<td class="column-transid">
										<?php
											if(!empty($transaction->transaction_status)) {
												echo $transaction->transaction_status;
											} else {
												echo __('None yet','membership');
											}
										?>
									</td>
									<td class="column-transid">
										<?php
											if(!empty($transaction->transaction_note)) {
												echo esc_html($transaction->transaction_note);
											} else {
												echo __('None','membership');
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

		function process_payment_return( $gateway ) {
			if( apply_filters( 'membership_override_payment_return_' . $gateway, false ) ) {
				return;
			}

			// Payment return
			do_action( 'membership_handle_payment_return_' . $gateway );
		}

		function record_user_gateway( $user_id ) {
			update_user_meta( $user_id, 'membership_signup_gateway', $this->gateway );
			if($this->issingle) {
				update_user_meta( $user_id, 'membership_signup_gateway_is_single', 'yes' );
			} else {
				update_user_meta( $user_id, 'membership_signup_gateway_is_single', 'no' );
			}

		}

		function display_upgrade_button($pricing, $subscription, $user_id, $fromsub_id = false) {
			// By default there is no default button available
			echo "<form class=''>";
			echo "<input type='submit' value=' " . __('Upgrades not available', 'membership') . " ' disabled='disabled' />";
			echo "</form>";
		}

		function display_cancel_button($subscription, $pricing, $user_id) {
			// By default there is no default button available
			echo '<form class="unsubbutton" action="" method="post">';
			echo "<input type='button' value=' " . __('Unsubscribe not available', 'membership') . " ' disabled='disabled' />";
			echo "</form>";
		}

	}

}

function M_register_gateway($gateway, $class) {

	global $M_Gateways;

	if(!is_array($M_Gateways)) {
		$M_Gateways = array();
	}

	$M_Gateways[$gateway] = new $class;

}

function M_get_class_for_gateway($gateway) {

	global $M_Gateways;

	if(isset($M_Gateways[$gateway])) {
		return $M_Gateways[$gateway];
	} else {
		return false;
	}

}

?>