<?php
/**
 * Gateway parent model.
 *
 * Every payment gateway extends from this class.
 * A payment gateway can process payments using three possible functions:
 *
 * - - - - - - - - - -
 *
 * function handle_return()
 *   This function is called by M2 when the IPN URL was called.
 *   E.g. calling "/ms-payment-return/paypalstandard" will trigger the function
 *   handle_return() for the PayPal Standard gateway.
 *   Subscription data must be fetched from the $_POST data collection.
 *
 * function process_purchase( $subscription )
 *   Called automatically by M2 when a new subscription was created, i.e.
 *   handles the first payment of any subscription.
 *   This function might create a new customer account/etc via the gateway API.
 *
 * function request_payment( $subscription )
 *   Called automatically by M2 when a payment is due, i.e. when the second
 *   payment of a recurring subscription is due.
 *
 * - - - - - - - - - -
 *
 * A single gateway should not implement all three payment methods! Either use
 *   handle_return   - or -
 *   process_purchase and request_payment
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway extends MS_Model_Option {

	/**
	 * Gateway opertaion mode contants.
	 *
	 * @since  1.0.0
	 * @see $mode
	 * @var string The operation mode.
	 */
	const MODE_SANDBOX = 'sandbox';
	const MODE_LIVE    = 'live';

	/**
	 * Singleton object.
	 *
	 * @since  1.0.0
	 * @see $type
	 * @var string The singleton object.
	 */
	public static $instance;

	/**
	 * Gateway group.
	 *
	 * This is a label that is used to group settings together on the Payment
	 * Settings page.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $group = '';

	/**
	 * Gateway ID.
	 *
	 * @since  1.0.0
	 * @var int $id
	 */
	protected $id = 'admin';

	/**
	 * Gateway name.
	 *
	 * @since  1.0.0
	 * @var string $name
	 */
	protected $name = '';

	/**
	 * Gateway description.
	 *
	 * @since  1.0.0
	 * @var string $description
	 */
	protected $description = '';

	/**
	 * Gateway active status.
	 *
	 * @since  1.0.0
	 * @var string $active
	 */
	protected $active = false;

	/**
	 * Manual payment indicator.
	 *
	 * True: Recurring payments need to be made manually.
	 * False: Gateway is capable of automatic recurring payments.
	 *
	 * @since  1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = true;

	/**
	 * List of payment_type IDs that are not supported by this gateway.
	 *
	 * @since  1.0.0
	 * @var array $unsupported_payment_types
	 */
	protected $unsupported_payment_types = array();

	/**
	 * Gateway allows Pro rating.
	 *
	 * Pro rating means that a user will get a discount for a new subscription
	 * when he upgrades from another subscription that is not fully consumed
	 * yet.
	 *
	 * @since  1.0.0
	 * @var bool
	 */
	protected $pro_rate = false;

	/**
	 * Custom payment button text or url.
	 *
	 * Overrides default purchase button.
	 *
	 * @since  1.0.0
	 * @var string $pay_button_url The url or button label (text).
	 */
	protected $pay_button_url;

	/**
	 * Custom cancel button text or url.
	 *
	 * Overrides default cancel button.
	 *
	 * @since  1.0.0
	 * @var string $cancel_button_url The url or button label (text).
	 */
	protected $cancel_button_url;

	/**
	 * Gateway operation mode.
	 *
	 * Live or sandbox (test) mode.
	 *
	 * @since  1.0.0
	 * @var string $mode
	 */
	protected $mode;

	/**
	 * Hook to process gateway returns (IPN).
	 *
	 * @see MS_Controller_Gateway: handle_payment_return()
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * Checks if the specified payment type is supported by the current gateway.
	 *
	 * @since  1.0.0
	 * @param  string|MS_Model_Membership $payment_type Either a payment type
	 *         identifier or a membership model object.
	 * @return bool
	 */
	public function payment_type_supported( $payment_type ) {
		if ( is_object( $payment_type ) ) {
			$payment_type = $payment_type->payment_type;
		}

		$types = $this->supported_payment_types();
		$result = isset( $types[$payment_type] );

		return $result;
	}

	/**
	 * Returns a list of supported payment types.
	 *
	 * @since  1.0.0
	 * @return array Payment types, index is the type-key / value the label.
	 */
	public function supported_payment_types() {
		static $Payment_Types = array();

		if ( ! isset( $Payment_Types[$this->id] ) ) {
			$Payment_Types[$this->id] = MS_Model_Membership::get_payment_types();

			foreach ( $this->unsupported_payment_types as $remove ) {
				unset( $Payment_Types[$this->id][$remove] );
			}
		}

		return $Payment_Types[$this->id];
	}

	/**
	 * Processes gateway IPN return.
	 *
	 * Overridden in child gateway classes.
	 *
	 * @since  1.0.0
	 * @param  MS_Model_Transactionlog $log Optional. A transaction log item
	 *         that will be updated instead of creating a new log entry.
	 */
	public function handle_return( $log = false ) {
		do_action(
			'ms_gateway_handle_return',
			$this,
			$log
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
	 * @since  1.0.0
	 * @param MS_Model_Relationship $ms_relationship The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		do_action(
			'ms_gateway_process_purchase_before',
			$subscription,
			$this
		);

		$invoice = $subscription->get_current_invoice();
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
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 */
	public function cancel_membership( $subscription ) {
		do_action(
			'ms_gateway_cancel_membership',
			$subscription,
			$this
		);
	}

	/**
	 * Request automatic payment to the gateway.
	 *
	 * Overridden in child gateway classes.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 * @return bool True on success.
	 */
	public function request_payment( $subscription ) {
		do_action(
			'ms_gateway_request_payment',
			$subscription,
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
	 * @since  1.0.0
	 *
	 * @access protected
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 */
	public function check_card_expiration( $subscription ) {
		do_action( 'ms_gateway_check_card_expiration_before', $this );

		$member = MS_Factory::load( 'MS_Model_Member', $subscription->user_id );
		$card_exp = $member->get_gateway_profile( $this->id, 'card_exp' );

		if ( ! empty( $card_exp ) ) {
			$comm = MS_Model_Communication::get_communication(
				MS_Model_Communication::COMM_TYPE_CREDIT_CARD_EXPIRE
			);

			$days = MS_Helper_Period::get_period_in_days(
				$comm->period['period_unit'],
				$comm->period['period_type']
			);
			$card_expire_days = MS_Helper_Period::subtract_dates(
				$card_exp,
				MS_Helper_Period::current_date()
			);
			if ( $card_expire_days < 0 || ( $days == $card_expire_days ) ) {
				MS_Model_Event::save_event(
					MS_Model_Event::TYPE_CREDIT_CARD_EXPIRE,
					$subscription
				);
			}
		}

		do_action(
			'ms_gateway_check_card_expiration_after',
			$this,
			$subscription
		);
	}

	/**
	 * Url that fires handle_return of this gateway (IPN).
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @return array {
	 *     Returns array of ( $code => $name ).
	 *     @type string $code The country code.
	 *     @type string $name The country name.
	 * }
	 */
	static public function get_country_codes() {
		static $Countries = null;

		if ( is_null( $Countries ) ) {
			$Countries = array(
				'' => '- ' . __( 'Select country', MS_TEXT_DOMAIN ) . ' -',
				'AX' => __( 'Aland Islands', MS_TEXT_DOMAIN ),
				'AL' => __( 'Albania', MS_TEXT_DOMAIN ),
				'DZ' => __( 'Algeria', MS_TEXT_DOMAIN ),
				'AS' => __( 'American Samoa', MS_TEXT_DOMAIN ),
				'AD' => __( 'Andorra', MS_TEXT_DOMAIN ),
				'AI' => __( 'Anguilla', MS_TEXT_DOMAIN ),
				'AQ' => __( 'Antarctica', MS_TEXT_DOMAIN ),
				'AG' => __( 'Antigua And Barbuda', MS_TEXT_DOMAIN ),
				'AR' => __( 'Argentina', MS_TEXT_DOMAIN ),
				'AM' => __( 'Armenia', MS_TEXT_DOMAIN ),
				'AW' => __( 'Aruba', MS_TEXT_DOMAIN ),
				'AU' => __( 'Australia', MS_TEXT_DOMAIN ),
				'AT' => __( 'Austria', MS_TEXT_DOMAIN ),
				'AZ' => __( 'Azerbaijan', MS_TEXT_DOMAIN ),
				'BS' => __( 'Bahamas', MS_TEXT_DOMAIN ),
				'BH' => __( 'Bahrain', MS_TEXT_DOMAIN ),
				'BD' => __( 'Bangladesh', MS_TEXT_DOMAIN ),
				'BB' => __( 'Barbados', MS_TEXT_DOMAIN ),
				'BE' => __( 'Belgium', MS_TEXT_DOMAIN ),
				'BZ' => __( 'Belize', MS_TEXT_DOMAIN ),
				'BJ' => __( 'Benin', MS_TEXT_DOMAIN ),
				'BM' => __( 'Bermuda', MS_TEXT_DOMAIN ),
				'BT' => __( 'Bhutan', MS_TEXT_DOMAIN ),
				'BA' => __( 'Bosnia-herzegovina', MS_TEXT_DOMAIN ),
				'BW' => __( 'Botswana', MS_TEXT_DOMAIN ),
				'BV' => __( 'Bouvet Island', MS_TEXT_DOMAIN ),
				'BR' => __( 'Brazil', MS_TEXT_DOMAIN ),
				'IO' => __( 'British Indian Ocean Territory', MS_TEXT_DOMAIN ),
				'BN' => __( 'Brunei Darussalam', MS_TEXT_DOMAIN ),
				'BG' => __( 'Bulgaria', MS_TEXT_DOMAIN ),
				'BF' => __( 'Burkina Faso', MS_TEXT_DOMAIN ),
				'CA' => __( 'Canada', MS_TEXT_DOMAIN ),
				'CV' => __( 'Cape Verde', MS_TEXT_DOMAIN ),
				'KY' => __( 'Cayman Islands', MS_TEXT_DOMAIN ),
				'CF' => __( 'Central African Republic', MS_TEXT_DOMAIN ),
				'CL' => __( 'Chile', MS_TEXT_DOMAIN ),
				'CN' => __( 'China', MS_TEXT_DOMAIN ),
				'CX' => __( 'Christmas Island', MS_TEXT_DOMAIN ),
				'CC' => __( 'Cocos (keeling) Islands', MS_TEXT_DOMAIN ),
				'CO' => __( 'Colombia', MS_TEXT_DOMAIN ),
				'CK' => __( 'Cook Islands', MS_TEXT_DOMAIN ),
				'CR' => __( 'Costa Rica', MS_TEXT_DOMAIN ),
				'CY' => __( 'Cyprus', MS_TEXT_DOMAIN ),
				'CZ' => __( 'Czech Republic', MS_TEXT_DOMAIN ),
				'DK' => __( 'Denmark', MS_TEXT_DOMAIN ),
				'DJ' => __( 'Djibouti', MS_TEXT_DOMAIN ),
				'DM' => __( 'Dominica', MS_TEXT_DOMAIN ),
				'DO' => __( 'Dominican Republic', MS_TEXT_DOMAIN ),
				'EC' => __( 'Ecuador', MS_TEXT_DOMAIN ),
				'EG' => __( 'Egypt', MS_TEXT_DOMAIN ),
				'SV' => __( 'El Salvador', MS_TEXT_DOMAIN ),
				'EE' => __( 'Estonia', MS_TEXT_DOMAIN ),
				'FK' => __( 'Falkland Islands (malvinas)', MS_TEXT_DOMAIN ),
				'FO' => __( 'Faroe Islands', MS_TEXT_DOMAIN ),
				'FJ' => __( 'Fiji', MS_TEXT_DOMAIN ),
				'FI' => __( 'Finland', MS_TEXT_DOMAIN ),
				'FR' => __( 'France', MS_TEXT_DOMAIN ),
				'GF' => __( 'French Guiana', MS_TEXT_DOMAIN ),
				'PF' => __( 'French Polynesia', MS_TEXT_DOMAIN ),
				'TF' => __( 'French Southern Territories', MS_TEXT_DOMAIN ),
				'GA' => __( 'Gabon', MS_TEXT_DOMAIN ),
				'GM' => __( 'Gambia', MS_TEXT_DOMAIN ),
				'GE' => __( 'Georgia', MS_TEXT_DOMAIN ),
				'DE' => __( 'Germany', MS_TEXT_DOMAIN ),
				'GH' => __( 'Ghana', MS_TEXT_DOMAIN ),
				'GI' => __( 'Gibraltar', MS_TEXT_DOMAIN ),
				'GR' => __( 'Greece', MS_TEXT_DOMAIN ),
				'GL' => __( 'Greenland', MS_TEXT_DOMAIN ),
				'GD' => __( 'Grenada', MS_TEXT_DOMAIN ),
				'GP' => __( 'Guadeloupe', MS_TEXT_DOMAIN ),
				'GU' => __( 'Guam', MS_TEXT_DOMAIN ),
				'GG' => __( 'Guernsey', MS_TEXT_DOMAIN ),
				'GY' => __( 'Guyana', MS_TEXT_DOMAIN ),
				'HM' => __( 'Heard Island And Mcdonald Islands', MS_TEXT_DOMAIN ),
				'VA' => __( 'Holy See (vatican City State)', MS_TEXT_DOMAIN ),
				'HN' => __( 'Honduras', MS_TEXT_DOMAIN ),
				'HK' => __( 'Hong Kong', MS_TEXT_DOMAIN ),
				'HU' => __( 'Hungary', MS_TEXT_DOMAIN ),
				'IS' => __( 'Iceland', MS_TEXT_DOMAIN ),
				'IN' => __( 'India', MS_TEXT_DOMAIN ),
				'ID' => __( 'Indonesia', MS_TEXT_DOMAIN ),
				'IE' => __( 'Ireland', MS_TEXT_DOMAIN ),
				'IM' => __( 'Isle Of Man', MS_TEXT_DOMAIN ),
				'IL' => __( 'Israel', MS_TEXT_DOMAIN ),
				'IT' => __( 'Italy', MS_TEXT_DOMAIN ),
				'JM' => __( 'Jamaica', MS_TEXT_DOMAIN ),
				'JP' => __( 'Japan', MS_TEXT_DOMAIN ),
				'JE' => __( 'Jersey', MS_TEXT_DOMAIN ),
				'JO' => __( 'Jordan', MS_TEXT_DOMAIN ),
				'KZ' => __( 'Kazakhstan', MS_TEXT_DOMAIN ),
				'KI' => __( 'Kiribati', MS_TEXT_DOMAIN ),
				'KR' => __( 'Korea, Republic Of', MS_TEXT_DOMAIN ),
				'KW' => __( 'Kuwait', MS_TEXT_DOMAIN ),
				'KG' => __( 'Kyrgyzstan', MS_TEXT_DOMAIN ),
				'LV' => __( 'Latvia', MS_TEXT_DOMAIN ),
				'LS' => __( 'Lesotho', MS_TEXT_DOMAIN ),
				'LI' => __( 'Liechtenstein', MS_TEXT_DOMAIN ),
				'LT' => __( 'Lithuania', MS_TEXT_DOMAIN ),
				'LU' => __( 'Luxembourg', MS_TEXT_DOMAIN ),
				'MO' => __( 'Macao', MS_TEXT_DOMAIN ),
				'MK' => __( 'Macedonia', MS_TEXT_DOMAIN ),
				'MG' => __( 'Madagascar', MS_TEXT_DOMAIN ),
				'MW' => __( 'Malawi', MS_TEXT_DOMAIN ),
				'MY' => __( 'Malaysia', MS_TEXT_DOMAIN ),
				'MT' => __( 'Malta', MS_TEXT_DOMAIN ),
				'MH' => __( 'Marshall Islands', MS_TEXT_DOMAIN ),
				'MQ' => __( 'Martinique', MS_TEXT_DOMAIN ),
				'MR' => __( 'Mauritania', MS_TEXT_DOMAIN ),
				'MU' => __( 'Mauritius', MS_TEXT_DOMAIN ),
				'YT' => __( 'Mayotte', MS_TEXT_DOMAIN ),
				'MX' => __( 'Mexico', MS_TEXT_DOMAIN ),
				'FM' => __( 'Micronesia, Federated States Of', MS_TEXT_DOMAIN ),
				'MD' => __( 'Moldova, Republic Of', MS_TEXT_DOMAIN ),
				'MC' => __( 'Monaco', MS_TEXT_DOMAIN ),
				'MN' => __( 'Mongolia', MS_TEXT_DOMAIN ),
				'ME' => __( 'Montenegro', MS_TEXT_DOMAIN ),
				'MS' => __( 'Montserrat', MS_TEXT_DOMAIN ),
				'MA' => __( 'Morocco', MS_TEXT_DOMAIN ),
				'MZ' => __( 'Mozambique', MS_TEXT_DOMAIN ),
				'NA' => __( 'Namibia', MS_TEXT_DOMAIN ),
				'NR' => __( 'Nauru', MS_TEXT_DOMAIN ),
				'NP' => __( 'Nepal', MS_TEXT_DOMAIN ),
				'NL' => __( 'Netherlands', MS_TEXT_DOMAIN ),
				'AN' => __( 'Netherlands Antilles', MS_TEXT_DOMAIN ),
				'NC' => __( 'New Caledonia', MS_TEXT_DOMAIN ),
				'NZ' => __( 'New Zealand', MS_TEXT_DOMAIN ),
				'NI' => __( 'Nicaragua', MS_TEXT_DOMAIN ),
				'NE' => __( 'Niger', MS_TEXT_DOMAIN ),
				'NU' => __( 'Niue', MS_TEXT_DOMAIN ),
				'NF' => __( 'Norfolk Island', MS_TEXT_DOMAIN ),
				'MP' => __( 'Northern Mariana Islands', MS_TEXT_DOMAIN ),
				'NO' => __( 'Norway', MS_TEXT_DOMAIN ),
				'OM' => __( 'Oman', MS_TEXT_DOMAIN ),
				'PW' => __( 'Palau', MS_TEXT_DOMAIN ),
				'PS' => __( 'Palestine', MS_TEXT_DOMAIN ),
				'PA' => __( 'Panama', MS_TEXT_DOMAIN ),
				'PY' => __( 'Paraguay', MS_TEXT_DOMAIN ),
				'PE' => __( 'Peru', MS_TEXT_DOMAIN ),
				'PH' => __( 'Philippines', MS_TEXT_DOMAIN ),
				'PN' => __( 'Pitcairn', MS_TEXT_DOMAIN ),
				'PL' => __( 'Poland', MS_TEXT_DOMAIN ),
				'PT' => __( 'Portugal', MS_TEXT_DOMAIN ),
				'PR' => __( 'Puerto Rico', MS_TEXT_DOMAIN ),
				'QA' => __( 'Qatar', MS_TEXT_DOMAIN ),
				'RE' => __( 'Reunion', MS_TEXT_DOMAIN ),
				'RO' => __( 'Romania', MS_TEXT_DOMAIN ),
				'RU' => __( 'Russian Federation', MS_TEXT_DOMAIN ),
				'RW' => __( 'Rwanda', MS_TEXT_DOMAIN ),
				'SH' => __( 'Saint Helena', MS_TEXT_DOMAIN ),
				'KN' => __( 'Saint Kitts And Nevis', MS_TEXT_DOMAIN ),
				'LC' => __( 'Saint Lucia', MS_TEXT_DOMAIN ),
				'PM' => __( 'Saint Pierre And Miquelon', MS_TEXT_DOMAIN ),
				'VC' => __( 'Saint Vincent And The Grenadines', MS_TEXT_DOMAIN ),
				'WS' => __( 'Samoa', MS_TEXT_DOMAIN ),
				'SM' => __( 'San Marino', MS_TEXT_DOMAIN ),
				'ST' => __( 'Sao Tome And Principe', MS_TEXT_DOMAIN ),
				'SA' => __( 'Saudi Arabia', MS_TEXT_DOMAIN ),
				'SN' => __( 'Senegal', MS_TEXT_DOMAIN ),
				'RS' => __( 'Serbia', MS_TEXT_DOMAIN ),
				'SC' => __( 'Seychelles', MS_TEXT_DOMAIN ),
				'SG' => __( 'Singapore', MS_TEXT_DOMAIN ),
				'SK' => __( 'Slovakia', MS_TEXT_DOMAIN ),
				'SI' => __( 'Slovenia', MS_TEXT_DOMAIN ),
				'SB' => __( 'Solomon Islands', MS_TEXT_DOMAIN ),
				'ZA' => __( 'South Africa', MS_TEXT_DOMAIN ),
				'GS' => __( 'South Georgia And The South Sandwich Islands', MS_TEXT_DOMAIN ),
				'ES' => __( 'Spain', MS_TEXT_DOMAIN ),
				'SR' => __( 'Suriname', MS_TEXT_DOMAIN ),
				'SJ' => __( 'Svalbard And Jan Mayen', MS_TEXT_DOMAIN ),
				'SZ' => __( 'Swaziland', MS_TEXT_DOMAIN ),
				'SE' => __( 'Sweden', MS_TEXT_DOMAIN ),
				'CH' => __( 'Switzerland', MS_TEXT_DOMAIN ),
				'TW' => __( 'Taiwan, Province Of China', MS_TEXT_DOMAIN ),
				'TZ' => __( 'Tanzania, United Republic Of', MS_TEXT_DOMAIN ),
				'TH' => __( 'Thailand', MS_TEXT_DOMAIN ),
				'TL' => __( 'Timor-leste', MS_TEXT_DOMAIN ),
				'TG' => __( 'Togo', MS_TEXT_DOMAIN ),
				'TK' => __( 'Tokelau', MS_TEXT_DOMAIN ),
				'TO' => __( 'Tonga', MS_TEXT_DOMAIN ),
				'TT' => __( 'Trinidad And Tobago', MS_TEXT_DOMAIN ),
				'TN' => __( 'Tunisia', MS_TEXT_DOMAIN ),
				'TR' => __( 'Turkey', MS_TEXT_DOMAIN ),
				'TM' => __( 'Turkmenistan', MS_TEXT_DOMAIN ),
				'TC' => __( 'Turks And Caicos Islands', MS_TEXT_DOMAIN ),
				'TV' => __( 'Tuvalu', MS_TEXT_DOMAIN ),
				'UG' => __( 'Uganda', MS_TEXT_DOMAIN ),
				'UA' => __( 'Ukraine', MS_TEXT_DOMAIN ),
				'AE' => __( 'United Arab Emirates', MS_TEXT_DOMAIN ),
				'GB' => __( 'United Kingdom', MS_TEXT_DOMAIN ),
				'US' => __( 'United States', MS_TEXT_DOMAIN ),
				'UM' => __( 'United States Minor Outlying Islands', MS_TEXT_DOMAIN ),
				'UY' => __( 'Uruguay', MS_TEXT_DOMAIN ),
				'UZ' => __( 'Uzbekistan', MS_TEXT_DOMAIN ),
				'VU' => __( 'Vanuatu', MS_TEXT_DOMAIN ),
				'VE' => __( 'Venezuela', MS_TEXT_DOMAIN ),
				'VN' => __( 'Viet Nam', MS_TEXT_DOMAIN ),
				'VG' => __( 'Virgin Islands, British', MS_TEXT_DOMAIN ),
				'VI' => __( 'Virgin Islands, U.s.', MS_TEXT_DOMAIN ),
				'WF' => __( 'Wallis And Futuna', MS_TEXT_DOMAIN ),
				'EH' => __( 'Western Sahara', MS_TEXT_DOMAIN ),
				'ZM' => __( 'Zambia', MS_TEXT_DOMAIN ),
			);

			$Countries = apply_filters(
				'ms_gateway_get_country_codes',
				$Countries
			);
		}

		return $Countries;
	}
}