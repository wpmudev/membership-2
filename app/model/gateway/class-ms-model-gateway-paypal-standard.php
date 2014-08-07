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

class MS_Model_Gateway_Paypal_Standard extends MS_Model_Gateway {
	
	protected static $CLASS_NAME = __CLASS__;
	
	public static $instance;
	
	protected $id = self::GATEWAY_PAYPAL_STANDARD;
	
	const STATUS_FAILED = 'failed';
	
	const STATUS_REVERSED = 'reversed';
	
	const STATUS_REFUNDED = 'refunded';
	
	const STATUS_PENDING = 'pending';
	
	const STATUS_DISPUTE = 'dispute';
	
	const STATUS_DENIED = 'denied';
	
	protected $name = 'PayPal Standard Gateway';
	
	protected $description = 'PayPal Standard Gateway for recurrent payments';
	
	protected $manual_payment = false;
	
	protected $pro_rate = false;
	
	protected $merchant_id;
	
	protected $paypal_site;
	
	protected $mode;
	
	public function after_load() {
		parent::after_load();
		if( $this->active ) {
			$this->add_filter( 'ms_view_shortcode_membership_signup_button_html_membership_cancel_' . $this->id, 'cancel_button', 10, 2 );
			$this->add_filter( 'ms_model_invoice_get_status', 'gateway_custom_status' );
		}
	}
	
	public function gateway_custom_status( $status ) {
		$paypal_status = array(
				self::STATUS_FAILED => __( 'Failed', MS_TEXT_DOMAIN ),
				self::STATUS_REVERSED => __( 'Reversed', MS_TEXT_DOMAIN ),
				self::STATUS_REFUNDED => __( 'Refunded', MS_TEXT_DOMAIN ),
				self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
				self::STATUS_DISPUTE => __( 'Dispute', MS_TEXT_DOMAIN ),
				self::STATUS_DENIED => __( 'Denied', MS_TEXT_DOMAIN ),
		);
	
		return array_merge( $status, $paypal_status );
	}
	
