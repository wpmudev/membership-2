<?php
/**
 * Gateway: 2Checkout
 *
 * @since 1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_2checkout extends MS_Gateway {

	const ID = '2checkout';

	/**
	 * Gateway singleton instance.
	 *
	 * @since 1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * The 2Checkout Account number (also called "Seller ID" sometimes)
	 *
	 * @var string
	 */
	public $seller_id = '';

	/**
	 * The 2Checkout secret word that is used to create the return value hash.
	 *
	 * @var string
	 */
	public $secret_word = '';

	/**
	 * Hook to show payment info.
	 * This is called by the MS_Factory
	 *
	 * @since 1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->id = self::ID;
		$this->name = __( '2Checkout', MS_TEXT_DOMAIN );
		$this->group = __( '2Checkout', MS_TEXT_DOMAIN );
		$this->active = true;
		$this->manual_payment = true;
	}

	/**
	 * Processes gateway IPN return.
	 *
	 * @since 1.0.0
	 */
	public function handle_return() {
		$success = false;
		$exit = false;
		$redirect = false;
		$notes = '';
		$status = null;
		$external_id = null;
		$invoice_id = 0;
		$subscription_id = 0;
		$amount = 0;

		if ( ! empty( $_POST['vendor_order_id'] ) && ! empty( $_POST['md5_hash'] ) ) {
			$invoice_id = intval( $_POST['vendor_order_id'] );
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $invoice_id );

			$raw_hash = $_POST['sale_id'] . $this->seller_id . $_POST['invoice_id'] . $this->secret_word;
			$md5_hash = strtoupper( md5( $raw_hash ) );

			if ( $md5_hash == $_POST['md5_hash']
				&& ! empty( $_POST['message_type'] )
				&& $invoice->id = $invoice_id
			) {
				$subscription = $invoice->get_subscription();
				$membership = $subscription->get_membership();
				$member = $subscription->get_member();
				$subscription_id = $subscription->id;
				$external_id = $_POST['invoice_id'];

				switch ( $_POST['message_type'] ) {
					case 'RECURRING_INSTALLMENT_SUCCESS':
						$notice = 'Payment received';
						$success = true;
						break;

					case 'RECURRING_STOPPED':
						$notice = 'Recurring payments stopped manually';
						$member->cancel_membership( $membership->id );
						$member->save();
						break;

					case 'INVOICE_STATUS_CHANGED':
						$notice = sprintf(
							'Ignored: Invoice was %s',
							$_POST['invoice_status']
						);
						break;

					case 'FRAUD_STATUS_CHANGED':
						$notice = 'Ignored: Users Fraud-status was checked';
						break;

					case 'ORDER_CREATED':
						$notice = 'Ignored: 2Checkout created a new order';
						break;

					case 'RECURRING_RESTARTED':
						$notice = 'Ignored: Recurring payments started';
						break;

					case 'RECURRING_COMPLETE':
						$notice = 'Ignored: Recurring complete';
						break;

					case 'RECURRING_INSTALLMENT_FAILED':
						$notice = 'Ignored: Recurring payment failed';
						$status = MS_Model_Invoice::STATUS_PENDING;
						break;

					default:
						$notice = sprintf(
							'Ignored: Unclear command "%s"',
							$_POST['message_type']
						);
						break;
				}

				$invoice->add_notes( $notes );
				$invoice->save();

				if ( $success ) {
					$invoice->pay_it( $this->id, $external_id );
				} elseif ( ! empty( $status ) ) {
					$invoice->status = $status;
					$invoice->save();
					$invoice->changed();
				}

				do_action(
					'ms_gateway_2checkout_payment_processed_' . $status,
					$invoice,
					$subscription
				);
			} else {
				$reason = 'Unexpected transaction response';

				switch ( true ) {
					case $md5_hash != $_POST['md5_hash']:
						$reason = 'MD5 Hash invalid';
						break;

					case empty( $_POST['message_type'] ):
						$reason = 'Message type is empty';
						break;

					case $invoice->id != $invoice_id:
						$reason = sprintf(
							'Expected invoice_id "%s" but got "%s"',
							$invoice->id,
							$invoice_id
						);
						break;
				}

				$notes = 'Response Error: ' . $reason;
				MS_Helper_Debug::log( $notes );
				MS_Helper_Debug::log( $response );
				$exit = true;
			}
		} else {
			// Did not find expected POST variables. Possible access attempt from a non PayPal site.

			$notes = 'Error: Missing POST variables. Identification is not possible.';
			MS_Helper_Debug::log( $notes );
			$redirect = home_url();
			$exit = true;
		}

		do_action(
			'ms_gateway_transaction_log',
			self::ID, // gateway ID
			'handle', // request|process|handle
			$success, // success flag
			$subscription_id, // subscription ID
			$invoice_id, // invoice ID
			$amount, // charged amount
			$notes // Descriptive text
		);

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit;
		}
		if ( $exit ) {
			exit;
		}

		do_action(
			'ms_gateway_2checkout_handle_return_after',
			$this
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
		$required = array( 'seller_id', 'secret_word' );

		foreach ( $required as $field ) {
			$value = $this->$field;
			if ( empty( $value ) ) {
				$is_configured = false;
				break;
			}
		}

		return apply_filters(
			'ms_gateway_2checkout_is_configured',
			$is_configured
		);
	}

}
