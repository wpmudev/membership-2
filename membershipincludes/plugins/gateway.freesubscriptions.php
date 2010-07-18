<?php

class freesubscriptions extends M_Gateway {

	var $gateway = 'freesubscriptions';
	var $title = 'Free Subscriptions';

	function freesubscriptions() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));

		// If I want to override the transactions output - then I can use this action
		add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		if($this->is_active()) {
			// Subscription form gateway
			add_filter('membership_purchase_button', array(&$this, 'display_subscribe_button'), 1, 4);
			add_action( 'membership_subscriptionform_subscription_process', array(&$this, 'signup_free_subscription') );
		}



	}

	function mytransactions() {

		echo '<div class="tablenav">';
		echo '</div>';

		echo "<p>" . __('No transactions data for the Free gateway','membership') . "</p>";
	}

	function mysettings() {

		?>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
		  <th scope="row"><?php _e('Subscription button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_payment_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );
		  ?>
		  <td><input type="text" name="payment_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
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

	function signup_free_subscription() {

	}

	function single_sub_button($pricing, $subscription, $user_id, $norepeat = false) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '';

		$form .= '<form id="reg-form" action="' . get_permalink() . '" method="post">';
		$form .= '<input type="hidden" name="action" value="validatepage2" />';

		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, '0') .'">';

		$button = get_option( $this->gateway . "_paypal_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );

		$form .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
		$form .= '</form>';

		return $form;

	}

	function build_subscribe_button($subscription, $pricing, $user_id) {

		if(!empty($pricing)) {

			$free = true;
			foreach($pricing as $key => $price) {
				if(!empty($price['amount']) && $price['amount'] > 0 ) {
					$free = false;
				}
			}

			if($free) {
				return $this->single_sub_button($pricing, $subscription, $user_id, true);
			}

		}

	}

	function display_subscribe_button($content, $subscription, $pricing, $user_id) {

		$content .= $this->build_subscribe_button($subscription, $pricing, $user_id);

		return $content;

	}

	function update() {

		if(isset($_POST['payment_button'])) {
			update_option( $this->gateway . "_payment_button", $_POST[ 'payment_button' ] );
		}

		// default action is to return true
		return true;

	}

	// IPN stuff
	function handle_paypal_return() {
		// PayPal IPN handling code

	}

}

M_register_gateway('freesubscriptions', 'freesubscriptions');

?>