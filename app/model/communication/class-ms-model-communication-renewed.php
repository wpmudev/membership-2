<?php
/**
 * Communication model - subscription was renewed.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Communication_Renewed extends MS_Model_Communication {

	/**
	 * Add action to credit card expire event.
	 *
	 * Related Action Hooks:
	 * - ms_model_event_paid
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_RENEWED;

	/**
	 * Add action to renewal event.
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	public function after_load() {
		parent::after_load();

		if ( $this->enabled ) {
			$this->add_action(
				'ms_model_event_'. MS_Model_Event::TYPE_MS_RENEWED,
				'process_communication_renewed', 10, 2
			);
		}
	}

	/**
	 * Get communication description.
	 *
	 * @since  1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __(
			'Sent to the member when a previously expired or cancelled membership is renewed - triggered for both free and paid memberships.', MS_TEXT_DOMAIN
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
			__( 'Subscription renewed for %s', MS_TEXT_DOMAIN ),
			self::COMM_VAR_BLOG_NAME
		);
		$this->message = self::get_default_message();
		$this->enabled = false;
		$this->save();

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
			__( 'Thanks for renewing your <strong>%1$s</strong> subscription over at %2$s!', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_NAME,
			self::COMM_VAR_BLOG_NAME
		);
		$body_account = sprintf(
			__( 'You can review and edit your membership details here: %1$s', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_ACCOUNT_PAGE_URL
		);

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s<br /><br />%3$s<br />',
			$subject,
			$body_notice,
			$body_account
		);

		return apply_filters(
			'ms_model_communication_renewed_get_default_message',
			$html
		);
	}

	/**
	 * Process communication registration.
	 *
	 * Related Action Hooks:
	 * - ms_model_event_renewed
	 *
	 * @since  1.0.0
	 * @var string The communication type.
	 */
	public function process_communication_renewed( $event, $subscription ) {
		do_action(
			'ms_model_communication_renewed_process_before',
			$subscription,
			$event,
			$this
		);

		$this->send_message( $subscription );

		do_action(
			'ms_model_communication_renewed_process_after',
			$subscription,
			$event,
			$this
		);
	}
}