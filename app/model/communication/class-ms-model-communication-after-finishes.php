<?php
/**
 * Communication model - after membership finishes.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Communication_After_Finishes extends MS_Model_Communication {

	/**
	 * Communication type.
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_AFTER_FINISHES;

	/**
	 * Populates the field title/description of the Period before/after field
	 * in the admin settings.
	 *
	 * @since  1.0.0
	 * @param array $field A HTML definition, passed to lib3()->html->element()
	 */
	public function set_period_name( $field ) {
		$field['title'] = __( 'Message Delay', 'membership2' );
		$field['desc'] = __( 'Use "0" to send instantly, or another value to delay the message.', 'membership2' );

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
			'Sent a predefined number of days after the membership finishes. You must decide how many days after a message is to be sent.', 'membership2'
		);
	}

	/**
	 * Communication default communication.
	 *
	 * @since  1.0.0
	 */
	public function reset_to_default() {
		parent::reset_to_default();

		$this->subject = sprintf(
			__( 'Reminder: your %s membership has ended', 'membership2' ),
			self::COMM_VAR_MS_NAME
		);
		$this->message = self::get_default_message();
		$this->enabled = false;
		$this->period_enabled = true;

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
			__( 'Hi %1$s,', 'membership2' ),
			self::COMM_VAR_USERNAME
		);
		$body_notice = sprintf(
			__( 'This is a reminder that your %1$s membership at %2$s has ended on %3$s.', 'membership2' ),
			self::COMM_VAR_MS_NAME,
			self::COMM_VAR_BLOG_NAME,
			self::COMM_VAR_MS_EXPIRY_DATE
		);
		$body_renew = sprintf(
			__( 'You can renew your membership here: %1$s', 'membership2' ),
			self::COMM_VAR_MS_ACCOUNT_PAGE_URL
		);

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s<br /><br />%3$s',
			$subject,
			$body_notice,
			$body_renew
		);

		return apply_filters(
			'ms_model_communication_after_finished_get_default_message',
			$html
		);
	}
}