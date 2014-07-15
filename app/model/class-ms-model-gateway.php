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

class MS_Model_Gateway extends MS_Model_Option {
	
	const MODE_SANDBOX = 'sandbox';
	const MODE_LIVE    = 'live';
	
	const GATEWAY_FREE = 'free';
	const GATEWAY_MANUAL = 'manual';
	const GATEWAY_PAYPAL_SINGLE = 'paypal_single';
	const GATEWAY_PAYPAL_STANDARD = 'paypal_standard';
	const GATEWAY_AUTHORIZE = 'authorize';
	const GATEWAY_STRIPE = 'stripe';
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id = 'gateway';
	
	protected $name = 'Abstract Gateway';
	
	protected $description = 'Abstract Gateway Desc';
	
	protected $active = false;
	
	protected $manual_payment = true;
	
	protected $pro_rate = false;
	
	protected $pay_button_url;
	
	protected $upgrade_button_url;
	
	protected $cancel_button_url;
	
	protected $mode;
	
	protected static $gateways;
	
	public function after_load() {
		if( $this->active ) {
			$this->add_action( 'ms_view_registration_payment_form', 'purchase_button', 10, 4 );
			$this->add_action( "ms_model_gateway_handle_payment_return_{$this->id}", 'handle_return' );
		}
	}
	
