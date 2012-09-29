<?php
/*
Addon Name: Authorize.net gateway
Description: The Payment gateway for Authorize.net.
Author: S H Mohanjith (Incsub)
Author URI: http://premium.wpmudev.org
Gateway ID: authorizenetaim
*/

class M_authorizenetaim extends M_Gateway {

	var $gateway = 'authorizenetaim';
	var $title = 'Authorize.net';
	//var $issingle = true;
	var $haspaymentform = true;
	var $ssl = true;

	function M_authorizenetaim() {
		global $M_membership_url;

		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));

		// If I want to override the transactions output - then I can use this action
		//add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));
		
		add_action('membership_subscription_form_registration_process', array(&$this, 'force_ssl_cookie'), null, 2);
		
		if($this->is_active()) {
			// Subscription form gateway
			add_action('membership_purchase_button', array(&$this, 'display_subscribe_button'), 1, 3);
			add_action('membership_payment_form', array(&$this, 'display_payment_form'), 10, 3 );

			// Payment return
			add_action('membership_handle_payment_return_' . $this->gateway, array(&$this, 'handle_payment_return'));
			add_filter('membership_subscription_form_subscription_process', array(&$this, 'signup_subscription'), 10, 2 );
			
		}

	}
	function force_ssl_cookie($errors, $user_id) {
		if(empty($errors)) {
			wp_set_auth_cookie($user_id,true,true);
			wp_set_current_user($user_id);
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

			foreach ($modes as $key => $value) : ?>
				<option value="<?php echo esc_attr($key); ?>" <?php selected($key, $sel_mode); ?>><?php echo esc_html($value); ?></option>
			<?php endforeach; ?>

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
				<tr valign="top">
					<th scope="row" colspan="2"><div class="updated below-h2"><p><?php _e('Authorize.net requires an SSL certificate to be installed on this domain', 'membership') ?></p></div></th>
				</tr>
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

	function show_payment_form() {


	}
	function single_button($pricing, $subscription, $user_id) {
		global $M_options;
		
		$popup = (isset($M_options['formtype']) && $M_options['formtype'] == 'new' ? true : false);
		
		$reg_page = (isset($M_options['registration_page']) ? get_permalink($M_options['registration_page']) : '');

		$form = '';
		$coupon_code = (isset($_REQUEST['remove_coupon']) ? '' : $_REQUEST['coupon_code']);
		$form .= '<form action="'.str_replace('http:', 'https:',$reg_page.'?action=registeruser&amp;subscription='.$subscription->id).'" method="post">';
		$form .= '<input type="submit" class="button blue" value="'.__('Pay Now','membership').'" />';
		$form .= '<input type="hidden" name="gateway" value="' . $this->gateway . '" />';
		
		//if($popup)
			//$form .= '<input type="hidden" name="action" value="extra_form" />';
		
		$form .= '<input type="hidden" name="extra_form" value="1">';
		//$form .= '<input type="hidden" name="subscription" value="' . $subscription->id . '" />';
		$form .= '<input type="hidden" name="coupon_code" value="'.(!empty($coupon_code) ? $_REQUEST['coupon_code'] : '').'" />';
		$form .= '</form>';
		
		return $form;
	}
	function display_payment_form($subscription, $pricing, $user_id) {
		global $M_options, $M_membership_url;
		
		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}
		$popup = (isset($M_options['formtype']) && $M_options['formtype'] == 'new' ? true : false);
		$reg_page = (isset($M_options['registration_page']) ? get_permalink($M_options['registration_page']) : '');
		$form = '';

		$M_secure_home_url = preg_replace('/http:/i', 'https:', trailingslashit(get_option('home')));
		
		?>
		<script type="text/javascript">
			_authorize_return_url = "<?php echo $M_secure_home_url . 'paymentreturn/' . esc_attr($this->gateway); ?>";
			_permalink_url = "<?php echo get_permalink(); ?>";
			_authorize_payment_error_msg = "<?php echo __('There was an unknown error encountered with your payment.  Please contact the site administrator.','membership'); ?>";
			jQuery("head").append('<link href="<?php echo $M_membership_url; ?>membershipincludes/css/authorizenet.css" rel="stylesheet" type="text/css">');
		</script>
		
		<script type="text/javascript" src="<?php echo $M_membership_url; ?>membershipincludes/js/authorizenet.js"></script>
		<form method="post" action="" class="membership_payment_form authorizenet single">
		
		<?php
		$coupon_code = (isset($_REQUEST['remove_coupon']) ? '' : $_REQUEST['coupon_code']);
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
		?>
		<?php if($popup) : ?>
			<h1><?php echo __('Enter Your Credit Card Information','membership'); ?></h1>
		<?php endif; ?>
		
		<div id="authorize_errors" class="message error hidden"></div>
		<input type="hidden" name="subscription_id" value="<?php echo $subscription->id; ?>" />
		<input type="hidden" name="gateway" value="<?php echo $this->gateway; ?>" />
		<?php if(!empty($coupon_code)) : ?>
			<input type="hidden" name="coupon_code" value="<?php echo $coupon_code; ?>" />
		<?php endif; ?>
		<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
		<div class="membership_cart_billing">
			<div class="auth-body">
				<div class="auth-billing">
					<div class="auth-billing-name"><?php echo __('Credit Card Billing Information:', 'mp'); ?>*</div>
					<div class="auth-billing-fname-label">
						<label class="inputLabel" for="first_name"><?php echo __('First Name:', 'mp'); ?></label>
					</div>
					<div class="auth-billing-fname">
						<input id="first_name" name="first_name" class="input_field noautocomplete" type="text" size="20" maxlength="20" />
					</div>
					<div class="auth-billing-lname-label">
						<label class="inputLabel" for="last_name"><?php echo __('Last Name:', 'mp'); ?></label>
					</div>
					<div class="auth-billing-lname"><input id="last_name" name="last_name" class="input_field noautocomplete" type="text" size="20" maxlength="20" /></div>
					<div class="auth-billing-address-label">
						<label class="inputLabel" for="address"><?php echo __('Address:', 'mp'); ?></label>
					</div>
					<div class="auth-billing-address">
						<input id="address" name="address" class="input_field noautocomplete" type="text" size="120" maxlength="120" />
					</div>
					<div class="auth-billing-zip-label">
						<label class="inputLabel" for="zip"><?php echo __('Billing 5-Digit Zipcode:', 'mp'); ?></label>
					</div>
					<div class="auth-billing-zip">
						<input id="zip" name="zip" class="input_field noautocomplete" type="text" size="5" maxlength="5" />
					</div>
				</div>
				<div class="auth-cc">
					<div class="auth-cc-label"><?php echo __('Credit Card Number:', 'mp'); ?>*</div>
					<div class="auth-cc-input">
						<input name="card_num" onkeyup="cc_card_pick('#cardimage', '#card_num')" id="card_num" class="credit_card_number input_field noautocomplete" type="text" size="22" maxlength="22" />
						<div class="hide_after_success nocard cardimage"  id="cardimage" style="background: url(<?php echo $M_membership_url; ?>membershipincludes/images/card_array.png) no-repeat;"></div>
					</div>
				</div>
				<div class="auth-exp">
					<div class="auth-exp-label"><?php echo __('Expiration Date:', 'mp'); ?>*</div>
					<div class="auth-exp-input">
						<label class="inputLabel" for="exp_month"><?php echo __('Month', 'membership'); ?></label>
						<select name="exp_month" id="exp_month"><?php echo $this->_print_month_dropdown(); ?></select>
						<label class="inputLabel" for="exp_year"><?php echo __('Year', 'membership'); ?></label>
						<select name="exp_year" id="exp_year"><?php echo $this->_print_year_dropdown('', true); ?></select>
					</div>
				</div>
				<div class="auth-sec">
					<div class="auth-sec-label"><?php echo __('Security Code:', 'mp'); ?></div>
					<div class="auth-sec-input">
						<input id="card_code" name="card_code" class="input_field noautocomplete" type="text" size="4" maxlength="4" />
					</div>
				</div>
				<div class="auth-submit">
					<div class="auth-submit-button">
						<input type="image" src="<?php echo $M_membership_url; ?>membershipincludes/images/cc_process_payment.png" alt="<?php echo __("Pay with Credit Card", "membership"); ?>" />
					</div>
				</div>
			</div>
		</div>
	</form><?php
	}
	
	function handle_payment_return() {
		global $M_options, $M_membership_url;
		
		$return = array();
		
		if($_SERVER['HTTPS'] != 'on') {
			wp_die(__('You must use HTTPS in order to do this','membership'));
			exit;
		}
		
		$coupon_code = (isset($_REQUEST['remove_coupon']) ? '' : $_REQUEST['coupon_code']);

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$subscription = new M_Subscription($_POST['subscription_id']);
		$pricing = $subscription->get_pricingarray();
		
		if(!empty($coupon_code))
			$pricing = $subscription->apply_coupon_pricing($coupon_code,$pricing);

		$user_id = ( is_user_logged_in() ? get_current_user_id() : $_POST['user_id'] );
		$user = get_userdata($user_id);
		$sub_id = $subscription->id;

		// A basic price or a single subscription
		if($pricing) {
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
			$amount = number_format($pricing[0]['amount'], 2);
			// Billing Info
			$payment->setParameter("x_card_code", $_POST['card_code']);
			$payment->setParameter("x_exp_date ", $_POST['exp_month'] . $_POST['exp_year']);
			$payment->setParameter("x_amount", $amount);

			// Payment billing information passed to authorize, thanks to Kevin L. for spotting this.
			$payment->setParameter("x_first_name", $_POST['first_name']);
			$payment->setParameter("x_last_name", $_POST['last_name']);
			$payment->setParameter("x_address", $_POST['address']);
			$payment->setParameter("x_zip", $_POST['zip']);
			$payment->setParameter("x_email", ( is_email($user->user_email) != false ? is_email($user->user_email) : '' ) );

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

				$status = __('Processed','membership');
				$note = '';

				$member = new M_Membership($user_id);
				if($member) {
					if($member->has_subscription() && $member->on_sub($sub_id)) {
						remove_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );
						remove_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );
						$member->expire_subscription($sub_id);
						$member->create_subscription($sub_id, $this->gateway);
					} else {
						$member->create_subscription($sub_id, $this->gateway);
					}
				}

				// TODO: create switch for handling different authorize aim respone codes

				$this->record_transaction($user_id, $sub_id, $amount, $M_options['paymentcurrency'], time(), ( $payment->results[6] == 0 ? 'TESTMODE' : $payment->results[6]) , $status, $note);

				do_action('membership_payment_subscr_signup', $user_id, $sub_id);
				$return['status'] = 'success';
				$return['redirect'] = (!strpos(home_url,'https:') ? str_replace('https:','http:',M_get_registrationcompleted_permalink()) : M_get_registrationcompleted_permalink());
			} else {
				$return['status'] = 'error';
				$return['errors'][] =  __('Your payment was declined.  Please check all your details or use a different card.','membership');
			}
		} else {
			$return['status'] = 'error';
			$return['errors'][] =  __('There was an issue determining the price.','membership');
		}
		
		echo json_encode($return);
		exit;

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

	function display_subscribe_button($subscription, $pricing, $user_id, $sublevel = 1) {

		if(isset($pricing[$sublevel - 1]) && $pricing[$sublevel - 1]['amount'] < 1)
			echo $this->single_free_button($pricing, $subscription, $user_id, $sublevel);
		else
			echo $this->build_subscribe_button($subscription, $pricing, $user_id, $sublevel);

	}
	function single_upgrade_button($pricing, $subscription, $user_id, $norepeat = false, $fromsub_id = false) {
		if($norepeat === true) {
			$form = '<a class="button" href="'.M_get_registration_permalink().'?action=registeruser&subscription='.$subscription->id.'">'.__('Upgrade','membership').'</a>';
		} else {
			$form = '<a class="button" href="'.M_get_registration_permalink().'?action=registeruser&subscription='.$subscription->id.'">'.__('Upgrade Subscription','membership').'</a>';
		}
		echo $form;
	}
	function complex_upgrade_button($pricing, $subscription, $user_id, $fromsub_id = false) {
		$form = '<a class="button" href="'.M_get_registration_permalink().'?action=registeruser&subscription='.$subscription->id.'">'.__('Upgrade','membership').'</a>';
		echo $form;
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

	function display_upgrade_button($pricing, $subscription, $user_id, $fromsub_id = false) {
		$this->build_upgrade_button($pricing, $subscription, $user_id, $fromsub_id = false);
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

M_register_gateway('authorizenetaim', 'M_authorizenetaim');