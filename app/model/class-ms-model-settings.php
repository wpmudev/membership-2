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


class MS_Model_Settings extends MS_Model_Option {

	protected static $CLASS_NAME = __CLASS__;

	public static $instance;

	const SPECIAL_PAGE_NO_ACCESS = 'no_access';
	const SPECIAL_PAGE_ACCOUNT = 'account';
	const SPECIAL_PAGE_WELCOME = 'welcome';
	const SPECIAL_PAGE_SIGNUP = 'signup';
	const SPECIAL_PAGE_MENU = 'menu';

	const PROTECTION_MSG_CONTENT = 'content';
	const PROTECTION_MSG_SHORTCODE = 'shortcode';
	const PROTECTION_MSG_MORE_TAG = 'more_tag';

	protected $id = 'ms_plugin_settings';

	protected $name = 'Plugin settings';

	/** Current db version */
	protected $version;

	protected $plugin_enabled = false;

	protected $initial_setup = true;

	protected $wizard_step;

	protected $hide_wizard_pointer;

	protected $pages = array();

	protected $hide_admin_bar = true;

	protected $currency = 'USD';

	protected $tax;

	protected $invoice_sender_name;

	protected $is_global_payments_set = false;

	/** For extensions settings.*/
	protected $custom;

	/**
	 * Shortcode protection message.
	 *
	 * @var $protection_messages
	 */
	protected $protection_messages = array();

	protected $downloads = array(
		'protection_type' => MS_Model_Rule_Media::PROTECTION_TYPE_DISABLED,
		'masked_url' => 'downloads',
	);

	public static function get_setting( $field ) {
		$value = null;
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		if ( property_exists( $settings, $field ) ) {
			$value = $settings->$field;
		}

		return apply_filters( 'ms_model_settings_get_setting', $value, $field );
	}



	public static function get_protection_msg_types() {
		$types = array(
				self::PROTECTION_MSG_CONTENT,
				self::PROTECTION_MSG_SHORTCODE,
				self::PROTECTION_MSG_MORE_TAG,
		);
		return apply_filters( 'ms_model_settings_get_protection_msg_types', $types );
	}

	public static function is_valid_protection_msg_type( $type ) {
		$types = self::get_protection_msg_types();
		return apply_filters( 'ms_model_settings_is_valid_protection_msg_type', in_array( $type, $types ) );
	}

	public function set_protection_message( $type, $msg ) {
		if ( self::is_valid_protection_msg_type( $type ) ) {
			$this->protection_messages[ $type ] = wp_kses_post( $msg );
		}
	}

	public function get_protection_message( $type ) {
		$msg = '';
		if ( self::is_valid_protection_msg_type( $type ) ) {
			if ( isset( $this->protection_messages[ $type ] ) ) {
				$msg = $this->protection_messages[ $type ];
			}
			else {
				$msg = __( 'The content you are trying to access is only available to members. Sorry.', MS_TEXT_DOMAIN );
			}
		}

		return apply_filters( 'ms_model_settings_get_protection_message', $msg, $type );
	}

	public function set_custom_setting( $group, $field, $value ) {
		$this->custom[ $group ][ $field ] = apply_filters( 'ms_model_settings_set_custom_setting', $value, $group, $field );
	}

	public function get_custom_setting( $group, $field ) {
		$value = '';
		if ( ! empty( $this->custom[ $group ][ $field ] ) ) {
			$value = $this->custom[ $group ][ $field ];
		}
		return apply_filters( 'ms_model_settings_get_custom_setting', $value, $group, $field );
	}

