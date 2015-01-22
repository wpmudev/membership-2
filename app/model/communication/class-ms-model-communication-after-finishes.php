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
 * Communication model - after membership finishes.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Communication_After_Finishes extends MS_Model_Communication {

	/**
	 * Communication type.
	 *
	 * @since 1.0.0
	 * @var string The communication type.
	 */
	protected $type = self::COMM_TYPE_AFTER_FINISHES;

	/**
	 * Get communication description.
	 *
	 * @since 1.0.0
	 * @return string The description.
	 */
	public function get_description() {
		return __(
			'Sent a predefined number of days after the membership finishes. You must decide how many days after a message is to be sent.', MS_TEXT_DOMAIN
		);
	}

	/**
	 * Communication default communication.
	 *
	 * @since 1.0.0
	 */
	public function reset_to_default() {
		parent::reset_to_default();

		$this->subject = sprintf(
			__( 'Reminder: your %s membership has ended', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_NAME
		);
		$this->message = self::get_default_message();
		$this->enabled = false;
		$this->period_enabled = true;
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
			__( 'This is a reminder that your %1$s membership at %2$s has ended on %3$s.', MS_TEXT_DOMAIN ),
			self::COMM_VAR_MS_NAME,
			self::COMM_VAR_BLOG_NAME,
			self::COMM_VAR_MS_EXPIRY_DATE
		);
		$body_renew = sprintf(
			__( 'You can renew your membership here: %1$s', MS_TEXT_DOMAIN ),
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