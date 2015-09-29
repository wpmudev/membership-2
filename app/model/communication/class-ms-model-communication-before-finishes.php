<?php
/**
 * Communication model - before finishes.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Communication_Before_Finishes extends MS_Model_Communication {

	/**
	 * Communication type.
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_BEFORE_FINISHES;

	/**
	 * Populates the field title/description of the Period before/after field
	 * in the admin settings.
	 *
	 * @since  1.0.0
	 * @param array $field A HTML definition, passed to lib3()->html->element()
	 */
	public function set_period_name( $field ) {
		$field['title'] = __( 'Notice Period', 'membership2' );
		$field['desc'] = __( 'Define, how many days in advance the user should be notified.', 'membership2' );

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
			'Sent a predefined number of days before the membership finishes. You must decide how many days beforehand a message is to be sent.', 'membership2'
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
			__( 'Your %s membership will end soon', 'membership2' ),
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
			__( 'This is just a reminder that your %1$s membership at %2$s will end in %3$s.', 'membership2' ),
			self::COMM_VAR_MS_NAME,
			self::COMM_VAR_BLOG_NAME,
			self::COMM_VAR_MS_REMAINING_DAYS
		);

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s',
			$subject,
			$body_notice
		);

		return apply_filters(
			'ms_model_communication_before_finishes_get_default_message',
			$html
		);
	}
}