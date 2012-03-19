<?php
/*
Addon Name: Authorize.net gateway
Description: The Payment gateway for Authorize.net
Author: S H Mohanjith (Incsub)
Author URI: http://premium.wpmudev.org
Gateway ID: authorizenetaim
*/

class authorizenetaim extends M_Gateway {

	var $gateway = 'authorizenetaim';
	var $title = 'Authorize.net AIM';

	function authorizenetaim() {
		global $M_membership_url;

		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));

		// If I want to override the transactions output - then I can use this action
		//add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		if($this->is_active()) {
			// Subscription form gateway
			add_action('membership_purchase_button', array(&$this, 'display_subscribe_button'), 1, 3);

			wp_enqueue_script('jquery');

			// Payment return
			add_action('membership_handle_payment_return_' . $this->gateway, array(&$this, 'handle_payment_return'));
			add_filter('membership_subscription_form_subscription_process', array(&$this, 'signup_subscription'), 10, 2 );
			add_action('signup_hidden_fields', array(&$this, 'force_ssl_account_creation'));

			if (!is_admin()) {
				$M_membership_url = preg_replace('/http:/i', 'https:', $M_membership_url);
			}
		}

	}
	function force_ssl_account_creation() {
		if($_SERVER['HTTPS'] != 'on') {
			$url = home_url($_SERVER['REQUEST_URI'].'/','https');
			wp_redirect($url);
			exit;
		}
	}
	function mysettings() {
		global $M_options;

		?>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
		  <th scope="row"><?php _e('Mode', 'membership') ?></th>
		  <td><select name="mode">
		  <?php
		      $sel_mode = get_option( $this->gateway . "_mode", "sandbox");
		      $modes = array(
		          'sandbox'	=> __('Sandbox','membership'),
		          'live'	=> __('Live','membership')
			);

		      foreach ($modes as $key => $value) {
					echo '<option value="' . esc_attr($key) . '"';
		 			if($key == $sel_mode) echo 'selected="selected"';
		 			echo '>' . esc_html($value) . '</option>' . "\n";
		      }
		  ?>
		  </select></td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Login ID', 'membership') ?></th>
		    <td><input type="text" name="api_user" value="<?php esc_attr_e(get_option( $this->gateway . "_api_user", "" )); ?>" /></td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('Transaction key', 'membership') ?></th>
		    <td><input type="text" name="api_key" value="<?php esc_attr_e(get_option( $this->gateway . "_api_key", "" )); ?>" /></td>
		  </tr>
		</tbody>
		</table>
		<h3><?php print _e('Advanced Settings', 'membership'); ?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php _e('Delimiter Character', 'membership') ?></th>
					<td><input type="text" name="delim_char" value="<?php esc_attr_e(get_option( $this->gateway . "_delim_char", "," )); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Encapsulation Character', 'membership') ?></th>
					<td><input type="text" name="encap_char" value="<?php esc_attr_e(get_option( $this->gateway . "_encap_char", "" )); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Email Customer (on success)', 'membership') ?></th>
					<td><select name="email_customer">
						<?php
						    $sel_mode = get_option( $this->gateway . "_email_customer", "yes" );
						    $modes = array(
							'yes'	=> __('Yes', 'membership'),
							'no'	=> __('No', 'membership')
							);

						    foreach ($modes as $key => $value) {
								      echo '<option value="' . esc_attr($key) . '"';
								      if($key == $sel_mode) echo 'selected="selected"';
								      echo '>' . esc_html($value) . '</option>' . "\n";
						    }
						?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Customer Receipt Email Header', 'membership') ?></th>
					<td><input type="text" name="header_email_receipt" value="<?php esc_attr_e(get_option( $this->gateway . "_header_email_receipt", __("Thanks for your payment!", "membership"))); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Customer Receipt Email Footer', 'membership') ?></th>
					<td><input type="text" name="footer_email_receipt" value="<?php esc_attr_e(get_option( $this->gateway . "_footer_email_receipt", "" )); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Security: MD5 Hash', 'membership') ?></th>
					<td><input type="text" name="md5_hash" value="<?php esc_attr_e(get_option( $this->gateway . "_md5_hash" )); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php _e('Request a delimited response from the payment gateway', 'membership') ?></th>
					<td><select name="delim_data">
						<?php
						    $sel_mode = get_option( $this->gateway . "_delim_data", "yes" );
						    $modes = array(
							'yes'	=> __('Yes', 'membership'),
							'no'	=> __('No', 'membership')
							);

						    foreach ($modes as $key => $value) {
								      echo '<option value="' . esc_attr($key) . '"';
								      if($key == $sel_mode) echo 'selected="selected"';
								      echo '>' . esc_html($value) . '</option>' . "\n";
						    }
						?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	function build_custom($user_id, $sub_id, $amount) {
		$custom = '';

		$custom = time() . ':' . $user_id . ':' . $sub_id . ':';
		$key = md5('MEMBERSHIP' . $amount);

		$custom .= $key;

		return $custom;
	}

	function signup_subscription($content, $error) {

		if(isset($_POST['custom'])) {
			list($timestamp, $user_id, $sub_id, $key) = explode(':', $_POST['custom']);
		}
				
		$content .= '<div id="reg-form">'; // because we can't have an enclosing form for this part

		$content .= '<div class="formleft">';

		$message = get_option( $this->gateway . "_completed_message", $this->defaultmessage );
		$content .= stripslashes($message);

		$content .= '</div>';

		$content .= "</div>";

		$content = apply_filters('membership_subscriptionform_signedup', $content, $user_id, $sub_id);

		return $content;

	}

	function single_button($pricing, $subscription, $user_id) {
		global $M_options, $M_membership_url;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}
		$form = '';

		if (!function_exists('wp_https_redirect'))
		{
		  if ($_SERVER['HTTPS'] != "on" && preg_match('/^https/', get_option('siteurl')) == 0)
		  {
		    $host_x = preg_split('/\//', get_option('siteurl'));
		    $host = $host_x[2];
		    echo '<script type="text/javascript">';
		    echo 'window.location = "https://'. $host . $_SERVER['REQUEST_URI'].'"';
		    echo '</script>';
		    exit(0);
		  }
		}

		$M_secure_home_url = preg_replace('/http:/i', 'https:', trailingslashit(get_option('home')));
		$form .= '<div class="auth-header">'. __('Enter Your Credit Card Information:', 'membership'). '</div>';
		$form .= '</td>';
		$form .= '<tr><td colspan="3">';
		
		
		$form .= '<script type="text/javascript">';
		$form .= '_aim_return_url = "'.$M_secure_home_url . 'paymentreturn/' . esc_attr($this->gateway).'";';
		$form .= '_permalink_url = "'.get_permalink().'";';
		$form .= '</script>';

		$form .= '<script type="text/javascript" src="' . $M_membership_url . 'membershipincludes/js/authorizenet.js"></script>';

//Removed width to style in CSS
		$form .= '<style type="text/css">';
		$form .= '				
				.membership_cart_billing {
					
				}
				
				.purchase-wrapper {
				padding: 10px;
				border: 1px solid #d6d6d6;
				border-radius: 10px;
				background: #efefef
				}
				
				.purchase-item {
				background: #a0a0a0;
				padding: 10px;
				border-radius: 5px;
				font-size: 130%;
				font-weight: 700;
				color: #fff;
				margin-bottom: 10px;
				}
				
				.purchase-item-details {
				float: left;
				}
				
				.purchase-item-price {
				text-align: right;
				}
				
				.buynow {
				background: #fff;
				padding: 10px;
				border-radius: 5px;
				border: 1px solid #d6d6d6;
				}
				
				.auth-header {
				font-size: 120%;
				margin-bottom: 5px;
				font-weight: 700;
				}
				
				.auth-body {
				background: #EFEFEF;
				padding: 10px;
				border-radius: 5px;
				}
				
				.auth-billing {
				padding: 5px;
				background: white;
				border-radius: 3px;
				margin-bottom: 10px;
				}
				
				.auth-billing-name {
				font-size: 110%;
				margin-bottom: 10px;
				}
				
				.auth-billing-fname-label {
				float: left;
				padding-top: 5px;
				margin-right: 10px;
				}
				
				.auth-billing-fname {
				float: left;
				margin-right: 15px;
				}
				
				.auth-billing-lname-label {
				float: left;
				padding-top: 5px;
				margin-right: 10px;
				}
				
				.auth-billing-address-label {
				float: left;
				padding-top: 5px;
				margin-right: 27px;
				}
				
				.auth-billing-zip-label {
				float: left;
				padding-top: 5px;
				margin-right: 10px;
				}
				
				.auth-cc {
				padding: 5px;
				background: white;
				border-radius: 3px;
				margin-bottom: 10px;
				}
				.auth-exp {
				padding: 10px 10px 0 5px;
				background: white;
				border-radius: 3px;
				margin-bottom: 10px;
				}
				.auth-sec {
				padding: 10px 10px 0 5px;
				background: white;
				border-radius: 3px;
				margin-bottom: 10px;
				}
				
				.cardimage {
				height: 23px;
				width: 157px;
				display: inline-table;
				margin-left: 20px;
				}
				
				.auth-exp-input .inputLabel {
					margin: 0 5px 0 20px;
				}
				
				#membership-wrapper select, #membership-wrapper input[type="file"] {
					height: 28px;
					width: 100px;
				}
				
				.auth-cc-label {
				}
				
				.auth-exp-label {
					float: left;
					padding-top: 2px;
				}
				
				.auth-sec-label {
					float: left;
					margin-right: 10px;
					padding-top: 5px;
				}
				
				.auth-submit-button {
					text-align: right;
				}
				.membership_payment_form.authorizenet {
					width: 100%;
				}
		';
		$form .= '</style>';
		$form .= '<form method="post" action="'.$M_secure_home_url . 'paymentreturn/' . esc_attr($this->gateway).'" class="membership_payment_form authorizenet single">';
		
		$api_u = get_option( $this->gateway . "_api_user");
		$api_k = get_option( $this->gateway . "_api_key");
		
		$error = false;
		if(isset($_GET['errors'])) {
			if($_GET['errors'] == 1)
				$error = __('Payment method not supported for the payment', 'membership');
			if($_GET['errors'] == 2)
				$error = __('There was a problem processing your purchase. Please try again', 'membership');
		} 
		if(!isset($api_u) || $api_u == '' || $api_u == false || !isset($api_k) || $api_k == '' || $api_k == false) {
			$error = __('This payment gateway has not been configured.  Your transaction will not be processed.', 'membership');
		}
		$form .= '<div class="message error'.($error == false ? ' hidden' : '').'">'.$error.'</div>';
		$form .= '<input type="hidden" name="subscription_id" value="'.$subscription->id.'" />';
		$form .= '<input type="hidden" name="user_id" value="'.$user_id.'" />';
		
