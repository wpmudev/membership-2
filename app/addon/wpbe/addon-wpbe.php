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
 *
 * WP Better Emails integration.
 *
 */
class MS_Addon_Wpbe extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since 1.1.0
	 */
	const ID = 'addon_wpbe';

	protected $text_message = '';

	/**
	 * Registers the Add-On
	 *
	 * @since  1.1.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $addons ) {
		/*
		// Don't register: Not completed yet...

		$addons[ self::ID ] = (object) array(
			'name' => __( 'WP Better Emails', MS_TEXT_DOMAIN ),
			'description' => __( 'WP Better Emails integration.', MS_TEXT_DOMAIN ),
		);
		*/
		return $addons;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.0
	 */
	public function init() {
		global $wp_better_emails;

		if ( $wp_better_emails ) {
			$this->add_filter( 'ms_model_communication_send_message_html_message', 'html_message' );
		}
	}

	/**
	 * WP Better email wrapper.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $html_message The html message body.
	 * @return string The modified html message.
	 */
	public function html_message( $html_message ) {
		global $wp_better_emails;

		if ( $wp_better_emails ) {
			$html_message = apply_filters(
				'ms_wpbe_html_body',
				$wp_better_emails->template_vars_replacement(
					$wp_better_emails->set_email_template( $html_message, 'template' )
				)
			);

			$this->text_message = apply_filters(
				'wpbe_plaintext_body',
				$wp_better_emails->template_vars_replacement(
					$wp_better_emails->set_email_template( $text_message, 'plaintext_template' )
				)
			);

			$this->add_filter( 'wpbe_plaintext_body', 'text_message' );
			add_filter( 'wpbe_plaintext_body', 'stripslashes', 11 );
		}

		return $html_message;
	}

	public function text_message() {
		$this->remove_filter( 'wpbe_plaintext_body', 'text_message' );
		remove_filter( 'wpbe_plaintext_body', 'stripslashes', 11 );

		return sprintf( 'return "%s";', addslashes( $this->text_message ) );
	}
}