	public function handle_return() {
		
		MS_Helper_Debug::log( 'Paypal standard IPN POST:' );
		MS_Helper_Debug::log( $_POST );

		if( ( isset($_POST['payment_status'] ) || isset( $_POST['txn_type'] ) ) && ! empty( $_POST['custom'] ) ) {
			if( self::MODE_LIVE == $this->mode ) {
				$domain = 'https://www.paypal.com';
			}
			else {
				$domain = 'https://www.sandbox.paypal.com';
			}
			
			//Paypal post authenticity verification
			$ipn_data = (array) stripslashes_deep( $_POST );
			$ipn_data['cmd'] = '_notify-validate';
			$response = wp_remote_post( "$domain/cgi-bin/webscr", array(
					'timeout' => 60,
					'sslverify' => false,
					'httpversion' => '1.1',
					'body' => $ipn_data,
			) );
		
			if ( ! is_wp_error( $response ) && 200 == $response['response']['code'] && ! empty( $response['body'] ) && "VERIFIED" == $response['body'] ) {
				MS_Helper_Debug::log( 'PayPal Transaction Verified' );
			} 
			else {
				$error = 'Response Error: Unexpected transaction response';
				MS_Helper_Debug::log( $error );
				MS_Helper_Debug::log( $response );
				echo $error;
				exit;
			}
		
			if( empty( $_POST['custom'] ) ) {
				$error = 'Response Error: No relationship identification found.';
				MS_Helper_Debug::log( $error );
				MS_Helper_Debug::log( $response );
				exit;
			}
			
			$invoice = MS_Factory::get_factory()->load_invoice( $_POST['custom'] );
			$ms_relationship = MS_Factory::get_factory()->load_membership_relationship( $invoice->ms_relationship_id );
			$membership = $ms_relationship->get_membership();
			$member = MS_Factory::get_factory()->load_member( $ms_relationship->user_id );
			
			$currency = $_POST['mc_currency'];
			$status = null;
			$notes = null;
			$external_id = null;
			$amount = 0;
			
			/** Process PayPal payment status */
			if( ! empty( $_POST['payment_status'] ) ) {
				$amount = (float) $_POST['mc_gross'];
				$external_id = $_POST['txn_id'];
				switch ( $_POST['payment_status'] ) {
					/** Successful payment */
					case 'Completed':
					case 'Processed':
						$status = MS_Model_Invoice::STATUS_PAID;
						break;
					case 'Reversed':
						$notes = __('Last transaction has been reversed. Reason: Payment has been reversed (charge back). ', MS_TEXT_DOMAIN );
						$status = self::STATUS_REVERSED;
						break;
					case 'Refunded':
						$notes = __( 'Last transaction has been reversed. Reason: Payment has been refunded', MS_TEXT_DOMAIN );
						$status = self::STATUS_REFUNDED;
						break;
					case 'Denied':
						$notes = __('Last transaction has been reversed. Reason: Payment Denied', MS_TEXT_DOMAIN );
						$status = self::STATUS_DENIED;
						break;
					case 'Pending':
						$pending_str = array(
							'address' => __( 'Customer did not include a confirmed shipping address', MS_TEXT_DOMAIN ),
							'authorization' => __( 'Funds not captured yet', MS_TEXT_DOMAIN ),
							'echeck' => __( 'eCheck that has not cleared yet', MS_TEXT_DOMAIN ),
							'intl' => __( 'Payment waiting for aproval by service provider', MS_TEXT_DOMAIN ),
							'multi-currency' => __( 'Payment waiting for service provider to handle multi-currency process', MS_TEXT_DOMAIN ),
							'unilateral' => __( 'Customer did not register or confirm his/her email yet', MS_TEXT_DOMAIN ),
							'upgrade' => __( 'Waiting for service provider to upgrade the PayPal account', MS_TEXT_DOMAIN ),
							'verify' => __( 'Waiting for service provider to verify his/her PayPal account', MS_TEXT_DOMAIN ),
							'*' => ''
						);
						$reason = $_POST['pending_reason'];
						$notes = __( 'Last transaction is pending. Reason: ', MS_TEXT_DOMAIN ) . ( isset($pending_str[$reason] ) ? $pending_str[$reason] : $pending_str['*'] );
						$status = self::STATUS_PENDING;
						break;
			
					default:
					case 'Partially-Refunded':
					case 'In-Progress':
						$notes = __( 'Not handling payment_status: ', MS_TEXT_DOMAIN ) . $_POST['payment_status'];
						MS_Helper_Debug::log( $notes );
						break;
				}
			}
			
			/** Check for subscription details */
			if( ! empty( $_POST['txn_type'] ) ) {
				switch ( $_POST['txn_type'] ) {
					case 'subscr_signup':
						$notes = __( 'Paypal subscipton profile has been created.', MS_TEXT_DOMAIN );
						break;
					case 'subscr_modify':
						$notes = __( 'Paypal subscipton profile has been modified.', MS_TEXT_DOMAIN );
						break;
					case 'recurring_payment_profile_canceled':
					case 'subscr_cancel':
						$notes = __( 'Paypal subscipton profile has been canceled.', MS_TEXT_DOMAIN );
						$member->cancel_membership( $membership->id );
						$member->save();
						break;
					case 'recurring_payment_suspended':
						$notes = __( 'Paypal subscipton profile has been suspended.', MS_TEXT_DOMAIN );
						$member->cancel_membership( $membership->id );
						$member->save();
						break;
					case 'recurring_payment_suspended_due_to_max_failed_payment':
						$notes = __( 'Paypal subscipton profile has failed.', MS_TEXT_DOMAIN );
						$member->cancel_membership( $membership->id );
						$member->save();
						break;
					case 'new_case':
						$status = self::STATUS_DISPUTE;
						break;
					default:
						$notes = __( 'Not handling txn_type: ', MS_TEXT_DOMAIN ) . $_POST['txn_type'];
						MS_Helper_Debug::log( $notes );
						break;
				}
			}
// 			MS_Helper_Debug::log( "transaction_id: $invoice_id, ext_id: $external_id status: $status, notes: $notes" );
			
			if( empty( $invoice ) ) {
 				$invoice = $ms_relationship->get_current_invoice();
			}
			$invoice->external_id = $external_id;
			if( ! empty( $notes ) ) {
				$invoice->add_notes( $notes );
			}
			$invoice->gateway_id = $this->id;
			$invoice->save();
			
			if( ! empty( $status ) ) {
				$invoice->status = $status;
				$this->process_transaction( $invoice );
				$invoice->save();
			}
			do_action( "ms_model_gateway_paypal_standard_payment_processed_{$status}", $invoice, $ms_relationship );
		} 
		else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('Status: 404 Not Found');
			$notes = __( 'Error: Missing POST variables. Identification is not possible.', MS_TEXT_DOMAIN );
			MS_Helper_Debug::log( $notes );
			exit;
		}
	}
	
	/**
	 * Cancel membership button.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function cancel_button( $html_link, $membership ) {
	
		if( MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING == $membership->membership_type || $membership->trial_period_enabled ) {
			if( ! empty( $this->cancel_button_url ) && strpos( $this->cancel_button_url, 'http' ) !== 0 ) {
				$cancel_btn = array(
						'id' => 'submit',
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'value' => $this->pay_button_url,
				);
			}
			else {
				$cancel_btn = array(
						'id' => 'submit',
						'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
						'value' =>  $this->cancel_button_url ? $this->cancel_button_url : 'https://www.paypal.com/en_US/i/btn/btn_unsubscribe_LG.gif',
				);
			}

			if( self::MODE_LIVE == $this->mode ) {
				$action = 'https://www.paypal.com/cgi-bin/webscr';
			}
			else {
				$action = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}
			MS_Helper_Html::html_link( array(
				'url' => $action . '?cmd=_subscr-find&alias=' . $this->merchant_id,
				'value' => MS_Helper_Html::html_input( $cancel_btn, true ),
			) );
		}
	}
	
	public function get_status_types() {
		return apply_filters( 'ms_model_gateway_paypal_standard_get_status', array(
				'live' => __( 'Live Site', MS_TEXT_DOMAIN ),
				'test' => __( 'Test Mode (Sandbox)', MS_TEXT_DOMAIN ),
			)
		);
	}
	
	public function get_paypal_sites() {
		return apply_filters( 'ms_model_gateway_paylpay_standard_get_paypal_sites', self::get_country_codes() );
	}
	
	/**
	 * Process transaction.
	 *
	 * Process transaction status change related to this membership relationship.
	 * Change status accordinly to transaction status.
	 *
	 * @since 4.0
	 *
	 * @param MS_Model_Invoice $invoice The Transaction.
	 */
	public function process_transaction( $invoice ) {
		$ms_relationship = MS_Factory::get_factory()->load_membership_relationship( $invoice->ms_relationship_id );
		$member = MS_Factory::get_factory()->load_member( $invoice->user_id );
		switch( $invoice->status ) {
			case self::STATUS_REVERSED:
			case self::STATUS_REFUNDED:
			case self::STATUS_DENIED:
			case self::STATUS_DISPUTE:
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_DENIED, $ms_relationship );
				$ms_relationship->status = MS_Model_Membership_Relationship::STATUS_DEACTIVATED;
				$member->active = false;
				break;
			default:
				$ms_relationship = parent::process_transaction( $invoice );
				break;
		}
		$member->save();
		$ms_relationship->gateway_id = $this->gateway_id;
		$ms_relationship->save();
	}
	
	/**
	 * Validate specific property before set.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'paypal_site':
					if( array_key_exists( $value, self::get_paypal_sites() ) ) {
						$this->$property = $value;
					}
					break;
				default:
					parent::__set( $property, $value );
					break;
			}
		}
	}
	
}