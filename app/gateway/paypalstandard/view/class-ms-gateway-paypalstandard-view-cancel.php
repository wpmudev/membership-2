<?php
/**
 * Render Paypal cancel button for subscriptions.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 * @package Membership2
 * @subpackage View
 */
class MS_Gateway_Paypalstandard_View_Cancel extends MS_View {

	/**
	 * Create the Cancel Button.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_button() {
		$gateway = $this->data['gateway'];
		$button = null;

		if ( ! empty( $this->data['ms_relationship'] ) ) {
			$ms_relationship = $this->data['ms_relationship'];
			$membership = $ms_relationship->get_membership();

			if ( MS_Model_Membership::PAYMENT_TYPE_RECURRING == $membership->payment_type
				|| $membership->trial_period_enabled
			) {
				if ( MS_Gateway::MODE_LIVE == $gateway->mode ) {
					$cancel_url = 'https://www.paypal.com/cgi-bin/webscr';
				} else {
					$cancel_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
				}

				$button = array(
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'url' => $cancel_url . '?cmd=_subscr-find&alias=' . $gateway->merchant_id,
					'value' => '<img src="https://www.paypal.com/en_US/i/btn/btn_unsubscribe_LG.gif" alt="" />',
				);
			}
		}

		return apply_filters(
			'ms_gateway_paypalstandard_cancel_button',
			$button,
			$this
		);
	}
}