	public static function get_currencies() {
		static $Currencies = null;

		if ( null === $Currencies ) {
			$Currencies = apply_filters(
				'ms_model_settings_get_currencies',
				array(
					'AUD' => __( 'AUD - Australian Dollar', MS_TEXT_DOMAIN ),
					'BRL' => __( 'BRL - Brazilian Real', MS_TEXT_DOMAIN ),
					'CAD' => __( 'CAD - Canadian Dollar', MS_TEXT_DOMAIN ),
					'CHF' => __( 'CHF - Swiss Franc', MS_TEXT_DOMAIN ),
					'CZK' => __( 'CZK - Czech Koruna', MS_TEXT_DOMAIN ),
					'DKK' => __( 'DKK - Danish Krone', MS_TEXT_DOMAIN ),
					'EUR' => __( 'EUR - Euro', MS_TEXT_DOMAIN ),
					'GBP' => __( 'GBP - Pound Sterling', MS_TEXT_DOMAIN ),
					'HKD' => __( 'HKD - Hong Kong Dollar', MS_TEXT_DOMAIN ),
					'HUF' => __( 'HUF - Hungarian Forint', MS_TEXT_DOMAIN ),
					'ILS' => __( 'ILS - Israeli Shekel', MS_TEXT_DOMAIN ),
					'JPY' => __( 'JPY - Japanese Yen', MS_TEXT_DOMAIN ),
					'MYR' => __( 'MYR - Malaysian Ringgits', MS_TEXT_DOMAIN ),
					'MXN' => __( 'MXN - Mexican Peso', MS_TEXT_DOMAIN ),
					'NOK' => __( 'NOK - Norwegian Krone', MS_TEXT_DOMAIN ),
					'NZD' => __( 'NZD - New Zealand Dollar', MS_TEXT_DOMAIN ),
					'PHP' => __( 'PHP - Philippine Pesos', MS_TEXT_DOMAIN ),
					'PLN' => __( 'PLN - Polish Zloty', MS_TEXT_DOMAIN ),
					'RUB' => __( 'RUB - Russian Ruble', MS_TEXT_DOMAIN ),
					'SEK' => __( 'SEK - Swedish Krona', MS_TEXT_DOMAIN ),
					'SGD' => __( 'SGD - Singapore Dollar', MS_TEXT_DOMAIN ),
					'TWD' => __( 'TWD - Taiwan New Dollars', MS_TEXT_DOMAIN ),
					'THB' => __( 'THB - Thai Baht', MS_TEXT_DOMAIN ),
					'USD' => __( 'USD - U.S. Dollar', MS_TEXT_DOMAIN ),
					'ZAR' => __( 'ZAR - South African Rand', MS_TEXT_DOMAIN ),
				)
			);
		}

		return $Currencies;
	}

	/**
	 * Set specific property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'currency':
					if ( array_key_exists( $value, self::get_currencies() ) ) {
						$this->$property = $value;
					}
					break;
				case 'invoice_sender_name':
					$this->$property = sanitize_text_field( $value );
					break;
				case 'plugin_enabled':
				case 'initial_setup':
				case 'hide_admin_bar':
					$this->$property = $this->validate_bool( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
		else {
			switch ( $property ) {
				case 'page_no_access':
					$this->pages[ self::SPECIAL_PAGE_NO_ACCESS ] = $this->validate_min( $value, 0 );
					break;
				case 'page_account':
					$this->pages[ self::SPECIAL_PAGE_ACCOUNT ] = $this->validate_min( $value, 0 );
					break;
				case 'page_welcome':
					$this->pages[ self::SPECIAL_PAGE_WELCOME ] = $this->validate_min( $value, 0 );
					break;
				case 'page_signup':
					$this->pages[ self::SPECIAL_PAGE_SIGNUP ] = $this->validate_min( $value, 0 );
					break;
				case 'protection_type':
					if ( MS_Model_Rule_Media::is_valid_protection_type( $value ) ) {
						$this->downloads['protection_type'] = $value;
					}
					break;
				case 'masked_url':
					$this->downloads['masked_url'] = sanitize_text_field( $value );
					break;
			}
		}
	}

	/**
	 * Returns a specific property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param  string $property The name of a property.
	 * @return mixed $value The value of a property.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property;
		}
		else {
			switch ( $property ) {
				case 'currency_symbol':
					// Same translation table in:
					// -> ms-view-membership-setup-payment.js
					$symbol = $this->currency;
					switch ( $symbol ) {
						case 'USD': $symbol = '$'; break;
						case 'EUR': $symbol = '€'; break;
						case 'JPY': $symbol = '¥'; break;
					}
					return $symbol;
			}
		}
	}
}