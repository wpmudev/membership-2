<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Communication model -  failed payment.
 *
 * Persisted by parent class MS_Model_Custom_Post_Type.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Communication_Failed_Payment extends MS_Model_Communication {

	/**
	 * Communication type.
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_FAILED_PAYMENT;

	/**
	 * Add action to payment failed event.
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	public function after_load() {
		parent::after_load();

		if ( $this->enabled ) {
			$this->add_action(
				'ms_model_event_' . MS_Model_Event::TYPE_PAYMENT_FAILED,
				'enqueue_messages', 10, 2
			);
		}
	}

	/**
	 * Get communication description.
	 *
	 * @since 1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __(
			'Sent when a member automatic recurring payment fails.', MS_TEXT_DOMAIN
		);
	}

	/**
	 * Communication default communication.
	 *
	 * @since 1.0.0
	 */
	public function reset_to_default() {
		parent::reset_to_default();

		$this->subject = __( 'Your membership payment has failed', MS_TEXT_DOMAIN );
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
	 * @since 1.0.0
	 * @return string The email message.
	 */
	public static function get_default_message() {
		$subject = sprintf(
			__( 'Hi %1$s,', MS_TEXT_DOMAIN ),
			self::COMM_VAR_USERNAME
		);
		$body_notice = sprintf(
			__( 'Unfortunately, your recurring payment for your %1$s membership at %2$s has failed.', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_NAME,
			self::COMM_VAR_BLOG_NAME
		);
		$body_continue = sprintf(
			__( 'To continue as a member, please review and edit your billing information as necessary in your account here: %1$s', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_ACCOUNT_PAGE_URL
		);
		$body_invoice = __( 'Here is your latest invoice which is due now:', MS_TEXT_DOMAIN );

		$html = sprintf(
			'<h2>%1$s</h2><br /><br />%2$s<br /><br />%3$s<br /><br />%4$s<br /><br />%5$s',
			$subject,
			$body_notice,
			$body_continue,
			$body_invoice,
			self::COMM_VAR_MS_INVOICE
		);

		return apply_filters(
			'ms_model_communication_failed_payment_get_default_message',
			$html
		);
	}
}