//New DIV based form by Kevin D. Lyons
		$form .= '<div class="membership_cart_billing">';
			
			$form .= '<div class="auth-body">';
//New Address Verification as Billing Address added by Kevin D. Lyons
				$form .= '<div class="auth-billing">';
					$form .= '<div class="auth-billing-name">'.__('Credit Card Billing Information:', 'mp'). '*</div>';
					$form .= '<div class="auth-billing-fname-label"><label class="inputLabel" for="first_name">'.__('First Name:', 'mp'). '</label></div>';
					$form .= '<div class="auth-billing-fname"><input id="first_name" name="first_name" class="input_field noautocomplete" style="width: 160px;" ';
					$form .= 'type="text" size="20" maxlength="20" /></div>';
					$form .= '<div class="auth-billing-lname-label"><label class="inputLabel" for="last_name">'.__('Last Name:', 'mp'). '</label></div>';
					$form .= '<div class="auth-billing-lname"><input id="last_name" name="last_name" class="input_field noautocomplete" style="width: 160px;" ';
					$form .= 'type="text" size="20" maxlength="20" /></div>';
					$form .= '<div class="auth-billing-address-label"><label class="inputLabel" for="address">'.__('Address:', 'mp'). '</label></div>';
					$form .= '<div class="auth-billing-address"><input id="address" name="address" class="input_field noautocomplete" style="width: 427px;" ';
					$form .= 'type="text" size="120" maxlength="120" /></div>';
					$form .= '<div class="auth-billing-zip-label"><label class="inputLabel" for="zip">'.__('Billing 5-Digit Zipcode:', 'mp'). '</label></div>';
					$form .= '<div class="auth-billing-zip"><input id="zip" name="zip" class="input_field noautocomplete" style="width: 80px;" ';
					$form .= 'type="text" size="5" maxlength="5" /></div></div>';
