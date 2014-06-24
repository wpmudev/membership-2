<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License, version 2, as  
 * published by the Free Software Foundation.                           
 *
 * This program is distributed in the hope that it will be useful,      
 * but WITHOUT ANY WARRANTY; without even the implied warranty of       
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        
 * GNU General Public License for more details.                         
 *
 * You should have received a copy of the GNU General Public License    
 * along with this program; if not, write to the Free Software          
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/

class MS_Model_Gateway_Authorize extends MS_Model_Gateway {
	
	const TRANSACTION_TYPE_AUTHORIZED        = 1;
	const TRANSACTION_TYPE_CAPTURED          = 2;
	const TRANSACTION_TYPE_RECURRING         = 3;
	const TRANSACTION_TYPE_VOIDED            = 4;
	const TRANSACTION_TYPE_CANCELED_RECURING = 5;
	const TRANSACTION_TYPE_CIM_AUTHORIZED    = 6;
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id = 'authorize';
	
	protected $name = 'Authorize.net Gateway';
	
	protected $description = 'Authorize.net gateway integration';
	
	protected $is_single = false;
	
	protected $active = false;
	
	protected $api_login_id;
	
	protected $api_transaction_key;
	
	protected $log_file;
	
	protected $mode;
	
	protected $cim_profile_id;
	
	protected $cim_payment_profile_id;
	
	protected $transactions;
	
	protected $payment_result;
	
