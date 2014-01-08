<?php
/*
Addon Name: 2Checkout payment plugin
Author: Incsub
Author URI: http://premium.wpmudev.org
Gateway ID: twocheckout
*/

class twocheckout extends Membership_Gateway {

	var $gateway = 'twocheckout';
	var $title = '2Checkout';

	public function __construct() {
		parent::__construct();

		add_action( 'M_gateways_settings_' . $this->gateway, array( &$this, 'mysettings' ) );

		// If I want to override the transactions output - then I can use this action
		//add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));

		if ( $this->is_active() ) {
			// Subscription form gateway
			add_action( 'membership_purchase_button', array( &$this, 'display_subscribe_button' ), 1, 3 );

			// Payment return
			add_action( 'membership_handle_payment_return_' . $this->gateway, array( &$this, 'handle_2checkout_return' ) );

			add_filter( 'membership_gateway_exp_window', array( &$this, 'twocheckout_expiration_window' ) );

			add_action( 'membership_mark_for_expire', array( &$this, 'remove_recurring_line_item' ), null, 2 );
		}
	}

	function twocheckout_expiration_window($time) {
		//2Checkout will sometimes send notifications up to 24 hours after a subscription expires, so we need to adjust the window.
		return "+ 24 hours";
	}
	function remove_recurring_line_item($sub_id, $user_id) {

		$invoice_id = $this->db->get_var( $this->db->prepare( "SELECT transaction_paypal_ID FROM " . MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION . " WHERE transaction_subscription_ID = %s AND transaction_user_ID = %s AND transaction_gateway = %s LIMIT 1", $sub_id, $user_id, $this->gateway ) );

		if(empty($invoice_id) || !$invoice_id) {
			// Don't really know what else to do if we can't find the Invoice ID besides echo an error.
			echo '<div class="alert alert-error">'.__('Invoice ID could not be determined','membership').'</div>';
		} else {
			$args = array();

			$args['headers'] = array(
				'Authorization' => 'Basic '.base64_encode(get_option( $this->gateway . "_twocheckout_username" ).':'.get_option( $this->gateway . "_twocheckout_password" )),
				'Accept' => 'application/json',
			);

			$args['user-agent'] = "Membership/3.0.0: http://premium.wpmudev.org/project/membership | 2CO Payment plugin/1.1";
			$args['body'] = array('invoice_id' => $invoice_id);
			$args['sslverify'] = false;
			$args['timeout'] = 30;

			$endpoint = "https://www.2checkout.com/api/sales/detail_sale";

			$response = wp_remote_post($endpoint, $args);

			if (is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200 && wp_remote_retrieve_response_code($response) != 400)) {
				print '<div class="alert alert-error">'.__('There was a problem connecting to 2CO. Please try again.', 'membership').'</div>';
			} else {
				$response_obj = json_decode($response['body']);

				$lineitem_id = false;
				$product_id = false;

				$subscription = new M_Subscription($sub_id);
				if($subscription) {
					$product_id = $this->get_product_id($subscription, $subscription->get_pricingarray());
				}

				if ($response_obj->response_code == "OK" && $product_id != false) {
					//$product_id = $response_obj->assigned_product_id;
					foreach($response_obj->sale->invoices as $invoice) {
						foreach($invoice->lineitems as $lineitem) {
							if($lineitem->product_id == $product_id)
								$lineitem_id = $lineitem->lineitem_id;
						}
					}
				}


				if($lineitem_id == false) {
					print '<div class="alert alert-error">'.__('There was a problem finding your transaction in 2CO.  Please contact an administrator.', 'membership').'</div>';
				} else {
					$args = array();
					$args['headers'] = array(
						'Authorization' => 'Basic '.base64_encode(get_option( $this->gateway . "_twocheckout_username" ).':'.get_option( $this->gateway . "_twocheckout_password" )),
						'Accept' => 'application/json',
					);

					$args['user-agent'] = "Membership/3.0.0: http://premium.wpmudev.org/project/membership | 2CO Payment plugin/1.1";
					$args['body'] = array('lineitem_id' => $lineitem_id);
					$args['sslverify'] = false;
					$args['timeout'] = 30;

					$endpoint = "https://www.2checkout.com/api/sales/stop_lineitem_recurring";

					$stop_response = wp_remote_post($endpoint, $args);

					if (is_wp_error($stop_response) || (wp_remote_retrieve_response_code($stop_response) != 200 && wp_remote_retrieve_response_code($stop_response) != 400)) {
						print '<div class="alert alert-error">'.__('There was a problem connecting to 2CO. Please try again.', 'membership').'</div>';
					} else {
						$stop_response_obj = json_decode($stop_response['body']);

						if ($response_obj->response_code !== "OK" ) {
							print '<div class="alert alert-error">'.__('An unknown error prevented 2CO from canceling your payment. Please contact an administrator or cancel your payment from the 2Checkout website.', 'membership').'</div>';
						} else {
							print '<div class="alert alert-success">'.__('Your recurring payment has been canceled successfully', 'membership').'</div>';
						}
					}
				}

			}
		}

	}
	function mysettings() {
		global $M_options, $M_membership_url;

		?>
		<table class="form-table">
		<tbody>
		  <tr valign="top">
		  	<th scope="row" colspan="2"><div class="updated below-h2"><p><?php _e('In order for recurring payments to log properly you must enable your global instant notifications url pointing to '.home_url('paymentreturn/' . esc_attr($this->gateway)).' ', 'membership') ?></p></div></th>
		  </tr>
		  <tr valign="top">
		  	<th scope="row"><?php _e('2Checkout Username', 'membership') ?></th>
		  	<td><input type="text" name="twocheckout_username" value="<?php esc_attr_e(get_option( $this->gateway . "_twocheckout_username" )); ?>" />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('2Checkout Password', 'membership') ?></th>
		  <td><input type="password" name="twocheckout_password" value="" />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('2Checkout Seller ID', 'membership') ?></th>
		  <td><input type="text" name="twocheckout_sid" value="<?php esc_attr_e(get_option( $this->gateway . "_twocheckout_sid" )); ?>" />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('2Checkout Secret Word', 'membership') ?></th>
		  <td><input type="text" name="twocheckout_secret_word" value="<?php esc_attr_e(get_option( $this->gateway . "_twocheckout_secret_word" )); ?>" />
		  </td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('2Checkout Currency', 'membership') ?></th>
		  <td><?php
			if(empty($M_options['paymentcurrency'])) {
				$M_options['paymentcurrency'] = 'USD';
			}
			echo esc_html($M_options['paymentcurrency']); ?></td>
		  </tr>
		  <tr valign="top">
		  <th scope="row"><?php _e('2Checkout Mode', 'membership') ?></th>
		  <td><select name="twocheckout_status">
		  <option value="live" <?php if (get_option( $this->gateway . "_twocheckout_status" ) == 'live') echo 'selected="selected"'; ?>><?php _e('Live Site', 'membership') ?></option>
		  <option value="test" <?php if (get_option( $this->gateway . "_twocheckout_status" ) == 'test') echo 'selected="selected"'; ?>><?php _e('Test Mode', 'membership') ?></option>
		  </select>
		  <br />
		  </td>
		  </tr>

		<tr valign="top">
			<th scope="row"><?php _e('2Checkout Language', 'membership') ?></th>
			<td>
				<select name="twocheckout_lang">
					<?php $lang = get_option($this->gateway.'_twocheckout_lang'); ?>
					<option value="en" <?php selected($lang,'en'); ?>><?php _e('English','membership') ?></option>
					<option value="zh" <?php selected($lang,'zh'); ?>><?php _e('Chinese','membership') ?></option>
					<option value="da" <?php selected($lang,'da'); ?>><?php _e('Danish','membership') ?></option>
					<option value="fr" <?php selected($lang,'fr'); ?>><?php _e('French','membership') ?></option>
					<option value="gr" <?php selected($lang,'gr'); ?>><?php _e('German','membership') ?></option>
					<option value="el" <?php selected($lang,'el'); ?>><?php _e('Greek','membership') ?></option>
					<option value="it" <?php selected($lang,'it'); ?>><?php _e('Italian','membership') ?></option>
					<option value="jp" <?php selected($lang,'jp'); ?>><?php _e('Japanese','membership') ?></option>
					<option value="no" <?php selected($lang,'no'); ?>><?php _e('Norwegian','membership') ?></option>
					<option value="pt" <?php selected($lang,'pt'); ?>><?php _e('Portuguese','membership') ?></option>
					<option value="sl" <?php selected($lang,'sl'); ?>><?php _e('Slovenian','membership') ?></option>
					<option value="es_ib" <?php selected($lang,'es_ib'); ?>><?php _e('Spanish','membership') ?></option>
					<option value="es_la" <?php selected($lang,'es_la'); ?>><?php _e('Spanish (Latin America)','membership') ?></option>
					<option value="sv" <?php selected($lang,'sv'); ?>><?php _e('Swedish','membership') ?></option>
				</select>
				<br />
			</td>
		</tr>

		  <tr valign="top">
		  <th scope="row"><?php _e('Skip Order Review Page', 'membership') ?></th>
		  <td><select name="twocheckout_skip_landing">
		  <?php $skip = get_option($this->gateway.'_twocheckout_skip_landing'); ?>
		  <option value="1" <?php selected($skip,'1'); ?>><?php _e('Yes','membership') ?></option>
		  <option value="0" <?php selected($skip,'0'); ?>><?php _e('No','membership') ?></option>
		  </select>
		  <br />
		  </td>
		  </tr>

		  <tr valign="top">
		  <th scope="row"><?php _e('Checkout Style', 'membership') ?></th>
		  <td><select name="twocheckout_checkout_type">
		  <?php $checkout_type = get_option($this->gateway.'_twocheckout_checkout_type'); ?>
		  <option value="multi" <?php selected($checkout_type,'multi'); ?>><?php _e('Multi Page Checkout','membership') ?></option>
		  <option value="single" <?php selected($checkout_type,'single'); ?>><?php _e('Single Page Checkout','membership') ?></option>
		  </select>
		  <br />
		  </td>
		  </tr>

		  <tr valign="top">
		  <th scope="row"><?php _e('Subscription button', 'membership') ?></th>
		  <?php
		  	$button = get_option( $this->gateway . "_twocheckout_button", $M_membership_url.'membershipincludes/images/2co_logo_64.png');
		  ?>
		  <td><input type="text" name="twocheckout_button" value="<?php esc_attr_e($button); ?>" style='width: 40em;' />
		  <br />
		  </td>
		  </tr>
		</tbody>
		</table>
		<?php
	}


	function update() {

		if(isset($_POST['twocheckout_sid'])) {
			update_option( $this->gateway . "_twocheckout_username", $_POST[ 'twocheckout_username' ] );
			if (isset($_POST[ 'twocheckout_password' ]) && !empty($_POST[ 'twocheckout_password' ])) {
				update_option( $this->gateway . "_twocheckout_password", $_POST[ 'twocheckout_password' ] );
			}
			update_option( $this->gateway . "_twocheckout_sid", $_POST[ 'twocheckout_sid' ] );
			update_option( $this->gateway . "_twocheckout_secret_word", $_POST[ 'twocheckout_secret_word' ] );
			update_option( $this->gateway . "_currency", $_POST[ 'currency' ] );
			update_option( $this->gateway . "_twocheckout_status", $_POST[ 'twocheckout_status' ] );
			update_option( $this->gateway . "_twocheckout_button", $_POST[ 'twocheckout_button' ] );
			update_option( $this->gateway . "_twocheckout_lang", $_POST[ 'twocheckout_lang' ] );
			update_option( $this->gateway . "_twocheckout_skip_landing", $_POST[ 'twocheckout_skip_landing' ] );
			update_option( $this->gateway . "_twocheckout_checkout_type", $_POST[ 'twocheckout_checkout_type' ] );
		}

		// default action is to return true
		return true;

	}

	function build_custom($user_id, $sub_id, $amount) {
		$custom = '';

		$custom = time() . ':' . $user_id . ':' . $sub_id . ':';
		$key = md5('MEMBERSHIP' . $amount);
		$custom .= $key;
		return $custom;
	}

	/**
	 * Get product ID
	 *
	 * Search for the product, on failure to find it add it and return the
	 * new product id. If found return the product id.
	 *
	 * @param	Object	$subscription	Subscription
	 * @param	Array	$pricing	Pricing plan
	 * @return	Integer	$product_id
	 */
	function get_product_id($subscription, $pricing) {
		$product_id = 0;

		$args = array();

		$args['headers'] = array(
			'Authorization' => 'Basic '.base64_encode(get_option( $this->gateway . "_twocheckout_username" ).':'.get_option( $this->gateway . "_twocheckout_password" )),
			'Accept' => 'application/json',
		);

		$args['user-agent'] = "Membership/3.0.0: http://premium.wpmudev.org/project/membership | 2CO Payment plugin/1.1";
		$args['body'] = array('product_id' => $subscription->sub_id());
		$args['sslverify'] = false;
		$args['timeout'] = 10;

		$endpoint = "https://www.2checkout.com/api/products/";

		$response = wp_remote_post($endpoint."list_products", $args);

		if (is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200 && wp_remote_retrieve_response_code($response) != 400)) {
			print __('There was a problem connecting to 2CO. Please try again. REF:'.wp_remote_retrieve_response_code($response), 'membership');
		} else {
			$response_obj = json_decode($response['body']);

			$found = false;

			if ($response_obj->products && is_array($response_obj->products) && count($response_obj->products) > 0) {
				foreach ($response_obj->products as $product) {
					if ($subscription->sub_id() == $product->vendor_product_id) {
						$found = true;
						break;
					}
				}
			}

			if (!$found) {
				if (count($pricing) < 2) {
					$bargs = array(
						'name' => $subscription->sub_name(),
						'description' => $subscription->sub_description(),
						'vendor_product_id' => $subscription->sub_id(),
						'approved_url' => trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway),
						'tangible' => 0,
						'price' => number_format($pricing[0]['amount'], 2, '.', ''),
					);


					if (isset($pricing[0]['type']) && $pricing[0]['type'] == 'serial') {
						$bargs['recurring'] = 1;
						$bargs['recurrence'] = $this->convert_duration($pricing[0]['period'], $pricing[0]['unit']);
						$bargs['duration'] = 'Forever';
					}

					$body = array();

					foreach ($bargs as $bkey => $bval) {
						$body[$bkey] = $bval;
					}

					$args['body'] = $body;

					$response = wp_remote_post($endpoint."create_product", $args);

					if (is_wp_error($response) || (wp_remote_retrieve_response_code($response) != 200 && wp_remote_retrieve_response_code($response) != 400)) {
						print __('There was a problem connecting to 2CO. Please try again.', 'membership');
					} else {
						$response_obj = json_decode($response['body']);

						if ($response_obj->response_code == "OK") {
							$product_id = $response_obj->assigned_product_id;
						}
					}
				}
			} else {
				$product_id = $product->assigned_product_id;
			}
		}

		return $product_id;
	}

	function convert_duration($length, $unit) {
		if (($length%7) == 0 && $unit == 'd') {
			return intval($length/7) . " Week";
		}
		if ($unit == 'w') {
			return "{$length} Week";
		}
		if ($unit == 'm') {
			return "{$length} Month";
		}
		if ($unit == 'y') {
			return "{$length} Year";
		}
	}

	function single_sub_button($pricing, $subscription, $user_id, $repeat = false) {

		global $M_options, $M_membership_url;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		// Fetch product_id
		//$product_id = $this->get_product_id($subscription, $pricing);

		$form = '';

		if (get_option( $this->gateway . '_twocheckout_checkout_type') == 'multi') {
			$endpoint = 'https://www.2checkout.com/checkout/purchase';
		} else {
			$endpoint = 'https://www.2checkout.com/checkout/spurchase';
		}
		$form .= sprintf('<form action="%s" method="post">',$endpoint);

		if (get_option( $this->gateway . "_twocheckout_status" ) != 'live') {
			$form .= '<input type="hidden" name="demo" value="Y">';
		}

		$form .= '<input type="hidden" name="sid" value="' . esc_attr(get_option( $this->gateway . "_twocheckout_sid" )) . '">';
		$form .= '<input type="hidden" name="mode" value="2CO" />';
		$form .= '<input type="hidden" name="li_1_type" value="product" />';
		$form .= '<input type="hidden" name="li_1_name" value="'.$subscription->sub_name().'" />';
		$form .= '<input type="hidden" name="li_1_price" value="'.number_format($pricing[0]['amount'], 2, '.', '').'" />';
		$form .= '<input type="hidden" name="li_1_tangible" value="N" />';
		$form .= '<input type="hidden" name="li_1_product_id" value="'.$subscription->id.'" />';
		$form .= '<input type="hidden" name="li_1_description" value="'.$subscription->sub_description().'" />';
		if($repeat) {
			$form .= '<input type="hidden" name="li_1_recurrence" value="'.$this->convert_duration($pricing[0]['period'], $pricing[0]['unit']).'" />';
			$form .= '<input type="hidden" name="li_1_duration" value="Forever" />';
		}
		$form .= '<input type="hidden" name="merchant_order_id" value="'.$subscription->id.':'.$user_id.'" />';
		$form .= '<input type="hidden" name="skip_landing" value="'.esc_attr(get_option($this->gateway.'_twocheckout_skip_landing')).'" />';
		$form .= '<input type="hidden" name="lang" value="'.esc_attr(get_option( $this->gateway . "_twocheckout_lang" )).'" />';
		$form .= '<input type="hidden" name="user_id" value="'.$user_id.'">';

		$user_data = get_userdata($user_id);
		if($user_data)
			$form .= '<input type="hidden" name="email" value="'.$user_data->data->user_email.'" />';

		$form .= '<input type="hidden" name="currency" value="'.$M_options['paymentcurrency'].'">';
		$form .= '<input type="hidden" name="return_url" value="'.trailingslashit(get_option('home')) . 'paymentreturn/' . esc_attr($this->gateway).'" />';
		$form .= '<input type="hidden" name="return_method" value="1" />';
		//$form .= '<input type="hidden" name="return_url" value="'.M_get_registrationcompleted_permalink().'" />';

		$button = get_option( $this->gateway . "_twocheckout_button", $M_membership_url . 'membershipincludes/images/2co_logo_64.png' );

		$form .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="Pay via 2Checkout">';
		$form .= '</form>';

		return $form;

	}

	function complex_sub_button($pricing, $subscription, $user_id) {

		global $M_options, $M_membership_url;

		if(empty($M_options['paymentcurrency'])) {
			$M_options['paymentcurrency'] = 'USD';
		}

		// Fetch product_id
		$product_id = $this->get_product_id($subscription, $pricing);

		$form = '';

		$form .= '<form action="https://www.2checkout.com/checkout/spurchase" method="post">';

		if (get_option( $this->gateway . "_twocheckout_status" ) != 'live') {
			$form .= '<input type="hidden" name="demo" value="Y">';
		}

		$form .= '<input type="hidden" name="sid" value="' . esc_attr(get_option( $this->gateway . "_twocheckout_sid" )) . '">';
		$form .= '<input type="hidden" name="li_0_type" value="product" />';
		$form .= '<input type="hidden" name="li_0_name" value="'.$subscription->sub_name().'" />';
		$form .= '<input type="hidden" name="li_0_price" value="'.number_format($pricing[0]['amount'], 2, '.', '').'" />';
		$form .= '<input type="hidden" name="li_0_tangible" value="N" />';
		$form .= '<input type="hidden" name="li_0_product_id" value="'.$subscription->id.'" />';
		$form .= '<input type="hidden" name="li_0_description" value="'.$subscription->sub_description().'" />';
		//$form .= '<input type="hidden" name="li_0_recurrence" value="'.$this->convert_duration($pricing[0]['period'], $pricing[0]['unit']).'" />';
		//$form .= '<input type="hidden" name="li_0_duration" value="Forever" />';
		$form .= '<input type="hidden" name="merchant_order_id" value="'.$subscription->id.':'.$user_id.'" />';
		$form .= '<input type="hidden" name="skip_landing" value="'.esc_attr(get_option($this->gateway.'_twocheckout_skip_landing')).'" />';
		$form .= '<input type="hidden" name="lang" value="'.esc_attr(get_option( $this->gateway . "_twocheckout_lang" )).'" />';
		$form .= '<input type="hidden" name="user_id" value="'.$user_id.'">';
		$form .= '<input type="hidden" name="currency" value="'.$M_options['paymentcurrency'].'">';

		$button = get_option( $this->gateway . "_twocheckout_button", $M_membership_url . 'membershipincludes/images/2co_logo_64.png' );

		$form .= '<input type="image" name="submit" border="0" src="' . $button . '" alt="Pay via 2Checkout">';
		$form .= '</form>';

		return $form;

	}

	function build_subscribe_button($subscription, $pricing, $user_id, $sublevel) {

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
						return $this->single_sub_button($pricing, $subscription, $user_id, false);
					} else {
						// simple subscription
						return $this->single_sub_button($pricing, $subscription, $user_id, true);
					}
				} else {
					// something much more complex
					// Complex buttons currently not supported by 2CO
					//return $this->complex_sub_button($pricing, $subscription, $user_id);

				}
			} else {
				return $this->single_free_button($pricing, $subscription, $user_id);
			}

		}

	}

	function display_subscribe_button($subscription, $pricing, $user_id, $sublevel = 1) {

		if(isset($pricing[$sublevel - 1]) && $pricing[$sublevel - 1]['amount'] < 1)
			echo $this->single_free_button($pricing, $subscription, $user_id, $sublevel);
		else
			echo $this->build_subscribe_button($subscription, $pricing, $user_id, $sublevel);

	}

	function display_cancel_button($subscription, $pricing, $user_id) {

		echo '<form class="unsubbutton" action="' . M_get_subscription_permalink() . '" method="post">';
		wp_nonce_field('cancel-sub_' . $subscription->sub_id());
		echo "<input type='hidden' name='action' value='unsubscribe' />";
		echo "<input type='hidden' name='gateway' value='" . $this->gateway . "' />";
		echo "<input type='hidden' name='subscription' value='" . $subscription->sub_id() . "' />";
		echo "<input type='hidden' name='user' value='" . $user_id . "' />";
		echo "<input type='submit' name='submit' value=' " . __('Unsubscribe', 'membership') . " ' class='button white' />";
		echo "</form>";
	}

	// Return stuff
	function handle_2checkout_return() {

		// Return handling code
		$timestamp = time();
		if (isset($_REQUEST['key'])) {
			$total = $_REQUEST['total'];

			$sub_id = false;
			$user_id = false;

			list($sub_id, $user_id) = explode(':', $_REQUEST['merchant_order_id']);

			if (esc_attr(get_option( $this->gateway . "_twocheckout_status" )) == 'test') {
				$hash = strtoupper(md5(esc_attr(get_option( $this->gateway . "_twocheckout_secret_word" )) . esc_attr(get_option( $this->gateway . "_twocheckout_sid" )) . 1 . $total));
			} else {
				$hash = strtoupper(md5(esc_attr(get_option( $this->gateway . "_twocheckout_secret_word" )) . esc_attr(get_option( $this->gateway . "_twocheckout_sid" )) . $_REQUEST['order_number'] . $total));
			}

			if ($sub_id && $user_id && $_REQUEST['key'] == $hash && $_REQUEST['credit_card_processed'] == 'Y') {

				$this->_record_transaction($user_id, $sub_id, $_REQUEST['total'], $_REQUEST['currency'], $timestamp, $_REQUEST['order_number'], 'Processed', '');

				// Added for affiliate system link
				do_action('membership_payment_processed', $user_id, $sub_id, $_REQUEST['total'], $_REQUEST['currency'], $_REQUEST['order_number']);

				$member = Membership_Plugin::factory()->get_member($user_id);
				if($member) {
					$member->create_subscription($sub_id, $this->gateway);

					membership_debug_log( sprintf(__('Order complete for user %d on subscription %d.', 'membership'), $user_id, $sub_id ) );
				}

				do_action('membership_payment_subscr_signup', $user_id, $sub_id);
				wp_redirect(get_option('home'));
				exit();
			}
		} else if (isset($_REQUEST['message_type'])) {
			$md5_hash = strtoupper(md5("{$_REQUEST['sale_id']}".esc_attr(get_option( $this->gateway . "_twocheckout_sid" ))."{$_REQUEST['invoice_id']}".esc_attr(get_option( $this->gateway . "_twocheckout_secret_word" ))));

			$sub_id = false;
			$user_id = false;

			//$product_id = $_REQUEST['item_id_1'];
			list($sub_id, $user_id) = explode(':', $_REQUEST['vendor_order_id']);

			if ($md5_hash == $_REQUEST['md5_hash']) {
				switch ($_REQUEST['message_type']) {
					case 'RECURRING_INSTALLMENT_SUCCESS':
						if ( !$this->_check_duplicate_transaction( $user_id, $sub_id, $timestamp, $_POST['invoice_id'] ) ) {
							$this->_record_transaction( $user_id, $sub_id, $_REQUEST['item_rec_list_amount_1'], $_REQUEST['list_currency'], $timestamp, $_POST['invoice_id'], 'Processed', '' );
							$member = Membership_Plugin::factory()->get_member( $user_id );
							if ( $member ) {
								remove_action( 'membership_expire_subscription', 'membership_record_user_expire', 10, 2 );
								remove_action( 'membership_add_subscription', 'membership_record_user_subscribe', 10, 4 );
								$member->expire_subscription( $sub_id );
								$member->create_subscription( $sub_id, $this->gateway );

								membership_debug_log( sprintf( __( 'Recurring installment for user %d on subscription %d.', 'membership' ), $user_id, $sub_id ) );
							}
							// Added for affiliate system link
							do_action( 'membership_payment_processed', $user_id, $sub_id, $_REQUEST['item_rec_list_amount_1'], $_REQUEST['list_currency'], $_POST['invoice_id'] );
						}
						break;
					case 'FRAUD_STATUS_CHANGED':
					case 'INVOICE_STATUS_CHANGED':
						// We don't really want to do anything here without pulling out more information
						break;
					case 'ORDER_CREATED':
					case 'RECURRING_RESTARTED':
						$this->_record_transaction($user_id, $sub_id, $_REQUEST['item_rec_list_amount_1'], $_REQUEST['list_currency'], $timestamp, $_POST['invoice_id'], 'Processed', '');
						$member = Membership_Plugin::factory()->get_member($user_id);
						if($member) {
							$member->create_subscription($sub_id, $this->gateway);

							membership_debug_log( sprintf(__('Recurring restarted for user %d on subscription %d.', 'membership'), $user_id, $sub_id ) );
						}
						break;
					case 'RECURRING_STOPPED':
					case 'RECURRING_COMPLETE':
					case 'RECURRING_INSTALLMENT_FAILED':
					default:
						$member = Membership_Plugin::factory()->get_member($user_id);
						if($member) {
							$member->mark_for_expire($sub_id);

							membership_debug_log( sprintf(__('Recurring failed for user %d on subscription %d.', 'membership'), $user_id, $sub_id ) );
						}
						do_action('membership_payment_subscr_cancel', $user_id, $sub_id);
						break;
				}
			} else {
				// MD5 Hash Failed
				header('Status: 403 Forbidden');
				echo 'Error: Unexpected Security Value. Verification is not possible.';

				membership_debug_log( 'Error: Unexpected Security Value. Verification is not possible.' );

				exit();
			}

			echo  "OK";

			membership_debug_log( 'OK' );

			exit();
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('Status: 400 Bad Request');
			echo 'Error: Missing POST variables. Identification is not possible.';

			membership_debug_log( 'Error: Missing POST variables. Identification is not possible.' );

			exit();
		}
	}

}

Membership_Gateway::register_gateway( 'twocheckout', 'twocheckout' );