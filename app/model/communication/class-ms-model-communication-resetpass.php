<?php
/**
 * Communication model - user forgot password.
 * Triggered when user requests a reset-password email via the M2
 * Forgot Password form.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.2.3
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Communication_Resetpass extends MS_Model_Communication {

	/**
	 * Communication type.
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_RESETPASSWORD;

	/**
	 * Get communication description.
	 *
	 * @since  1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return sprintf(
			__( 'Reset-Password email sent to the user when the requests a new password via the "Forgot Password" form.<br>Note that this email must include the variable %s to allow the member to reset his password.', 'membership2' ),
			'<code>' . self::COMM_VAR_RESETURL . '</code>'
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
			__( 'Reset your password for %s', 'membership2' ),
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
	 * @since  1.0.0
	 * @return string The email message.
	 */
	public static function get_default_message() {
		$subject = sprintf(
			__( 'Hi %1$s,', 'membership2' ),
			self::COMM_VAR_USERNAME
		);
		$body_notice = sprintf(
			__( 'Someone requested that the password be reset for the following account: %sIf this was a mistake, just ignore this email and nothing will happen.%s', 'membership2' ),
			'<br><br>' . self::COMM_VAR_BLOG_URL . '<br>' .
			sprintf( __( 'Your username: %s', 'membership2' ), self::COMM_VAR_USERNAME ) . '<br><br>',
			'<br><br><a href="' . self::COMM_VAR_RESETURL . '">' . __( 'Click here to reset your password', 'membership2' ) . '</a><br>' . self::COMM_VAR_RESETURL . '<br>'
		);

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s',
			$subject,
			$body_notice
		);

		return apply_filters(
			'ms_model_communication_resetpassword_get_default_message',
			$html
		);
	}

	/**
	 * Process communication registration.
	 *
	 * @since  1.0.0
	 */
	public function process_communication( $event, $subscription ) {
		do_action(
			'ms_model_communication_resetpassword_process_before',
			$subscription,
			$event,
			$this
		);

		// Flag if we sent the customized reset password email.
		if ( $this->send_message( $subscription ) ) {
			add_action( 'ms_sent_reset_password_email', '__return_true' );
		}

		do_action(
			'ms_model_communication_resetpassword_process_after',
			$subscription,
			$event,
			$this
		);
	}
}