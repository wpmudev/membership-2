<?php
/**
 * Communication model - email verification
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.1.3
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Communication_Registration_Verify extends MS_Model_Communication {

	/**
	 * Add action to credit card expire event.
	 *
	 * Related Action Hooks:
	 * - ms_model_event_paid
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_REGISTRATION_VERIFY;


	/**
	 * Defines if it should be shown to admin
	 *
	 * Only relevant for user specific mails
	 *
	 * @since 1.1.3
	 * @var   bool
	 */
	protected $show_admin_cc = false;

	/**
	 * Get communication description.
	 *
	 * @since  1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __(
			'Sent when a user completes the signup to verify their email', 'membership2'
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
			__( 'Confirmation of your email at %s', 'membership2' ),
			self::COMM_VAR_BLOG_NAME
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
	 * @since  1.1.3
	 * 
	 * @return string The email message.
	 */
	public static function get_default_message() {
		$subject = sprintf(
			__( 'Hi %1$s,', 'membership2' ),
			self::COMM_VAR_USERNAME
		);
		$body_notice = sprintf(
			__( 'Thanks for registering for an account at %1$s!', 'membership2' ),
			self::COMM_VAR_BLOG_NAME
		);
		$body_account = sprintf(
			__( 'Please click on the link %1$s to verify your account', 'membership2' ),
			self::COMM_VAR_VERIFICATION_URL
		);

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s<br /><br />%3$s',
			$subject,
			$body_notice,
			$body_account
		);

		return apply_filters(
			'ms_model_communication_registration_verify_get_default_message',
			$html
		);
	}

	/**
	 * Process communication verification code.
	 *
	 * @since  1.1.3
	 */
	public function process_communication( $event, $subscription ) {

		do_action(
			'ms_model_communication_registration_verify_process_before',
			$subscription,
			$event,
			$this
		);

		$this->send_message( $subscription );

		do_action(
			'ms_model_communication_registration_verify_process_after',
			$subscription,
			$event,
			$this
		);
	}
}