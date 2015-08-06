<?php
/**
 * Communication model - invoice.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Communication_Invoice extends MS_Model_Communication {

	/**
	 * Communication type.
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_INVOICE;

	/**
	 * Get communication description.
	 *
	 * @since  1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __( 'Sent each time a payment has been made.', MS_TEXT_DOMAIN );
	}

	/**
	 * Communication default communication.
	 *
	 * @since  1.0.0
	 */
	public function reset_to_default() {
		parent::reset_to_default();

		$this->subject = sprintf(
			__( 'Your %s membership receipt', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_NAME
		);
		$this->message = self::get_default_message();
		$this->enabled = false;

		do_action(
			'ms_model_communication_reset_to_default_after',
			$this->type,
			$this
		);
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
			__( 'We\'ve received your payment for your %1$s membership at %2$s. Thanks!', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_NAME,
			self::COMM_VAR_BLOG_NAME
		);
		$body_invoice = __( 'Here are your latest payment details:', MS_TEXT_DOMAIN );

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s<br /><br />%3$s<br /><br />%4$s',
			$subject,
			$body_notice,
			$body_invoice,
			self::COMM_VAR_MS_INVOICE
		);

		return apply_filters(
			'ms_model_communication_registration_get_default_message',
			$html
		);
	}
}