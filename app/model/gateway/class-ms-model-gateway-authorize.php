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
	
	const AUTHORIZE_CIM_ID_USER_META = 'ms_authorize_cim_id';
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id = self::GATEWAY_AUTHORIZE;
	
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
	
	public function purchase_button( $ms_relationship ) {
		$fields = array(
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->id,
				),
				'ms_relationship_id' => array(
						'id' => 'ms_relationship_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $ms_relationship->id,
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
		$actionurl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		?>
			<form action="<?php echo $actionurl; ?>" method="post">
				<?php wp_nonce_field( "{$this->id}_{$membership->id}" ); ?>
				<?php MS_Helper_Html::html_input( $fields['gateway'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['ms_relationship_id'] ); ?>
				<?php MS_Helper_Html::html_input( $fields['step'] ); ?>
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
		$cim->setSandbox( $this->mode != self::MODE_LIVE );
		if ( $this->log_file ) {
			$cim->setLogFile( $this->log_file );
		}
	
		return $cim;
	}
	
	/**
	 * Get customer information manager profile id.
	 * 
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param int $user_id The user Id.
	 */
	protected function get_cim_profile_id( $user_id ) {
		$this->cim_profile_id = apply_filters( 'ms_model_gateway_authorize_get_cim_profile_id', get_user_meta( $user_id, self::AUTHORIZE_CIM_ID_USER_META, true ), $user_id );
		return $this->cim_profile_id;
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
	public function get_cim_profile( $user_id, $membership_id ) {

		$cim_profiles = array();
		$this->get_cim_profile_id( $user_id );
		$membership = MS_Model_Membership::load( $membership_id );
		
		if( $this->cim_profile_id && $membership->id > 0 && ! $membership->trial_period_enabled && 
			MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING != $membership->membership_type ) {
			
			$response = $this->get_cim()->getCustomerProfile( $this->cim_profile_id );
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
	 * @since 4.0.0
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
			MS_Helper_Debug::log( 'CIM profile not created: ' . $response->getMessageText() );
			return false;
		}
	
		$profile_id = $response->getCustomerProfileId();
		update_user_meta( $member->id, self::AUTHORIZE_CIM_ID_USER_META, $profile_id );
	
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
		$response = $this->get_cim()->createCustomerPaymentProfile( $this->cim_profile_id, $payment );
		if ( $response->isError() ) {
			MS_Helper_Debug::log( 'CIM profile not updated: ' . $response->getMessageText() );
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
	
		/** A purchase is serial when has more than one payment (recurrent, or with trial period) */
		$has_serial = true;
		
		$this->get_cim_profile_id( $member->id );
		$this->cim_payment_profile_id = null;
		
		$transactions = array();
		try{
			switch( $membership->membership_type ) {
				case MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING:
					$transactions = $this->process_serial_purchase( $membership, $coupon_id );
					break;
				case MS_Model_Membership::MEMBERSHIP_TYPE_FINITE:
				case MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE:
				case MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT:
					/**
					 * CIM can't handle recurring transaction, so we need
					 * to use standard ARB aproach and full cards details
					 */ 
					if( $membership->trial_period_enabled && ! empty( $membership->trial_period['period_unit'] ) ) {
						$transactions = $this->process_serial_purchase( $membership, $coupon_id );
					}
					/**
					 * No serial purchase. Fetch CIM user and payment profiles info.
					 * Only allowed for non serial purchases. Otherwise need to use ARB.
					 */ 
					else {
						$has_serial = false;
						if( $this->cim_payment_profile_id = trim( filter_input( INPUT_POST, 'profile' ) ) ) {
							$response = $this->get_cim()->getCustomerPaymentProfile( $this->cim_profile_id, $this->cim_payment_profile_id );
							if ( $response->isError() ) {
								$this->cim_payment_profile_id = null;
							}
						}
						$transactions[] = $this->process_non_serial_purchase( $membership->price, $coupon_id );
					}
					break;
			}
			
			$this->commit_transactions( $transactions );
			
			foreach( $transactions as $transaction ) {
				if ( in_array( $transaction['status'], array( self::TRANSACTION_TYPE_CAPTURED, self::TRANSACTION_TYPE_RECURRING ) ) ) {
					
					$notes = $this->mode == self::MODE_SANDBOX ? 'Sandbox' : '';
					$status = MS_Model_Transaction::STATUS_PAID;
						
					$ms_transaction = $this->add_transaction( array( 
							'membership' => $membership,
							'member' => $member,
							'status' => $status,
							'move_from_id' => $move_from_id,
							'coupon_id' => $coupon_id,
							'external_id' => $transaction['transaction'],
							'notes' => $notes,
							'amount' => $transaction['amount'],
					) );
				}
			}
			
			if( ! $this->cim_profile_id ) {
				$this->create_cim_profile( $member );
			}
			elseif ( ! $has_serial && empty( $this->cim_payment_profile_id ) ) {
				$this->update_cim_profile();
			}
			$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) );
			wp_safe_redirect( $url );
				
		}
		catch( Exception $e ) {
			MS_Helper_Debug::log( $e->getMessage() );
			
			$this->rollback_transactions( $transactions );
		}
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
	protected function process_non_serial_purchase( $price, $coupon_id = 0 ) {
		if ( 0 == $price ) {
			return null;
		}
	
		$transaction_id = null; 
		$method = null;
		$amount = number_format( $price, 2, '.', '' );
		
		if ( ! empty( $this->cim_profile_id ) && ! empty( $this->cim_payment_profile_id ) ) {
			$transaction = $this->get_cim_transaction();
			$transaction->amount = $amount;
	
			$response = $this->get_cim()->createCustomerProfileTransaction( 'AuthOnly', $transaction );
			if ( $response->isOk() ) {
				$method = 'cim';
				$transaction_id = $response->getTransactionResponse()->transaction_id;
			} 
			else {
				throw new Exception( $response->getMessageText() );
			}
		} 
		else {
			$response = $this->get_aim()->authorizeOnly( $amount );
			if ( $response->approved ) {
				$transaction_id = $response->transaction_id;
				$method = 'aim';
			} 
			elseif ( $response->error ) {
				throw new Exception( $response->response_reason_text );
			}
		}
		
		return  array(
				'method'      => $method,
				'transaction' => $transaction_id,
				'date'        => MS_Helper_Period::current_date(),
				'amount'      => $amount,
		);
		
	}
	
	/**
	 * Processes serial or with trial period purchase.
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
	protected function process_serial_purchase( $membership, $coupon_id = 0 ) {
		if ( 0 == $membership->price ) {
			return null;
		}
	
		$transactions = array();
	
		// initialize AIM transaction to check CC
		$transaction = $this->process_non_serial_purchase( $membership->price );
		if ( is_null( $transaction ) ) {
			return null;
		}
		else {
			$transactions[] = $transaction;
		}
	
		$amount = number_format( $membership->price, 2, '.', '' );
	
		$subscription = $this->get_arb_subscription( $membership );
		$subscription->name = $membership->name;
		$subscription->amount = $amount;
		$subscription->startDate = MS_Helper_Period::current_date();
	
		if( $membership->trial_period_enabled && $membership->trial_period['period_unit'] > 0 ) {
			switch( $membership->membership_type ) {
				case MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING:
					if( $membership->trial_period['period_unit'] != $membership->pay_cycle_period['period_unit'] ||
						$membership->trial_period['period_type'] != $membership->pay_cycle_period['period_type'] ) {
							
						$transactions[] = $this->process_non_serial_purchase( $membership->trial_price );
						$subscription->startDate = $membership->get_trial_expire_date();
						/** serial ocurrency with on going subscription (no end date) = 9999 */
						$subscription->totalOccurrences = 9999;
					}
					else {
						$subscription->trialAmount = number_format( $membership->trial_price, 2, '.', '' );
						$subscription->trialOccurrences = 1;
						/** serial ocurrency with on going subscription (no end date) = 9999 */
						$subscription->totalOccurrences = 9999;
					}
					/** only days or months period types are allowed */
					if( MS_Helper_Period::PERIOD_TYPE_YEARS == $membership->pay_cycle_period['period_type'] ) {
						$subscription->intervalLength = $membership->pay_cycle_period['period_unit'] * 12;
						$subscription->intervalUnit = MS_Helper_Period::PERIOD_TYPE_MONTHS;
					}
					else {
						$subscription->intervalLength = $membership->pay_cycle_period['period_unit'];
						$subscription->intervalUnit = $membership->pay_cycle_period['period_type'];
					}
					break;
				case MS_Model_Membership::MEMBERSHIP_TYPE_FINITE:
					$transactions[] = $this->process_non_serial_purchase( $membership->trial_price );
					$subscription->startDate = $membership->get_trial_expire_date();
					$subscription->totalOccurrences = 1;
					$subscription->intervalLength = $membership->period['period_unit'];
					$subscription->intervalUnit = $membership->period['period_type'];
					break;
				case MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE:
					$transactions[] = $this->process_non_serial_purchase( $membership->trial_price );
					$subscription->startDate = $membership->get_trial_expire_date();
					$subscription->totalOccurrences = 1;
					$subscription->intervalLength = MS_Helper_Period::subtract_dates( $membership->period_date_end, $membership->period_date_start )->days;
					$subscription->intervalUnit = 'days';
					break;
				case MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT:
					$transactions[] = $this->process_non_serial_purchase( $membership->trial_price );
					$subscription->startDate = $membership->get_trial_expire_date();
					$subscription->totalOccurrences = 1;
					$subscription->intervalLength = 1;
					$subscription->intervalUnit = 'month';
					break;
			}
		}
		MS_Helper_Debug::log($subscription);
		$arb = $this->get_arb();
		$response = $arb->createSubscription( $subscription );
		if ( $response->isOk() ) {
			$transactions[] = array(
					'method'      => 'arb',
					'transaction' => $response->getSubscriptionId(),
					'date'        => MS_Helper_Period::current_date(),
					'amount'      => $amount,
			);
			return $transactions;
		}
		else {
			throw new Exception( $response->getMessageText() );
		}
	}
	
	/**
	 * Processes transactions.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function commit_transactions( &$transactions ) {
	
		// process each transaction information and save it to CIM
		foreach ( $transactions as $index => $info ) {
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
			$transactions[ $index ]['status'] = $status;
		}
	}
	
	/**
	 * Rollbacks transactions all transactions and subscriptions.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function rollback_transactions( $transactions ) {
		foreach ( $transactions as $info ) {
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
			$member = MS_Model_Member::get_current_member();
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
	protected function get_arb_subscription( $member ) {

		$this->load_authorize_lib();
		
		// create new subscription
		$subscription = new AuthorizeNet_Subscription();
		$subscription->customerId = $member->id;
		$subscription->customerEmail = $member->email;
		$subscription->customerPhoneNumber = substr( trim( filter_input( INPUT_POST, 'phone' ) ), 0, 25 );
	
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
	
	/**
	 * Cancels active recuring subscriptions
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function cancel_membership( $ms_relationship ) {
		MS_Helper_Debug::log( 'cancel_membership');
		MS_Helper_Debug::log($ms_relationship);
		
		$membership = $ms_relationship->get_membership();
		if( MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING == $membership->membership_type || 
			$membership->trial_period_enabled && $membership->trial_period['period_unit'] > 0 ) {

// 			$args['author'] = $ms_relationship->user_id;
// 			$args['meta_query']['membership_id'] = array(
// 					'key' => 'membership_id',
// 					'value' => $ms_relationship->membership_id,
// 			);
// 			$transactions = MS_Model_Transaction::get_transactions( $args );
			foreach( $ms_relationship->transaction_ids as $transaction_id ) {
				$transaction = MS_Model_Transaction::load( $transaction_id );
				MS_Helper_Debug::log("external transId: $transaction->external_id ");
				$this->get_arb()->cancelSubscription( $transaction->external_id );
			}
		}
	}
}
