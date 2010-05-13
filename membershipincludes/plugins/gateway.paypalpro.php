<?php

class paypalpro extends M_Gateway {

	var $gateway = 'paypalpro';
	var $title = 'PayPal Pro';

	function paypalpro() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));
		add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

	}

	function mysettings() {

		echo "<p>" . __('Placeholder : The settings for the PayPal Pro payment gateway will be here.','membership') . "</p>";

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

		// default action is to return true
		return true;

	}

}

M_register_gateway('paypalpro', 'paypalpro');

?>