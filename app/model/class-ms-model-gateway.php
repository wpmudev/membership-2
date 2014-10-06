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

/**
 * Gateway parent model.
 * 
 * Register valid gateways.
 *
 * Persisted by parent class MS_Model_Option. Singleton. 
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Gateway extends MS_Model_Option {
	
	/**
	 * Gateway opertaion mode contants.
	 *
	 * @since 1.0.0
	 * @see $mode
	 * @var string The operation mode.
	 */
	const MODE_SANDBOX = 'sandbox';
	const MODE_LIVE    = 'live';
	
	/**
	 * Gateway types contants.
	 *
	 * @since 1.0.0
	 * @see $id
	 * @var string The type contants.
	 */
	const GATEWAY_FREE = 'free';
	const GATEWAY_MANUAL = 'manual';
	const GATEWAY_PAYPAL_SINGLE = 'paypal_single';
	const GATEWAY_PAYPAL_STANDARD = 'paypal_standard';
	const GATEWAY_AUTHORIZE = 'authorize';
	const GATEWAY_STRIPE = 'stripe';
	
	/**
	 * Singleton object.
	 *
	 * @since 1.0.0
	 * @see $type
	 * @var string The singleton object.
	 */
	public static $instance;
	
	/**
	 * Gateway ID. 
	 * 
	 * @since 1.0.0
	 * @var int $id
	 */
	protected $id = 'admin';
	
	/**
	 * Gateway name. 
	 * 
	 * @since 1.0.0
	 * @var string $name
	 */
	protected $name = 'Abstract Gateway';
	
	/**
	 * Gateway description. 
	 * 
	 * @since 1.0.0
	 * @var string $name
	 */
	protected $description = 'Abstract Gateway Desc';
	
	/**
	 * Gateway active status. 
	 * 
	 * @since 1.0.0
	 * @var string $active
	 */
	protected $active = false;
	
	/**
	 * Manual payment indicator.
	 * 
	 * If the gateway does not allow automatic reccuring billing.
	 * 
	 * @since 1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = true;
	
	/**
	 * Gateway allow Pro rating.
	 * 
	 * @todo To be released in further versions.
	 * @since 1.0.0
	 * @var bool $pro_rate
	 */
	protected $pro_rate = false;
	
	/**
	 * Custom payment button text or url.
	 *
	 * Overrides default purchase button.
	 *
	 * @since 1.0.0
	 * @var string $pay_button_url The url or button label (text).
	 */
	protected $pay_button_url;
	
	/**
	 * Custom cancel button text or url.
	 *
	 * Overrides default cancel button.
	 *
	 * @since 1.0.0
	 * @var string $cancel_button_url The url or button label (text).
	 */
	protected $cancel_button_url;
	
	/**
	 * Gateway operation mode.
	 *
	 * Live or sandbox (test) mode.
	 *
	 * @since 1.0.0
	 * @var string $mode
	 */
	protected $mode;
	
	/**
	 * Gateway list singleton instances.
	 *
	 * @since 1.0.0
	 * @var string $gateways
	 */
	protected static $gateways;
	
	/**
	 * Hook to process gateway returns (IPN).
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		if( $this->active ) {
			$this->add_action( "ms_model_gateway_handle_payment_return_{$this->id}", 'handle_return' );
		}
	}
	
	/**
	 * Load and get all registered gateways.
	 *
	 * @since 1.0.0
	 * @param bool $only_active Optional. When to return only activated gateways. 
	 */
	public static function get_gateways( $only_active = false ) {
		if( empty( self::$gateways ) ) {
			self::$gateways = array(
					self::GATEWAY_PAYPAL_STANDARD => MS_Factory::load( 'MS_Model_Gateway_Paypal_Standard' ),
					self::GATEWAY_PAYPAL_SINGLE => MS_Factory::load( 'MS_Model_Gateway_Paypal_Single' ),
					self::GATEWAY_AUTHORIZE => MS_Factory::load( 'MS_Model_Gateway_Authorize' ),
					self::GATEWAY_MANUAL => MS_Factory::load( 'MS_Model_Gateway_Manual' ),
					self::GATEWAY_FREE => MS_Factory::load( 'MS_Model_Gateway_Free' ),
					self::GATEWAY_STRIPE => MS_Factory::load( 'MS_Model_Gateway_Stripe' ),
			);
		}
		
		if( $only_active ) {
			$gateways = self::$gateways;
			foreach( $gateways as $id => $gateway ) {
				if( ! $gateway->active ) {
					unset( $gateways[ $id ] );
				}
			}
			return apply_filters( 'ms_model_gateway_get_gateways_active', $gateways );
		}
		
		return apply_filters( 'ms_model_gateway_get_gateways', self::$gateways );
	}
	
	/**
	 * Get all registered gateway names.
	 *
	 * @since 1.0.0
	 * @param bool $only_active Optional. False (default) returns only activated gateways.
	 * @param bool $include_gateway_free Optional. True (default) includes Gateway Free. 
	 */
	public static function get_gateway_names( $only_active = false, $include_gateway_free = false ) {
		
		$gateways = self::get_gateways( $only_active );
		$names = array();
		
		foreach( $gateways as $gateway ) {
			$names[ $gateway->id ] = $gateway->name;
		}
		if( ! $include_gateway_free ) {
			unset( $names[ self::GATEWAY_FREE ] );
		}
		
		return apply_filters( 'ms_model_gateway_get_gateway_names' , $names );
	}
	
	/**
	 * Validate gateway.
	 *
	 * @since 1.0.0
	 * @param string $gateway_id The gateway ID to validate. 
	 */
	public static function is_valid_gateway( $gateway_id ) {
		
		$valid = array_key_exists( $gateway_id, self::get_gateways() );
		
		return apply_filters( 'ms_model_gateway_is_valid_gateway', $valid );
	}
	
	/**
	 * Gateway factory.
	 *
	 * @since 1.0.0
	 * @param string $gateway_id The gateway ID to create. 
	 */
	public static function factory( $gateway_id ) {
		$gateway = null;
		
		if( 'admin' == $gateway_id || empty( $gateway_id ) || 'gateway' == $gateway_id ) {
			$gateway = new self();
		}
		elseif( self::is_valid_gateway( $gateway_id ) ) {
			$gateways = self::get_gateways();
			$gateway = $gateways[ $gateway_id ];
		}
		
		return apply_filters( 'ms_model_gateway_factory', $gateway, $gateway_id );
	}
	
	/**
	 * Processes gateway IPN return.
	 *
	 * Overridden in child gateway classes.
	 * 
	 * @since 1.0.0
	 */
	public function handle_return() {
		MS_Helper_Debug::log( __( 'Override the handle_return method of the child gateway: '. $this->id, MS_TEXT_DOMAIN ) );
	}
	
	/**
	 * Processes purchase action.
	 *
	 * Overridden in child classes. 
	 * This parent method only covers free purchases.
	 * 
	 * @since 1.0.0
	 */
	public function process_purchase( $ms_relationship ) {
		
		$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
		$invoice->gateway_id = $this->id;
		$invoice->save();
		
		if( 0 == $invoice->total ) {
			$this->process_transaction( $invoice );
		}
		
		return apply_filters( 'ms_model_gateway_process_purchase', $invoice );
	}
		
	/**
	 * Propagate membership cancelation to the gateway.
	 *
	 * Overridden in child classes. 
	 * 
	 * @since 1.0.0
	 */
	public function cancel_membership( $ms_relationship ) {
		MS_Helper_Debug::log( __( 'Override the cancel_membership method of the child gateway: '. $this->id, MS_TEXT_DOMAIN ) );
	}
	
	/**
	 * Request automatic payment to the gateway.
	 *
	 * Overridden in child gateway classes.
	 * 
	 * @since 1.0.0
	 *
	 * @access public
	 */
	public function request_payment( $ms_relationship ) {
		MS_Helper_Debug::log( __( 'Override the request_payment method of the child gateway: '. $this->id, MS_TEXT_DOMAIN ) );
	}
	
	/**
	 * Check for card expiration date.
	 * 
	 * Save event for card expire soon.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 */
	public function check_card_expiration( $ms_relationship ) {

		do_action( 'ms_model_gateway_check_card_expiration_before', $this );
		
		$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );
		$card_exp = $member->get_gateway_profile( $this->id, 'card_exp' );
		if( ! empty( $card_exp ) ) {
			$comm = MS_Model_Communication::get_communication( MS_Model_Communication::COMM_TYPE_CREDIT_CARD_EXPIRE );
		
			$days = MS_Helper_Period::get_period_in_days( $comm->period );
			$card_expire_days = MS_Helper_Period::subtract_dates( $card_exp, MS_Helper_Period::current_date() );
			if( $card_expire_days < 0 || ( $days == $card_expire_days ) ) {
				MS_Model_Event::save_event( MS_Model_Event::TYPE_CREDIT_CARD_EXPIRE, $ms_relationship );
			}
		}
		
		do_action( 'ms_model_gateway_check_card_expiration_after', $this );
	}
	
	/**
	 * Process transaction.
	 *
	 * Process transaction status change related to this membership relationship.
	 * Change status accordinly to transaction status.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Invoice $invoice The invoice to process.
	 * @return MS_Model_Invoice The processed invoice.
	 */
	public function process_transaction( $invoice ) {
	
		do_action( 'ms_model_gateway_process_transacation_before', $this );
		
		$ms_relationship = MS_Factory::load( 'MS_Model_Membership_Relationship', $invoice->ms_relationship_id );
		$member = MS_Factory::load( 'MS_Model_Member', $invoice->user_id );
		$membership = $ms_relationship->get_membership();
		
		switch( $invoice->status ) {
			case MS_Model_Invoice::STATUS_BILLED:
				break;
			case MS_Model_Invoice::STATUS_PAID:
				if( $invoice->total > 0 ) {
					MS_Model_Event::save_event( MS_Model_Event::TYPE_PAID, $ms_relationship );
				}
				if( $invoice->coupon_id ) {
					$coupon = MS_Factory::load( 'MS_Model_Coupon', $invoice->coupon_id );
					$coupon->remove_coupon_application( $member->id, $invoice->membership_id );
					$coupon->used++;
					$coupon->save();
				}

				/** Check for moving memberships */
				if( MS_Model_Membership_Relationship::STATUS_PENDING == $ms_relationship->status && $ms_relationship->move_from_id && 
					! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
					$move_from = MS_Model_Membership_Relationship::get_membership_relationship( $ms_relationship->user_id, $ms_relationship->move_from_id );
					if( $move_from->is_valid() ) {
						/** if allow pro rate, immediatly deactivate */
						if( $this->pro_rate && MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_PRO_RATE ) ) {
							$move_from->set_status( MS_Model_Membership_Relationship::STATUS_DEACTIVATED );
						}
						/** if not, cancel it, and allow using it until expires */
						else {
							$move_from->set_status( MS_Model_Membership_Relationship::STATUS_CANCELED );
						}
						$move_from->save();
					}
				}
				/* The trial period info gets updated after MS_Model_Membership_Relationship::config_period() */ 
				$trial_period = $ms_relationship->is_trial_eligible();
				$ms_relationship->current_invoice_number = max( $ms_relationship->current_invoice_number, $invoice->invoice_number + 1 );
				$member->active = true;
				$ms_relationship->config_period();
				$ms_relationship->set_status( MS_Model_Membership_Relationship::STATUS_ACTIVE );
				
				/** Generate next invoice */
				if( MS_Model_Membership::PAYMENT_TYPE_RECURRING == $membership->payment_type || $trial_period ) {
					$next_invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
					$next_invoice->gateway_id = $this->id;
					$next_invoice->save();
				}
				break;
			case MS_Model_Invoice::STATUS_FAILED:
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $ms_relationship );
				break;	
			case MS_Model_Invoice::STATUS_DENIED:
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_DENIED, $ms_relationship );
				break;	
			case MS_Model_Invoice::STATUS_PENDING:
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_PENDING, $ms_relationship );
				break;
			default:
				do_action( 'ms_model_gateway_process_transaction', $invoice );
				break;
		}
		$member->save();
		$ms_relationship->gateway_id = $this->id;
		$ms_relationship->save();
		$invoice->gateway_id = $this->id;
		$invoice->save();

		do_action( 'ms_model_gateway_process_transacation_after', $this );
		
		return apply_filters( 'ms_model_gateway_processed_transaction', $invoice, $this );
	}
	
	/**
	 * Url that fires handle_return of this gateway (IPN).
	 * 
	 * @since 1.0.0
	 * @return string The return url.
	 */
	public function get_return_url() {
		
		$return_url = site_url( '/ms-payment-return/' . $this->id );
		
		return apply_filters( 'ms_model_gateway_get_return_url', $return_url, $this );
	}
	
	/**
	 * Get gateway mode types.
	 *
	 * @since 1.0.0
	 * @return array {
	 * 		Returns array of ( $mode_type => $description ).
	 * 		@type string $mode_type The mode type.
	 * 		@type string $description The mode type description.
	 * }
	 */
	public function get_mode_types() {
		$mode_types = array(
				self::MODE_LIVE => __( 'Live Site', MS_TEXT_DOMAIN ),
				self::MODE_SANDBOX => __( 'Sandbox Mode (test)', MS_TEXT_DOMAIN ),
		);
		
		return apply_filters( 'ms_model_gateway_get_mode_types', $mode_types, $this );
	}
	
	/**
	 * Return if is live mode.
	 * 
	 * @since 1.0.0
	 * 
	 * @return boolean True if is in live mode.
	 */
	public function is_live_mode() {
		$is_live_mode = ( self::MODE_LIVE == $this->mode );
		return apply_filters( 'ms_model_gateway_is_live_mode', $is_live_mode );
	}
	
	/**
	 * Verify required fields.
	 * 
	 * To be overridden in children classes.
	 *
	 * @since 1.0
	 *
	 * @return boolean
	 */
	public function is_configured() {
		MS_Helper_Debug::log( __( 'Override the is_configured method of the child gateway: '. $this->id, MS_TEXT_DOMAIN ) );
		return false;
	}
	
	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'id':
				case 'name':
					break;
				case 'description':
				case 'pay_button_url':
				case 'upggrade_button_url':
				case 'cancel_button_url':
					$this->$property = sanitize_text_field( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
		
		do_action( 'ms_model_gateway__set_after', $property, $value, $this );
	}
	
	/**
	 * Get countries code and names.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 * 		Returns array of ( $code => $name ).
	 * 		@type string $code The country code.
	 * 		@type string $name The country name.
	 * }
	 */
	public function get_country_codes() {
		$countries = array(
			'' => __( 'Select country', MS_TEXT_DOMAIN ),
			'AX' => __( 'ÃLAND ISLANDS', MS_TEXT_DOMAIN ),
			'AL' => __( 'ALBANIA', MS_TEXT_DOMAIN ),
			'DZ' => __( 'ALGERIA', MS_TEXT_DOMAIN ),
			'AS' => __( 'AMERICAN SAMOA', MS_TEXT_DOMAIN ),
			'AD' => __( 'ANDORRA', MS_TEXT_DOMAIN ),
			'AI' => __( 'ANGUILLA', MS_TEXT_DOMAIN ),
			'AQ' => __( 'ANTARCTICA', MS_TEXT_DOMAIN ),
			'AG' => __( 'ANTIGUA AND BARBUDA', MS_TEXT_DOMAIN ),
			'AR' => __( 'ARGENTINA', MS_TEXT_DOMAIN ),
			'AM' => __( 'ARMENIA', MS_TEXT_DOMAIN ),
			'AW' => __( 'ARUBA', MS_TEXT_DOMAIN ),
			'AU' => __( 'AUSTRALIA', MS_TEXT_DOMAIN ),
			'AT' => __( 'AUSTRIA', MS_TEXT_DOMAIN ),
			'AZ' => __( 'AZERBAIJAN', MS_TEXT_DOMAIN ),
			'BS' => __( 'BAHAMAS', MS_TEXT_DOMAIN ),
			'BH' => __( 'BAHRAIN', MS_TEXT_DOMAIN ),
			'BD' => __( 'BANGLADESH', MS_TEXT_DOMAIN ),
			'BB' => __( 'BARBADOS', MS_TEXT_DOMAIN ),
			'BE' => __( 'BELGIUM', MS_TEXT_DOMAIN ),
			'BZ' => __( 'BELIZE', MS_TEXT_DOMAIN ),
			'BJ' => __( 'BENIN', MS_TEXT_DOMAIN ),
			'BM' => __( 'BERMUDA', MS_TEXT_DOMAIN ),
			'BT' => __( 'BHUTAN', MS_TEXT_DOMAIN ),
			'BA' => __( 'BOSNIA-HERZEGOVINA', MS_TEXT_DOMAIN ),
			'BW' => __( 'BOTSWANA', MS_TEXT_DOMAIN ),
			'BV' => __( 'BOUVET ISLAND', MS_TEXT_DOMAIN ),
			'BR' => __( 'BRAZIL', MS_TEXT_DOMAIN ),
			'IO' => __( 'BRITISH INDIAN OCEAN TERRITORY', MS_TEXT_DOMAIN ),
			'BN' => __( 'BRUNEI DARUSSALAM', MS_TEXT_DOMAIN ),
			'BG' => __( 'BULGARIA', MS_TEXT_DOMAIN ),
			'BF' => __( 'BURKINA FASO', MS_TEXT_DOMAIN ),
			'CA' => __( 'CANADA', MS_TEXT_DOMAIN ),
			'CV' => __( 'CAPE VERDE', MS_TEXT_DOMAIN ),
			'KY' => __( 'CAYMAN ISLANDS', MS_TEXT_DOMAIN ),
			'CF' => __( 'CENTRAL AFRICAN REPUBLIC', MS_TEXT_DOMAIN ),
			'CL' => __( 'CHILE', MS_TEXT_DOMAIN ),
			'CN' => __( 'CHINA', MS_TEXT_DOMAIN ),
			'CX' => __( 'CHRISTMAS ISLAND', MS_TEXT_DOMAIN ),
			'CC' => __( 'COCOS (KEELING) ISLANDS', MS_TEXT_DOMAIN ),
			'CO' => __( 'COLOMBIA', MS_TEXT_DOMAIN ),
			'CK' => __( 'COOK ISLANDS', MS_TEXT_DOMAIN ),
			'CR' => __( 'COSTA RICA', MS_TEXT_DOMAIN ),
			'CY' => __( 'CYPRUS', MS_TEXT_DOMAIN ),
			'CZ' => __( 'CZECH REPUBLIC', MS_TEXT_DOMAIN ),
			'DK' => __( 'DENMARK', MS_TEXT_DOMAIN ),
			'DJ' => __( 'DJIBOUTI', MS_TEXT_DOMAIN ),
			'DM' => __( 'DOMINICA', MS_TEXT_DOMAIN ),
			'DO' => __( 'DOMINICAN REPUBLIC', MS_TEXT_DOMAIN ),
			'EC' => __( 'ECUADOR', MS_TEXT_DOMAIN ),
			'EG' => __( 'EGYPT', MS_TEXT_DOMAIN ),
			'SV' => __( 'EL SALVADOR', MS_TEXT_DOMAIN ),
			'EE' => __( 'ESTONIA', MS_TEXT_DOMAIN ),
			'FK' => __( 'FALKLAND ISLANDS (MALVINAS)', MS_TEXT_DOMAIN ),
			'FO' => __( 'FAROE ISLANDS', MS_TEXT_DOMAIN ),
			'FJ' => __( 'FIJI', MS_TEXT_DOMAIN ),
			'FI' => __( 'FINLAND', MS_TEXT_DOMAIN ),
			'FR' => __( 'FRANCE', MS_TEXT_DOMAIN ),
			'GF' => __( 'FRENCH GUIANA', MS_TEXT_DOMAIN ),
			'PF' => __( 'FRENCH POLYNESIA', MS_TEXT_DOMAIN ),
			'TF' => __( 'FRENCH SOUTHERN TERRITORIES', MS_TEXT_DOMAIN ),
			'GA' => __( 'GABON', MS_TEXT_DOMAIN ),
			'GM' => __( 'GAMBIA', MS_TEXT_DOMAIN ),
			'GE' => __( 'GEORGIA', MS_TEXT_DOMAIN ),
			'DE' => __( 'GERMANY', MS_TEXT_DOMAIN ),
			'GH' => __( 'GHANA', MS_TEXT_DOMAIN ),
			'GI' => __( 'GIBRALTAR', MS_TEXT_DOMAIN ),
			'GR' => __( 'GREECE', MS_TEXT_DOMAIN ),
			'GL' => __( 'GREENLAND', MS_TEXT_DOMAIN ),
			'GD' => __( 'GRENADA', MS_TEXT_DOMAIN ),
			'GP' => __( 'GUADELOUPE', MS_TEXT_DOMAIN ),
			'GU' => __( 'GUAM', MS_TEXT_DOMAIN ),
			'GG' => __( 'GUERNSEY', MS_TEXT_DOMAIN ),
			'GY' => __( 'GUYANA', MS_TEXT_DOMAIN ),
			'HM' => __( 'HEARD ISLAND AND MCDONALD ISLANDS', MS_TEXT_DOMAIN ),
			'VA' => __( 'HOLY SEE (VATICAN CITY STATE)', MS_TEXT_DOMAIN ),
			'HN' => __( 'HONDURAS', MS_TEXT_DOMAIN ),
			'HK' => __( 'HONG KONG', MS_TEXT_DOMAIN ),
			'HU' => __( 'HUNGARY', MS_TEXT_DOMAIN ),
			'IS' => __( 'ICELAND', MS_TEXT_DOMAIN ),
			'IN' => __( 'INDIA', MS_TEXT_DOMAIN ),
			'ID' => __( 'INDONESIA', MS_TEXT_DOMAIN ),
			'IE' => __( 'IRELAND', MS_TEXT_DOMAIN ),
			'IM' => __( 'ISLE OF MAN', MS_TEXT_DOMAIN ),
			'IL' => __( 'ISRAEL', MS_TEXT_DOMAIN ),
			'IT' => __( 'ITALY', MS_TEXT_DOMAIN ),
			'JM' => __( 'JAMAICA', MS_TEXT_DOMAIN ),
			'JP' => __( 'JAPAN', MS_TEXT_DOMAIN ),
			'JE' => __( 'JERSEY', MS_TEXT_DOMAIN ),
			'JO' => __( 'JORDAN', MS_TEXT_DOMAIN ),
			'KZ' => __( 'KAZAKHSTAN', MS_TEXT_DOMAIN ),
			'KI' => __( 'KIRIBATI', MS_TEXT_DOMAIN ),
			'KR' => __( 'KOREA, REPUBLIC OF', MS_TEXT_DOMAIN ),
			'KW' => __( 'KUWAIT', MS_TEXT_DOMAIN ),
			'KG' => __( 'KYRGYZSTAN', MS_TEXT_DOMAIN ),
			'LV' => __( 'LATVIA', MS_TEXT_DOMAIN ),
			'LS' => __( 'LESOTHO', MS_TEXT_DOMAIN ),
			'LI' => __( 'LIECHTENSTEIN', MS_TEXT_DOMAIN ),
			'LT' => __( 'LITHUANIA', MS_TEXT_DOMAIN ),
			'LU' => __( 'LUXEMBOURG', MS_TEXT_DOMAIN ),
			'MO' => __( 'MACAO', MS_TEXT_DOMAIN ),
			'MK' => __( 'MACEDONIA', MS_TEXT_DOMAIN ),
			'MG' => __( 'MADAGASCAR', MS_TEXT_DOMAIN ),
			'MW' => __( 'MALAWI', MS_TEXT_DOMAIN ),
			'MY' => __( 'MALAYSIA', MS_TEXT_DOMAIN ),
			'MT' => __( 'MALTA', MS_TEXT_DOMAIN ),
			'MH' => __( 'MARSHALL ISLANDS', MS_TEXT_DOMAIN ),
			'MQ' => __( 'MARTINIQUE', MS_TEXT_DOMAIN ),
			'MR' => __( 'MAURITANIA', MS_TEXT_DOMAIN ),
			'MU' => __( 'MAURITIUS', MS_TEXT_DOMAIN ),
			'YT' => __( 'MAYOTTE', MS_TEXT_DOMAIN ),
			'MX' => __( 'MEXICO', MS_TEXT_DOMAIN ),
			'FM' => __( 'MICRONESIA, FEDERATED STATES OF', MS_TEXT_DOMAIN ),
			'MD' => __( 'MOLDOVA, REPUBLIC OF', MS_TEXT_DOMAIN ),
			'MC' => __( 'MONACO', MS_TEXT_DOMAIN ),
			'MN' => __( 'MONGOLIA', MS_TEXT_DOMAIN ),
			'ME' => __( 'MONTENEGRO', MS_TEXT_DOMAIN ),
			'MS' => __( 'MONTSERRAT', MS_TEXT_DOMAIN ),
			'MA' => __( 'MOROCCO', MS_TEXT_DOMAIN ),
			'MZ' => __( 'MOZAMBIQUE', MS_TEXT_DOMAIN ),
			'NA' => __( 'NAMIBIA', MS_TEXT_DOMAIN ),
			'NR' => __( 'NAURU', MS_TEXT_DOMAIN ),
			'NP' => __( 'NEPAL', MS_TEXT_DOMAIN ),
			'NL' => __( 'NETHERLANDS', MS_TEXT_DOMAIN ),
			'AN' => __( 'NETHERLANDS ANTILLES', MS_TEXT_DOMAIN ),
			'NC' => __( 'NEW CALEDONIA', MS_TEXT_DOMAIN ),
			'NZ' => __( 'NEW ZEALAND', MS_TEXT_DOMAIN ),
			'NI' => __( 'NICARAGUA', MS_TEXT_DOMAIN ),
			'NE' => __( 'NIGER', MS_TEXT_DOMAIN ),
			'NU' => __( 'NIUE', MS_TEXT_DOMAIN ),
			'NF' => __( 'NORFOLK ISLAND', MS_TEXT_DOMAIN ),
			'MP' => __( 'NORTHERN MARIANA ISLANDS', MS_TEXT_DOMAIN ),
			'NO' => __( 'NORWAY', MS_TEXT_DOMAIN ),
			'OM' => __( 'OMAN', MS_TEXT_DOMAIN ),
			'PW' => __( 'PALAU', MS_TEXT_DOMAIN ),
			'PS' => __( 'PALESTINE', MS_TEXT_DOMAIN ),
			'PA' => __( 'PANAMA', MS_TEXT_DOMAIN ),
			'PY' => __( 'PARAGUAY', MS_TEXT_DOMAIN ),
			'PE' => __( 'PERU', MS_TEXT_DOMAIN ),
			'PH' => __( 'PHILIPPINES', MS_TEXT_DOMAIN ),
			'PN' => __( 'PITCAIRN', MS_TEXT_DOMAIN ),
			'PL' => __( 'POLAND', MS_TEXT_DOMAIN ),
			'PT' => __( 'PORTUGAL', MS_TEXT_DOMAIN ),
			'PR' => __( 'PUERTO RICO', MS_TEXT_DOMAIN ),
			'QA' => __( 'QATAR', MS_TEXT_DOMAIN ),
			'RE' => __( 'REUNION', MS_TEXT_DOMAIN ),
			'RO' => __( 'ROMANIA', MS_TEXT_DOMAIN ),
			'RU' => __( 'RUSSIAN FEDERATION', MS_TEXT_DOMAIN ),
			'RW' => __( 'RWANDA', MS_TEXT_DOMAIN ),
			'SH' => __( 'SAINT HELENA', MS_TEXT_DOMAIN ),
			'KN' => __( 'SAINT KITTS AND NEVIS', MS_TEXT_DOMAIN ),
			'LC' => __( 'SAINT LUCIA', MS_TEXT_DOMAIN ),
			'PM' => __( 'SAINT PIERRE AND MIQUELON', MS_TEXT_DOMAIN ),
			'VC' => __( 'SAINT VINCENT AND THE GRENADINES', MS_TEXT_DOMAIN ),
			'WS' => __( 'SAMOA', MS_TEXT_DOMAIN ),
			'SM' => __( 'SAN MARINO', MS_TEXT_DOMAIN ),
			'ST' => __( 'SAO TOME AND PRINCIPE', MS_TEXT_DOMAIN ),
			'SA' => __( 'SAUDI ARABIA', MS_TEXT_DOMAIN ),
			'SN' => __( 'SENEGAL', MS_TEXT_DOMAIN ),
			'RS' => __( 'SERBIA', MS_TEXT_DOMAIN ),
			'SC' => __( 'SEYCHELLES', MS_TEXT_DOMAIN ),
			'SG' => __( 'SINGAPORE', MS_TEXT_DOMAIN ),
			'SK' => __( 'SLOVAKIA', MS_TEXT_DOMAIN ),
			'SI' => __( 'SLOVENIA', MS_TEXT_DOMAIN ),
			'SB' => __( 'SOLOMON ISLANDS', MS_TEXT_DOMAIN ),
			'ZA' => __( 'SOUTH AFRICA', MS_TEXT_DOMAIN ),
			'GS' => __( 'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS', MS_TEXT_DOMAIN ),
			'ES' => __( 'SPAIN', MS_TEXT_DOMAIN ),
			'SR' => __( 'SURINAME', MS_TEXT_DOMAIN ),
			'SJ' => __( 'SVALBARD AND JAN MAYEN', MS_TEXT_DOMAIN ),
			'SZ' => __( 'SWAZILAND', MS_TEXT_DOMAIN ),
			'SE' => __( 'SWEDEN', MS_TEXT_DOMAIN ),
			'CH' => __( 'SWITZERLAND', MS_TEXT_DOMAIN ),
			'TW' => __( 'TAIWAN, PROVINCE OF CHINA', MS_TEXT_DOMAIN ),
			'TZ' => __( 'TANZANIA, UNITED REPUBLIC OF', MS_TEXT_DOMAIN ),
			'TH' => __( 'THAILAND', MS_TEXT_DOMAIN ),
			'TL' => __( 'TIMOR-LESTE', MS_TEXT_DOMAIN ),
			'TG' => __( 'TOGO', MS_TEXT_DOMAIN ),
			'TK' => __( 'TOKELAU', MS_TEXT_DOMAIN ),
			'TO' => __( 'TONGA', MS_TEXT_DOMAIN ),
			'TT' => __( 'TRINIDAD AND TOBAGO', MS_TEXT_DOMAIN ),
			'TN' => __( 'TUNISIA', MS_TEXT_DOMAIN ),
			'TR' => __( 'TURKEY', MS_TEXT_DOMAIN ),
			'TM' => __( 'TURKMENISTAN', MS_TEXT_DOMAIN ),
			'TC' => __( 'TURKS AND CAICOS ISLANDS', MS_TEXT_DOMAIN ),
			'TV' => __( 'TUVALU', MS_TEXT_DOMAIN ),
			'UG' => __( 'UGANDA', MS_TEXT_DOMAIN ),
			'UA' => __( 'UKRAINE', MS_TEXT_DOMAIN ),
			'AE' => __( 'UNITED ARAB EMIRATES', MS_TEXT_DOMAIN ),
			'GB' => __( 'UNITED KINGDOM', MS_TEXT_DOMAIN ),
			'US' => __( 'UNITED STATES', MS_TEXT_DOMAIN ),
			'UM' => __( 'UNITED STATES MINOR OUTLYING ISLANDS', MS_TEXT_DOMAIN ),
			'UY' => __( 'URUGUAY', MS_TEXT_DOMAIN ),
			'UZ' => __( 'UZBEKISTAN', MS_TEXT_DOMAIN ),
			'VU' => __( 'VANUATU', MS_TEXT_DOMAIN ),
			'VE' => __( 'VENEZUELA', MS_TEXT_DOMAIN ),
			'VN' => __( 'VIET NAM', MS_TEXT_DOMAIN ),
			'VG' => __( 'VIRGIN ISLANDS, BRITISH', MS_TEXT_DOMAIN ),
			'VI' => __( 'VIRGIN ISLANDS, U.S.', MS_TEXT_DOMAIN ),
			'WF' => __( 'WALLIS AND FUTUNA', MS_TEXT_DOMAIN ),
			'EH' => __( 'WESTERN SAHARA', MS_TEXT_DOMAIN ),
			'ZM' => __( 'ZAMBIA', MS_TEXT_DOMAIN ),
		);
		
		return apply_filters( 'ms_model_gateway_get_country_codes', $countries );
	}
}