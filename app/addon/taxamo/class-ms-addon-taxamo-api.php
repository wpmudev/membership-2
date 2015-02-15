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
 * Taxamo API functions.
 *
 * @since 1.1.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Addon_Taxamo_Api extends MS_Controller {

	/**
	 * Returns tax information that must be applied to the specified amount.
	 *
	 * The country for the tax calculation is automatically determined by Taxamo.
	 *
	 * @since  1.1.0
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
			try {
				$resp = self::taxamo()->calculateSimpleTax(
					null // buyer_credit_card_prefix
					,null // buyer_tax_number
					,null // product_type
					,self::country_code() // force_country_code
					,null // quantity
					,null // unit_price
					,null // total_amount
					,null // tax_deducted
					,100 // amount
					,null // billing_country_code
					,MS_Addon_Taxamo::model()->currency // currency_code
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
	 * @since  1.1.0
	 * @param  numeric $amount Transaction amount
	 */
	static public function register_payment( $amount, $label, $tax_rate, $invoice_id, $name, $email, $gateway ) {
		try {
			// Register the transaction with Taxamo.
			$transaction = self::prepare_transaction( $amount, $label, $tax_rate, $invoice_id, $name, $email );
			$resp = self::taxamo()->createTransaction( array( 'transaction' => $transaction ) );

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
	 * Returns the country-code of the current user.
	 *
	 * @since  1.1.0
	 * @return string 2-Digit country code, e.g. 'IE' for Ireland
	 */
	static public function country_code() {
		$data = self::location_infos();

		if ( isset( $data->country_code ) ) {
			return $data->country_code;
		} else {
			return 'US';
		}
	}

	/**
	 * Returns the IP address of the user.
	 *
	 * @since  1.1.0
	 * @return string IP Address
	 */
	static public function buyer_ip() {
		$data = self::location_infos();

		if ( isset( $data->remote_addr ) ) {
			return $data->remote_addr;
		} else {
			return '127.0.0.1';
		}
	}

	// ------------------------------------------------------- PRIVATE FUNCTIONS

	static private function prepare_transaction( $amount, $label, $tax_rate, $invoice_id, $name, $email ) {
		self::taxamo();

		$tr_line = array(
			'product_type' => 'e-service',
			'total_amount' => floatval( $amount ),
			'tax_rate' => floatval( $tax_rate ),
			'quantity' => 1,
			'custom_id' => (string) $invoice_id,
			'description' => (string) $label,
		);

		$transaction = array(
			'currency_code' => MS_Addon_Taxamo::model()->currency,
			'billing_country_code' => self::country_code(),
			'buyer_ip' => self::buyer_ip(),
			'custom_id' => (string) $invoice_id,
			'buyer_name' => $name,
			'buyer_email' => $email,
			'transaction_lines' => array( $tr_line ),
		);

		return $transaction;
	}

	/**
	 * Returns the location details of the current user.
	 *
	 * @since  1.1.0
	 * @return object
	 */
	static private function location_infos() {
		self::taxamo();

		$location = WDev()->session->get( 'ms_ta_country' );

		if ( ! count( $location ) ) {
			self::determine_country();
			$location = WDev()->session->get( 'ms_ta_country' );
		}

		if ( ! count( $location ) ) {
			$dummy_location = array(
				'remote_addr' => WDev()->current_ip(),
				'country_code' => 'US',
				'country' => array(
					'tax_supported' => false,
					'code' => 'US',
				),
			);

			WDev()->session->add( 'ms_ta_country', $dummy_location );
			$location = WDev()->session->get( 'ms_ta_country' );
		}

		return $location[0];
	}

	/**
	 * Determines the users country based on his IP address.
	 *
	 * Should be called only by `country_code()`
	 *
	 * @since 1.1.0
	 */
	static private function determine_country() {
		try {
			$ip_info = WDev()->current_ip();
			$country = (object)(array) self::taxamo()->locateGivenIP( $ip_info->ip );
			WDev()->session->add( 'ms_ta_country', $country );
		}
		catch ( Exception $ex ) {
			MS_Helper_Debug::log( 'Taxamo error: ' . $ex->getMessage() );
		}
	}

	/**
	 * Returns the Taxamo REST API object.
	 *
	 * Important: All calls to `taxamo()->` functions must be wrapped in
	 * try..catch because an invalid API token will result in a fatal error.
	 *
	 * @since  1.1.0
	 * @return Taxamo
	 */
	static private function taxamo() {
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
	 * @since  1.1.0
	 */
	static private function init() {
		self::taxamo();
		self::country_code();
	}

}
