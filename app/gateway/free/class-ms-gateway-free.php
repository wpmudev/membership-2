<?php
/**
 * Free Gateway.
 *
 * Process free memberships.
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_Free extends MS_Gateway {

	const ID = 'free';

	/**
	 * Gateway singleton instance.
	 *
	 * @since  1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Hook to show payment info.
	 * This is called by the MS_Factory
	 *
	 * @since  1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->id = self::ID;
		$this->name = __( 'Free Gateway', MS_TEXT_DOMAIN );
		$this->group = '';
		$this->active = true;
		$this->manual_payment = true; // Recurring billed/paid manually
	}

	/**
	 * Return status if all fields are configured
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function is_configured() {
		// Free products need no payment-configuration. Always true.
		return true;
	}

	/**
	 * Processes purchase action.
	 * This can happen when a 100% coupon is applied and an otherwise paid
	 * membership becomes a free membership during checkout.
	 *
	 * We need to confirm that it's actually free and mark it paid.
	 *
	 * @since  1.0.0
	 * @param MS_Model_Relationship $subscription The related membership relationship.
	 */
	public function process_purchase( $subscription ) {
		do_action(
			'ms_gateway_free_process_purchase_before',
			$subscription,
			$this
		);

		$invoice = $subscription->get_current_invoice();

		if ( 0 == $invoice->total || $invoice->uses_trial ) {
			// Free, just process.
			$invoice->pay_it( self::ID, '' );
		}

		return apply_filters(
			'ms_gateway_free_process_purchase',
			$invoice,
			$this
		);
	}

}