	public function purchase_button( $membership, $member, $move_from_id = 0, $coupon_id = 0 ) {
		$fields = array(
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->id,
				),
				'membership_id' => array(
						'id' => 'membership_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $membership->id,
				),
				'move_from_id' => array(
						'id' => 'move_from_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $move_from_id,
				),
				'coupon_id' => array(
						'id' => 'coupon_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $coupon_id,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'extra_form',
				),
		);
		if( strpos( $this->payment_url, 'http' ) === 0 ) {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
					'value' =>  $this->pay_button_url,
			);
		}
		else {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' =>  $this->pay_button_url ? $this->pay_button_url : __( 'Signup', MS_TEXT_DOMAIN ),
			);
		}
		?>
			<form action="" method="post">
				<?php wp_nonce_field( "{$this->id}_{$membership->id}" ); ?>
				<?php MS_Helper_Html::html_input( $fields['gateway'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['membership_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['move_from_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['coupon_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['extra_form'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['submit'] ); ?>
			</form>
		<?php 
	}
	
	/**
	 * Loads Authorize.net lib.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function load_authorize_lib(){
		require_once MS_Plugin::instance()->dir . '/lib/authorize.net/autoload.php';
	} 
	
	/**
	 * Returns the instance of AuthorizeNetCIM class.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @staticvar AuthorizeNetCIM $cim The instance of AuthorizeNetCIM class.
	 * @return AuthorizeNetCIM The instance of AuthorizeNetCIM class.
	 */
	protected function get_cim() {
		static $cim = null;
	
		if ( !is_null( $cim ) ) {
			return $cim;
		}
	
		$this->load_authorize_lib();
	
		$cim = new AuthorizeNetCIM( $this->api_login_id, $this->api_transaction_key );
		$cim->setSandbox( $mode != self::MODE_LIVE );
		if ( $this->log_file ) {
			$cim->setLogFile( $this->log_file );
		}
	
		return $cim;
	}
	
	public function get_cim_profile_id( $user_id ) {
		return get_user_meta( $user_id, 'authorize_cim_id', true );
	}
	/**
	 * Get customer information manager profile.
	 * 
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param int $user_id The user Id.
	 * @param int $membership_id The membership Id.
	 */
	public function get_cim_profile( $user_id ) {

		$cim_profiles = array();
		$cim_profile_id = $this->get_cim_profile_id( $user_id );
		$membership = MS_Model_Membership::load( $membership_id );
		
		if( $cim_profile_id && $membership->id > 0 && MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING != $membership->membership_type ) {
			$response = $this->get_cim()->getCustomerProfile( $cim_profile_id );
			if ( $response->isOk() ) {
				$cim_profiles = json_decode( json_encode( $response->xml->profile ), true );
				if( is_array( $cim_profiles ) && !empty( $cim_profiles['paymentProfiles'] ) && is_array( $cim_profiles['paymentProfiles'] ) ) {
					$cim_profiles = $cim_profiles['paymentProfiles'];
				}
			}
		}
		return $cim_profiles;
	}
	
	/**
	 * Creates Authorize.net CIM profile for current user.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @return int Customer profile ID on success, otherwise FALSE.
	 */
	protected function create_cim_profile( $member ) {

		$this->load_authorize_lib();
	
		$customer = new AuthorizeNetCustomer();
		$customer->merchantCustomerId = $member->id;
		$customer->email = $member->email;
		$customer->paymentProfiles[] = $this->create_cim_payment_profile();
	
		$response = $this->get_cim()->createCustomerProfile( $customer );
		if ( $response->isError() ) {
			return false;
		}
	
		$profile_id = $response->getCustomerProfileId();
		update_user_meta( $member->id, 'authorize_cim_id', $profile_id );
	
		return $profile_id;
	}
	
	/**
	 * Updates CIM profile by adding a new credit card.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	protected function update_cim_profile() {
		$payment = $this->create_cim_payment_profile();
		$response = $this->get_cim()->createCustomerPaymentProfile( $this->_cim_profile_id, $payment );
		if ( $response->isError() ) {
			return false;
		}
	
		return true;
	}
	
	/**
	 * Creates CIM payment profile and fills it with posted credit card data.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @return AuthorizeNetPaymentProfile The instance of AuthorizeNetPaymentProfile class.
	 */
	protected function create_cim_payment_profile() {

		$this->load_authorize_lib();
	
		$payment = new AuthorizeNetPaymentProfile();
	
		// billing information
		$payment->billTo->firstName = substr( trim( filter_input( INPUT_POST, 'first_name' ) ), 0, 50 );
		$payment->billTo->lastName = substr( trim( filter_input( INPUT_POST, 'last_name' ) ), 0, 50 );
		$payment->billTo->company = substr( trim( filter_input( INPUT_POST, 'company' ) ), 0, 50 );
		$payment->billTo->address = substr( trim( filter_input( INPUT_POST, 'address' ) ), 0, 60 );
		$payment->billTo->city = substr( trim( filter_input( INPUT_POST, 'city' ) ), 0, 40 );
		$payment->billTo->state = substr( trim( filter_input( INPUT_POST, 'state' ) ), 0, 40 );
		$payment->billTo->zip = substr( trim( filter_input( INPUT_POST, 'zip' ) ), 0, 20 );
		$payment->billTo->country = substr( trim( filter_input( INPUT_POST, 'country' ) ), 0, 60 );
		$payment->billTo->phoneNumber = substr( trim( filter_input( INPUT_POST, 'phone' ) ), 0, 25 );
	
		// card information
		$payment->payment->creditCard->cardNumber = preg_replace( '/\D/', '', filter_input( INPUT_POST, 'card_num' ) );
		$payment->payment->creditCard->cardCode = trim( filter_input( INPUT_POST, 'card_code' ) );
		$payment->payment->creditCard->expirationDate = sprintf( '%04d-%02d', filter_input( INPUT_POST, 'exp_year', FILTER_VALIDATE_INT ), substr( filter_input( INPUT_POST, 'exp_month', FILTER_VALIDATE_INT ), -2 ) );
	
		return $payment;
	}
	
	/**
	 * Processes purchase action.
	 *
	 * @since 4.0
	 *
	 * @access public
	 */
	public function process_purchase( $member, $membership, $move_from_id, $coupon_id ) {
		if ( !is_ssl() ) {
			wp_die( __( 'You must use HTTPS in order to do this', 'membership' ) );
			exit;
		}
	
		// fetch CIM user and payment profiles info
		// pay attention that CIM can't handle recurring transaction, so we need
		// to use standard ARB aproach and full cards details
		if( MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING != $membership->membership_type ) {
			if( $this->cim_payment_profile_id = trim( filter_input( INPUT_POST, 'profile' ) ) ) {
				if( $this->cim_profile_id = $this->get_cim_profile_id( $user_id ) ) {
					$response = $this->get_cim()->getCustomerPaymentProfile( $this->cim_profile_id, $this->cim_payment_profile_id );
					if ( $response->isError() ) {
						$this->cim_payment_profile_id = false;
					}				
				}
			}
		}
	
		switch( $membership->membership_type ) {
			case MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING:
				$this->transactions[] = $this->process_serial_purchase( $membership, $coupon_id, 9999 );
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_FINITE:
			case MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE:
			case MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT:
				if( $membership->trial_period_enabled && ! empty( $membership->trial_price ) && ! empty( $membership->trial_period['period_unit'] ) ) {
					$this->transactions[] = $this->process_serial_purchase( $membership, $coupon_id, 2 );
				}
				else {
					$this->transactions[] = $this->process_non_serial_purchase( $membership->price );
				}
				break;
				
		}
		// process payments
		$first_payment = false;
		$started = new DateTime();
		$payment_result = array( 'status' => '', 'errors' => array() );
		$this->_transactions = array();
		for ( $i = 0, $count = count( $pricing ); $i < $count; $i++ ) {
				if ( $first_payment === false && $pricing[$i]['amount'] > 0 ) {
				$first_payment = $pricing[$i]['amount'];
		}
	
		switch ( $pricing[$i]['type'] ) {
		case 'finite':
		//Call ARB with only one recurrency for each subscription level.
		$this->_transactions[] = $this->_process_serial_purchase( $pricing[$i], $started, 1 );
		$interval = self::_get_period_interval_in_date_format( $pricing[$i]['unit'] );
		$started->modify( sprintf( '+%d %s', $pricing[$i]['period'], $interval ) );
		break;
		case 'indefinite':
				$this->_transactions[] = $this->_process_nonserial_purchase( $pricing[$i], $started );
				break 2;
				case 'serial':
				//Call ARB with no end date (an ongoing subscription).
				$this->_transactions[] = $this->_process_serial_purchase( $pricing[$i], $started, 9999 );
				break 2;
		}
	
		if ( $this->_payment_result['status'] == 'error' ) {
			$this->rollback_transactions();
			break;
		}
	}
	
	if ( $this->_payment_result['status'] == 'success' ) {
		// create member subscription
		if ( $this->_member->has_subscription() ) {
			$from_sub_id = filter_input( INPUT_POST, 'from_subscription', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
				if ( $this->_member->on_sub( $from_sub_id ) ) {
					$this->_member->drop_subscription( $from_sub_id );
			}
	
			if ( $this->_member->on_sub( $sub_id ) ) {
			$this->_member->drop_subscription( $sub_id );
			}
			}
			$this->_member->create_subscription( $sub_id, $this->gateway );
	
			// create CIM profile it is not exists, otherwise update it if new card was added
			$this->_cim_profile_id = get_user_meta( $this->_member->ID, 'authorize_cim_id', true );
			if ( !$this->_cim_profile_id ) {
			$this->_create_cim_profile();
		} 
		elseif ( !$has_serial && empty( $this->_cim_payment_profile_id ) ) {
			$this->update_cim_profile();
		}
	
		// process transactions
		$this->commit_transactions();
	
		if ( $first_payment ) {
		do_action( 'membership_authorizenet_payment_processed', $this->_member->ID, $sub_id );
		do_action( 'membership_payment_processed', $this->_member->ID, $sub_id, $first_payment, MS_Plugin::instance()->settings->currency, $this->_transactions[0]['transaction'] );
		}
	
		// process response message and redirect
		if ( self::is_popup() && !empty( $M_options['registrationcompleted_message'] ) ) {
		$html = '<div class="header" style="width: 750px"><h1>';
		$html .= sprintf( __( 'Sign up for %s completed', 'membership' ), $this->_subscription->sub_name() );
				$html .= '</h1></div><div class="fullwidth">';
				$html .= stripslashes( wpautop( $M_options['registrationcompleted_message'] ) );
					$html .= '</div>';
	
				$this->_payment_result['redirect'] = 'no';
					$this->_payment_result['message'] = $html;
		} else {
		$this->_payment_result['message'] = '';
		$this->_payment_result['redirect'] = strpos( home_url(), 'https://' ) === 0
		? str_replace( 'https:', 'http:', M_get_registrationcompleted_permalink() )
					: M_get_registrationcompleted_permalink();
		}
		}
	
		echo json_encode( $this->_payment_result );
		exit;
		}
		
	/**
	 * Processes non recurrent purchase.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param float $price The current price information.
	 * @param DateTime $date The date when to process this transaction.
	 * @return array Returns transaction information on success, otherwise NULL.
	 */
	protected function process_non_serial_purchase( $price, $date ) {
		if ( $price == 0 ) {
			$payment_result['status'] = 'success';
			return null;
		}
	
		$success = $transaction_id = $method = $error = false;
		$amount = number_format( $price, 2, '.', '' );
		if ( ! empty( $this->cim_profile_id ) && ! empty( $this->cim_payment_profile_id ) ) {
			$transaction = $this->get_cim_transaction();
			$transaction->amount = $amount;
	
			$response = $this->get_cim()->createCustomerProfileTransaction( 'AuthOnly', $transaction );
			if ( $response->isOk() ) {
				$success = true;
				$method = 'cim';
				$transaction_id = $response->getTransactionResponse()->transaction_id;
			} 
			else {
				$error = $response->getMessageText();
			}
		} 
		else {
			$response = $this->get_aim()->authorizeOnly( $amount );
			if ( $response->approved ) {
				$success = true;
				$transaction_id = $response->transaction_id;
				$method = 'aim';
			} 
			elseif ( $response->error ) {
				$error = $response->response_reason_text;
			}
		}
	
		if ( $success ) {
			$this->payment_result['status'] = 'success';
			return array(
					'method'      => $method,
					'transaction' => $transaction_id,
					'date'        => $date->format( 'U' ),
					'amount'      => $amount,
			);
		}
		else {
			$this->payment_result['status'] = 'error';
			$this->payment_result['errors'][] = $error;
		}	
		return null;
	}
	
	/**
	 * Processes serial level purchase.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @global array $M_options The array of plugin options.
	 * @param array $price The array with current price information.
	 * @param DateTime $date The date when to process this transaction.
	 * @param int $total_occurencies The number of billing occurrences or payments for the subscription.
	 * 		To submit a subscription with no end date, this field must be submitted with a value of 9999
	 * @return array Returns transaction information on success, otherwise NULL.
	 */
	protected function process_serial_purchase( $price, $date, $total_occurencies) {
		if ( $price['amount'] == 0 ) {
			$this->payment_result['status'] = 'success';
			return null;
		}
	
		// initialize AIM transaction to check CC
		if ( count( array_filter( $this->transactions ) ) == 0 ) {
			$transaction = $this->process_non_serial_purchase( $price, $date );
			if ( is_null( $transaction ) ) {
				return null;
			}
	
			$this->transactions[] = $transaction;
	
			$date->modify( sprintf( '+%d %s', $price['period'], $interval ) );
		}
	
		$amount = number_format( $price['amount'], 2, '.', '' );
	
		$level = Membership_Plugin::factory()->get_level( $price['level_id'] );
		$name = substr( sprintf(
				'%s / %s',
				$level->level_title(),
				$this->_subscription->sub_name()
		), 0, 50 );
	
		$subscription = $this->get_arb_subscription( $price );
		$subscription->name = $name;
		$subscription->amount = $amount;
		$subscription->startDate = $date->format( 'Y-m-d' );
		$subscription->totalOccurrences = $total_occurencies;
	
		if ( isset( $price['origin'] ) ) {
			// coupon is applied, so we need to add trial period
			$subscription->amount = $amount = number_format( $price['origin'], 2, '.', '' );
			$subscription->trialAmount = number_format( $price['amount'], 2, '.', '' );
			$subscription->trialOccurrences = 1;
			$subscription->totalOccurrences = $subscription->totalOccurrences + $subscription->trialOccurrences;
		}
	
		$arb = $this->get_arb();
		$response = $arb->createSubscription( $subscription );
		if ( $response->isOk() ) {
			$this->payment_result['status'] = 'success';
			return array(
					'method'      => 'arb',
					'transaction' => $response->getSubscriptionId(),
					'date'        => $date->format( 'U' ),
					'amount'      => $amount,
			);
		}
	
		$this->payment_result['status'] = 'error';
		$this->payment_result['errors'][] = $response->getMessageText();
	
		return null;
	}
	
	/**
	 * Processes transactions.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function commit_transactions() {
	
		$move_from_id = 0;
		$coupon_id = 0;
		$notes = $this->mode == self::MODE_SANDBOX ? 'Sandbox' : '';
	
		// process each transaction information and save it to CIM
		foreach ( $this->transactions as $index => $info ) {
			if ( is_null( $info ) ) {
				continue;
			}
	
			$status = 0;
			if ( $info['method'] == 'aim' ) {
				$status = self::TRANSACTION_TYPE_AUTHORIZED;
	
				// capture first transaction
				if ( $index == 0 ) {
					$this->get_aim( true, false )->priorAuthCapture( $info['transaction'] );
					$status = self::TRANSACTION_TYPE_CAPTURED;
				}
			} 
			elseif ( $info['method'] == 'cim' ) {
				$status = self::TRANSACTION_TYPE_CIM_AUTHORIZED;
	
				// capture first transaction
				if ( $index == 0 ) {
					$transaction = $this->get_cim_transaction();
					$transaction->transId = $info['transaction'];
					$transaction->amount = $info['amount'];
					$this->get_cim()->createCustomerProfileTransaction( 'PriorAuthCapture', $transaction );
					$status = self::TRANSACTION_TYPE_CAPTURED;
				}
			} 
			elseif ( $info['method'] == 'arb' ) {
				$status = self::TRANSACTION_TYPE_RECURRING;
			}
	
			if ( $status ) {
				$this->add_transaction( $membership, $member, $status, $move_from_id, $coupon_id, $transaction->transId, $notes );
			}
		}
	}
	
	/**
	 * Rollbacks transactions all transactions and subscriptions.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function rollback_transactions() {
		foreach ( $this->transactions as $info ) {
			if ( $info['method'] == 'aim' ) {
				$this->get_aim()->void( $info['transaction'] );
			} 
			elseif ( $info['method'] == 'arb' ) {
				$this->get_arb()->cancelSubscription( $info['transaction'] );
			}
		}
	}
	
	/**
	 * Initializes and returns Authorize.net CIM transaction object.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @return AuthorizeNetTransaction The instance of AuthorizeNetTransaction class.
	 */
	protected function get_cim_transaction() {
		$this->load_authorize_lib();
	
		$transaction = new AuthorizeNetTransaction();
		$transaction->customerProfileId = $this->cim_profile_id;
		$transaction->customerPaymentProfileId = $this->cim_payment_profile_id;
	
		return $transaction;
	}
	
	/**
	 * Initializes and returns AuthorizeNetAIM object.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @staticvar AuthorizeNetAIM $aim The instance of AuthorizeNetAIM class.
	 * @param boolean $refresh Determines whether we need to refresh $aim object or not.
	 * @param boolean $pre_fill Determines whether we need to pre fill AIM object with posted data or not.
	 * @return AuthorizeNetAIM The instance of AuthorizeNetAIM class.
	 */
	protected function get_aim( $refresh = false, $pre_fill = true ) {
		static $aim = null;
	
		if ( ! $refresh && !is_null( $aim ) ) {
			return $aim;
		}
	
		$this->load_authorize_lib();
	
		// create new AIM
		$aim = new AuthorizeNetAIM( $this->api_login_id, $this->api_transaction_key );
		$aim->setSandbox( $this->mode != self::MODE_LIVE );
		if ( $this->log_file ) {
			$aim->setLogFile( $this->log_file );
		}
	
		if ( $pre_fill ) {
			// card information
			$aim->card_num = preg_replace( '/\D/', '', filter_input( INPUT_POST, 'card_num' ) );
			$aim->card_code = trim( filter_input( INPUT_POST, 'card_code' ) );
			$aim->exp_date = sprintf( '%02d/%02d', filter_input( INPUT_POST, 'exp_month', FILTER_VALIDATE_INT ), substr( filter_input( INPUT_POST, 'exp_year', FILTER_VALIDATE_INT ), -2 ) );
			$aim->duplicate_window = MINUTE_IN_SECONDS;
	
			// customer information
			$aim->cust_id = $member->id;
			$aim->customer_ip = self::get_remote_ip();
			$aim->email = $member->email;
	
			// billing information
			$aim->first_name = substr( trim( filter_input( INPUT_POST, 'first_name' ) ), 0, 50 );
			$aim->last_name = substr( trim( filter_input( INPUT_POST, 'last_name' ) ), 0, 50 );
			$aim->company = substr( trim( filter_input( INPUT_POST, 'company' ) ), 0, 50 );
			$aim->address = substr( trim( filter_input( INPUT_POST, 'address' ) ), 0, 60 );
			$aim->city = substr( trim( filter_input( INPUT_POST, 'city' ) ), 0, 40 );
			$aim->state = substr( trim( filter_input( INPUT_POST, 'state' ) ), 0, 40 );
			$aim->zip = substr( trim( filter_input( INPUT_POST, 'zip' ) ), 0, 20 );
			$aim->country = substr( trim( filter_input( INPUT_POST, 'country' ) ), 0, 60 );
			$aim->phone = substr( trim( filter_input( INPUT_POST, 'phone' ) ), 0, 25 );
		}
	
		return $aim;
	}
	
	/**
	 * Initializes and returns AuthorizeNetARB object.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @return AuthorizeNetARB The instance of AuthorizeNetARB class.
	 */
	protected function get_arb() {
	
		$this->load_authorize_lib();
		
		// create new AIM
		$arb = new AuthorizeNetARB( $this->api_login_id, $this->api_transaction_key );
		$arb->setSandbox( $this->mode != self::MODE_LIVE );
		if ( $this->log_file ) {
			$arb->setLogFile( $this->log_file );
		}
			
		return $arb;
	}
	
	/**
	 * Initializes and returns AuthorizeNet_Subscription object.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @return AuthorizeNet_Subscription The instance of AuthorizeNet_Subscription class.
	 */
	protected function get_arb_subscription( $user_id, $membership_id ) {

		$this->load_authorize_lib();
		
		$member = MS_Model_Member::load( $user_id );
		$membership = MS_Model_Membership::load( $membership_id );
		
		// create new subscription
		$subscription = new AuthorizeNet_Subscription();
		$subscription->customerId = $member->id;
		$subscription->customerEmail = $member->email;
		$subscription->customerPhoneNumber = substr( trim( filter_input( INPUT_POST, 'phone' ) ), 0, 25 );
	
		switch( $membership->membership_type ) {
			case MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING:
				/** only days or months period types are allowed */
				if( MS_Helper_Period::PERIOD_TYPE_YEARS == $membership->pay_cycle_period['type'] ) {
					$subscription->intervalLength = $membership->pay_cycle_period['unit'] * 12;
					$subscription->intervalUnit = MS_Helper_Period::PERIOD_TYPE_MONTHS;
				}
				else {
					$subscription->intervalLength = $membership->pay_cycle_period['unit'];
					$subscription->intervalUnit = $membership->pay_cycle_period['type'];
				}
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_FINITE:
			case MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE:
			case MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT:
			default:
				MS_Helper_Debug::log( "Membership type not allowed to have recurring payments, membership_id: $membership->id, type: $membership->membership_type");
				break;
		
		}
		// card information
		$subscription->creditCardCardNumber = preg_replace( '/\D/', '', filter_input( INPUT_POST, 'card_num' ) );
		$subscription->creditCardCardCode = trim( filter_input( INPUT_POST, 'card_code' ) );
		$subscription->creditCardExpirationDate = sprintf( '%04d-%02d', filter_input( INPUT_POST, 'exp_year', FILTER_VALIDATE_INT ), filter_input( INPUT_POST, 'exp_month', FILTER_VALIDATE_INT ) );
	
		// billing information
		$subscription->billToFirstName = substr( trim( filter_input( INPUT_POST, 'first_name' ) ), 0, 50 );
		$subscription->billToLastName = substr( trim( filter_input( INPUT_POST, 'last_name' ) ), 0, 50 );
		$subscription->billToCompany = substr( trim( filter_input( INPUT_POST, 'company' ) ), 0, 50 );
		$subscription->billToAddress = substr( trim( filter_input( INPUT_POST, 'address' ) ), 0, 60 );
		$subscription->billToCity = substr( trim( filter_input( INPUT_POST, 'city' ) ), 0, 40 );
		$subscription->billToState = substr( trim( filter_input( INPUT_POST, 'state' ) ), 0, 40 );
		$subscription->billToZip = substr( trim( filter_input( INPUT_POST, 'zip' ) ), 0, 20 );
		$subscription->billToCountry = substr( trim( filter_input( INPUT_POST, 'country' ) ), 0, 60 );
	
		return $subscription;
	}
}
