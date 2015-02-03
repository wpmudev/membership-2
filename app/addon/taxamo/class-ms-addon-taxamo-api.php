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
	 * Confirms a single transaction.
	 *
	 * This is done once we get the payment confirmation from the payment
	 * provider.
	 *
	 * @since  1.1.0
	 * @param  string $transaction Taxamo Transaction code
	 */
	static public function confirm( $transaction ) {
		self::taxamo()->confirmTransaction( $transaction_key, null );
	}

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
			} else {
				$Info = (object) array(
					'country' => 'US',
					'rate' => 0,
					'name' => __( 'No Tax', MS_TEXT_DOMAIN ),
					'amount' => 0,
				);
			}
		}

		$Info->amount = $amount / 100 * $Info->rate;

		return $Info;
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

	static private function get_transaction( $amount, $ms_id = 0, $label = '' ) {
		$transaction_line = array(
			'amount' => $amount,
			'line_price' => $amount,
			'quantity' => 1,
			'custom_id' => $ms_id,
			'description' => $label,
		);

		$transaction = array(
			'currency_code' => MS_Addon_Taxamo::model()->currency,
			'buyer_ip' => self::buyer_ip(),
			'billing_country_code' => self::country_code(),
			'transaction_lines' => array( $transaction_line ),
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

		$location = WDev()->store_get( 'ms_ta_country' );

		if ( ! count( $location ) ) {
			self::determine_country();
			$location = WDev()->store_get( 'ms_ta_country' );
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
		$country = (object)(array) self::taxamo()->locateMyIP();
		WDev()->store_add( 'ms_ta_country', $country );
	}

	/**
	 * Returns the Taxamo REST API object.
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