	public static function get_gateways( $only_active = false ) {
		if( empty( self::$gateways ) ) {
			self::$gateways = array(
// 				self::GATEWAY_FREE => MS_Model_Gateway_Free::load(),
				self::GATEWAY_MANUAL => MS_Model_Gateway_Manual::load(),
				self::GATEWAY_PAYPAL_STANDARD => MS_Model_Gateway_Paypal_Standard::load(),
				self::GATEWAY_PAYPAL_SINGLE => MS_Model_Gateway_Paypal_Single::load(),
				self::GATEWAY_AUTHORIZE => MS_Model_Gateway_Authorize::load(),
				self::GATEWAY_STRIPE => MS_Model_Gateway_Stripe::load(),
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
	
	public static function get_gateway_names( $only_active = false ) {
		$gateways = self::get_gateways( $only_active );
		$names = array();
		foreach( $gateways as $gateway ) {
			$names[ $gateway->id ] = $gateway->name;
		}
		return apply_filters( 'ms_model_gateway_get_gateway_names' , $names );
	}
	
	public static function is_valid_gateway( $gateway_id ) {
		return apply_filters( 'ms_model_gateway_is_valid_gateway', array_key_exists( $gateway_id, self::get_gateways() ) );
	}
	
	public static function factory( $gateway_id ) {
		$gateway = null;
		
		if( 'admin' == $gateway_id ) {
			return new self();
		}
		elseif( self::is_valid_gateway( $gateway_id ) ) {
			$gateways = self::get_gateways();
			$gateway = $gateways[ $gateway_id ];
		}
		
		return apply_filters( 'ms_model_gateway_factory', $gateway, $gateway_id );
	}
	
	/**
	 * Render purchase button.
	 *
	 * @since 4.0
	 *
	 * @access public
	 */
	public function purchase_button( $ms_relationship = false ) {
		
	}
	
	/**
	 * Processes gateway IPN return.
	 *
	 * @since 4.0
	 *
	 * @access public
	 */
	public function handle_return() {
		
	}
	
	/**
	 * Processes purchase action.
	 *
	 * @since 4.0
	 *
	 * @access public
	 */
	public function process_purchase( $ms_relationship ) {
		/** Change the query to show memberships special page and replace the content with payment instructions */
		global $wp_query;
		$settings = MS_Plugin::instance()->settings;
		$wp_query->query_vars['page_id'] = $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS );
		$wp_query->query_vars['post_type'] = 'page';

		if( ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $this->id .'_' . $_POST['ms_relationship_id'] ) ) {
		
			$invoice = $ms_relationship->get_current_invoice();

			if( 0 == $invoice->total ) {
				$this->process_transaction( $invoice );
			}

			if( MS_Model_Membership_Relationship::STATUS_PENDING != $ms_relationship->status ) {
				$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) );
				wp_safe_redirect( $url );
				exit;
			}
			else{
				$this->add_action( 'the_content', 'content' );
			}
		}
		else {
			$this->add_action( 'the_content', 'content_error' );
		}
	}
	
	public function content() {
		return '';
	}
	
	public function content_error() {
		return __( 'Sorry, your signup request has failed. Try again.', MS_TEXT_DOMAIN );
	}
	
	/**
	 * Propagate membership cancelation to the gateway.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function cancel_membership( $ms_relationship ) {
		
	}
	
	/**
	 * Request automatic payment to the gateway.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function request_payment( $ms_relationship ) {
		
	}
	
	/**
	 * Process transaction.
	 *
	 * Process transaction status change related to this membership relationship.
	 * Change status accordinly to transaction status.
	 *
	 * @param MS_Model_Invoice $invoice The Transaction.
	 */
	public function process_transaction( $invoice ) {
	
		$ms_relationship = MS_Model_Membership_Relationship::load( $invoice->ms_relationship_id );
		$member = MS_Model_Member::load( $invoice->user_id );
		switch( $invoice->status ) {
			case MS_Model_Invoice::STATUS_BILLED:
				break;
			case MS_Model_Invoice::STATUS_PAID:
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAID );
				if( $invoice->coupon_id ) {
					$coupon = MS_Model_Coupon::load( $invoice->coupon_id );
					$coupon->remove_coupon_application( $member->id, $invoice->membership_id );
					$coupon->used++;
					$coupon->save();
					
					/** Send invoice paid communication. */
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_INVOICE ];
					$comm->add_to_queue( $invoice->ms_relationship_id );	
				}
				
				/** Check for moving memberships */
				if( MS_Model_Membership_Relationship::STATUS_PENDING == $ms_relationship->status && $ms_relationship->move_from_id && 
					! MS_Plugin::instance()->addon->multiple_membership) {
					
					$move_from = MS_Model_Membership_Relationship::get_membership_relationship( $ms_relationship->user_id, $ms_relationship->move_from_id );
					if( ! empty( $move_from->id ) ) {
						/** if allow pro rate, immediatly deactivate */
						if( $this->pro_rate ) {
							$move_from->status = MS_Model_Membership_Relationship::STATUS_DEACTIVATED;
						}
						/** if not, cancel it, and allow using it until expires */
						else {
							$move_from->status = MS_Model_Membership_Relationship::STATUS_CANCELED;
						}
						$move_from->save();
					}
				}
				
				$ms_relationship->current_invoice_number = max( $ms_relationship->current_invoice_number, $invoice->invoice_number + 1 );
				$member->active = true;
				$ms_relationship->config_period();
				$ms_relationship->status = MS_Model_Membership_Relationship::STATUS_ACTIVE;
				break;
			case MS_Model_Invoice::STATUS_FAILED:
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED );
				break;	
			case MS_Model_Invoice::STATUS_DENIED:
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_DENIED );
				break;	
			case MS_Model_Invoice::STATUS_PENDING:
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_PENDING );
				break;
			default:
				do_action( 'ms_model_gateway_process_transaction', $invoice );
				break;
		}
		$member->save();
		$ms_relationship->gateway_id = $invoice->gateway_id;
		$ms_relationship->save();
	}
	
	/**
	 * Url that fires handle_return of this gateway.
	 * 
	 * @return string The return url.
	 */
	public function get_return_url() {
		return apply_filters( 'ms_model_gateway_get_return_url', site_url( '/ms-payment-return/' . $this->id ), $this->id );
	}
	
	/**
	 * Get gateway mode types.
	 *
	 * @since 4.0
	 *
	 */
	public function get_mode_types() {
		return apply_filters( 'ms_model_gateway_get_mode_types', array(
				self::MODE_LIVE => __( 'Live Site', MS_TEXT_DOMAIN ),
				self::MODE_SANDBOX => __( 'Sandbox Mode (test)', MS_TEXT_DOMAIN ),
		) );
	}
	
	/**
	 * Validate specific property before set.
	 *
	 * @since 4.0
	 *
	 * @access public
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
	}
	
	public function get_country_codes() {
		return apply_filters( 'ms_model_gateway_get_country_codes', array(
				'' => __('Select country', MS_TEXT_DOMAIN ),
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
	 * Returns user IP address.
	 *
	 * @since 4.0.0
	 *
	 * @static
	 * @access protected
	 * @return string Remote IP address on success, otherwise FALSE.
	 */
	protected static function get_remote_ip() {
		$flag = ! WP_DEBUG ? FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE : null;
		$keys = array(
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_X_CLUSTER_CLIENT_IP',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',
				'REMOTE_ADDR',
		);
	
		$remote_ip = false;
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( array_filter( array_map( 'trim', explode( ',', $_SERVER[$key] ) ) ) as $ip ) {
					if ( filter_var( $ip, FILTER_VALIDATE_IP, $flag ) !== false ) {
						$remote_ip = $ip;
						break;
					}
				}
			}
		}
	
		return $remote_ip;
	}
}