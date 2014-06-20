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
	
	protected $id = 'paypal_standard_gateway';
	
	protected $name = 'PayPal Standard Gateway';
	
	protected $description = 'PayPal Standard Gateway for recurrent payments';
	
	protected $is_single = false;
	
	protected $merchant_id;
	
	protected $paypal_site;
	
	protected $mode;
	
	public function purchase_button( $membership, $member, $move_from_id = 0, $coupon_id = 0 ) {
		$fields = array(
				'charset' => array(
						'id' => 'charset',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'utf-8',
				),
				'business' => array(
						'id' => 'business',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->merchant_id,
				),
				'cmd' => array(
						'id' => 'cmd',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => '_xclick-subscriptions',
				),
				'item_name' => array(
						'id' => 'item_name',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $membership->name,
				),
				'item_number' => array(
						'id' => 'item_number',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $membership->id,
				),
				'currency_code' => array(
						'id' => 'currency_code',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => MS_Plugin::instance()->settings->currency,
				),
				'return' => array(
						'id' => 'return',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) ),
				),
				'cancel_return' => array(
						'id' => 'cancel_return',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS ) ),
				),
				'notify_url' => array(
						'id' => 'notify_url',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->get_return_url(),
				),
				'country' => array(
						'id' => 'country',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->paypal_site,
				),
				'no_note' => array(
						'id' => 'no_note',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 1,
				),
				'no_shipping' => array(
						'id' => 'no_shipping',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 1,
				),
				'custom' => array(
						'id' => 'custom',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->build_custom( $member->id, $membership->id, $membership->price, $move_from_id, $coupon_id ),
				),
		);
		if( ! empty( $this->pay_button_url ) && strpos( $this->payment_url, 'http' ) !== 0 ) {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => $this->pay_button_url,
			);
		}
		else {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
					'value' =>  $this->pay_button_url ? $this->pay_button_url : 'https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif',
					'alt' => __( 'PayPal - The safer, easier way to pay online', MS_TEXT_DOMAIN ),
			);
		}
		
		/** Trial period */
		if( $membership->trial_period_enabled && ! empty( $membership->trial_period['period_unit'] ) ) {
			$fields['a1'] = array(
					'id' => 'a1',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $membership->trial_price,
			);
			$fields['p1'] = array(
					'id' => 'p1',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => ! empty( $membership->trial_period['period_unit'] ) ? $membership->trial_period['period_unit']: 1,
			);
			$fields['t1'] = array(
					'id' => 't1',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => ! empty( $membership->trial_period['period_type'] ) ? strtoupper( $membership->trial_period['period_type'][0] ) : 'D',
			);
		}
		
		/** Membership price */
		$fields['a3'] = array(
				'id' => 'a3',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $membership->price,
		);
		
		$recurring = 0;
		switch( $membership->membership_type ) {
			case MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING:
				$fields['p3'] = array(
						'id' => 'p3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => ! empty( $membership->pay_cycle_period['period_unit'] ) ? $membership->pay_cycle_period['period_unit']: 0,
				);
				$fields['t3'] = array(
						'id' => 't3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => ! empty( $membership->pay_cycle_period['period_type'] ) ? strtoupper( $membership->pay_cycle_period['period_type'][0] ) : 'D',
				);
				$recurring = 1;
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_FINITE:
				$fields['p3'] = array(
						'id' => 'p3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => ! empty( $membership->period['period_unit'] ) ? $membership->period['period_unit']: 1,
				);
				$fields['t3'] = array(
						'id' => 't3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => ! empty( $membership->period['period_type'] ) ? strtoupper( $membership->period['period_type'][0] ) : 'D',
				);
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE:
				$fields['p3'] = array(
						'id' => 'p3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => MS_Helper_Period::subtract_dates( $membership->period_date_end, $membership->period_date_start )->days,
				);
				$fields['t3'] = array(
						'id' => 't3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => ! empty( $membership->period['period_type'] ) ? strtoupper( $membership->period['period_type'][0] ) : 'D',
				);
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT:
				$fields['p3'] = array(
						'id' => 'p3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 5,
				);
				$fields['t3'] = array(
						'id' => 't3',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'Y',
				);
				break;
		}
		
		/** Coupon valid for the first period */
		if( $coupon_id ) {
			$coupon = MS_Model_Coupon::load( $coupon_id );
			$discount = $coupon->get_coupon_application( $member->id, $membership->id );

			/** Trial period set */
			if( isset ( $fields['a1']['value'] ) ) {
				/** Not a free trial, apply discount in the trial period */
				if( $fields['a1']['value'] > 0 ) {
					$fields['a1']['value'] -= $discount;
					$fields['a1']['value'] = max( $fields['a1']['value'], 0 );
				}
			}
			/** No Trial period set */
			else {
				/** recurrent payment, apply discount in the first payment */
				if( MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING == $membership->membership_type ) {
					$fields['a1'] = $fields['a3'];
					$fields['p1'] = $fields['p3'];
					$fields['t1'] = $fields['t3'];
					$fields['a1']['value'] -= $discount;
					$fields['a1']['value'] = max( $fields['a1']['value'], 0 );
				}
				/** Only one payment */
				else {
					$fields['a3']['value'] -= $discount;
					$fields['a3']['value'] = max( $fields['a3']['value'], 0 );
				}
			}
		}
		
		/**
		 * Recurring field.
		 * 0 – subscription payments do not recur
		 * 1 – subscription payments recur
		 */
		$fields['src'] = array(
				'id' => 'src',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $recurring,
		);
		
		/** 
		 * Modify current subscription field.
		 * value != 0 does not allow trial period.
		 * 0 – allows subscribers only to sign up for new subscriptions
		 * 1 – allows subscribers to sign up for new subscriptions and modify their current subscriptions
		 * 2 – allows subscribers to modify only their current subscriptions
		 */
		 $fields['modify'] = array(
		 		'id' => 'modify',
		 		'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
		 		'value' => empty( $move_from_id ) ? 0 : 2,
		 );
		 
		if( 'live' == $this->mode ) {
			$action = 'https://www.paypal.com/cgi-bin/webscr';
		}
		else {
			$action = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
		}
		
		?>
			<form action="<?php echo $action;?>" method="post">
				<?php 
					foreach( $fields as $field ) {
						MS_Helper_Html::html_input( $field ); 
					}
				?>
				<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >
			</form>
		<?php
	}
	
	public function handle_return() {
		
		MS_Helper_Debug::log( 'Paypal standard IPN POST:' );
		MS_Helper_Debug::log( $_POST );

		if( ( isset($_POST['payment_status'] ) || isset( $_POST['txn_type'] ) ) && isset( $_POST['custom'] ) ) {
			if( 'live' == $this->mode ) {
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
		
			list( $timestamp, $user_id, $membership_id, $move_from_id, $coupon_id, $key ) = explode( ':', $_POST['custom'] );
			
			$membership = MS_Model_Membership::load( $membership_id );
			$member = MS_Model_Member::load( $user_id );
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
						$status = MS_Model_Transaction::STATUS_PAID;
						break;
					case 'Reversed':
						$notes = __('Last transaction has been reversed. Reason: Payment has been reversed (charge back). ', MS_TEXT_DOMAIN );
						$status = MS_Model_Transaction::STATUS_REVERSED;
						break;
					case 'Refunded':
						$notes = __( 'Last transaction has been reversed. Reason: Payment has been refunded', MS_TEXT_DOMAIN );
						$status = MS_Model_Transaction::STATUS_REFUNDED;
						break;
					case 'Denied':
						$notes = __('Last transaction has been reversed. Reason: Payment Denied', MS_TEXT_DOMAIN );
						$status = MS_Model_Transaction::STATUS_DENIED;
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
						$notes = 'Last transaction is pending. Reason: ' . ( isset($pending_str[$reason] ) ? $pending_str[$reason] : $pending_str['*'] );
						$status = MS_Model_Transaction::STATUS_PENDING;
						break;
			
					default:
					case 'Partially-Refunded':
					case 'In-Progress':
						MS_Helper_Debug::log( "Not handling payment_status: " . $_POST['payment_status'] );
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
						$status = MS_Model_Transaction::STATUS_DISPUTE;
						break;
					default:
						MS_Helper_Debug::log( "Not handling txn_type: " . $_POST['txn_type'] );
						break;
				}
			}
			MS_Helper_Debug::log( "ext_id: $external_id status: $status, notes: $notes" );
			
			if( ! empty( $status ) ) {
				if( $transaction = MS_Model_Transaction::load_by_external_id( $external_id, $this->id ) ) {
					if( ! empty( $notes ) ) {
						$transaction->notes = $notes;
					}
					$transaction->process_transaction( $status );
				}
				else {
					$transaction = $this->add_transaction( $membership, $member, $status, $move_from_id, $coupon_id, $external_id, $notes );
					$transaction->amount = $amount;
					$transaction->process_transaction( $status, true );
				}
				do_action( "ms_model_gateway_paypal_single_payment_processed_{$status}", $user_id, $membership_id, $amount, $currency, $external_id );
			}				
		} 
		else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.
			header('Status: 404 Not Found');
			$notes = __( 'Error: Missing POST variables. Identification is not possible.', MS_TEXT_DOMAIN );
			MS_Helper_Debug::log( $notes );
			exit;
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
		return apply_filters( 'ms_model_gateway_paylpay_standard_get_paypal_sites', array(
				'AX' => __('ÃLAND ISLANDS', MS_TEXT_DOMAIN ),
				'AL' => __('ALBANIA', MS_TEXT_DOMAIN ),
				'DZ' => __('ALGERIA', MS_TEXT_DOMAIN ),
				'AS' => __('AMERICAN SAMOA', MS_TEXT_DOMAIN ),
				'AD' => __('ANDORRA', MS_TEXT_DOMAIN ),
				'AI' => __('ANGUILLA', MS_TEXT_DOMAIN ),
				'AQ' => __('ANTARCTICA', MS_TEXT_DOMAIN ),
				'AG' => __('ANTIGUA AND BARBUDA', MS_TEXT_DOMAIN ),
				'AR' => __('ARGENTINA', MS_TEXT_DOMAIN ),
				'AM' => __('ARMENIA', MS_TEXT_DOMAIN ),
				'AW' => __('ARUBA', MS_TEXT_DOMAIN ),
				'AU' => __('AUSTRALIA', MS_TEXT_DOMAIN ),
				'AT' => __('AUSTRIA', MS_TEXT_DOMAIN ),
				'AZ' => __('AZERBAIJAN', MS_TEXT_DOMAIN ),
				'BS' => __('BAHAMAS', MS_TEXT_DOMAIN ),
				'BH' => __('BAHRAIN', MS_TEXT_DOMAIN ),
				'BD' => __('BANGLADESH', MS_TEXT_DOMAIN ),
				'BB' => __('BARBADOS', MS_TEXT_DOMAIN ),
				'BE' => __('BELGIUM', MS_TEXT_DOMAIN ),
				'BZ' => __('BELIZE', MS_TEXT_DOMAIN ),
				'BJ' => __('BENIN', MS_TEXT_DOMAIN ),
				'BM' => __('BERMUDA', MS_TEXT_DOMAIN ),
				'BT' => __('BHUTAN', MS_TEXT_DOMAIN ),
				'BA' => __('BOSNIA-HERZEGOVINA', MS_TEXT_DOMAIN ),
				'BW' => __('BOTSWANA', MS_TEXT_DOMAIN ),
				'BV' => __('BOUVET ISLAND', MS_TEXT_DOMAIN ),
				'BR' => __('BRAZIL', MS_TEXT_DOMAIN ),
				'IO' => __('BRITISH INDIAN OCEAN TERRITORY', MS_TEXT_DOMAIN ),
				'BN' => __('BRUNEI DARUSSALAM', MS_TEXT_DOMAIN ),
				'BG' => __('BULGARIA', MS_TEXT_DOMAIN ),
				'BF' => __('BURKINA FASO', MS_TEXT_DOMAIN ),
				'CA' => __('CANADA', MS_TEXT_DOMAIN ),
				'CV' => __('CAPE VERDE', MS_TEXT_DOMAIN ),
				'KY' => __('CAYMAN ISLANDS', MS_TEXT_DOMAIN ),
				'CF' => __('CENTRAL AFRICAN REPUBLIC', MS_TEXT_DOMAIN ),
				'CL' => __('CHILE', MS_TEXT_DOMAIN ),
				'CN' => __('CHINA', MS_TEXT_DOMAIN ),
				'CX' => __('CHRISTMAS ISLAND', MS_TEXT_DOMAIN ),
				'CC' => __('COCOS (KEELING) ISLANDS', MS_TEXT_DOMAIN ),
				'CO' => __('COLOMBIA', MS_TEXT_DOMAIN ),
				'CK' => __('COOK ISLANDS', MS_TEXT_DOMAIN ),
				'CR' => __('COSTA RICA', MS_TEXT_DOMAIN ),
				'CY' => __('CYPRUS', MS_TEXT_DOMAIN ),
				'CZ' => __('CZECH REPUBLIC', MS_TEXT_DOMAIN ),
				'DK' => __('DENMARK', MS_TEXT_DOMAIN ),
				'DJ' => __('DJIBOUTI', MS_TEXT_DOMAIN ),
				'DM' => __('DOMINICA', MS_TEXT_DOMAIN ),
				'DO' => __('DOMINICAN REPUBLIC', MS_TEXT_DOMAIN ),
				'EC' => __('ECUADOR', MS_TEXT_DOMAIN ),
				'EG' => __('EGYPT', MS_TEXT_DOMAIN ),
				'SV' => __('EL SALVADOR', MS_TEXT_DOMAIN ),
				'EE' => __('ESTONIA', MS_TEXT_DOMAIN ),
				'FK' => __('FALKLAND ISLANDS (MALVINAS)', MS_TEXT_DOMAIN ),
				'FO' => __('FAROE ISLANDS', MS_TEXT_DOMAIN ),
				'FJ' => __('FIJI', MS_TEXT_DOMAIN ),
				'FI' => __('FINLAND', MS_TEXT_DOMAIN ),
				'FR' => __('FRANCE', MS_TEXT_DOMAIN ),
				'GF' => __('FRENCH GUIANA', MS_TEXT_DOMAIN ),
				'PF' => __('FRENCH POLYNESIA', MS_TEXT_DOMAIN ),
				'TF' => __('FRENCH SOUTHERN TERRITORIES', MS_TEXT_DOMAIN ),
				'GA' => __('GABON', MS_TEXT_DOMAIN ),
				'GM' => __('GAMBIA', MS_TEXT_DOMAIN ),
				'GE' => __('GEORGIA', MS_TEXT_DOMAIN ),
				'DE' => __('GERMANY', MS_TEXT_DOMAIN ),
				'GH' => __('GHANA', MS_TEXT_DOMAIN ),
				'GI' => __('GIBRALTAR', MS_TEXT_DOMAIN ),
				'GR' => __('GREECE', MS_TEXT_DOMAIN ),
				'GL' => __('GREENLAND', MS_TEXT_DOMAIN ),
				'GD' => __('GRENADA', MS_TEXT_DOMAIN ),
				'GP' => __('GUADELOUPE', MS_TEXT_DOMAIN ),
				'GU' => __('GUAM', MS_TEXT_DOMAIN ),
				'GG' => __('GUERNSEY', MS_TEXT_DOMAIN ),
				'GY' => __('GUYANA', MS_TEXT_DOMAIN ),
				'HM' => __('HEARD ISLAND AND MCDONALD ISLANDS', MS_TEXT_DOMAIN ),
				'VA' => __('HOLY SEE (VATICAN CITY STATE)', MS_TEXT_DOMAIN ),
				'HN' => __('HONDURAS', MS_TEXT_DOMAIN ),
				'HK' => __('HONG KONG', MS_TEXT_DOMAIN ),
				'HU' => __('HUNGARY', MS_TEXT_DOMAIN ),
				'IS' => __('ICELAND', MS_TEXT_DOMAIN ),
				'IN' => __('INDIA', MS_TEXT_DOMAIN ),
				'ID' => __('INDONESIA', MS_TEXT_DOMAIN ),
				'IE' => __('IRELAND', MS_TEXT_DOMAIN ),
				'IM' => __('ISLE OF MAN', MS_TEXT_DOMAIN ),
				'IL' => __('ISRAEL', MS_TEXT_DOMAIN ),
				'IT' => __('ITALY', MS_TEXT_DOMAIN ),
				'JM' => __('JAMAICA', MS_TEXT_DOMAIN ),
				'JP' => __('JAPAN', MS_TEXT_DOMAIN ),
				'JE' => __('JERSEY', MS_TEXT_DOMAIN ),
				'JO' => __('JORDAN', MS_TEXT_DOMAIN ),
				'KZ' => __('KAZAKHSTAN', MS_TEXT_DOMAIN ),
				'KI' => __('KIRIBATI', MS_TEXT_DOMAIN ),
				'KR' => __('KOREA, REPUBLIC OF', MS_TEXT_DOMAIN ),
				'KW' => __('KUWAIT', MS_TEXT_DOMAIN ),
				'KG' => __('KYRGYZSTAN', MS_TEXT_DOMAIN ),
				'LV' => __('LATVIA', MS_TEXT_DOMAIN ),
				'LS' => __('LESOTHO', MS_TEXT_DOMAIN ),
				'LI' => __('LIECHTENSTEIN', MS_TEXT_DOMAIN ),
				'LT' => __('LITHUANIA', MS_TEXT_DOMAIN ),
				'LU' => __('LUXEMBOURG', MS_TEXT_DOMAIN ),
				'MO' => __('MACAO', MS_TEXT_DOMAIN ),
				'MK' => __('MACEDONIA', MS_TEXT_DOMAIN ),
				'MG' => __('MADAGASCAR', MS_TEXT_DOMAIN ),
				'MW' => __('MALAWI', MS_TEXT_DOMAIN ),
				'MY' => __('MALAYSIA', MS_TEXT_DOMAIN ),
				'MT' => __('MALTA', MS_TEXT_DOMAIN ),
				'MH' => __('MARSHALL ISLANDS', MS_TEXT_DOMAIN ),
				'MQ' => __('MARTINIQUE', MS_TEXT_DOMAIN ),
				'MR' => __('MAURITANIA', MS_TEXT_DOMAIN ),
				'MU' => __('MAURITIUS', MS_TEXT_DOMAIN ),
				'YT' => __('MAYOTTE', MS_TEXT_DOMAIN ),
				'MX' => __('MEXICO', MS_TEXT_DOMAIN ),
				'FM' => __('MICRONESIA, FEDERATED STATES OF', MS_TEXT_DOMAIN ),
				'MD' => __('MOLDOVA, REPUBLIC OF', MS_TEXT_DOMAIN ),
				'MC' => __('MONACO', MS_TEXT_DOMAIN ),
				'MN' => __('MONGOLIA', MS_TEXT_DOMAIN ),
				'ME' => __('MONTENEGRO', MS_TEXT_DOMAIN ),
				'MS' => __('MONTSERRAT', MS_TEXT_DOMAIN ),
				'MA' => __('MOROCCO', MS_TEXT_DOMAIN ),
				'MZ' => __('MOZAMBIQUE', MS_TEXT_DOMAIN ),
				'NA' => __('NAMIBIA', MS_TEXT_DOMAIN ),
				'NR' => __('NAURU', MS_TEXT_DOMAIN ),
				'NP' => __('NEPAL', MS_TEXT_DOMAIN ),
				'NL' => __('NETHERLANDS', MS_TEXT_DOMAIN ),
				'AN' => __('NETHERLANDS ANTILLES', MS_TEXT_DOMAIN ),
				'NC' => __('NEW CALEDONIA', MS_TEXT_DOMAIN ),
				'NZ' => __('NEW ZEALAND', MS_TEXT_DOMAIN ),
				'NI' => __('NICARAGUA', MS_TEXT_DOMAIN ),
				'NE' => __('NIGER', MS_TEXT_DOMAIN ),
				'NU' => __('NIUE', MS_TEXT_DOMAIN ),
				'NF' => __('NORFOLK ISLAND', MS_TEXT_DOMAIN ),
				'MP' => __('NORTHERN MARIANA ISLANDS', MS_TEXT_DOMAIN ),
				'NO' => __('NORWAY', MS_TEXT_DOMAIN ),
				'OM' => __('OMAN', MS_TEXT_DOMAIN ),
				'PW' => __('PALAU', MS_TEXT_DOMAIN ),
				'PS' => __('PALESTINE', MS_TEXT_DOMAIN ),
				'PA' => __('PANAMA', MS_TEXT_DOMAIN ),
				'PY' => __('PARAGUAY', MS_TEXT_DOMAIN ),
				'PE' => __('PERU', MS_TEXT_DOMAIN ),
				'PH' => __('PHILIPPINES', MS_TEXT_DOMAIN ),
				'PN' => __('PITCAIRN', MS_TEXT_DOMAIN ),
				'PL' => __('POLAND', MS_TEXT_DOMAIN ),
				'PT' => __('PORTUGAL', MS_TEXT_DOMAIN ),
				'PR' => __('PUERTO RICO', MS_TEXT_DOMAIN ),
				'QA' => __('QATAR', MS_TEXT_DOMAIN ),
				'RE' => __('REUNION', MS_TEXT_DOMAIN ),
				'RO' => __('ROMANIA', MS_TEXT_DOMAIN ),
				'RU' => __('RUSSIAN FEDERATION', MS_TEXT_DOMAIN ),
				'RW' => __('RWANDA', MS_TEXT_DOMAIN ),
				'SH' => __('SAINT HELENA', MS_TEXT_DOMAIN ),
				'KN' => __('SAINT KITTS AND NEVIS', MS_TEXT_DOMAIN ),
				'LC' => __('SAINT LUCIA', MS_TEXT_DOMAIN ),
				'PM' => __('SAINT PIERRE AND MIQUELON', MS_TEXT_DOMAIN ),
				'VC' => __('SAINT VINCENT AND THE GRENADINES', MS_TEXT_DOMAIN ),
				'WS' => __('SAMOA', MS_TEXT_DOMAIN ),
				'SM' => __('SAN MARINO', MS_TEXT_DOMAIN ),
				'ST' => __('SAO TOME AND PRINCIPE', MS_TEXT_DOMAIN ),
				'SA' => __('SAUDI ARABIA', MS_TEXT_DOMAIN ),
				'SN' => __('SENEGAL', MS_TEXT_DOMAIN ),
				'RS' => __('SERBIA', MS_TEXT_DOMAIN ),
				'SC' => __('SEYCHELLES', MS_TEXT_DOMAIN ),
				'SG' => __('SINGAPORE', MS_TEXT_DOMAIN ),
				'SK' => __('SLOVAKIA', MS_TEXT_DOMAIN ),
				'SI' => __('SLOVENIA', MS_TEXT_DOMAIN ),
				'SB' => __('SOLOMON ISLANDS', MS_TEXT_DOMAIN ),
				'ZA' => __('SOUTH AFRICA', MS_TEXT_DOMAIN ),
				'GS' => __('SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS', MS_TEXT_DOMAIN ),
				'ES' => __('SPAIN', MS_TEXT_DOMAIN ),
				'SR' => __('SURINAME', MS_TEXT_DOMAIN ),
				'SJ' => __('SVALBARD AND JAN MAYEN', MS_TEXT_DOMAIN ),
				'SZ' => __('SWAZILAND', MS_TEXT_DOMAIN ),
				'SE' => __('SWEDEN', MS_TEXT_DOMAIN ),
				'CH' => __('SWITZERLAND', MS_TEXT_DOMAIN ),
				'TW' => __('TAIWAN, PROVINCE OF CHINA', MS_TEXT_DOMAIN ),
				'TZ' => __('TANZANIA, UNITED REPUBLIC OF', MS_TEXT_DOMAIN ),
				'TH' => __('THAILAND', MS_TEXT_DOMAIN ),
				'TL' => __('TIMOR-LESTE', MS_TEXT_DOMAIN ),
				'TG' => __('TOGO', MS_TEXT_DOMAIN ),
				'TK' => __('TOKELAU', MS_TEXT_DOMAIN ),
				'TO' => __('TONGA', MS_TEXT_DOMAIN ),
				'TT' => __('TRINIDAD AND TOBAGO', MS_TEXT_DOMAIN ),
				'TN' => __('TUNISIA', MS_TEXT_DOMAIN ),
				'TR' => __('TURKEY', MS_TEXT_DOMAIN ),
				'TM' => __('TURKMENISTAN', MS_TEXT_DOMAIN ),
				'TC' => __('TURKS AND CAICOS ISLANDS', MS_TEXT_DOMAIN ),
				'TV' => __('TUVALU', MS_TEXT_DOMAIN ),
				'UG' => __('UGANDA', MS_TEXT_DOMAIN ),
				'UA' => __('UKRAINE', MS_TEXT_DOMAIN ),
				'AE' => __('UNITED ARAB EMIRATES', MS_TEXT_DOMAIN ),
				'GB' => __('UNITED KINGDOM', MS_TEXT_DOMAIN ),
				'US' => __('UNITED STATES', MS_TEXT_DOMAIN ),
				'UM' => __('UNITED STATES MINOR OUTLYING ISLANDS', MS_TEXT_DOMAIN ),
				'UY' => __('URUGUAY', MS_TEXT_DOMAIN ),
				'UZ' => __('UZBEKISTAN', MS_TEXT_DOMAIN ),
				'VU' => __('VANUATU', MS_TEXT_DOMAIN ),
				'VE' => __('VENEZUELA', MS_TEXT_DOMAIN ),
				'VN' => __('VIET NAM', MS_TEXT_DOMAIN ),
				'VG' => __('VIRGIN ISLANDS, BRITISH', MS_TEXT_DOMAIN ),
				'VI' => __('VIRGIN ISLANDS, U.S.', MS_TEXT_DOMAIN ),
				'WF' => __('WALLIS AND FUTUNA', MS_TEXT_DOMAIN ),
				'EH' => __('WESTERN SAHARA', MS_TEXT_DOMAIN ),
				'ZM' => __('ZAMBIA', MS_TEXT_DOMAIN ),
			)
		);
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