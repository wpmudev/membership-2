<?php
/*
Addon Name: Authorize.net ARB gateway (Deprecated)
Description: The Payment gateway for Authorize.net ARB. This gateway is deprecated. Use Authorize.net gateway instead.
Author: Incsub
Author URI: http://premium.wpmudev.org
Gateway ID: authorizenetarb
*/

class M_authorizenetarb extends Membership_Gateway {

	var $gateway = 'authorizenetarb';
	var $title = 'Authorize.net ARB';
	var $issingle = false;
	var $haspaymentform = true;
	var $ssl = true;

	function __construct() {
		parent::__construct();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));

		add_action('membership_subscription_form_registration_process', array(&$this, 'force_ssl_cookie'), null, 2);

		if($this->is_active()) {
			// Subscription form gateway
			add_action('membership_purchase_button', array(&$this, 'display_subscribe_button'), 1, 3);
			add_action('membership_payment_form', array(&$this, 'display_payment_form'), 10, 3 );

			// Payment return
			add_action('membership_handle_payment_return_' . $this->gateway, array(&$this, 'handle_payment_return'));
			add_filter('membership_subscription_form_subscription_process', array(&$this, 'signup_subscription'), 10, 2 );

			// Ajax calls for purchase buttons - if logged out
			add_action( 'wp_ajax_nopriv_purchaseform', array(&$this, 'popover_payment_form') );
			// if logged in
			add_action( 'wp_ajax_purchaseform', array(&$this, 'popover_payment_form') );

			// Ajax calls for purchase processing - if logged out
			add_action( 'wp_ajax_nopriv_processpurchase_' . $this->gateway , array(&$this, 'process_payment_form') );
			// if logged in
			add_action( 'wp_ajax_processpurchase_' . $this->gateway, array(&$this, 'process_payment_form') );

			// Cancel subscription button
			add_filter( 'membership_unsubscribe_subscription', array(&$this, 'process_unsubscribe_subscription'), 10, 3 );
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

		if ( !is_ssl() ) {
			echo '<div id="message" class="updated fade"><p>' . __('Authorize.net requires an SSL certificate to be installed on this domain', 'membership') . '</p></div>';
		}

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

	function build_custom($user_id, $sub_id, $amount, $fromsub_id = false) {
		$custom = '';

		$custom = time() . ':' . $user_id . ':' . $sub_id . ':';
		$key = md5('MEMBERSHIP' . $amount);

		$custom .= $key;

		if($fromsub_id !== false) {
			$custom .= ":" . $fromsub_id;
		} else {
			$custom .= ":0";
		}

		return $custom;
	}

	function signup_subscription($content, $error) {

		if( isset($_POST['custom'])) {
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

		$popup = ( isset($M_options['formtype']) && $M_options['formtype'] == 'new' ? true : false);

		$reg_page = ( isset($M_options['registration_page']) ? get_permalink($M_options['registration_page']) : '');

		$form = '';

		//$coupon_code = ( isset($_REQUEST['remove_coupon']) ? '' : $_REQUEST['coupon_code']);
		$coupon = membership_get_current_coupon();

		$form .= '<form action="'.str_replace('http:', 'https:',$reg_page.'?action=registeruser&amp;subscription='.$subscription->id).'" method="post" id="signup-form">';
		$form .= '<input type="submit" class="button ' . apply_filters('membership_subscription_button_color', 'blue') . '" value="'.__('Pay Now','membership').'" />';
		$form .= '<input type="hidden" name="gateway" id="subscription_gateway" value="' . $this->gateway . '" />';

		//if($popup)
			//$form .= '<input type="hidden" name="action" value="extra_form" />';

		$form .= '<input type="hidden" name="extra_form" value="1">';

		$form .= '<input type="hidden" name="subscription" id="subscription_id" value="' . $subscription->id . '" />';
		$form .= '<input type="hidden" name="user" id="subscription_user_id" value="' . $user_id . '" />';

		$form .= '<input type="hidden" name="coupon_code" id="subscription_coupon_code" value="' . (!empty($coupon) ? $coupon->get_coupon_code() : '') . '" />';
		$form .= '</form>';

		return $form;
	}

	function popover_payment_form() {

		global $M_options;

		$gateway = $_POST['gateway'];

		if( $gateway == 'authorizenetarb' ) {

			$subscription_id = $_POST['subscription'];
			$coupon_code = $_POST['coupon_code'];
			$user_id = $_POST['user'];

			if( empty($user_id) ) {
				$user = wp_get_current_user();

				$spmemuserid = $user->ID;

				if(!empty($user->ID) && is_numeric($user->ID) ) {
					$member = Membership_Plugin::factory()->get_member( $user->ID);
				} else {
					$member = current_member();
				}
			} else {
				$member = Membership_Plugin::factory()->get_member( $user_id );
			}

			$subscription = (int) $_REQUEST['subscription'];

			$gateway = M_get_class_for_gateway($gateway);

			if($gateway && is_object($gateway) && $gateway->haspaymentform == true) {
				$sub =  new M_Subscription( $subscription );
				// Get the coupon
				$coupon = membership_get_current_coupon();
				// Build the pricing array
				$pricing = $sub->get_pricingarray();

				if(!empty($pricing) && !empty($coupon) ) {
					$pricing = $coupon->apply_coupon_pricing( $pricing );
				}

				// Check if the pricing is now a free subscription and if so then handle the signup directly
				// We are on a free signup - check the subscription then set it up
				if( (isset($pricing[0]) && $pricing[0]['amount'] < 1 && count($pricing) == 1) || (count($pricing) == 2 && $pricing[0]['amount'] < 1 && $pricing[1]['amount'] < 1 ) ) {
					// We have a free level
					$member->create_subscription( $subscription_id, $this->gateway );
					if( !empty($M_options['registrationcompleted_message']) ) {
						echo $this->get_completed_message( $sub );
					} else {
						wp_safe_redirect( (!strpos(home_url(),'https:') ? str_replace('https:','http:',M_get_registrationcompleted_permalink()) : M_get_registrationcompleted_permalink()) );
					}
				} else {
					?>
					<div class='header' style='width: 750px'>
						<h1><?php echo __('Enter Your Credit Card Information','membership'); ?></h1>
					</div>
					<?php

					$this->display_payment_form( $sub, $pricing, $member->ID );
				}
			}

		}

		// Need this to stop processing
		die();

	}

	function display_payment_form($subscription, $pricing, $user_id) {
		global $M_options, $M_membership_url;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}
		$popup = ( isset($M_options['formtype']) && $M_options['formtype'] == 'new' ? true : false);
		$reg_page = ( isset($M_options['registration_page']) ? get_permalink($M_options['registration_page']) : '');
		$form = '';

		$M_secure_home_url = preg_replace('/http:/i', 'https:', trailingslashit(get_option('home')));

		?>
		<script type="text/javascript">
			_authorize_return_url = "<?php echo admin_url( 'admin-ajax.php', 'https' ) . '?action=processpurchase_' . $this->gateway; ?>";
			_permalink_url = "<?php echo get_permalink(); ?>";
			_authorize_payment_error_msg = "<?php echo __('There was an unknown error encountered with your payment.  Please contact the site administrator.','membership'); ?>";
			jQuery("head").append('<link href="<?php echo membership_url( 'membershipincludes/css/authorizenet.css' ); ?>" rel="stylesheet" type="text/css">');
		</script>

		<script type="text/javascript" src="<?php echo $M_membership_url; ?>membershipincludes/js/authorizenet.js"></script>
		<form method="post" action="" class="membership_payment_form authorizenet single">

		<?php

		$coupon = membership_get_current_coupon();

		$api_u = get_option( $this->gateway . "_api_user");
		$api_k = get_option( $this->gateway . "_api_key");
		$error = false;
		if( isset($_GET['errors'])) {
			if($_GET['errors'] == 1)
				$error = __('Payment method not supported for the payment', 'membership');
			if($_GET['errors'] == 2)
				$error = __('There was a problem processing your purchase. Please try again', 'membership');
		}
		if(!isset($api_u) || $api_u == '' || $api_u == false || !isset($api_k) || $api_k == '' || $api_k == false) {
			$error = __('This payment gateway has not been configured.  Your transaction will not be processed.', 'membership');
		}
		?>

		<div id="authorize_errors" class=""></div>
		<input type="hidden" name="subscription_id" value="<?php echo $subscription->id; ?>" />
		<input type="hidden" name="gateway" value="<?php echo $this->gateway; ?>" />
		<?php if(!empty($coupon)) : ?>
			<input type="hidden" name="coupon_code" value="<?php echo $coupon->get_coupon_code(); ?>" />
		<?php endif; ?>
		<input type="hidden" name="user_id" value="<?php echo $user_id; ?>" />
		<div class="membership_cart_billing">
			<div class="auth-body">
				<div class="auth-billing">
					<div class="auth-billing-name auth-field"><?php echo __('Credit Card Billing Information:', 'mp'); ?>*</div>
					<div class="auth-billing-fname-label auth-field">
						<label class="inputLabel" for="first_name"><?php echo __('First Name:', 'mp'); ?></label>
					</div>
					<div class="auth-billing-fname auth-field">
						<input id="first_name" name="first_name" class="input_field noautocomplete" type="text" size="20" maxlength="20" />
					</div>
					<div class="auth-billing-lname-label auth-field">
						<label class="inputLabel" for="last_name"><?php echo __('Last Name:', 'mp'); ?></label>
					</div>
					<div class="auth-billing-lname auth-field"><input id="last_name" name="last_name" class="input_field noautocomplete" type="text" size="20" maxlength="20" /></div>
					<div class="auth-billing-address-label auth-field">
						<label class="inputLabel" for="address"><?php echo __('Address:', 'mp'); ?></label>
					</div>
					<div class="auth-billing-address auth-field">
						<input id="address" name="address" class="input_field noautocomplete" type="text" size="120" maxlength="120" />
					</div>
					<div class="auth-billing-zip-label auth-field">
						<label class="inputLabel" for="zip"><?php echo __('Billing 5-Digit Zipcode:', 'mp'); ?></label>
					</div>
					<div class="auth-billing-zip auth-field">
						<input id="zip" name="zip" class="input_field noautocomplete" type="text" size="5" maxlength="5" />
					</div>
				</div>
				<div class="auth-cc">
					<div class="auth-cc-label auth-field"><?php echo __('Credit Card Number:', 'mp'); ?>*</div>
					<div class="auth-cc-input auth-field">
						<input class="auth-cc-cardnum" name="card_num" onkeyup="cc_card_pick('#cardimage', '#card_num')" id="card_num" class="credit_card_number input_field noautocomplete" type="text" size="22" maxlength="22" />
						<div class="hide_after_success nocard cardimage"  id="cardimage" style="background: url(<?php echo $M_membership_url; ?>membershipincludes/images/card_array.png) no-repeat;"></div>
					</div>
				</div>
				<div class="auth-exp">
					<div class="auth-exp-label auth-field"><?php echo __('Expiration Date:', 'mp'); ?>*</div>
					<div class="auth-exp-input auth-field">
						<label class="inputLabel" for="exp_month"><?php echo __('Month', 'membership'); ?></label>
						<select name="exp_month" id="exp_month"><?php echo $this->_print_month_dropdown(); ?></select>
						<label class="inputLabel" for="exp_year"><?php echo __('Year', 'membership'); ?></label>
						<select name="exp_year" id="exp_year"><?php echo $this->_print_year_dropdown('', true); ?></select>
					</div>
				</div>
				<div class="auth-sec">
					<div class="auth-sec-label auth-field"><?php echo __('Security Code:', 'mp'); ?></div>
					<div class="auth-sec-input auth-field">
						<input id="card_code" name="card_code" class="input_field noautocomplete" type="text" size="4" maxlength="4" />
					</div>
				</div>
				<div class="auth-submit">
					<div class="auth-submit-button auth-field">
						<input type="image" src="<?php echo $M_membership_url; ?>membershipincludes/images/cc_process_payment.png" alt="<?php echo __("Pay with Credit Card", "membership"); ?>" />
					</div>
				</div>
			</div>
		</div>
	</form><?php
	}

	function process_unsubscribe_subscription( $pass, $sub_id, $user_id ) {

		// We need to find if we have a subscription and attempt to remove it
		$subscription_id = get_user_meta( $user_id, 'membership_' . $this->gateway . '_subscription_' . $sub_id, true );

		if(!empty($subscription_id)) {

			$arbsubscription = new M_Gateway_Worker_AuthorizeNet_ARB( 	get_option( $this->gateway . "_api_user", '' ),
			  															get_option( $this->gateway . "_api_key", '' ),
			  															(get_option( $this->gateway . "_mode", 'sandbox' ) == 'sandbox'));

			$arbsubscription->setParameter('subscrId', $subscription_id);
			$arbsubscription->deleteAccount();

			if( $arbsubscription->isSuccessful() ) {
				return true;
			} else {
				return false;
			}

		} else {
			return $pass;
		}

	}

	function process_aim_payment( $amount, $user, $subscription ) {

		global $M_options;

		// A basic price or a single subscription
		$return = array();

		if($amount >= 1) {
			// We have a charge to make
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
			$amount = number_format($amount, 2, '.', '');
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

				$return['status'] = 'success';

				$status = __('Processed','membership');
				$note = '';

				$this->record_transaction($user->ID, $subscription->sub_id(), $amount, $M_options['paymentcurrency'], time(), ( $payment->results[6] == 0 ? 'TESTMODE' : $payment->results[6]) , $status, $note);

			} else {
				$return['status'] = 'error';
				$return['errors'][] =  __('Your payment was declined.  Please check all your details or use a different card.','membership');
			}
		} else {
			$return['status'] = 'error';
			$return['errors'][] =  __('There was an issue determining the price.','membership');
		}

		return $return;

	}

	// Function to process the ajax payment for gateways that do live processing / no ipn
	function process_payment_form() {
		global $M_options, $M_membership_url;

		$return = array();

		if( !is_ssl() ) {
			wp_die(__('You must use HTTPS in order to do this','membership'));
			exit;
		}

		$popup = ( isset($M_options['formtype']) && $M_options['formtype'] == 'new' ? true : false);
		$coupon = membership_get_current_coupon();

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$subscription = new M_Subscription($_POST['subscription_id']);
		$pricing = $subscription->get_pricingarray();

		if(!empty($pricing) && !empty($coupon) && method_exists( $coupon, 'valid_for_subscription') && $coupon->valid_for_subscription( $subscription->id ) ) {
			$pricing = $coupon->apply_coupon_pricing( $pricing );
		}

		$user_id = ( is_user_logged_in() ? get_current_user_id() : $_POST['user_id'] );
		$user = get_userdata($user_id);
		$sub_id = $subscription->id;

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
						// one-off payment - so we just use AIM instead

						$return = $this->process_aim_payment( $pricing[0]['amount'], $user, $subscription );

						if( !empty($return) && $return['status'] == 'success' ) {
							// The payment went through ok
							$member = Membership_Plugin::factory()->get_member($user_id);
							if($member) {
								if($member->has_subscription() && $member->on_sub($sub_id)) {
									//remove_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );
									//remove_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );
									$member->expire_subscription($sub_id);
									$member->create_subscription($sub_id, $this->gateway);
								} else {
									$member->create_subscription($sub_id, $this->gateway);
								}
							}

							if( $popup && !empty($M_options['registrationcompleted_message']) ) {
								$return['redirect'] = 'no';
								$registrationcompletedmessage = $this->get_completed_message( $subscription );
								$return['message'] = $registrationcompletedmessage;
							} else {
								$return['redirect'] = (!strpos(home_url(),'https:') ? str_replace('https:','http:',M_get_registrationcompleted_permalink()) : M_get_registrationcompleted_permalink());
								$return['message'] = '';
							}
						} else {
							// The payment didn't go through, so leave the return array holding the error
						}

						// Encode the return, echo it and exit so no more processing occurs
						echo json_encode($return);
						exit;

					} elseif( $pricing[0]['type'] == 'serial' ) {
						// Single serial subscription - we want to charge the first amount using AIM so we can validate the payment, then setup the subscription to start one period later

						$return = $this->process_aim_payment( $pricing[0]['amount'], $user, $subscription );

						if( !empty($return) && $return['status'] == 'success' ) {
							// The payment went through ok
							$arbsubscription = new M_Gateway_Worker_AuthorizeNet_ARB( 	get_option( $this->gateway . "_api_user", '' ),
							  															get_option( $this->gateway . "_api_key", '' ),
							  															(get_option( $this->gateway . "_mode", 'sandbox' ) == 'sandbox'));

						    $arbsubscription->setParameter( 'subscrName', $subscription->sub_name() . ' ' . __('subscription', 'membership') );

							switch($pricing[0]['unit']) {
								case 'd':	$arbsubscription->setParameter( 'interval_length', $pricing[0]['period'] );
											$arbsubscription->setParameter( 'interval_unit', "days" );

											$trialperiod = '+' . $pricing[0]['period'] . ' days';
											break;

								case 'w':	$arbsubscription->setParameter( 'interval_length', ( $pricing[0]['period'] * 7 ) );
											$arbsubscription->setParameter( 'interval_unit', "days" );

											$trialperiod = '+' . $pricing[0]['period'] . ' weeks';
											break;

								case 'm':	$arbsubscription->setParameter( 'interval_length', $pricing[0]['period'] );
											$arbsubscription->setParameter( 'interval_unit', "months" );

											$trialperiod = '+' . $pricing[0]['period'] . ' months';
											break;

								case 'y':	$arbsubscription->setParameter( 'interval_length', ( $pricing[0]['period'] * 12 ) );
											$arbsubscription->setParameter( 'interval_unit', "months" );

											$trialperiod = '+' . $pricing[0]['period'] . ' years';
											break;
							}

							// Add a period to the start date
							$arbsubscription->setParameter( 'startDate', date( "Y-m-d", strtotime( $trialperiod ) ) ); // Next period
						    $arbsubscription->setParameter( 'totalOccurrences', "9999" ); // 9999 = ongoing subscription in ARB docs

						    $arbsubscription->setParameter( 'amount', number_format($pricing[0]['amount'], 2, '.', '') );

							$arbsubscription->setParameter( 'cardNumber', $_POST['card_num'] );
						    $arbsubscription->setParameter( 'expirationDate', $_POST['exp_year'] . '-' . $_POST['exp_month'] );
						    $arbsubscription->setParameter( 'cardCode', $_POST['card_code'] );

							$arbsubscription->setParameter( 'firstName', $_POST['first_name'] );
						    $arbsubscription->setParameter( 'lastName', $_POST['last_name'] );

							$arbsubscription->setParameter( 'address', $_POST['address'] );
							$arbsubscription->setParameter( 'zip', $_POST['zip'] );

							$arbsubscription->setParameter( 'customerId', $user->ID );

							$arbsubscription->setParameter( 'customerEmail', ( is_email($user->user_email) != false ) ? $user->user_email : '' );

							$arbsubscription->createAccount();

							if( $arbsubscription->isSuccessful() ) {
								// Get the subscription ID
								$subscription_id = $arbsubscription->getSubscriberID();

								$member = Membership_Plugin::factory()->get_member($user_id);
								if($member) {
									if($member->has_subscription() && $member->on_sub($sub_id)) {
										//remove_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );
										//remove_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );
										$member->expire_subscription($sub_id);
										$member->create_subscription($sub_id, $this->gateway);
									} else {
										$member->create_subscription($sub_id, $this->gateway);
									}

									// Store the subscription id in the user meta for later use
									update_user_meta( $member->ID, 'membership_' . $this->gateway . '_subscription_' . $sub_id , $subscription_id );

								}

								if( $popup && !empty($M_options['registrationcompleted_message']) ) {
									$return['redirect'] = 'no';
									$registrationcompletedmessage = $this->get_completed_message( $subscription );
									$return['message'] = $registrationcompletedmessage;
								} else {
									$return['redirect'] = (!strpos(home_url(),'https:') ? str_replace('https:','http:',M_get_registrationcompleted_permalink()) : M_get_registrationcompleted_permalink());
									$return['message'] = '';
								}

							} else {
								// The subscription was not created!
								$return['status'] = 'error';
								$return['errors'][] =  __('Sorry, your subscription could not be created.','membership');
							}

						} else {
							// The payment didn't go through so return the error passed through from the aim processing

						}

						// Encode the return, echo it and exit so no more processing occurs
						echo json_encode($return);
						exit;

					}
				} else {
					// something much more complex
					$processsecond = true;

					$arbsubscription = new M_Gateway_Worker_AuthorizeNet_ARB( 	get_option( $this->gateway . "_api_user", '' ),
					  															get_option( $this->gateway . "_api_key", '' ),
					  															(get_option( $this->gateway . "_mode", 'sandbox' ) == 'sandbox'));

				    $arbsubscription->setParameter( 'subscrName', $subscription->sub_name() . ' ' . __('subscription', 'membership') );


					switch( $pricing[0]['type'] ) {
						case 'finite':			// This is the one we are expecting here as anything else would be silly
												// Set the trial period up
												switch($pricing[0]['unit']) {
													case 'd':	$trialperiod = '+' . $pricing[0]['period'] . ' days';
																break;

													case 'w':	$trialperiod = '+' . $pricing[0]['period'] . ' weeks';
																break;

													case 'm':	$trialperiod = '+' . $pricing[0]['period'] . ' months';
																break;

													case 'y':	$trialperiod = '+' . $pricing[0]['period'] . ' years';
																break;
												}
												break;

						case 'indefinite':		// Hmmm - ok
												$processsecond = false;

												$return = $this->process_aim_payment( $pricing[0]['amount'], $user, $subscription );

												if( !empty($return) && $return['status'] == 'success' ) {
													// The payment went through ok
													$member = Membership_Plugin::factory()->get_member($user_id);
													if($member) {
														if($member->has_subscription() && $member->on_sub($sub_id)) {
															//remove_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );
															//remove_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );
															$member->expire_subscription($sub_id);
															$member->create_subscription($sub_id, $this->gateway);
														} else {
															$member->create_subscription($sub_id, $this->gateway);
														}
													}

													if( $popup && !empty($M_options['registrationcompleted_message']) ) {
														$return['redirect'] = 'no';
														$registrationcompletedmessage = $this->get_completed_message( $subscription );
														$return['message'] = $registrationcompletedmessage;
													} else {
														$return['redirect'] = (!strpos(home_url(),'https:') ? str_replace('https:','http:',M_get_registrationcompleted_permalink()) : M_get_registrationcompleted_permalink());
														$return['message'] = '';
													}
												} else {
													// The payment didn't go through, so leave the return array holding the error
												}

												// Encode the return, echo it and exit so no more processing occurs
												echo json_encode($return);
												exit;

												break;

						case 'serial':			// Hmmm - ok par deux
												$processsecond = false;

												$return = $this->process_aim_payment( $pricing[0]['amount'], $user, $subscription );

												if( !empty($return) && $return['status'] == 'success' ) {
													// The payment went through ok
													$arbsubscription = new M_Gateway_Worker_AuthorizeNet_ARB( 	get_option( $this->gateway . "_api_user", '' ),
													  															get_option( $this->gateway . "_api_key", '' ),
													  															(get_option( $this->gateway . "_mode", 'sandbox' ) == 'sandbox'));

												    $arbsubscription->setParameter( 'subscrName', $subscription->sub_name() . ' ' . __('subscription', 'membership') );

													switch($pricing[0]['unit']) {
														case 'd':	$arbsubscription->setParameter( 'interval_length', $pricing[0]['period'] );
																	$arbsubscription->setParameter( 'interval_unit', "days" );
																	break;

														case 'w':	$arbsubscription->setParameter( 'interval_length', ( $pricing[0]['period'] * 7 ) );
																	$arbsubscription->setParameter( 'interval_unit', "days" );
																	break;

														case 'm':	$arbsubscription->setParameter( 'interval_length', $pricing[0]['period'] );
																	$arbsubscription->setParameter( 'interval_unit', "months" );
																	break;

														case 'y':	$arbsubscription->setParameter( 'interval_length', ( $pricing[0]['period'] * 12 ) );
																	$arbsubscription->setParameter( 'interval_unit', "months" );
																	break;
													}

													// Add a period to the start date
													$arbsubscription->setParameter( 'startDate', date( "Y-m-d", strtotime( '+' . $arbsubscription->intervalLength . ' ' . $arbsubscription->intervalUnit ) ) ); // Next period
												    $arbsubscription->setParameter( 'totalOccurrences', "9999" ); // 9999 = ongoing subscription in ARB docs

												    $arbsubscription->setParameter( 'amount', number_format($pricing[0]['amount'], 2, '.', '') );

													$arbsubscription->setParameter( 'cardNumber', $_POST['card_num'] );
												    $arbsubscription->setParameter( 'expirationDate', $_POST['exp_year'] . '-' . $_POST['exp_month'] );
												    $arbsubscription->setParameter( 'cardCode', $_POST['card_code'] );

													$arbsubscription->setParameter( 'firstName', $_POST['first_name'] );
												    $arbsubscription->setParameter( 'lastName', $_POST['last_name'] );

													$arbsubscription->setParameter( 'address', $_POST['address'] );
													$arbsubscription->setParameter( 'zip', $_POST['zip'] );

													$arbsubscription->setParameter( 'customerId', $user->ID );
													$arbsubscription->setParameter( 'customerEmail', ( is_email($user->user_email) != false ) ? $user->user_email : '' );

													$arbsubscription->createAccount();

													if( $arbsubscription->isSuccessful() ) {
														// Get the subscription ID
														$subscription_id = $arbsubscription->getSubscriberID();

														$member = Membership_Plugin::factory()->get_member($user_id);
														if($member) {
															if($member->has_subscription() && $member->on_sub($sub_id)) {
																//remove_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );
																//remove_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );
																$member->expire_subscription($sub_id);
																$member->create_subscription($sub_id, $this->gateway);
															} else {
																$member->create_subscription($sub_id, $this->gateway);
															}

															// Store the subscription id in the user meta for later use
															update_user_meta( $member->ID, 'membership_' . $this->gateway . '_subscription_' . $sub_id , $subscription_id );

														}

														if( $popup && !empty($M_options['registrationcompleted_message']) ) {
															$return['redirect'] = 'no';
															$registrationcompletedmessage = $this->get_completed_message( $subscription );
															$return['message'] = $registrationcompletedmessage;
														} else {
															$return['redirect'] = (!strpos(home_url(),'https:') ? str_replace('https:','http:',M_get_registrationcompleted_permalink()) : M_get_registrationcompleted_permalink());
															$return['message'] = '';
														}

													} else {
														// The subscription was not created!
														$return['status'] = 'error';
														$return['errors'][] =  __('Sorry, your subscription could not be created.','membership');
													}

												} else {
													// The payment didn't go through so return the error passed through from the aim processing

												}

												// Encode the return, echo it and exit so no more processing occurs
												echo json_encode($return);
												exit;

												break;
					}

					if( $processsecond == true ) {

						// We had an initial finite period so we need to see if we need to charge for it initially

						if( $pricing[0]['amount'] >= 1 ) {
							// The first period is not free so we have to charge for it
							$return = $this->process_aim_payment( $pricing[0]['amount'], $user, $subscription );
						} else {
							$return = array();
							$return['status'] = 'success';
						}

						if( !empty($return) && $return['status'] == 'success' ) {
							// The payment went through ok
							$arbsubscription = new M_Gateway_Worker_AuthorizeNet_ARB( 	get_option( $this->gateway . "_api_user", '' ),
							  															get_option( $this->gateway . "_api_key", '' ),
							  															(get_option( $this->gateway . "_mode", 'sandbox' ) == 'sandbox'));

						    $arbsubscription->setParameter( 'subscrName', $subscription->sub_name() . ' ' . __('subscription', 'membership') );

							switch($pricing[0]['unit']) {
								case 'd':	$arbsubscription->setParameter( 'interval_length', $pricing[0]['period'] );
											$arbsubscription->setParameter( 'interval_unit', "days" );
											break;

								case 'w':	$arbsubscription->setParameter( 'interval_length', ( $pricing[0]['period'] * 7 ) );
											$arbsubscription->setParameter( 'interval_unit', "days" );
											break;

								case 'm':	$arbsubscription->setParameter( 'interval_length', $pricing[0]['period'] );
											$arbsubscription->setParameter( 'interval_unit', "months" );
											break;

								case 'y':	$arbsubscription->setParameter( 'interval_length', ( $pricing[0]['period'] * 12 ) );
											$arbsubscription->setParameter( 'interval_unit', "months" );
											break;
							}

							// Add a period to the start date
							$arbsubscription->setParameter( 'startDate', date( "Y-m-d", strtotime( $trialperiod ) ) ); // Next period

							switch( $pricing[1]['type'] ) {
								case 'finite':			// For finite and indefinite we set up a subscription and only charge the once
								case 'indefinite':
														$arbsubscription->setParameter( 'totalOccurrences', "1" ); // 1 = a single future charge
														break;

								case 'serial':			// For serial we set up the subscription to keep being charged
														$arbsubscription->setParameter( 'totalOccurrences', "9999" ); // 9999 = ongoing subscription in ARB docs
														break;
							}

						    $arbsubscription->setParameter( 'amount', number_format($pricing[1]['amount'], 2, '.', '') );

							$arbsubscription->setParameter( 'cardNumber', $_POST['card_num'] );
						    $arbsubscription->setParameter( 'expirationDate', $_POST['exp_year'] . '-' . $_POST['exp_month'] );
						    $arbsubscription->setParameter( 'cardCode', $_POST['card_code'] );

							$arbsubscription->setParameter( 'firstName', $_POST['first_name'] );
						    $arbsubscription->setParameter( 'lastName', $_POST['last_name'] );

							$arbsubscription->setParameter( 'address', $_POST['address'] );
							$arbsubscription->setParameter( 'zip', $_POST['zip'] );

							$arbsubscription->setParameter( 'customerId', $user->ID );
							$arbsubscription->setParameter( 'customerEmail', ( is_email($user->user_email) != false ) ? $user->user_email : '' );

							$arbsubscription->createAccount();

							if( $arbsubscription->isSuccessful() ) {
								// Get the subscription ID
								$subscription_id = $arbsubscription->getSubscriberID();

								$member = Membership_Plugin::factory()->get_member($user_id);
								if($member) {
									if($member->has_subscription() && $member->on_sub($sub_id)) {
										//remove_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );
										//remove_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );
										$member->expire_subscription($sub_id);
										$member->create_subscription($sub_id, $this->gateway);
									} else {
										$member->create_subscription($sub_id, $this->gateway);
									}

									// Store the subscription id in the user meta for later use
									update_user_meta( $member->ID, 'membership_' . $this->gateway . '_subscription_' . $sub_id , $subscription_id );

								}

								if( $popup && !empty($M_options['registrationcompleted_message']) ) {
									$return['redirect'] = 'no';
									$registrationcompletedmessage = $this->get_completed_message( $subscription );
									$return['message'] = $registrationcompletedmessage;
								} else {
									$return['redirect'] = (!strpos(home_url(),'https:') ? str_replace('https:','http:',M_get_registrationcompleted_permalink()) : M_get_registrationcompleted_permalink());
									$return['message'] = '';
								}

							} else {
								// The subscription was not created!
								$return['status'] = 'error';
								$return['errors'][] =  __('Sorry, your subscription could not be created.','membership');
							}

						} else {
							// The payment didn't go through so return the error passed through from the aim processing

						}

						// Encode the return, echo it and exit so no more processing occurs
						echo json_encode($return);
						exit;

					}

				}
			}
		}

	}

	function get_completed_message( $sub ) {

		global $M_options;

		$html = '';

		$html .= "<div class='header'><h1>";
		$html .= __('Sign up for','membership') . " " . $sub->sub_name() . " " . __('completed', 'membership');
		$html .= "</h1></div><div class='fullwidth'>";

		$html .= stripslashes( wpautop( $M_options['registrationcompleted_message'] ) );

		$html .= "</div>";

		return $html;

	}

	function handle_payment_return() {

		// Not used for this gateway
		exit;
	}

	function single_sub_button($pricing, $subscription, $user_id, $norepeat = false) {
		global $M_options;

		$popup = ( isset($M_options['formtype']) && $M_options['formtype'] == 'new' ? true : false);

		$reg_page = ( isset($M_options['registration_page']) ? get_permalink($M_options['registration_page']) : '');

		$form = '';

		//$coupon_code = ( isset($_REQUEST['remove_coupon']) ? '' : $_REQUEST['coupon_code']);
		$coupon = membership_get_current_coupon();

		$form .= '<form action="'.str_replace('http:', 'https:',$reg_page.'?action=registeruser&amp;subscription='.$subscription->id).'" method="post" id="signup-form">';
		$form .= '<input type="submit" class="button ' . apply_filters('membership_subscription_button_color', 'blue') . '" value="'.__('Pay Now','membership').'" />';
		$form .= '<input type="hidden" name="gateway" id="subscription_gateway" value="' . $this->gateway . '" />';

		//if($popup)
			//$form .= '<input type="hidden" name="action" value="extra_form" />';

		$form .= '<input type="hidden" name="extra_form" value="1">';

		$form .= '<input type="hidden" name="subscription" id="subscription_id" value="' . $subscription->id . '" />';
		$form .= '<input type="hidden" name="user" id="subscription_user_id" value="' . $user_id . '" />';

		$form .= '<input type="hidden" name="coupon_code" id="subscription_coupon_code" value="' . (!empty($coupon) ? $coupon->get_coupon_code() : '') . '" />';
		$form .= '</form>';

		return $form;
	}

	function complex_sub_button($pricing, $subscription, $user_id) {
		global $M_options;

		$popup = ( isset($M_options['formtype']) && $M_options['formtype'] == 'new' ? true : false);

		$reg_page = ( isset($M_options['registration_page']) ? get_permalink($M_options['registration_page']) : '');

		$form = '';

		//$coupon_code = ( isset($_REQUEST['remove_coupon']) ? '' : $_REQUEST['coupon_code']);
		$coupon = membership_get_current_coupon();

		$form .= '<form action="'.str_replace('http:', 'https:',$reg_page.'?action=registeruser&amp;subscription='.$subscription->id).'" method="post" id="signup-form">';
		$form .= '<input type="submit" class="button ' . apply_filters('membership_subscription_button_color', 'blue') . '" value="'.__('Pay Now','membership').'" />';
		$form .= '<input type="hidden" name="gateway" id="subscription_gateway" value="' . $this->gateway . '" />';

		//if($popup)
			//$form .= '<input type="hidden" name="action" value="extra_form" />';

		$form .= '<input type="hidden" name="extra_form" value="1">';

		$form .= '<input type="hidden" name="subscription" id="subscription_id" value="' . $subscription->id . '" />';
		$form .= '<input type="hidden" name="user" id="subscription_user_id" value="' . $user_id . '" />';

		$form .= '<input type="hidden" name="coupon_code" id="subscription_coupon_code" value="' . (!empty($coupon) ? $coupon->get_coupon_code() : '') . '" />';
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
					if( ( count($pricing) == 2 ) ) {
						return $this->complex_sub_button($pricing, $subscription, $user_id);
					}
				}
			} else {
				return $this->single_free_button($pricing, $subscription, $user_id);
			}
		}
	}

	function single_free_button($pricing, $subscription, $user_id, $norepeat = false) {

		global $M_options;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		$form = '';

		$coupon = membership_get_current_coupon();

		$form .= '<form action="' . M_get_returnurl_permalink() . '" method="post" id="signup-form">';
		$form .=  wp_nonce_field('free-sub_' . $subscription->sub_id(), "_wpnonce", true, false);
		$form .=  "<input type='hidden' name='gateway' value='" . $this->gateway . "' id='subscription_gateway' />";
		$form .= '<input type="hidden" name="action" value="subscriptionsignup" />';
		$form .= '<input type="hidden" name="custom" value="' . $this->build_custom($user_id, $subscription->id, '0') .'">';

		$form .= '<input type="hidden" name="subscription" id="subscription_id" value="' . $subscription->id . '" />';
		$form .= '<input type="hidden" name="user" id="subscription_user_id" value="' . $user_id . '" />';

		$button = get_option( $this->gateway . "_payment_button", '' );
		if( empty($button) ) {
			$form .= '<input type="submit" class="button ' . apply_filters('membership_subscription_button_color', 'blue') . '" value="' . __('Sign Up','membership') . '" />';
		} else {
			$form .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="PayPal - The safer, easier way to pay online">';
		}

		$form .= '<input type="hidden" name="coupon_code" id="subscription_coupon_code" value="' . (!empty($coupon) ? $coupon->get_coupon_code() : '') . '" />';
		$form .= '</form>';

		return $form;

	}

	function display_subscribe_button($subscription, $pricing, $user_id, $sublevel = 1) {

		if(!empty($pricing) && (count($pricing) == 1 || count($pricing) == 2)) {
			// We have a pricing array that can be used for ARB so carry on processing
			if( count($pricing) == 1 && isset($pricing[$sublevel - 1]) && $pricing[$sublevel - 1]['amount'] < 1 ) {
				// Just a single free level
				echo $this->single_free_button($pricing, $subscription, $user_id, $sublevel);
			} else {
				// Slightly more complex so we have to do some building
				echo $this->build_subscribe_button($subscription, $pricing, $user_id, $sublevel);
			}
		}

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

	function old_display_upgrade_button($pricing, $subscription, $user_id, $fromsub_id = false) {
		$this->build_upgrade_button($pricing, $subscription, $user_id, $fromsub_id = false);
	}

	function display_cancel_button($subscription, $pricing, $user_id) {

		$subscription_id = get_user_meta( $user_id, 'membership_' . $this->gateway . '_subscription_' . $subscription->sub_id(), true );

		if(!empty($subscription_id)) {
			// We have an ARB subscription
			echo '<form class="unsubbutton" action="" method="post">';
			wp_nonce_field('cancel-sub_' . $subscription->sub_id());
			echo "<input type='hidden' name='action' value='unsubscribe' />";
			echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
			echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
			echo "<input type='hidden' name='user' value='" . $user_id . "' />";
			echo "<input type='submit' name='submit' value=' " . __('Unsubscribe', 'membership') . " ' class='button " . apply_filters('membership_subscription_button_color', 'blue') . "' />";
			echo "</form>";
		} else {
			// We don't seem to have a subscription - so just do a cancel
			echo '<form class="unsubbutton" action="" method="post">';
			wp_nonce_field('cancel-sub_' . $subscription->sub_id());
			echo "<input type='hidden' name='action' value='unsubscribe' />";
			echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
			echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
			echo "<input type='hidden' name='user' value='" . $user_id . "' />";
			echo "<input type='submit' name='submit' value=' " . __('Unsubscribe', 'membership') . " ' class='button " . apply_filters('membership_subscription_button_color', 'blue') . "' />";
			echo "</form>";
		}

	}

	function update() {
		if( isset($_POST['mode'])) {
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
		$output .=  "<option " . ($sel==12?' selected':'') . "  value='12'>12 - Dec</option>";

		return($output);
	}
}

// Based on a class from http://www.johnconde.net/blog/tutorial-integrate-the-authorize-net-arb-api-with-php/ by John Conde <johnny@johnconde.net>
if(!class_exists('M_Gateway_Worker_AuthorizeNet_ARB')) {
	class M_Gateway_Worker_AuthorizeNet_ARB
	{

	    const EXCEPTION_CURL = 10;

	    private $login;
	    private $transkey;
	    private $test;

	    private $params  = array();
	    private $success = false;
	    private $error   = true;

	    private $ch;
	    private $xml;
	    private $response;
	    private $resultCode;
	    private $code;
	    private $text;
	    private $subscrId;

	    function __construct($login, $transkey, $test = false )
	    {
	        $login    = trim($login);
	        $transkey = trim($transkey);
	        if (empty($login) || empty($transkey))
	        {
	            return false;
	        }

	        $this->login    = trim($login);
	        $this->transkey = trim($transkey);
	        $this->test     = $test;

	        $subdomain = ($this->test) ? 'apitest' : 'api';
	        $this->url = 'https://' . $subdomain . '.authorize.net/xml/v1/request.api';

	        $this->params['interval_length']  = 1;
	        $this->params['interval_unit']    = 'months';
	        $this->params['startDate']        = date("Y-m-d", strtotime("+ 1 month"));
	        $this->params['totalOccurrences'] = 9999;
	        $this->params['trialOccurrences'] = 0;
	        $this->params['trialAmount']      = 0.00;

	    }

	    function __toString()
	    {
	        if (!$this->params)
	        {
	            return (string) $this;
	        }
	        $output  = '';
	        $output .= '<table summary="Authnet Results" id="authnet">' . "\n";
	        $output .= '<tr>' . "\n\t\t" . '<th colspan="2"><b>Outgoing Parameters</b></th>' . "\n" . '</tr>' . "\n";
	        foreach ($this->params as $key => $value)
	        {
	            $output .= "\t" . '<tr>' . "\n\t\t" . '<td><b>' . $key . '</b></td>';
	            $output .= '<td>' . $value . '</td>' . "\n" . '</tr>' . "\n";
	        }
	        $output .= '</table>' . "\n";
	        if (!empty($this->xml))
	        {
	            $output .= 'XML: ';
	            $output .= htmlentities($this->xml);
	        }
	        return $output;
	    }

	    function process()
	    {

			$args = array();
			$args['user-agent'] = "Membership: http://premium.wpmudev.org/project/membeship | Authorize.net ARB Plugin/";
	        $args['body'] = $this->xml;
	        $args['sslverify'] = false;
			$args['headers'] = array( 'Content-Type' => 'text/xml' );

	        //use built in WP http class to work with most server setups
	        $response = wp_remote_post($this->url, $args);

			if (is_array($response) && isset($response['body'])) {
	          $this->response = $response['body'];
	        } else {
	          $this->response = "";
	          $this->error = true;
	          return false;
	        }

	        if($this->response)
	        {
	            $this->parseResults();
	            if ($this->resultCode === 'Ok')
	            {
	                $this->success = true;
	                $this->error   = false;
	            }
	            else
	            {
	                $this->success = false;
	                $this->error   = true;
	            }
	            return true;
	        } else {
				return false;
			}

	    }

	    function createAccount($echeck = false)
	    {
	        $this->xml = "<?xml version='1.0' encoding='utf-8'?>
	                      <ARBCreateSubscriptionRequest xmlns='AnetApi/xml/v1/schema/AnetApiSchema.xsd'>
	                          <merchantAuthentication>
	                              <name>" . $this->login . "</name>
	                              <transactionKey>" . $this->transkey . "</transactionKey>
	                          </merchantAuthentication>
	                          <refId>" . ( isset($this->params['refID'] ) ? $this->params['refID'] : '' ) ."</refId>
	                          <subscription>
	                              <name>". ( isset($this->params['subscrName'] ) ? $this->params['subscrName'] : '' ) ."</name>
	                              <paymentSchedule>
	                                  <interval>
	                                      <length>". ( isset($this->params['interval_length'] ) ? $this->params['interval_length'] : '' ) . "</length>
	                                      <unit>". ( isset($this->params['interval_unit'] ) ? $this->params['interval_unit'] : '' ) . "</unit>
	                                  </interval>
	                                  <startDate>" . ( isset($this->params['startDate'] ) ? $this->params['startDate'] : '' ) . "</startDate>
	                                  <totalOccurrences>". ( isset($this->params['totalOccurrences'] ) ? $this->params['totalOccurrences'] : '' ) . "</totalOccurrences>
	                                  <trialOccurrences>". ( isset($this->params['trialOccurrences'] ) ? $this->params['trialOccurrences'] : '' ) . "</trialOccurrences>
	                              </paymentSchedule>
	                              <amount>". ( isset($this->params['amount'] ) ? $this->params['amount'] : '' ) . "</amount>
	                              <trialAmount>" . ( isset($this->params['trialAmount'] ) ? $this->params['trialAmount'] : '' ) . "</trialAmount>
	                              <payment>";
	        if ($echeck)
	        {
	            $this->xml .= "
	                                  <bankAccount>
	                                      <accountType>". ( isset($this->params['accountType'] ) ? $this->params['accountType'] : '' ) . "</accountType>
	                                      <routingNumber>". ( isset($this->params['routingNumber'] ) ? $this->params['routingNumber'] : '' ) . "</routingNumber>
	                                      <accountNumber>". ( isset($this->params['accountNumber'] ) ? $this->params['accountNumber'] : '' ) . "</accountNumber>
	                                      <nameOnAccount>". ( isset($this->params['nameOnAccount'] ) ? $this->params['nameOnAccount'] : '' ) . "</nameOnAccount>
	                                      <bankName>". ( isset($this->params['bankName'] ) ? $this->params['bankName'] : '' ) . "</bankName>
	                                  </bankAccount>";
	        }
	        else
	        {
	            $this->xml .= "
	                                  <creditCard>
	                                      <cardNumber>" . ( isset($this->params['cardNumber'] ) ? $this->params['cardNumber'] : '' ) . "</cardNumber>
	                                      <expirationDate>" . ( isset($this->params['expirationDate'] ) ? $this->params['expirationDate'] : '' ) . "</expirationDate>
	                                      <cardCode>" . ( isset($this->params['cardCode'] ) ? $this->params['cardCode'] : '' ) . "</cardCode>
	                                  </creditCard>";
	        }

	        $this->xml .= "
	                              </payment>
	                              <order>
	                                  <invoiceNumber>" . ( isset($this->params['orderInvoiceNumber'] ) ? $this->params['orderInvoiceNumber'] : '' ) . "</invoiceNumber>
	                                  <description>" . ( isset($this->params['orderDescription'] ) ? $this->params['orderDescription'] : '' ) . "</description>
	                              </order>
	                              <customer>
	                                  <id>" . ( isset($this->params['customerId'] ) ? $this->params['customerId'] : '' ) . "</id>
	                                  <email>" . ( isset($this->params['customerEmail'] ) ? $this->params['customerEmail'] : '' ) . "</email>
	                                  <phoneNumber>" . ( isset($this->params['customerPhoneNumber'] ) ? $this->params['customerPhoneNumber'] : '' ) . "</phoneNumber>
	                                  <faxNumber>" . ( isset($this->params['customerFaxNumber'] ) ? $this->params['customerFaxNumber'] : '' ) . "</faxNumber>
	                              </customer>
	                              <billTo>
	                                  <firstName>". ( isset($this->params['firstName'] ) ? $this->params['firstName'] : '' ) . "</firstName>
	                                  <lastName>" . ( isset($this->params['lastName'] ) ? $this->params['lastName'] : '' ) . "</lastName>
	                                  <company>" . ( isset($this->params['company'] ) ? $this->params['company'] : '' ) . "</company>
	                                  <address>" . ( isset($this->params['address'] ) ? $this->params['address'] : '' ) . "</address>
	                                  <city>" . ( isset($this->params['city'] ) ? $this->params['city'] : '' ) . "</city>
	                                  <state>" . ( isset($this->params['state'] ) ? $this->params['state'] : '' ) . "</state>
	                                  <zip>" . ( isset($this->params['zip'] ) ? $this->params['zip'] : '' ) . "</zip>
	                              </billTo>
	                              <shipTo>
	                                  <firstName>". ( isset($this->params['shipFirstName'] ) ? $this->params['shipFirstName'] : '' ) . "</firstName>
	                                  <lastName>" . ( isset($this->params['shipLastName'] ) ? $this->params['shipLastName'] : '' ) . "</lastName>
	                                  <company>" . ( isset($this->params['shipCompany'] ) ? $this->params['shipCompany'] : '' ) . "</company>
	                                  <address>" . ( isset($this->params['shipAddress'] ) ? $this->params['shipAddress'] : '' ) . "</address>
	                                  <city>" . ( isset($this->params['shipCity'] ) ? $this->params['shipCity'] : '' ) . "</city>
	                                  <state>" . ( isset($this->params['shipState'] ) ? $this->params['shipState'] : '' ) . "</state>
	                                  <zip>" . ( isset($this->params['shipZip'] ) ? $this->params['shipZip'] : '' ) . "</zip>
	                              </shipTo>
	                          </subscription>
	                      </ARBCreateSubscriptionRequest>";

	        $this->process();
	    }

	    function updateAccount()
	    {
	        $this->xml = "<?xml version='1.0' encoding='utf-8'?>
	                      <ARBUpdateSubscriptionRequest xmlns='AnetApi/xml/v1/schema/AnetApiSchema.xsd'>
	                          <merchantAuthentication>
	                              <name>" . $this->login . "</name>
	                              <transactionKey>" . $this->transkey . "</transactionKey>
	                          </merchantAuthentication>
	                          <refId>" . ( isset($this->params['refID'] ) ? $this->params['refID'] : '' ) . "</refId>
	                          <subscriptionId>" . ( isset($this->params['subscrId'] ) ? $this->params['subscrId'] : '' ) . "</subscriptionId>
	                          <subscription>
	                              <name>". ( isset($this->params['subscrName'] ) ? $this->params['subscrName'] : '' ) . "</name>
	                              <amount>". ( isset($this->params['amount'] ) ? $this->params['amount'] : '' ) . "</amount>
	                              <trialAmount>" . ( isset($this->params['trialAmount'] ) ? $this->params['trialAmount'] : '' ) . "</trialAmount>
	                              <payment>
	                                  <creditCard>
	                                      <cardNumber>" . ( isset($this->params['cardNumber'] ) ? $this->params['cardNumber'] : '' ) . "</cardNumber>
	                                      <expirationDate>" . ( isset($this->params['expirationDate'] ) ? $this->params['expirationDate'] : '' ) . "</expirationDate>
	                                  </creditCard>
	                              </payment>
	                              <billTo>
	                                  <firstName>". ( isset($this->params['firstName'] ) ? $this->params['firstName'] : '' ) . "</firstName>
	                                  <lastName>" . ( isset($this->params['lastName'] ) ? $this->params['lastName'] : '' ) . "</lastName>
	                                  <address>" . ( isset($this->params['address'] ) ? $this->params['address'] : '' ) . "</address>
	                                  <city>" . ( isset($this->params['city'] ) ? $this->params['city'] : '' ) . "</city>
	                                  <state>" . ( isset($this->params['state'] ) ? $this->params['state'] : '' ) . "</state>
	                                  <zip>" . ( isset($this->params['zip'] ) ? $this->params['zip'] : '' ) . "</zip>
	                              </billTo>
	                          </subscription>
	                      </ARBUpdateSubscriptionRequest>";

	        $this->process();
	    }

	    function deleteAccount()
	    {
	        $this->xml = "<?xml version='1.0' encoding='utf-8'?>
	                      <ARBCancelSubscriptionRequest xmlns='AnetApi/xml/v1/schema/AnetApiSchema.xsd'>
	                          <merchantAuthentication>
	                              <name>" . $this->login . "</name>
	                              <transactionKey>" . $this->transkey . "</transactionKey>
	                          </merchantAuthentication>
	                          <refId>" . ( isset($this->params['refID'] ) ? $this->params['refID'] : '' ) . "</refId>
	                          <subscriptionId>" . ( isset($this->params['subscrId'] ) ? $this->params['subscrId'] : '' ) . "</subscriptionId>
	                      </ARBCancelSubscriptionRequest>";

	        $this->process();
	    }

	    function parseResults()
	    {
	        $response = str_replace('xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd"', '', $this->response);
	        $xml = new SimpleXMLElement($response);

	        $this->resultCode = (string) $xml->messages->resultCode;
	        $this->code       = (string) $xml->messages->message->code;
	        $this->text       = (string) $xml->messages->message->text;
	        $this->subscrId   = (string) $xml->subscriptionId;
	    }

	    function setParameter($field = '', $value = null)
	    {
	        $field = (is_string($field)) ? trim($field) : $field;
	        $value = (is_string($value)) ? trim($value) : $value;

	        $this->params[$field] = $value;
	    }

	    function isSuccessful()
	    {
	        return $this->success;
	    }

	    function isError()
	    {
	        return $this->error;
	    }

	    function getResponse()
	    {
	        return strip_tags($this->text);
	    }

	    function getResponseCode()
	    {
	        return $this->code;
	    }

	    function getSubscriberID()
	    {
	        return $this->subscrId;
	    }
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
      if ( isset($this->results[51]))
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

Membership_Gateway::register_gateway( 'authorizenetarb', 'M_authorizenetarb' );