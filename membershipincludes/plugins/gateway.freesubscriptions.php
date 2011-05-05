<?php

class freesubscriptions extends M_Gateway {

	var $gateway = 'freesubscriptions';
	var $title = 'Free Subscriptions';
	var $issingle = true;

	var $defaultmessage = "<h2>Completed: Thank you for signing up</h2>\n<p>\nYour subscription to our site is now set up and you should be able to visit the members only content.\n</p>\n";

	function freesubscriptions() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));

		// If I want to override the transactions output - then I can use this action
		add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		if($this->is_active()) {
			// Subscription form gateway
			add_action('membership_purchase_button', array(&$this, 'display_subscribe_button'), 1, 3);
			add_filter( 'membership_subscription_form_subscription_process', array(&$this, 'signup_free_subscription'), 10, 2 );
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

		  	<tr valign="top">
				<th scope="row"><?php _e('Completed message','membership'); ?><br/>
					<em style='font-size:smaller;'><?php _e("The message that is displayed to a user once they are signed up. HTML allowed",'membership'); ?>
					</em>
				</th>
				<td>
					<textarea name='completed_message' id='completed_message' rows='10' cols='40'><?php
					$message = get_option( $this->gateway . "_completed_message", $this->defaultmessage );
					echo stripslashes($message);
					?>
					</textarea>
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

	function not_yet_display_upgrade_button($subscription, $pricing, $user_id, $fromsub_id = false) {

		echo '<form class="upgradebutton" action="" method="post">';
		wp_nonce_field('upgrade-sub_' . $subscription->sub_id());
		echo "<input type='hidden' name='action' value='upgradefree' />";
		echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
		echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
		echo "<input type='hidden' name='user' value='" . $user_id . "' />";
		echo "<input type='hidden' name='fromsub_id' value='" . $fromsub_id . "' />";
		echo "<input type='submit' name='submit' value=' " . __('Upgrade', 'membership') . " ' />";
		echo "</form>";
	}

	function display_cancel_button($subscription, $pricing, $user_id) {

		echo '<form class="unsubbutton" action="" method="post">';
		wp_nonce_field('cancel-sub_' . $subscription->sub_id());
		echo "<input type='hidden' name='action' value='unsubscribe' />";
		echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
		echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
		echo "<input type='hidden' name='user' value='" . $user_id . "' />";
		echo "<input type='submit' name='submit' value=' " . __('Unsubscribe', 'membership') . " ' />";
		echo "</form>";
	}

	function signup_free_subscription($content, $error) {

		if(isset($_POST['custom'])) {
			list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);
		}

		// create_subscription
		$member = new M_Membership($user_id);
		if($member) {
			$member->create_subscription($sub_id, $this->gateway);
		}

		do_action('membership_payment_subscr_signup', $user_id, $sub_id);

		$content .= '<div id="reg-form">'; // because we can't have an enclosing form for this part

		$content .= '<div class="formleft">';

		$message = get_option( $this->gateway . "_completed_message", $this->defaultmessage );
		$content .= stripslashes($message);

		$content .= '</div>';

		$content .= "</div>";

		$content = apply_filters('membership_subscriptionform_signedup', $content, $user_id, $sub_id);

		return $content;

	}

	function single_sub_button($pricing, $subscription, $user_id, $norepeat = false) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '';

		$form .= '<form action="' . get_permalink() . '" method="post">';
		$form .= '<input type="hidden" name="action" value="validatepage2" />';
		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, '0') .'">';

		$button = get_option( $this->gateway . "_payment_button", 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif' );

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

	function display_subscribe_button($subscription, $pricing, $user_id) {

		echo $this->build_subscribe_button($subscription, $pricing, $user_id);

	}

	function update() {

		if(isset($_POST['payment_button'])) {
			update_option( $this->gateway . "_payment_button", $_POST[ 'payment_button' ] );
			update_option( $this->gateway . "_completed_message", $_POST[ 'completed_message' ] );
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