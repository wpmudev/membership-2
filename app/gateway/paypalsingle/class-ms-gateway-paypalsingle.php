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
 * Gateway: Paypal Single
 *
 * Officially: PayPal Payments Standard
 * https://developer.paypal.com/docs/classic/paypal-payments-standard/gs_PayPalPaymentsStandard/
 *
 * Process single paypal purchases/payments.
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Gateway_Paypalsingle extends MS_Gateway {

	const ID = 'paypalsingle';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Gateway ID.
	 *
	 * @since 1.0.0
	 * @var int $id
	 */
	protected $id = self::ID;

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
	 * Gateway allow Pro rating.
	 *
	 * @todo To be released in further versions.
	 * @since 1.0.0
	 * @var bool $pro_rate
	 */
	protected $pro_rate = true;

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
	 * Manual payment indicator.
	 *
	 * If the gateway does not allow automatic reccuring billing.
	 *
	 * @since 1.0.0
	 * @var bool $manual_payment
	 */
	protected $manual_payment = true;

	/**
	 * Paypal merchant/seller's email.
	 *
	 * @since 1.0.0
	 * @var bool $paypal_email
	 */
	protected $paypal_email;

	/**
	 * Paypal country site.
	 *
	 * @since 1.0.0
	 * @var bool $paypal_site
	 */
	protected $paypal_site;


	/**
	 * Hook to add custom transaction status.
	 * This is called by the MS_Factory
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->name = __( 'PayPal Single Gateway', MS_TEXT_DOMAIN );
	}

	/**
	 * Processes gateway IPN return.
	 *
	 * @since 1.0.0
	 */
	public function handle_return() {
		do_action(
			'ms_gateway_paypalsingle_handle_return_before',
			$this
		);

		lib2()->array->strip_slashes( $_POST, 'pending_reason' );

		if ( ( isset($_POST['payment_status'] ) || isset( $_POST['txn_type'] ) )
			&& ! empty( $_POST['invoice'] )
		) {
			if ( $this->is_live_mode() ) {
				$domain = 'https://www.paypal.com';
			} else {
				$domain = 'https://www.sandbox.paypal.com';
			}

			// Paypal post authenticity verification
			$ipn_data = (array) stripslashes_deep( $_POST );
			$ipn_data['cmd'] = '_notify-validate';
			$response = wp_remote_post(
				$domain . '/cgi-bin/webscr',
				array(
					'timeout' => 60,
					'sslverify' => false,
					'httpversion' => '1.1',
					'body' => $ipn_data,
				)
			);

			if ( ! is_wp_error( $response )
				&& 200 == $response['response']['code']
				&& ! empty( $response['body'] )
				&& 'VERIFIED' == $response['body']
			) {
				MS_Helper_Debug::log( 'PayPal Transaction Verified' );
			} else {
				$error = 'Response Error: Unexpected transaction response';
				MS_Helper_Debug::log( $error );
				MS_Helper_Debug::log( $response );
				wp_die( $error );
			}

			$new_status = false;
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $_POST['invoice'] );
			$ms_relationship = MS_Factory::load(
				'MS_Model_Relationship',
				$invoice->ms_relationship_id
			);
			$membership = $ms_relationship->get_membership();
			$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );

			if ( empty( $invoice ) ) {
				$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
			}

			$external_id = $_POST['txn_id'];
			$amount = $_POST['mc_gross'];
			$currency = $_POST['mc_currency'];
			$status = null;
			$notes = null;

			// Process PayPal response
			switch ( $_POST['payment_status'] ) {
				// Successful payment
				case 'Completed':
				case 'Processed':
					if ( $amount == $invoice->total ) {
						$status = MS_Model_Invoice::STATUS_PAID;
					} else {
						$notes = __( 'Payment amount differs from invoice total.', MS_TEXT_DOMAIN );
						$status = MS_Model_Invoice::STATUS_DENIED;
					}
					break;

				case 'Reversed':
					$notes = __( 'Last transaction has been reversed. Reason: Payment has been reversed (charge back). ', MS_TEXT_DOMAIN );
					$status = MS_Model_Invoice::STATUS_DENIED;
					break;

				case 'Refunded':
					$notes = __( 'Last transaction has been reversed. Reason: Payment has been refunded', MS_TEXT_DOMAIN );
					$status = MS_Model_Invoice::STATUS_DENIED;
					break;

				case 'Denied':
					$notes = __( 'Last transaction has been reversed. Reason: Payment Denied', MS_TEXT_DOMAIN );
					$status = MS_Model_Invoice::STATUS_DENIED;
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
						'*' => '',
					);

					$reason = $_POST['pending_reason'];
					$notes = __( 'Last transaction is pending. Reason: ', MS_TEXT_DOMAIN ) .
						( isset($pending_str[$reason] ) ? $pending_str[$reason] : $pending_str['*'] );
					$status = MS_Model_Invoice::STATUS_PENDING;
					break;

				default:
				case 'Partially-Refunded':
				case 'In-Progress':
					break;
			}

			if ( 'new_case' == $_POST['txn_type']
				&& 'dispute' == $_POST['case_type']
			) {
				// Status: Dispute
				$status = MS_Model_Invoice::STATUS_DENIED;
			}

			if ( ! empty( $notes ) ) { $invoice->add_notes( $notes ); }
			$invoice->save();

			if ( MS_Model_Invoice::STATUS_PAID == $status ) {
				$invoice->pay_it( $this->id, $external_id );
			} elseif ( ! empty( $status ) ) {
				$invoice->status = $status;
				$invoice->save();
				$invoice->changed();
			}

			do_action(
				'ms_gateway_paypalsingle_payment_processed_' . $status,
				$invoice,
				$ms_relationship
			);
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.

			$u_agent = $_SERVER['HTTP_USER_AGENT'];
			if ( false === strpos( $u_agent, 'PayPal' ) ) {
				// Very likely someone tried to open the URL manually. Redirect to home page
				$notes = 'Error: Missing POST variables. Redirect user to Home-URL.';
				MS_Helper_Debug::log( $notes );
				wp_safe_redirect( home_url() );
				exit;
			} else {
				status_header( 404 );
				$notes = 'Error: Missing POST variables. Identification is not possible.';
				MS_Helper_Debug::log( $notes );
				exit;
			}
		}

		do_action(
			'ms_gateway_paypalsingle_handle_return_after',
			$this
		);
	}

	/**
	 * Get paypal country sites list.
	 *
	 * @see MS_Gateway::get_country_codes()
	 * @since 1.0.0
	 * @return array
	 */
	public function get_paypal_sites() {
		return apply_filters(
			'ms_gateway_paylpaysingle_get_paypal_sites',
			self::get_country_codes()
		);
	}

	/**
	 * Verify required fields.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function is_configured() {
		$is_configured = true;
		$required = array( 'paypal_email', 'paypal_site' );

		foreach ( $required as $field ) {
			if ( empty( $this->$field ) ) {
				$is_configured = false;
				break;
			}
		}

		return apply_filters(
			'ms_gateway_paypalsingle_is_configured',
			$is_configured
		);
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'paypal_site':
					if ( array_key_exists( $value, self::get_paypal_sites() ) ) {
						$this->$property = $value;
					}
					break;

				default:
					parent::__set( $property, $value );
					break;
			}
		}

		do_action(
			'ms_gateway_paypalsingle__set_after',
			$property,
			$value,
			$this
		);
	}

}