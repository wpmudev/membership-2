<?php
/**
 * Manual Gateway.
 *
 * Process manual payments (Eg. check, bank transfer)
 *
 * Persisted by parent class MS_Model_Option. Singleton.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Gateway_Manual extends MS_Gateway {

	const ID = 'manual';

	/**
	 * Gateway singleton instance.
	 *
	 * @since  1.0.0
	 * @var string $instance
	 */
	public static $instance;

	/**
	 * Payment information for customer.
	 *
	 * The payment procedures like bank account, agency, etc.
	 *
	 * @since  1.0.0
	 * @var string $payment_info
	 */
	protected $payment_info;


	/**
	 * Hook to show payment info.
	 * This is called by the MS_Factory
	 *
	 * @since  1.0.0
	 */
	public function after_load() {
		parent::after_load();

		$this->id = self::ID;
		$this->name = __( 'Manual Payment Gateway', MS_TEXT_DOMAIN );
		$this->description = __( '(Bank orders, cash, etc)', MS_TEXT_DOMAIN );
		$this->group = __( 'Manual Payment', MS_TEXT_DOMAIN );
		$this->manual_payment = true; // Recurring billed/paid manually
		$this->pro_rate = true;

		if ( $this->active ) {
			$this->add_action(
				'ms_controller_gateway_purchase_info_content',
				'purchase_info_content'
			);
		}
	}

	/**
	 * Show manual purchase/payment information.
	 *
	 * Returns a default messsage if gateway is not configured.
	 *
	 * @hook ms_controller_gateway_purchase_info_content
	 *
	 * @since  1.0.0
	 * @return string The payment info.
	 */
	public function purchase_info_content() {
		static $Processed = false;

		/**
		 * If some plugin calls `the_content()` multiple times then this
		 * function will also run multiple times.
		 * We want to process the details only once, so we have this condition!
		 */
		if ( ! $Processed ) {
			$Processed = true;

			do_action(
				'ms_gateway_manual_purchase_info_content_before',
				$this
			);

			if ( empty( $this->payment_info ) ) {
				$link = MS_Controller_Plugin::get_admin_url( 'settings' );
				ob_start();
				?>
					<?php _e( 'This is only an example of manual payment gateway instructions', MS_TEXT_DOMAIN ); ?>
					<br />
					<?php
					printf(
						__( 'Edit it %shere%s', MS_TEXT_DOMAIN ),
						'<a href="' . $link . '">',
						'</a>'
					);
					?>
					<br /><br />
					<?php _e( 'Name: Example name.', MS_TEXT_DOMAIN ); ?>
					<br />
					<?php _e( 'Bank: Example bank.', MS_TEXT_DOMAIN ); ?>
					<br />
					<?php _e( 'Bank account: Example bank account 1234.', MS_TEXT_DOMAIN ); ?>
					<br />
				<?php
				$this->payment_info = ob_get_clean();
			}

			$this->payment_info = wpautop( $this->payment_info );

			if ( ! empty( $_POST['ms_relationship_id'] ) ) {
				$subscription = MS_Factory::load(
					'MS_Model_Relationship',
					$_POST['ms_relationship_id']
				);
				$invoice = $subscription->get_current_invoice();
				$this->payment_info .= sprintf(
					'<div class="ms-manual-price">%s: <span class="ms-price">%s%s</span></div>',
					__( 'Total value', MS_TEXT_DOMAIN ),
					$invoice->currency,
					$invoice->total
				);

				// The user did make his intention to pay the invoice. Set status
				// to billed.
				$invoice->status = MS_Model_Invoice::STATUS_BILLED;
				$invoice->save();
			}
		}

		return apply_filters(
			'ms_gateway_manual_purchase_info_content',
			$this->payment_info
		);
	}

	/**
	 * Verify required fields.
	 *
	 * @since  1.0.0
	 * @return boolean True if configured.
	 */
	public function is_configured() {
		$is_configured = true;
		$required = array( 'payment_info' );

		foreach ( $required as $field ) {
			if ( empty( $this->$field ) ) {
				$is_configured = false;
				break;
			}
		}

		return apply_filters(
			'ms_gateway_manual_is_configured',
			$is_configured
		);
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since  1.0.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'payment_info':
					$this->$property = wp_kses_post( $value );
					break;

				default:
					parent::__set( $property, $value );
					break;
			}
		}

		do_action(
			'ms_gateway_manual__set_after',
			$property,
			$value,
			$this
		);
	}

}