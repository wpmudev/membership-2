<?php
/**
 * Communication model -  credit card expire.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Communication_Credit_Card_Expire extends MS_Model_Communication {

	/**
	 * Communication type.
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_CREDIT_CARD_EXPIRE;

	/**
	 * Populates the field title/description of the Period before/after field
	 * in the admin settings.
	 *
	 * @since  1.0.0
	 * @param array $field A HTML definition, passed to lib3()->html->element()
	 */
	public function set_period_name( $field ) {
		$field['title'] = __( 'Notify Period', 'membership2' );
		$field['desc'] = __( 'We want to notify the user some days in advance, so there is time to react.<br>Enter here, how many days in advance this message should be sent.', 'membership2' );

		return $field;
	}

	/**
	 * Get communication description.
	 *
	 * @since  1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __( 'A notice to indicate that the member\'s credit card is about to expire.', 'membership2' );
	}

	/**
	 * Communication default communication.
	 *
	 * @since  1.0.0
	 */
	public function reset_to_default() {
		parent::reset_to_default();

		$this->subject = __( 'Your credit card is about to expire', 'membership2' );
		$this->message = self::get_default_message();
		$this->enabled = false;
		$this->period_enabled = true;

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
			__( 'Hi %1$s,', 'membership2' ),
			self::COMM_VAR_USERNAME
		);
		$body_notice = __( 'This is a reminder that your credit card is about to expire.', 'membership2' );
		$body_continue = sprintf(
			__( 'To continue your %1$s membership at %2$s, please update your card details before your next payment is due here: %3$s', 'membership2' ),
			self::COMM_VAR_MS_NAME,
			self::COMM_VAR_BLOG_NAME,
			self::COMM_VAR_MS_ACCOUNT_PAGE_URL
		);

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s<br /><br />%3$s',
			$subject,
			$body_notice,
			$body_continue
		);

		return apply_filters(
			'ms_model_communication_credit_card_expire_get_default_message',
			$html
		);
	}
}