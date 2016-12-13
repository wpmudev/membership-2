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
				MS_Helper_Period::current_date(),
				DAY_IN_SECONDS, // return value in DAYS.
				true // return negative value if first date is before second date.
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
		$url = MS_Helper_Utility::home_url( '/ms-payment-return/' . $this->id );

		return apply_filters(
			'ms_gateway_get_return_url',
			$url,
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
			self::MODE_LIVE => __( 'Live Site', 'membership2' ),
			self::MODE_SANDBOX => __( 'Sandbox Mode (test)', 'membership2' ),
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
		if ( empty( $this->mode ) ) {
			$this->mode = self::MODE_SANDBOX;
		}

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
				__( 'Override the is_configured method of the %s-gateway', 'membership2' ),
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
	 * Check if property isset.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns true/false.
	 */
	public function __isset( $property ) {
		return isset($this->$property);
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
				'' => '- ' . __( 'Select country', 'membership2' ) . ' -',
				'AX' => __( 'Aland Islands', 'membership2' ),
				'AL' => __( 'Albania', 'membership2' ),
				'DZ' => __( 'Algeria', 'membership2' ),
				'AS' => __( 'American Samoa', 'membership2' ),
				'AD' => __( 'Andorra', 'membership2' ),
				'AI' => __( 'Anguilla', 'membership2' ),
				'AQ' => __( 'Antarctica', 'membership2' ),
				'AG' => __( 'Antigua And Barbuda', 'membership2' ),
				'AR' => __( 'Argentina', 'membership2' ),
				'AM' => __( 'Armenia', 'membership2' ),
				'AW' => __( 'Aruba', 'membership2' ),
				'AU' => __( 'Australia', 'membership2' ),
				'AT' => __( 'Austria', 'membership2' ),
				'AZ' => __( 'Azerbaijan', 'membership2' ),
				'BS' => __( 'Bahamas', 'membership2' ),
				'BH' => __( 'Bahrain', 'membership2' ),
				'BD' => __( 'Bangladesh', 'membership2' ),
				'BB' => __( 'Barbados', 'membership2' ),
				'BE' => __( 'Belgium', 'membership2' ),
				'BZ' => __( 'Belize', 'membership2' ),
				'BJ' => __( 'Benin', 'membership2' ),
				'BM' => __( 'Bermuda', 'membership2' ),
				'BT' => __( 'Bhutan', 'membership2' ),
				'BA' => __( 'Bosnia-herzegovina', 'membership2' ),
				'BW' => __( 'Botswana', 'membership2' ),
				'BV' => __( 'Bouvet Island', 'membership2' ),
				'BR' => __( 'Brazil', 'membership2' ),
				'IO' => __( 'British Indian Ocean Territory', 'membership2' ),
				'BN' => __( 'Brunei Darussalam', 'membership2' ),
				'BG' => __( 'Bulgaria', 'membership2' ),
				'BF' => __( 'Burkina Faso', 'membership2' ),
				'CA' => __( 'Canada', 'membership2' ),
				'CV' => __( 'Cape Verde', 'membership2' ),
				'KY' => __( 'Cayman Islands', 'membership2' ),
				'CF' => __( 'Central African Republic', 'membership2' ),
				'CL' => __( 'Chile', 'membership2' ),
				'CN' => __( 'China', 'membership2' ),
				'CX' => __( 'Christmas Island', 'membership2' ),
				'CC' => __( 'Cocos (keeling) Islands', 'membership2' ),
				'CO' => __( 'Colombia', 'membership2' ),
				'CK' => __( 'Cook Islands', 'membership2' ),
				'CR' => __( 'Costa Rica', 'membership2' ),
				'HR' => __( 'Croatia', 'membership2' ),
				'CY' => __( 'Cyprus', 'membership2' ),
				'CZ' => __( 'Czech Republic', 'membership2' ),
				'DK' => __( 'Denmark', 'membership2' ),
				'DJ' => __( 'Djibouti', 'membership2' ),
				'DM' => __( 'Dominica', 'membership2' ),
				'DO' => __( 'Dominican Republic', 'membership2' ),
				'EC' => __( 'Ecuador', 'membership2' ),
				'EG' => __( 'Egypt', 'membership2' ),
				'SV' => __( 'El Salvador', 'membership2' ),
				'EE' => __( 'Estonia', 'membership2' ),
				'FK' => __( 'Falkland Islands (malvinas)', 'membership2' ),
				'FO' => __( 'Faroe Islands', 'membership2' ),
				'FJ' => __( 'Fiji', 'membership2' ),
				'FI' => __( 'Finland', 'membership2' ),
				'FR' => __( 'France', 'membership2' ),
				'GF' => __( 'French Guiana', 'membership2' ),
				'PF' => __( 'French Polynesia', 'membership2' ),
				'TF' => __( 'French Southern Territories', 'membership2' ),
				'GA' => __( 'Gabon', 'membership2' ),
				'GM' => __( 'Gambia', 'membership2' ),
				'GE' => __( 'Georgia', 'membership2' ),
				'DE' => __( 'Germany', 'membership2' ),
				'GH' => __( 'Ghana', 'membership2' ),
				'GI' => __( 'Gibraltar', 'membership2' ),
				'GR' => __( 'Greece', 'membership2' ),
				'GL' => __( 'Greenland', 'membership2' ),
				'GD' => __( 'Grenada', 'membership2' ),
				'GP' => __( 'Guadeloupe', 'membership2' ),
				'GU' => __( 'Guam', 'membership2' ),
				'GG' => __( 'Guernsey', 'membership2' ),
				'GY' => __( 'Guyana', 'membership2' ),
				'HM' => __( 'Heard Island And Mcdonald Islands', 'membership2' ),
				'VA' => __( 'Holy See (vatican City State)', 'membership2' ),
				'HN' => __( 'Honduras', 'membership2' ),
				'HK' => __( 'Hong Kong', 'membership2' ),
				'HU' => __( 'Hungary', 'membership2' ),
				'IS' => __( 'Iceland', 'membership2' ),
				'IN' => __( 'India', 'membership2' ),
				'ID' => __( 'Indonesia', 'membership2' ),
				'IE' => __( 'Ireland', 'membership2' ),
				'IM' => __( 'Isle Of Man', 'membership2' ),
				'IL' => __( 'Israel', 'membership2' ),
				'IT' => __( 'Italy', 'membership2' ),
				'JM' => __( 'Jamaica', 'membership2' ),
				'JP' => __( 'Japan', 'membership2' ),
				'JE' => __( 'Jersey', 'membership2' ),
				'JO' => __( 'Jordan', 'membership2' ),
				'KZ' => __( 'Kazakhstan', 'membership2' ),
				'KI' => __( 'Kiribati', 'membership2' ),
				'KR' => __( 'Korea, Republic Of', 'membership2' ),
				'KW' => __( 'Kuwait', 'membership2' ),
				'KG' => __( 'Kyrgyzstan', 'membership2' ),
				'LV' => __( 'Latvia', 'membership2' ),
				'LS' => __( 'Lesotho', 'membership2' ),
				'LI' => __( 'Liechtenstein', 'membership2' ),
				'LT' => __( 'Lithuania', 'membership2' ),
				'LU' => __( 'Luxembourg', 'membership2' ),
				'MO' => __( 'Macao', 'membership2' ),
				'MK' => __( 'Macedonia', 'membership2' ),
				'MG' => __( 'Madagascar', 'membership2' ),
				'MW' => __( 'Malawi', 'membership2' ),
				'MY' => __( 'Malaysia', 'membership2' ),
				'MT' => __( 'Malta', 'membership2' ),
				'MH' => __( 'Marshall Islands', 'membership2' ),
				'MQ' => __( 'Martinique', 'membership2' ),
				'MR' => __( 'Mauritania', 'membership2' ),
				'MU' => __( 'Mauritius', 'membership2' ),
				'YT' => __( 'Mayotte', 'membership2' ),
				'MX' => __( 'Mexico', 'membership2' ),
				'FM' => __( 'Micronesia, Federated States Of', 'membership2' ),
				'MD' => __( 'Moldova, Republic Of', 'membership2' ),
				'MC' => __( 'Monaco', 'membership2' ),
				'MN' => __( 'Mongolia', 'membership2' ),
				'ME' => __( 'Montenegro', 'membership2' ),
				'MS' => __( 'Montserrat', 'membership2' ),
				'MA' => __( 'Morocco', 'membership2' ),
				'MZ' => __( 'Mozambique', 'membership2' ),
				'NA' => __( 'Namibia', 'membership2' ),
				'NR' => __( 'Nauru', 'membership2' ),
				'NP' => __( 'Nepal', 'membership2' ),
				'NL' => __( 'Netherlands', 'membership2' ),
				'AN' => __( 'Netherlands Antilles', 'membership2' ),
				'NC' => __( 'New Caledonia', 'membership2' ),
				'NZ' => __( 'New Zealand', 'membership2' ),
				'NI' => __( 'Nicaragua', 'membership2' ),
				'NE' => __( 'Niger', 'membership2' ),
				'NU' => __( 'Niue', 'membership2' ),
				'NF' => __( 'Norfolk Island', 'membership2' ),
				'MP' => __( 'Northern Mariana Islands', 'membership2' ),
				'NO' => __( 'Norway', 'membership2' ),
				'OM' => __( 'Oman', 'membership2' ),
				'PW' => __( 'Palau', 'membership2' ),
				'PS' => __( 'Palestine', 'membership2' ),
				'PA' => __( 'Panama', 'membership2' ),
				'PY' => __( 'Paraguay', 'membership2' ),
				'PE' => __( 'Peru', 'membership2' ),
				'PH' => __( 'Philippines', 'membership2' ),
				'PN' => __( 'Pitcairn', 'membership2' ),
				'PL' => __( 'Poland', 'membership2' ),
				'PT' => __( 'Portugal', 'membership2' ),
				'PR' => __( 'Puerto Rico', 'membership2' ),
				'QA' => __( 'Qatar', 'membership2' ),
				'RE' => __( 'Reunion', 'membership2' ),
				'RO' => __( 'Romania', 'membership2' ),
				'RU' => __( 'Russian Federation', 'membership2' ),
				'RW' => __( 'Rwanda', 'membership2' ),
				'SH' => __( 'Saint Helena', 'membership2' ),
				'KN' => __( 'Saint Kitts And Nevis', 'membership2' ),
				'LC' => __( 'Saint Lucia', 'membership2' ),
				'PM' => __( 'Saint Pierre And Miquelon', 'membership2' ),
				'VC' => __( 'Saint Vincent And The Grenadines', 'membership2' ),
				'WS' => __( 'Samoa', 'membership2' ),
				'SM' => __( 'San Marino', 'membership2' ),
				'ST' => __( 'Sao Tome And Principe', 'membership2' ),
				'SA' => __( 'Saudi Arabia', 'membership2' ),
				'SN' => __( 'Senegal', 'membership2' ),
				'RS' => __( 'Serbia', 'membership2' ),
				'SC' => __( 'Seychelles', 'membership2' ),
				'SG' => __( 'Singapore', 'membership2' ),
				'SK' => __( 'Slovakia', 'membership2' ),
				'SI' => __( 'Slovenia', 'membership2' ),
				'SB' => __( 'Solomon Islands', 'membership2' ),
				'ZA' => __( 'South Africa', 'membership2' ),
				'GS' => __( 'South Georgia And The South Sandwich Islands', 'membership2' ),
				'ES' => __( 'Spain', 'membership2' ),
				'SR' => __( 'Suriname', 'membership2' ),
				'SJ' => __( 'Svalbard And Jan Mayen', 'membership2' ),
				'SZ' => __( 'Swaziland', 'membership2' ),
				'SE' => __( 'Sweden', 'membership2' ),
				'CH' => __( 'Switzerland', 'membership2' ),
				'TW' => __( 'Taiwan, Province Of China', 'membership2' ),
				'TZ' => __( 'Tanzania, United Republic Of', 'membership2' ),
				'TH' => __( 'Thailand', 'membership2' ),
				'TL' => __( 'Timor-leste', 'membership2' ),
				'TG' => __( 'Togo', 'membership2' ),
				'TK' => __( 'Tokelau', 'membership2' ),
				'TO' => __( 'Tonga', 'membership2' ),
				'TT' => __( 'Trinidad And Tobago', 'membership2' ),
				'TN' => __( 'Tunisia', 'membership2' ),
				'TR' => __( 'Turkey', 'membership2' ),
				'TM' => __( 'Turkmenistan', 'membership2' ),
				'TC' => __( 'Turks And Caicos Islands', 'membership2' ),
				'TV' => __( 'Tuvalu', 'membership2' ),
				'UG' => __( 'Uganda', 'membership2' ),
				'UA' => __( 'Ukraine', 'membership2' ),
				'AE' => __( 'United Arab Emirates', 'membership2' ),
				'GB' => __( 'United Kingdom', 'membership2' ),
				'US' => __( 'United States', 'membership2' ),
				'UM' => __( 'United States Minor Outlying Islands', 'membership2' ),
				'UY' => __( 'Uruguay', 'membership2' ),
				'UZ' => __( 'Uzbekistan', 'membership2' ),
				'VU' => __( 'Vanuatu', 'membership2' ),
				'VE' => __( 'Venezuela', 'membership2' ),
				'VN' => __( 'Viet Nam', 'membership2' ),
				'VG' => __( 'Virgin Islands, British', 'membership2' ),
				'VI' => __( 'Virgin Islands, U.s.', 'membership2' ),
				'WF' => __( 'Wallis And Futuna', 'membership2' ),
				'EH' => __( 'Western Sahara', 'membership2' ),
				'ZM' => __( 'Zambia', 'membership2' ),
			);

			$Countries = apply_filters(
				'ms_gateway_get_country_codes',
				$Countries
			);
		}

		return $Countries;
	}
}