//End Address Verification
				$form .= '<div class="auth-cc">';
					$form .= '<div class="auth-cc-label">'. __('Credit Card Number:', 'mp'). '*</div>';
					$form .= '<div class="auth-cc-input"><input name="card_num" onkeyup="cc_card_pick(\'#cardimage\', \'#card_num\');"';
					$form .= 'id="card_num" class="credit_card_number input_field noautocomplete" type="text" size="22" maxlength="22" />';
						$form .= '<div class="hide_after_success nocard cardimage"  id="cardimage" ';
						$form .= 'style="background: url(' . $M_membership_url . 'membershipincludes/images/card_array.png) no-repeat;"></div></div></div>';
				$form .= '<div class="auth-exp">';
					$form .= '<div class="auth-exp-label">'.__('Expiration Date:', 'mp').'*</div>';
					$form .= '<div class="auth-exp-input"><label class="inputLabel" for="exp_month">'.__('Month', 'membership'). '</label>';
					$form .= '<select name="exp_month" id="exp_month">'.$this->_print_month_dropdown(). '</select>';
					$form .= '<label class="inputLabel" for="exp_year">'.__('Year', 'membership'). '</label>';
					$form .= '<select name="exp_year" id="exp_year">'.$this->_print_year_dropdown('', true).'</select></div></div>';
				$form .= '<div class="auth-sec">';
					$form .= '<div class="auth-sec-label">'.__('Security Code:', 'mp').'</div>';
					$form .= '<div class="auth-sec-input"><input id="card_code" name="card_code" class="input_field noautocomplete" style="width: 70px;" ';
					$form .= 'type="text" size="4" maxlength="4" /></div></div>';
				$form .= '<div class="auth-submit">';
					$form .= '<div class="auth-submit-button"><input type="image" src="' . $M_membership_url . 'membershipincludes/images/cc_process_payment.png" alt="'. __("Pay with Credit Card", "membership") .'" /></div></div>';
		$form .= '</div></div></form>';
