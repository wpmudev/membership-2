<?php
/**
 * Authorize.net Gateway.
 *
 * Must use SSL to send card info to gateway.
 * Integrate Authorize.net gateway send payment requests.
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_Authorize extends MS_Gateway {

	const ID = 'authorize';

	/**
	 * Gateway singleton instance.
	 *
	 * @since  1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Authorize.net's Customer Information Manager wrapper.
	 *
	 * @since  1.0.0
	 * @var string $cim
	 */
	protected static $cim = '';

	/**
	 * Authorize.net API login IP.
	 *
	 * @see @link https://www.authorize.net/support/CP/helpfiles/Account/Settings/Security_Settings/General_Settings/API_Login_ID_and_Transaction_Key.htm
	 * @since  1.0.0
	 * @var string $api_login_id
	 */
	protected $api_login_id = '';

	/**
	 * Authorize.net API transaction key.
	 *
	 * @since  1.0.0
	 * @var string $api_transaction_key
	 */
	protected $api_transaction_key = '';

	/**
	 * Secure transactions flag.
	 * If set to true then each payment request will include the users credit
	 * card verificaton code (CVC). This means that all recurring payments need
	 * the user to enter the CVC code again.
	 *
	 * @since  1.0.0
	 * @var bool $secure_cc
	 */
	protected $secure_cc = false;


	/**
	 * Initialize the object.
	 *
	 * @since  1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->id = self::ID;
		$this->name = __( 'Authorize.net Gateway', 'membership2' );
		$this->group = 'Authorize.net';
		$this->manual_payment = true; // Recurring billed/paid manually
		$this->pro_rate = true;
	}

	/**
	 * Processes purchase action.
	 *
	 * This function is called when a payment was made: We check if the
	 * transaction was successful. If it was we call `$invoice->changed()` which
	 * will update the membership status accordingly.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		do_action(
			'ms_gateway_authorize_process_purchase_before',
			$subscription,
			$this
		);

		if ( ! is_ssl() ) {
			throw new Exception( __( 'You must use HTTPS in order to do this', 'membership' ) );
		}

		$invoice = $subscription->get_current_invoice();
		$member = $subscription->get_member();

		// manage authorize customer profile
		$cim_profile_id = $this->get_cim_profile_id( $member );

		if ( empty( $cim_profile_id ) ) {
			$this->create_cim_profile( $member );
		} elseif ( $cim_payment_profile_id = trim( filter_input( INPUT_POST, 'profile' ) ) ) {
			// Fetch for user selected cim profile
			$response = $this->get_cim()->getCustomerPaymentProfile( $cim_profile_id, $cim_payment_profile_id );

			if ( $response->isError() ) {
				throw new Exception(
					__( 'The selected payment profile is invalid, enter a new credit card', 'membership2' )
				);
			}
		} else {
			$this->update_cim_profile( $member );
		}

		if ( ! $invoice->is_paid() ) {
			// Not paid yet, request the transaction.
			$this->online_purchase( $invoice, $member, 'process' );
		} elseif ( 0 == $invoice->total ) {
			// Paid and free.
			$invoice->changed();
		}

		$invoice->gateway_id = self::ID;
		$invoice->save();

		return apply_filters(
			'ms_gateway_authorize_process_purchase',
			$invoice,
			$this
		);
	}

	/**
	 * Request automatic payment to the gateway.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 * @return bool True on success.
	 */
	public function request_payment( $subscription ) {
		$was_paid = false;

		do_action(
			'ms_gateway_authorize_request_payment_before',
			$subscription,
			$this
		);

		$member = $subscription->get_member();
		$invoice = $subscription->get_current_invoice();

		if ( ! $invoice->is_paid() ) {
			// Not paid yet, request the transaction.
			$was_paid = $this->online_purchase( $invoice, $member, 'request' );
		} else {
			// Invoice was already paid earlier.
			$was_paid = true;
		}

		do_action(
			'ms_gateway_authorize_request_payment_after',
			$subscription,
			$was_paid,
			$this
		);

		return $was_paid;
	}

	/**
	 * Processes online payments.
	 *
	 * Send to Authorize.net to process the payment immediatly.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Invoice $invoice The invoice to pay.
	 * @param MS_Model_Member The member paying the invoice.
	 * @return bool True on success, otherwise throws an exception.
	 */
	protected function online_purchase( &$invoice, $member, $log_action ) {
		$success = false;
		$notes = '';
		$amount = 0;
		$subscription = $invoice->get_subscription();

		do_action(
			'ms_gateway_authorize_online_purchase_before',
			$invoice,
			$member,
			$this
		);

		$need_code = lib3()->is_true( $this->secure_cc );
		$have_code = ! empty( $_POST['card_code'] );

		if ( 0 == $invoice->total ) {
			$notes = __( 'Total is zero. Payment approved. Not sent to gateway.', 'membership2' );
			$invoice->pay_it( MS_Gateway_Free::ID, '' );
			$invoice->add_notes( $notes );
			$invoice->save();
			$invoice->changed();
		} elseif ( ! $need_code || $have_code ) {
			$amount = MS_Helper_Billing::format_price( $invoice->total );

			$cim_transaction = $this->get_cim_transaction( $member );
			$cim_transaction->amount = $amount;
			$cim_transaction->order->invoiceNumber = $invoice->id;

			$invoice->timestamp = time();
			$invoice->save();

			$_POST['API Out: Secure Payment'] = lib3()->is_true( $this->secure_cc );
			$_POST['API Out: CustomerProfileID'] = $cim_transaction->customerProfileId;
			$_POST['API Out: PaymentProfileID'] = $cim_transaction->customerPaymentProfileId;
			$_POST['API Out: InvoiceNumber'] = $cim_transaction->order->invoiceNumber;

			$response = $this->get_cim()->createCustomerProfileTransaction(
				'AuthCapture',
				$cim_transaction
			);

			if ( ! empty( $response->xml )
				&& ! empty( $response->xml->messages )
				&& ! empty( $response->xml->messages->message )
			) {
				$msg = $response->xml->messages->message;
				$_POST['API Response: Short'] = $msg->code . ': ' . $msg->text;
			} else {
				$_POST['API Response: Short'] = '-';
			}
			if ( isset( $response->response ) ) {
				if ( is_string( $response->response ) ) {
					$_POST['API Response: XML'] = $response->response;
				} else {
					$_POST['API Response: XML'] = json_encode( $response->response );
				}
			} else {
				$_POST['API Response: XML'] = json_encode( $response->response );
			}

			if ( $response->isOk() ) {
				$transaction_response = $response->getTransactionResponse();

				if ( $transaction_response->approved ) {
					$external_id = $response->getTransactionResponse()->transaction_id;
					$invoice->pay_it( self::ID, $external_id );
					$success = true;
					$notes = __( 'Payment successful', 'membership2' );
				} else {
					$notes = sprintf(
						__( 'Payment Failed: code %s, subcode %s, reason code %, reason %s', 'membership2' ),
						$transaction_response->response_code,
						$transaction_response->response_subcode,
						$transaction_response->response_reason_code,
						$transaction_response->response_reason
					);
				}
			} else {
				$notes = __( 'Payment Failed: ', 'membership2' ) . $response->getMessageText();
			}
		} elseif ( $need_code && ! $have_code ) {
			$notes = __( 'Secure payment failed: No Card Code found', 'membership2' );
		}

		// Mask the credit card number before logging it to database.
		$card_num = '';
		$card_code = '';
		if ( isset( $_POST['card_num'] ) ) {
			// Card Num   6789765435678765
			// Becomes    ************8765
			$card_num = str_replace( ' ', '', $_POST['card_num'] );
			$_POST['card_num'] = str_pad(
				substr( $card_num, -4 ),
				strlen( $card_num ),
				'*',
				STR_PAD_LEFT
			);
		}
		if ( isset( $_POST['card_code'] ) ) {
			$card_code = $_POST['card_code'];
			$_POST['card_code'] = str_repeat( '*', strlen( $card_code ) );
		}

		do_action(
			'ms_gateway_transaction_log',
			self::ID, // gateway ID
			$log_action, // request|process|handle
			$success, // success flag
			$subscription->id, // subscription ID
			$invoice->id, // invoice ID
			$amount, // charged amount
			$notes, // Descriptive text
			$external_id // External ID
		);

		// Restore the POST data in case it's used elsewhere.
		$_POST['card_num'] = $card_num;
		$_POST['card_code'] = $card_code;
		unset( $_POST['API Out: CustomerProfileID'] );
		unset( $_POST['API Out: PaymentProfileID'] );
		unset( $_POST['API Out: InvoiceNumber'] );
		unset( $_POST['API Out: Secure Payment'] );
		unset( $_POST['API Response: Short'] );
		unset( $_POST['API Response: XML'] );

		return $success;
	}

	/**
	 * Save card info.
	 *
	 * Save only 4 last digits and expire date.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Member $member The member to save card info.
	 */
	public function save_card_info( $member ) {
		$cim_profile_id = $this->get_cim_profile_id( $member );
		$cim_payment_profile_id = $this->get_cim_payment_profile_id( $member );
		$profile = $this->get_cim_profile( $member );

		if ( ! empty( $profile['customerPaymentProfileId'] )
			&& $cim_payment_profile_id == $profile['customerPaymentProfileId']
		) {
			$exp_year = filter_input( INPUT_POST, 'exp_year', FILTER_VALIDATE_INT );
			$exp_month = substr( filter_input( INPUT_POST, 'exp_month', FILTER_VALIDATE_INT ), -2 );
			$member->set_gateway_profile(
				self::ID,
				'card_exp',
				gmdate( 'Y-m-t', strtotime( "{$exp_year}-{$exp_month}-01" ) )
			);

			$member->set_gateway_profile(
				self::ID,
				'card_num',
				str_replace( 'XXXX', '', $profile['payment']['creditCard']['cardNumber'] )
			);
		}

		do_action(
			'ms_gateway_authorize_save_card_info_after',
			$member,
			$this
		);
	}

	/**
	 * Loads Authorize.net lib.
	 *
	 * @since  1.0.0
	 */
	protected function load_authorize_lib(){
		do_action( 'ms_gateway_authorize_load_authorize_lib', $this );

		require_once MS_Plugin::instance()->dir . '/lib/authorize.net/autoload.php';
	}

	/**
	 * Returns the instance of AuthorizeNetCIM class.
	 *
	 * @since  1.0.0
	 *
	 * @return M2_AuthorizeNetCIM The instance of AuthorizeNetCIM class.
	 */
	protected function get_cim() {
		$cim = null;

		if ( ! empty( self::$cim ) ) {
			$cim = self::$cim;
		} else {
			$this->load_authorize_lib();

			$cim = new M2_AuthorizeNetCIM( $this->api_login_id, $this->api_transaction_key );
			$cim->setSandbox( ! $this->is_live_mode() );

			if ( WDEV_DEBUG ) { // defined in wpmu-lib submodule.
				$cim->setLogFile( WP_CONTENT_DIR . '/authorize-net.log' );
			}
			self::$cim = $cim;
		}

		return apply_filters(
			'ms_gateway_authorize_get_cim',
			$cim,
			$this
		);
	}

	/**
	 * Get saved customer information manager profile id.
	 *
	 * @since  1.0.0
	 * @param int $user_id The user Id.
	 * @return string The CIM profile Id.
	 */
	public function get_cim_profile_id( $member ) {
		$cim_profile_id = $member->get_gateway_profile(
			self::ID,
			'cim_profile_id'
		);

		return apply_filters(
			'ms_gateway_authorize_get_cim_profile_id',
			$cim_profile_id,
			$member,
			$this
		);
	}

	/**
	 * Get saved customer information manager payment profile id.
	 *
	 * @since  1.0.0
	 * @param int $user_id The user Id.
	 * @return string The CIM payment profile Id.
	 */
	public function get_cim_payment_profile_id( $member ) {
		$cim_payment_profile_id = $member->get_gateway_profile(
			self::ID,
			'cim_payment_profile_id'
		);

		return apply_filters(
			'ms_gateway_authorize_get_cim_payment_profile_id',
			$cim_payment_profile_id,
			$member,
			$this
		);
	}

	/**
	 * Save CIM profile IDs.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Member $member The member to save CIM IDs.
	 * @param string $cim_profile_id The CIM profile ID to save.
	 * @param string $cim_payment_profile_id The CIM payment profile ID to save.
	 */
	protected function save_cim_profile( $member, $cim_profile_id, $cim_payment_profile_id ) {
		$member->set_gateway_profile(
			self::ID,
			'cim_profile_id',
			$cim_profile_id
		);
		$member->set_gateway_profile(
			self::ID,
			'cim_payment_profile_id',
			$cim_payment_profile_id
		);

		$this->save_card_info( $member );
		$member->save();

		do_action(
			'ms_gateway_authorize_save_cim_profile',
			$member,
			$cim_profile_id,
			$cim_payment_profile_id,
			$this
		);
	}

	/**
	 * Get customer information manager profile from Authorize.net.
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model_Member $member The member.
	 * @return array The A.net payment profiles array structure.
	 */
	public function get_cim_profile( $member ) {
		$cim_profiles = array();
		$cim_profile_id = $this->get_cim_profile_id( $member );

		if ( $cim_profile_id ) {
			$response = $this->get_cim()->getCustomerProfile( $cim_profile_id );

			if ( $response->isOk() ) {
				$cim_profiles = json_decode( json_encode( $response->xml->profile ), true );

				if ( is_array( $cim_profiles )
					&& ! empty( $cim_profiles['paymentProfiles'] )
					&& is_array( $cim_profiles['paymentProfiles'] )
				) {
					$cim_profiles = $cim_profiles['paymentProfiles'];
				}
			}
		}

		return apply_filters(
			'ms_gateway_authorize_get_cim_profile',
			$cim_profiles,
			$this
		);
	}

	/**
	 * Creates Authorize.net CIM profile for current user.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Member $member The member to create CIM profile to.
	 */
	protected function create_cim_profile( $member ) {
		do_action(
			'ms_gateway_authorize_create_cim_profile_before',
			$member,
			$this
		);

		$this->load_authorize_lib();
		$customer = new M2_AuthorizeNetCustomer();
		$customer->merchantCustomerId = $member->id;
		$customer->email = $member->email;
		$customer->paymentProfiles[] = $this->create_cim_payment_profile();

		$response = $this->get_cim()->createCustomerProfile( $customer );

		if ( $response->isError() ) {
			MS_Helper_Debug::log( $response );

			// Duplicate record, delete the old one.
			if ( 'E00039' == $response->xml->messages->message->code ) {
				$cim_profile_id = str_replace( 'A duplicate record with ID ', '', $response->xml->messages->message->text );
				$cim_profile_id = (int) str_replace( ' already exists.', '', $cim_profile_id );

				$this->get_cim()->deleteCustomerProfile( $cim_profile_id );

				// Try again
				$this->create_cim_profile( $member );
				return;
			} else {
				throw new Exception(
					__( 'Payment failed due to CIM profile not created: ', 'membership2' ) . $response->getMessageText()
				);
			}
		}

		$cim_profile_id = $response->getCustomerProfileId();
		$cim_payment_profile_id = $response->getCustomerPaymentProfileIds();
		$this->save_cim_profile( $member, $cim_profile_id, $cim_payment_profile_id );
	}

	/**
	 * Updates CIM profile by adding a new credit card.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Member $member The member to update CIM profile.
	 */
	public function update_cim_profile( $member ) {
		do_action(
			'ms_gateway_authorize_update_cim_profile_before',
			$member,
			$this
		);

		$cim_profile_id = $this->get_cim_profile_id( $member );
		$cim_payment_profile_id = $this->get_cim_payment_profile_id( $member );

		if ( empty( $cim_payment_profile_id ) ) {
			$response = $this->get_cim()->createCustomerPaymentProfile(
				$cim_profile_id,
				$this->create_cim_payment_profile()
			);
			$cim_payment_profile_id = $response->getCustomerPaymentProfileIds();
		} else {
			$response = $this->get_cim()->updateCustomerPaymentProfile(
				$cim_profile_id,
				$cim_payment_profile_id,
				self::create_cim_payment_profile()
			);
		}

		// If the error is not due to a duplicate customer payment profile.
		if ( $response->isError() && 'E00039' != $response->xml->messages->message->code ) {
			throw new Exception(
				__( 'Payment failed due to CIM profile not updated: ', 'membership2' ) . $response->getMessageText()
			);
		}

		$this->save_cim_profile( $member, $cim_profile_id, $cim_payment_profile_id );
	}

	/**
	 * Creates CIM payment profile and fills it with posted credit card data.
	 *
	 * @since  1.0.0
	 * @return M2_AuthorizeNetPaymentProfile The instance of AuthorizeNetPaymentProfile class.
	 */
	protected function create_cim_payment_profile() {
		$this->load_authorize_lib();

		$payment = new M2_AuthorizeNetPaymentProfile();

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
		$payment->payment->creditCard->expirationDate = sprintf(
			'%02d-%04d',
			filter_input( INPUT_POST, 'exp_month', FILTER_VALIDATE_INT ),
			filter_input( INPUT_POST, 'exp_year', FILTER_VALIDATE_INT )
		);

		return apply_filters(
			'ms_gateway_authorize_create_cim_payment_profile',
			$payment,
			$this
		);
	}

	/**
	 * Initializes and returns Authorize.net CIM transaction object.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Member $member The member.
	 * @return M2_AuthorizeNetTransaction The instance of AuthorizeNetTransaction class.
	 */
	protected function get_cim_transaction( $member ) {
		$this->load_authorize_lib();

		$cim_profile_id = $this->get_cim_profile_id( $member );
		$cim_payment_profile_id = $this->get_cim_payment_profile_id( $member );

		if ( empty( $cim_profile_id ) || empty( $cim_payment_profile_id ) ) {
			throw new Exception( __( 'CIM Payment profile not found', 'membership2' ) );
		}

		$transaction = new M2_AuthorizeNetTransaction();
		$transaction->customerProfileId = $cim_profile_id;
		$transaction->customerPaymentProfileId = $cim_payment_profile_id;

		// Include the card code if the secure-cc flag is enabled!
		if ( lib3()->is_true( $this->secure_cc ) ) {
			if ( ! empty( $_POST['card_code'] ) ) {
				$transaction->cardCode = $_POST['card_code'];
			}
		}

		return apply_filters(
			'ms_gateway_authorize_get_cim_transaction',
			$transaction,
			$member,
			$this
		);
	}

	/**
	 * Verify required fields.
	 *
	 * @since  1.0.0
	 * @return boolean True if it is configured.
	 */
	public function is_configured() {
		$is_configured = true;
		$required = array( 'api_login_id', 'api_transaction_key' );

		foreach ( $required as $field ) {
			$value = $this->$field;
			if ( empty( $value ) ) {
				$is_configured = false;
				break;
			}
		}

		return apply_filters(
			'ms_gateway_authorize_is_configured',
			$is_configured,
			$this
		);
	}
}
