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
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 1.1.0
 * @package Membership
 * @subpackage Model
 */
class MS_Gateway extends MS_Model_Option {

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
	protected $name = '';

	/**
	 * Gateway description.
	 *
	 * @since 1.0.0
	 * @var string $description
	 */
	protected $description = '';

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
	 * True: Recurring payments need to be made manually.
	 * False: Gateway is capable of automatic recurring payments.
	 *
	 * @since 1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = true;

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
	 * Hook to process gateway returns (IPN).
	 *
	 * @see MS_Controller_Gateway: handle_payment_return()
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		do_action( 'ms_gateway_after_load', $this );

		if ( $this->active ) {
			$this->add_action(
				'ms_gateway_handle_payment_return_' . $this->id,
				'handle_return'
			);
		}

		$this->add_filter( 'ms_model_gateway_register', 'register' );
	}

	/**
	 * Registers the Gateway
	 *
	 * @since  1.1.0
	 * @param  array $list The gateway list.
	 * @return array The updated gateway list.
	 */
	public function register( $list ) {
		$class = get_class( $this );
		$id = constant( $class . '::ID' );

		$list[$id] = $class;

		return $list;
	}

	/**
	 * Processes gateway IPN return.
	 *
	 * Overridden in child gateway classes.
	 *
	 * @since 1.0.0
	 */
	public function handle_return() {
		do_action(
			'ms_gateway_handle_return',
			$ms_relationship,
			$this
		);
	}

	/**
	 * Processes purchase action.
	 *
	 * This function is called when a payment was made: We check if the
	 * transaction was successful. If it was we call `$invoice->changed()` which
	 * will update the membership status accordingly.
	 *
	 * Overridden in child classes.
	 * This parent method only covers free purchases.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $ms_relationship The related membership relationship.
	 */
	public function process_purchase( $ms_relationship ) {
		do_action(
			'ms_gateway_process_purchase_before',
			$ms_relationship,
			$this
		);

		$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
		$invoice->gateway_id = $this->id;
		$invoice->save();

		// The default handler only processes free subscriptions.
		if ( 0 == $invoice->total ) {
			$invoice->changed();
		}

		return apply_filters(
			'ms_gateway_process_purchase',
			$invoice
		);
	}

	/**
	 * Propagate membership cancelation to the gateway.
	 *
	 * Overridden in child classes.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $ms_relationship The membership relationship.
	 */
	public function cancel_membership( $ms_relationship ) {
		do_action(
			'ms_gateway_cancel_membership',
			$ms_relationship,
			$this
		);
	}

	/**
	 * Request automatic payment to the gateway.
	 *
	 * Overridden in child gateway classes.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $ms_relationship The membership relationship.
	 * @return bool True on success.
	 */
	public function request_payment( $ms_relationship ) {
		do_action(
			'ms_gateway_request_payment',
			$ms_relationship,
			$this
		);

		// Default to "Payment successful"
		return true;
	}

	/**
	 * Check for card expiration date.
	 *
	 * Save event for card expire soon.
	 *
	 * @since 1.0.0
	 *
	 * @access protected
	 * @param MS_Model_Relationship $ms_relationship The membership relationship.
	 */
	public function check_card_expiration( $ms_relationship ) {
		do_action( 'ms_gateway_check_card_expiration_before', $this );

		$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );
		$card_exp = $member->get_gateway_profile( $this->id, 'card_exp' );

		if ( ! empty( $card_exp ) ) {
			$comm = MS_Model_Communication::get_communication( MS_Model_Communication::COMM_TYPE_CREDIT_CARD_EXPIRE );

			$days = MS_Helper_Period::get_period_in_days( $comm->period['period_unit'], $comm->period['period_type'] );
			$card_expire_days = MS_Helper_Period::subtract_dates( $card_exp, MS_Helper_Period::current_date() );
			if ( $card_expire_days < 0 || ( $days == $card_expire_days ) ) {
				MS_Model_Event::save_event( MS_Model_Event::TYPE_CREDIT_CARD_EXPIRE, $ms_relationship );
			}
		}

		do_action(
			'ms_gateway_check_card_expiration_after',
			$this
		);
	}

	/**
	 * Url that fires handle_return of this gateway (IPN).
	 *
	 * @since 1.0.0
	 * @return string The return url.
	 */
	public function get_return_url() {
		$return_url = home_url( '/ms-payment-return/' . $this->id );

		return apply_filters(
			'ms_gateway_get_return_url',
			$return_url,
			$this
		);
	}

	/**
	 * Get gateway mode types.
	 *
	 * @since 1.0.0
	 * @return array {
	 *     Returns array of ( $mode_type => $description ).
	 *     @type string $mode_type The mode type.
	 *     @type string $description The mode type description.
	 * }
	 */
	public function get_mode_types() {
		$mode_types = array(
			self::MODE_LIVE => __( 'Live Site', MS_TEXT_DOMAIN ),
			self::MODE_SANDBOX => __( 'Sandbox Mode (test)', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_gateway_get_mode_types',
			$mode_types,
			$this
		);
	}

	/**
	 * Return if is live mode.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if is in live mode.
	 */
	public function is_live_mode() {
		$is_live_mode = ( self::MODE_SANDBOX !== $this->mode );

		return apply_filters(
			'ms_gateway_is_live_mode',
			$is_live_mode
		);
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
		MS_Helper_Debug::log(
			sprintf(
				__( 'Override the is_configured method of the %s-gateway', MS_TEXT_DOMAIN ),
				$this->id
			)
		);

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
			switch ( $property ) {
				case 'id':
				case 'name':
					break;

				case 'description':
				case 'pay_button_url':
				case 'upgrade_button_url':
				case 'cancel_button_url':
					$this->$property = trim( sanitize_text_field( $value ) );
					break;

				case 'active':
				case 'manual_payment':
					$this->$property = ( ! empty( $value ) ? true : false );
					break;

				default:
					if ( is_string( $value ) ) {
						$this->$property = trim( $value );
					}
					break;
			}
		}

		do_action(
			'ms_gateway__set_after',
			$property,
			$value,
			$this
		);
	}

	/**
	 * Return a property value
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param  string $name The name of a property to associate.
	 * @return mixed The value of a property.
	 */
	public function __get( $property ) {
		$value = null;

		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'active':
				case 'manual_payment':
					return ( ! empty( $this->$property ) ? true : false );
					break;

				case 'id':
				case 'name':
				case 'description':
				case 'pay_button_url':
				case 'upgrade_button_url':
				case 'cancel_button_url':
				case 'mode':
					$value = trim( $this->$property );
					break;

				default:
					$value = $this->$property;
					break;
			}
		}

		return apply_filters(
			'ms_gateway__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Get countries code and names.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *     Returns array of ( $code => $name ).
	 *     @type string $code The country code.
	 *     @type string $name The country name.
	 * }
	 */
	public function get_country_codes() {
		static $Countries = null;

		if ( is_null( $Countries ) ) {
			$Countries = array(
				'' => __( 'Select country', MS_TEXT_DOMAIN ),
				'AX' => __( 'ALAND ISLANDS', MS_TEXT_DOMAIN ),
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

			$Countries = apply_filters(
				'ms_gateway_get_country_codes',
				$Countries
			);
		}

		return $Countries;
	}
}