// Replaced by Kevin D. Lyons for DIV based form
//		$form .= '<table class="membership_cart_billing">';
//		$form .= '<thead><tr><th colspan="2">'. __('Enter Your Credit Card Information:', 'membership'). '</th></tr></thead>';
//		$form .= '<tbody><tr><td align="right">'. __('Credit Card Number:', 'mp'). '*</td>';
//		$form .= '<td><input name="card_num" onkeyup="cc_card_pick(\'#cardimage\', \'#card_num\');"';
//		$form .= 'id="card_num" class="credit_card_number input_field noautocomplete" type="text" size="22" maxlength="22" />';
//		$form .= '<div class="hide_after_success nocard cardimage"  id="cardimage" ';
//		$form .= 'style="background: url(' . $M_membership_url . 'membershipincludes/images/card_array.png) no-repeat;"></div></td></tr>';
//		$form .= '<tr><td align="right">'.__('Expiration Date:', 'mp').'*</td>';
//		$form .= '<td><label class="inputLabel" for="exp_month">'.__('Month', 'membership'). '</label>';
//		$form .= '<select name="exp_month" id="exp_month">'.$this->_print_month_dropdown(). '</select>';
//		$form .= '<label class="inputLabel" for="exp_year">'.__('Year', 'membership'). '</label>';
//		$form .= '<select name="exp_year" id="exp_year">'.$this->_print_year_dropdown('', true).'</select></td></tr>';
//		$form .= '<tr><td align="right">'.__('Security Code:', 'mp').'</td>';
//		$form .= '<td><input id="card_code" name="card_code" class="input_field noautocomplete" style="width: 70px;" ';
//		$form .= 'type="text" size="4" maxlength="4" /></td></tr>';
//		$form .= '<tr><td colspan="2"><input type="image" src="' . $M_membership_url . 'membershipincludes/images/cc_process_payment.png" alt="'. __("Pay with Credit Card", "membership") .'" /></td></tr>';
//		$form .= '</tbody></table></form>';

		return $form;
	}

	function single_sub_button($pricing, $subscription, $user_id, $norepeat = false) {
		global $M_options, $M_membership_url;

		// No ARB yet
	}

	function complex_sub_button($pricing, $subscription, $user_id) {
		global $M_options, $M_membership_url;

		// No ARB yet
	}

	function build_subscribe_button($subscription, $pricing, $user_id) {
		if(!empty($pricing)) {
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
						return $this->single_button($pricing, $subscription, $user_id, true);
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

	function display_subscribe_button($subscription, $pricing, $user_id) {
		echo $this->build_subscribe_button($subscription, $pricing, $user_id);
	}

	function update() {
		if(isset($_POST['mode'])) {
			update_option( $this->gateway . "_mode", $_POST[ 'mode' ] );
			update_option( $this->gateway . "_api_user", $_POST[ 'api_user' ] );
			update_option( $this->gateway . "_api_key", $_POST[ 'api_key' ] );
			update_option( $this->gateway . "_delim_char", $_POST[ 'delim_char' ] );
			update_option( $this->gateway . "_encap_char", $_POST[ 'encap_char' ] );
			update_option( $this->gateway . "_email_customer", $_POST[ 'email_customer' ] );
			update_option( $this->gateway . "_header_email_receipt", $_POST[ 'header_email_receipt' ] );
			update_option( $this->gateway . "_footer_email_receipt", $_POST[ 'footer_email_receipt' ] );
			update_option( $this->gateway . "_md5_hash", $_POST[ 'md5_hash' ] );
			update_option( $this->gateway . "_delim_data", $_POST[ 'delim_data' ] );
		}
		// default action is to return true
		return true;
	}

	function _print_year_dropdown($sel='', $pfp = false) {
		$localDate=getdate();
		$minYear = $localDate["year"];
		$maxYear = $minYear + 15;

		$output =  "<option value=''>--</option>";
		for($i=$minYear; $i<$maxYear; $i++) {
			if ($pfp) {
				$output .= "<option value='". substr($i, 0, 4) ."'".($sel==(substr($i, 0, 4))?' selected':'').
				">". $i ."</option>";
			} else {
				$output .= "<option value='". substr($i, 2, 2) ."'".($sel==(substr($i, 2, 2))?' selected':'').
			">". $i ."</option>";
			}
		}
		return($output);
	}

	function _print_month_dropdown($sel='') {
		$output =  "<option value=''>--</option>";
		$output .=  "<option " . ($sel==1?' selected':'') . " value='01'>01 - Jan</option>";
		$output .=  "<option " . ($sel==2?' selected':'') . "  value='02'>02 - Feb</option>";
		$output .=  "<option " . ($sel==3?' selected':'') . "  value='03'>03 - Mar</option>";
		$output .=  "<option " . ($sel==4?' selected':'') . "  value='04'>04 - Apr</option>";
		$output .=  "<option " . ($sel==5?' selected':'') . "  value='05'>05 - May</option>";
		$output .=  "<option " . ($sel==6?' selected':'') . "  value='06'>06 - Jun</option>";
		$output .=  "<option " . ($sel==7?' selected':'') . "  value='07'>07 - Jul</option>";
		$output .=  "<option " . ($sel==8?' selected':'') . "  value='08'>08 - Aug</option>";
		$output .=  "<option " . ($sel==9?' selected':'') . "  value='09'>09 - Sep</option>";
		$output .=  "<option " . ($sel==10?' selected':'') . "  value='10'>10 - Oct</option>";
		$output .=  "<option " . ($sel==11?' selected':'') . "  value='11'>11 - Nov</option>";
		$output .=  "<option " . ($sel==12?' selected':'') . "  value='12'>12 - Doc</option>";

		return($output);
	}

	function handle_payment_return() {
		global $M_options, $M_membership_url;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$subscription = new M_Subscription($_POST['subscription_id']);
		$pricing = $subscription->get_pricingarray();

		$user_id = $_POST['user_id'];
		$sub_id = $subscription->id;

		if ($M_options['paymentcurrency'] == 'USD' && count($pricing) == 1) {
			// A basic price or a single subscription
			if(in_array($pricing[0]['type'], array('indefinite','finite'))) {
				$timestamp = time();

				if (get_option( $this->gateway . "_mode", 'sandbox' ) == 'sandbox')	{
					$endpoint = "https://test.authorize.net/gateway/transact.dll";
				} else {
					$endpoint = "https://secure.authorize.net/gateway/transact.dll";
				}

				$payment = new M_Gateway_Worker_AuthorizeNet_AIM($endpoint,
				  get_option( $this->gateway . "_delim_data", 'yes' ),
				  get_option( $this->gateway . "_delim_char", ',' ),
				  get_option( $this->gateway . "_encap_char", '' ),
				  get_option( $this->gateway . "_api_user", '' ),
				  get_option( $this->gateway . "_api_key", '' ),
				  (get_option( $this->gateway . "_mode", 'sandbox' ) == 'sandbox'));

				$payment->transaction($_POST['card_num']);

				// Billing Info
				$payment->setParameter("x_card_code", $_POST['card_code']);
				$payment->setParameter("x_exp_date ", $_POST['exp_month'] . $_POST['exp_year']);
				$payment->setParameter("x_amount", number_format($pricing[0]['amount'], 2));

				//NEW Added by Kevin D. Lyons Billing Address Information
				$payment->setParameter("x_first_name", $_POST['first_name']);
				$payment->setParameter("x_last_name", $_POST['last_name']);
				$payment->setParameter("x_address", $_POST['address']);
				$payment->setParameter("x_zip", $_POST['zip']);

				// Order Info
				$payment->setParameter("x_description", $subscription->sub_name());

				$payment->setParameter("x_duplicate_window", 30);

				// E-mail
				$payment->setParameter("x_header_email_receipt", get_option( $this->gateway . "_header_email_receipt", '' ));
				$payment->setParameter("x_footer_email_receipt", get_option( $this->gateway . "_footer_email_receipt", '' ));
				$payment->setParameter("x_email_customer", strtoupper(get_option( $this->gateway . "_email_customer", '' )));

				$payment->setParameter("x_customer_ip", $_SERVER['REMOTE_ADDR']);

				$payment->process();

				if ($payment->isApproved()) {
				  $status = __('The payment has been completed, and the funds have been added successfully to your account balance.', 'membership');

				  $member = new M_Membership($user_id);
				  if($member) {
					$member->create_subscription($sub_id, $this->gateway);
				  }
				do_action('membership_payment_subscr_signup', $user_id, $sub_id);
				wp_redirect(M_get_registrationcompleted_permalink());
				exit;
				} else {
					wp_redirect(M_get_registration_permalink().'?action=registeruser&subscription='.$sub_id.'&errors=1');
					exit;
				}
			} else {
				wp_redirect(M_get_registration_permalink().'?action=registeruser&subscription='.$sub_id.'&errors=2');
				exit;
			}
		} else {
			wp_redirect(M_get_registration_permalink().'?action=registeruser&subscription='.$sub_id.'&errors=2');
			exit;
		}
		global $m_aim_errors;
		$m_aim_errors = $error;
			
	}
}


if(!class_exists('M_Gateway_Worker_AuthorizeNet_AIM')) {
  class M_Gateway_Worker_AuthorizeNet_AIM
  {
    var $login;
    var $transkey;
    var $params   = array();
    var $results  = array();
    var $line_items = array();

    var $approved = false;
    var $declined = false;
    var $error    = true;
    var $method   = "";

    var $fields;
    var $response;

    var $instances = 0;

    function __construct($url, $delim_data, $delim_char, $encap_char, $gw_username, $gw_tran_key, $gw_test_mode)
    {
      if ($this->instances == 0)
      {
	$this->url = $url;

	$this->params['x_delim_data']     = $delim_data;
	$this->params['x_delim_char']     = $delim_char;
	$this->params['x_encap_char']     = $encap_char;
	$this->params['x_relay_response'] = "FALSE";
	$this->params['x_url']            = "FALSE";
	$this->params['x_version']        = "3.1";
	$this->params['x_method']         = "CC";
	$this->params['x_type']           = "AUTH_CAPTURE";
	$this->params['x_login']          = $gw_username;
	$this->params['x_tran_key']       = $gw_tran_key;
	$this->params['x_test_request']   = $gw_test_mode;

	$this->instances++;
      } else {
	return false;
      }
    }

    function transaction($cardnum)
    {
      $this->params['x_card_num']  = trim($cardnum);
    }

    function addLineItem($id, $name, $description, $quantity, $price, $taxable = 0)
    {
      $this->line_items[] = "{$id}<|>{$name}<|>{$description}<|>{$quantity}<|>{$price}<|>{$taxable}";
    }

    function process($retries = 1)
    {
      global $mp;

      $this->_prepareParameters();
      $query_string = rtrim($this->fields, "&");

      $count = 0;
      while ($count < $retries)
      {
        $args['user-agent'] = "Membership: http://premium.wpmudev.org/project/membeship | Authorize.net AIM Plugin/";
        $args['body'] = $query_string;
        $args['sslverify'] = false;

        //use built in WP http class to work with most server setups
        $response = wp_remote_post($this->url, $args);

        if (is_array($response) && isset($response['body'])) {
          $this->response = $response['body'];
        } else {
          $this->response = "";
          $this->error = true;
          return;
        }

	$this->parseResults();

	if ($this->getResultResponseFull() == "Approved")
	{
          $this->approved = true;
	  $this->declined = false;
	  $this->error    = false;
          $this->method   = $this->getMethod();
	  break;
	} else if ($this->getResultResponseFull() == "Declined")
	{
          $this->approved = false;
	  $this->declined = true;
	  $this->error    = false;
	  break;
	}
	$count++;
      }
    }

    function parseResults()
    {
      $this->results = explode($this->params['x_delim_char'], $this->response);
    }

    function setParameter($param, $value)
    {
      $param                = trim($param);
      $value                = trim($value);
      $this->params[$param] = $value;
    }

    function setTransactionType($type)
    {
      $this->params['x_type'] = strtoupper(trim($type));
    }

    function _prepareParameters()
    {
      foreach($this->params as $key => $value)
      {
	$this->fields .= "$key=" . urlencode($value) . "&";
      }
      for($i=0; $i<count($this->line_items); $i++) {
        $this->fields .= "x_line_item={$this->line_items[$i]}&";
      }
    }

    function getMethod()
    {
      if (isset($this->results[51]))
      {
        return str_replace($this->params['x_encap_char'],'',$this->results[51]);
      }
      return "";
    }

    function getGatewayResponse()
    {
      return str_replace($this->params['x_encap_char'],'',$this->results[0]);
    }

    function getResultResponseFull()
    {
      $response = array("", "Approved", "Declined", "Error");
      return $response[str_replace($this->params['x_encap_char'],'',$this->results[0])];
    }

    function isApproved()
    {
      return $this->approved;
    }

    function isDeclined()
    {
      return $this->declined;
    }

    function isError()
    {
      return $this->error;
    }

    function getResponseText()
    {
      return $this->results[3];
      $strip = array($this->params['x_delim_char'],$this->params['x_encap_char'],'|',',');
      return str_replace($strip,'',$this->results[3]);
    }

    function getAuthCode()
    {
      return str_replace($this->params['x_encap_char'],'',$this->results[4]);
    }

    function getAVSResponse()
    {
      return str_replace($this->params['x_encap_char'],'',$this->results[5]);
    }

    function getTransactionID()
    {
      return str_replace($this->params['x_encap_char'],'',$this->results[6]);
    }
  }
}

M_register_gateway('authorizenetaim', 'authorizenetaim');
