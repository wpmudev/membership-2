<?php
/**
 * Taxamo API functions.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Addon_Taxamo_Api extends MS_Controller {

	static protected $Countries = null;
	static protected $Countries_Prefix = null;
	static protected $Countries_Vat = null;

	/**
	 * Returns tax information that must be applied to the specified amount.
	 *
	 * This function first checks the user-metadata for logged-in users and
	 * takes the details that are stored in the user metadata table.
	 *
	 * If the user is not logged in or no metadata are found then the Taxamo API
	 * is queried to get the default tax details for the country that
	 * Taxamo automatically detects.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  numeric $amount The amount without taxes.
	 * @return object {
	 *         Tax information
	 *
	 *         string  $country Tax country (2-digit code)
	 *         string  $name Tax name
	 *         numeric $rate Tax rate (percent)
	 *         numeric $amount Tax amount
	 * }
	 */
	static public function tax_info( $amount = 0 ) {
		static $Info = null;

		if ( null === $Info ) {
			$settings = MS_Factory::load( 'MS_Model_Settings' );

			try {
				$profile = self::get_tax_profile();
				$tax_number = null;
				if ( $profile->use_vat_number ) {
					$tax_number = $profile->vat_number;
				}
				$resp = self::taxamo()->calculateSimpleTax(
					null // buyer_credit_card_prefix
					,$tax_number // buyer_tax_number
					,null // product_type
					,$profile->tax_country->code // force_country_code
					,null // quantity
					,null // unit_price
					,null // total_amount
					,null // tax_deducted
					,100 // amount
					,$profile->tax_country->code // billing_country_code
					,$settings->currency // currency_code
					,null // order_date
				);

				// Prepare the result object.
				if ( isset( $resp->transaction->transaction_lines[0] ) ) {
					$transaction = $resp->transaction->transaction_lines[0];
					$Info = (object) array(
						'country' => $resp->transaction->tax_country_code,
						'rate' => $transaction->tax_rate,
						'name' => $transaction->tax_name,
						'amount' => 0,
					);
				}
			}
			catch ( Exception $ex ) {
				MS_Helper_Debug::log( 'Taxamo error: ' . $ex->getMessage() );
			}
		}

		if ( ! is_object( $Info ) ) { $Info = (object) array(); }
		if ( ! isset( $Info->name ) ) { $Info->name = __( 'No Tax', MS_TEXT_DOMAIN ); }
		if ( ! isset( $Info->rate ) ) { $Info->rate = 0; }
		if ( ! isset( $Info->amount ) ) { $Info->amount = 0; }
		if ( ! isset( $Info->country ) ) { $Info->country = 'US'; }

		$Info->amount = $amount / 100 * $Info->rate;

		return $Info;
	}

	/**
	 * Creates a confirmed transaction in Taxamo.
	 *
	 * @since  1.0.0
	 * @param  numeric $amount Transaction amount
	 */
	static public function register_payment( $amount, $label, $tax_rate, $invoice_id, $name, $email, $gateway, $currency, $ip_addr ) {
		try {
			$profile = self::get_tax_profile();

			if ( empty( $ip_addr ) ) {
				$ip_addr = lib2()->net->current_ip()->ip;
			}

			// Register the transaction with Taxamo.
			$transaction = self::prepare_transaction(
				$amount,
				$label,
				$tax_rate,
				$invoice_id,
				$name,
				$email,
				$currency,
				$ip_addr
			);
			$payload = array( 'transaction' => $transaction );
			$resp = self::taxamo()->createTransaction( $payload );

			if ( ! empty( $resp->transaction->key ) ) {
				$tr_key = $resp->transaction->key;
				// Confirm the Transaction.
				$resp = self::taxamo()->confirmTransaction( $tr_key, null );

				// Register the payment.
				$information = sprintf(
					__( 'Invoice %1$s paid via %2$s.', MS_TEXT_DOMAIN ),
					$invoice_id,
					$gateway
				);
				$payment = array(
					'amount' => $amount,
					'payment_information' => $information,
				);
				$resp = self::taxamo()->createPayment( $tr_key, $payment );
			}
		}
		catch ( Exception $ex ) {
			MS_Helper_Debug::log( 'Taxamo error: ' . $ex->getMessage() );
		}
	}

	/**
	 * Returns an object containing all tax-related user profile details.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return object {
	 *         Local Taxamo user profile settings
	 *
	 *         $tax_country      {@see fetch_country()}
	 *         $detected_country {@see fetch_country()}
	 *         $declared_country {@see fetch_country()}
	 *         $vat_country      {@see fetch_country()}
	 *         $country_choice   string [auto|declared|vat]
	 *         $vat_number       string
	 *         $vat_valid        bool
	 *         $use_vat_number   bool
	 * }
	 */
	static public function get_tax_profile() {
		static $Profile = null;

		if ( null === $Profile ) {
			$member = MS_Model_Member::get_current_member();

			$Profile = (object) array();
			$Profile->detected_ip = lib2()->net->current_ip()->ip;
			$Profile->detected_country = self::fetch_country( 'auto' );
			$Profile->declared_country = self::fetch_country( 'declared' );
			$Profile->vat_country = self::fetch_country( 'vat' );
			$Profile->card_country = self::fetch_country( 'card' );
			$Profile->country_choice = self::get_tax_profile_value( $member, 'tax_country_choice' );

			$valid_choices = array( 'auto', 'vat', 'declared' );
			if ( ! in_array( $Profile->country_choice, $valid_choices ) ) {
				$Profile->country_choice = 'auto';
			}

			$Profile->card_info = self::get_tax_profile_value( $member, 'tax_card_info' );
			$Profile->vat_number = self::get_tax_profile_value( $member, 'tax_vat_number' );
			$Profile->vat_valid = self::get_tax_profile_value( $member, 'tax_vat_valid' );
			$Profile->use_vat_number = 'vat' == $Profile->country_choice && $Profile->vat_valid;

			// Decide which country to use for tax calculation. Default is auto-detected.
			$Profile->tax_country = $Profile->detected_country;

			switch ( $Profile->country_choice ) {
				case 'declared':
					$Profile->tax_country = $Profile->declared_country;
					break;

				case 'vat':
					if ( $Profile->vat_valid ) {
						$Profile->tax_country = $Profile->vat_country;
					} else {
						$Profile->tax_country = $Profile->detected_country;
					}
					break;
			}

			// For users without a VAT number the vat_valid field is missing.
			if ( empty( $Profile->vat_country->vat_valid ) ) {
				$Profile->vat_country->vat_valid = false;
			}

			$Profile = apply_filters(
				'ms_addon_taxamo_get_tax_profile',
				$Profile
			);
		}

		return $Profile;
	}

	/**
	 * Saves a single field to the user tax profile.
	 *
	 * All fields that are included in the get_tax_profile() response are
	 * valid field names. Exception: 'detected_country' cannot be changed.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param string $field The field key.
	 * @param mixed $value The new value.
	 * @return bool True on success.
	 */
	static public function set_tax_profile( $field, $value ) {
		$member = MS_Model_Member::get_current_member();

		$valid_keys = array(
			'country_choice',
			'declared_country',
			'card_country',
			'card_info',
			'vat_number',
		);

		$valid_keys = apply_filters(
			'ms_addon_taxamo_set_tax_profile_valid_keys',
			$valid_keys,
			$this
		);

		if ( ! in_array( $field, $valid_keys ) ) {
			return false;
		}

		// Special case: When VAT Number is changed also refresh VAT country.
		if ( 'vat_number' == $field ) {
			$valid_code = MS_Addon_Taxamo_Api::country_from_vat( $value );
			$value = str_replace( ' ', '', $value );

			if ( $valid_code ) {
				$vat_country = (object) array(
					'code' => $valid_code,
				);
				self::set_tax_profile_value( $member, 'tax_vat_valid', true );
			} else {
				$vat_country = (object) array(
					'code' => '',
				);
				self::set_tax_profile_value( $member, 'tax_vat_valid', false );
			}

			self::set_tax_profile_value( $member, 'tax_vat_country', $vat_country );
		}

		$key = 'tax_' . $field;
		if ( self::set_tax_profile_value( $member, $key, $value ) ) {
			$member->save();
		}

		return true;
	}

	/**
	 * Validates the given VAT number and returns the country code if the number
	 * is valid. Otherwise an empty string is returned.
	 *
	 * @since  1.0.0
	 * @param  string $vat_number The VAT number to validate.
	 * @return string Country code.
	 */
	static public function country_from_vat( $vat_number ) {
		if ( strlen( $vat_number ) < 5 ) { return ''; }

		$prefix = substr( $vat_number, 0, 2 );
		$codes = self::get_country_codes( 'vat' );
		if ( ! isset( $codes[$prefix] ) ) { return ''; }

		$country_code = $codes[$prefix];
		$resp = self::taxamo()->validateTaxNumber( null, $vat_number );

		$result = $resp->billing_country_code;
		return $result;
	}

	/**
	 * Returns a list of all taxamo relevant EU countries.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $type [prefix|name|vat]
	 *         name ..   code => "name"
	 *         prefix .. code => "prefix - name"
	 *         vat ..    vat-prefix => code
	 * @return array
	 */
	static public function get_country_codes( $type = 'prefix' ) {
		if ( null === self::$Countries ) {
			$country_names = MS_Gateway::get_country_codes(); // Country names in current language.

			$list = get_site_transient( 'ms_taxamo_countries' );
			$list = false;
			if ( ! $list || ! is_array( $list ) ) {
				$resp = self::taxamo()->getCountriesDict( 'true' );
				$list = array();
				foreach ( $resp->dictionary as $item ) {
					$list[$item->code] = array(
						'name' => $item->name,
						'vat' => $item->tax_number_country_code,
					);
				}
				set_site_transient( 'ms_taxamo_countries', $list, WEEK_IN_SECONDS );
			}

			self::$Countries = array();
			self::$Countries_Prefix = array();
			self::$Countries_Vat = array();
			foreach ( $list as $code => $item ) {
				if ( isset( $country_names[$code] ) ) {
					$item['name'] = $country_names[$code];
				}

				self::$Countries[$code] = $item['name'];
				self::$Countries_Prefix[$code] = $code . ' - ' . $item['name'];
				self::$Countries_Vat[$item['vat']] = $code;
			}
			self::$Countries['XX'] = '- ' . __( 'Outside the EU', MS_TEXT_DOMAIN ) . ' -';
			self::$Countries_Prefix['XX'] = '- ' . __( 'Outside the EU', MS_TEXT_DOMAIN ) . ' -';
		}

		switch ( $type ) {
			case 'prefix':
				return self::$Countries_Prefix;

			case 'vat':
				return self::$Countries_Vat;

			case 'name':
			default:
				return self::$Countries;
		}
	}

	// ------------------------------------------------------- PRIVATE FUNCTIONS

	/**
	 * Prepares a transaction object before it is sent to taxamo.
	 *
	 * @since  1.0.0
	 */
	static protected function prepare_transaction( $amount, $label, $tax_rate, $invoice_num, $name, $email, $currency, $ip_addr ) {
		self::taxamo();
		$profile = self::get_tax_profile();

		$amount = max( 0, floatval( $amount ) );
		$tax_rate = max( 0, floatval( $tax_rate ) );

		$tax_number = null;
		$amount_key = 'total_amount';
		if ( $profile->use_vat_number ) {
			$tax_number = $profile->vat_number;
			$tax_rate = 0;
		}
		if ( ! $tax_rate ) {
			$amount_key = 'amount';
		}

		$tr_line = array(
			'product_type' => 'e-service',
			$amount_key => $amount,
			'tax_rate' => $tax_rate,
			'quantity' => 1,
			'custom_id' => (string) $invoice_num,
			'description' => (string) $label,
		);

		// Assemble the available evidence.
		$tr_evidence = array(
			'by_ip' => array(
				'resolved_country_code' => $profile->detected_country->code,
				'evidence_value' => $profile->detected_ip,
			),
		);

		if ( 'XX' != $profile->declared_country->code ) {
			$tr_evidence['self_declaration'] = array(
				'resolved_country_code' => $profile->declared_country->code,
				'evidence_value' => 'Self declared country',
			);
		}

		if ( $profile->use_vat_number ) {
			$tr_evidence['by_tax_number'] = array(
				'resolved_country_code' => $profile->vat_country->code,
				'evidence_value' => $profile->vat_number,
			);
		}

		$used_code = $profile->tax_country->code;
		switch ( $profile->country_choice ) {
			case 'declared':
				$tr_evidence['forced'] = array(
					'resolved_country_code' => $used_code,
					'evidence_value' => 'Self declared country',
				);
				break;

			case 'vat':
			case 'auto':
			default:
				$tr_evidence['by_billing'] = array(
					'resolved_country_code' => $used_code,
					'evidence_value' => $profile->tax_country->code,
				);
				break;
		}

		foreach ( $tr_evidence as $key => $item ) {
			$tr_evidence[$key]['used'] = ($used_code == $item['resolved_country_code']);
		}

		$force_country = null;
		if ( 'declared' == $profile->country_choice ) {
			$force_country = $profile->tax_country->code;
		}

		$transaction = array(
			'currency_code' => $currency,
			'billing_country_code' => $profile->tax_country->code,
			'tax_country_code' => $profile->tax_country->code,
			'force_country_code' => $profile->tax_country->code,
			'buyer_ip' => $ip_addr,
			'custom_id' => (string) $invoice_num,
			'buyer_name' => $name,
			'buyer_email' => $email,
			'buyer_tax_number' => $tax_number,
			'tax_deducted' => $profile->use_vat_number,
			'transaction_lines' => array( $tr_line ),
			'evidence' => $tr_evidence,
		);

		return $transaction;
	}

	/**
	 * Determines the users country based on his IP address.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param string $mode [declared|vat|card|auto] Either the country from user
	 *        settings or the auto-detected country.
	 */
	static protected function fetch_country( $mode = 'declared' ) {
		$member = MS_Model_Member::get_current_member();
		$country = false;
		$store_it = false;

		$non_auto_countries = array( 'declared', 'vat', 'card' );
		if ( ! in_array( $mode, $non_auto_countries ) ) { $mode = 'auto'; }

		$auto_detect = ('auto' == $mode);
		$key = 'tax_' . $mode . '_country';

		// If no country is stored use the API to determine it.
		if ( $auto_detect ) {
			try {
				$ip_info = lib2()->net->current_ip();
				$data = (object)(array) self::taxamo()->locateGivenIP( $ip_info->ip );
				$country = (object) array(
					'code' => $data->country_code,
				);

				// Store result in Session, not in DB.
				$store_it = true;
				$member = null;
			}
			catch ( Exception $ex ) {
				MS_Helper_Debug::log( 'Taxamo error: ' . $ex->getMessage() );
			}
		} else {
			// Try to get the stored country from user-meta or session (for guest)
			$country = self::get_tax_profile_value( $member, $key );
		}

		// API did not return a valid resonse, use a dummy value.
		if ( ! $country ) {
			$country = (object) array(
				'code' => '',
			);
		}

		// Store result in user-deta or session.
		if ( $store_it && self::set_tax_profile_value( $member, $key, $country ) ) {
			$member->save();
		}

		$country_names = self::get_country_codes( 'name' );

		if ( $country->code && isset( $country_names[ $country->code ] ) ) {
			$country->name = $country_names[ $country->code ];
		} else {
			$country->name = $country_names['XX'];
		}

		return $country;
	}

	/**
	 * Internal helper function that returns a user profile value either from
	 * DB or from the session (depending if the user is logged in or not).
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  MS_Model_Member $member
	 * @param  string $key The field key.
	 * @return mixed The field value.
	 */
	static protected function get_tax_profile_value( $member, $key ) {
		if ( is_object( $member ) && $member->is_valid() ) {
			$result = $member->get_custom_data( $key );
		} else {
			$result = lib2()->session->get( 'ms_' . $key );
			if ( is_array( $result ) && count( $result ) ) {
				$result = $result[0];
			}
		}

		return $result;
	}

	/**
	 * Internal helper function that saves a user profile value either to DB or
	 * to the session (depending if the user is logged in or not).
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  MS_Model_Member $member
	 * @param  string $key The field key.
	 * @param  mixed $value The value to save
	 * @return bool True means that the value was set in $member, otherwise it
	 *         was set in the session.
	 */
	static protected function set_tax_profile_value( $member, $key, $value ) {
		if ( is_object( $member ) && $member->is_valid() ) {
			$member->set_custom_data( $key, $value );
			$need_save = true;
		} else {
			lib2()->session->get_clear( 'ms_' . $key );
			lib2()->session->add( 'ms_' . $key, $value );
			$need_save = false;
		}

		return $need_save;
	}

	/**
	 * Returns the Taxamo REST API object.
	 *
	 * Important: All calls to `taxamo()->` functions must be wrapped in
	 * try..catch because an invalid API token will result in a fatal error.
	 *
	 * @since  1.0.0
	 * @return Taxamo
	 */
	static protected function taxamo() {
		static $Taxamo = null;

		if ( null === $Taxamo ) {
			if ( ! class_exists( 'Taxamo' ) ) {
				require_once MS_Plugin::instance()->dir . '/lib/taxamo/Taxamo.php';
			}

			// Initialize the Taxamo API connection
			$connection = new APIClient(
				MS_Addon_Taxamo::model()->get( 'private_key' ),
				'https://api.taxamo.com'
			);

			// Initialize the Taxamo REST API wrapper.
			$Taxamo = new Taxamo( $connection );

			// Initialize the API object.
			self::init();
		}

		return $Taxamo;
	}

	/**
	 * Initializes the taxamo API object.
	 *
	 * @since  1.0.0
	 */
	static protected function init() {
		self::taxamo();
		self::fetch_country( 'auto' );
	}
}
