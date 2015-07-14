<?php
/**
 * Communication model - after payment is due.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Communication_After_Payment_Due extends MS_Model_Communication {

	/**
	 * Communication type.
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_AFTER_PAYMENT_DUE;

	/**
	 * Populates the field title/description of the Period before/after field
	 * in the admin settings.
	 *
	 * @since  1.0.0
	 * @param array $field A HTML definition, passed to lib2()->html->element()
	 */
	public function set_period_name( $field ) {
		$field['title'] = __( 'Message Delay', MS_TEXT_DOMAIN );
		$field['desc'] = __( 'Use "0" to send instantly, or another value to delay the message.', MS_TEXT_DOMAIN );

		return $field;
	}

	/**
	 * Get communication description.
	 *
	 * @since  1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __(
			'Sent a predefined number of days after the payment is due. You must decide how many days after a message is to be sent.', MS_TEXT_DOMAIN
		);
	}

	/**
	 * Communication default communication.
	 *
	 * @since  1.0.0
	 */
	public function reset_to_default() {
		parent::reset_to_default();

		$this->subject = __( 'Membership payment due', MS_TEXT_DOMAIN );
		$this->message = self::get_default_message();
		$this->enabled = false;
		$this->period_enabled = true;
		$this->save();

		do_action( 'ms_model_communication_reset_to_default_after', $this->type, $this );
	}

	/**
	 * Get default email message.
	 *
	 * @since  1.0.0
	 * @return string The email message.
	 */
	public static function get_default_message() {
		$subject = sprintf(
			__( 'Hi %1$s,', MS_TEXT_DOMAIN ),
			self::COMM_VAR_USERNAME
		);
		$body_notice = sprintf(
			__( 'This is a reminder that the payment for your %1$s membership at %2$s is now due (%3$s).', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_NAME,
			self::COMM_VAR_BLOG_NAME,
			self::COMM_VAR_MS_EXPIRY_DATE
		);
		$body_invoice = __( 'Here are your latest invoice details:', MS_TEXT_DOMAIN );

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s<br /><br />%3$s<br /><br />%4$s',
			$subject,
			$body_notice,
			$body_invoice,
			self::COMM_VAR_MS_INVOICE
		);

		return apply_filters(
			'ms_model_communication_after_payment_due_get_default_message',
			$html
		);
	}
}