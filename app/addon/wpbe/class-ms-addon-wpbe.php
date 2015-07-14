<?php
/**
 * WP Better Emails integration.
 */
class MS_Addon_Wpbe extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'addon_wpbe';

	protected $text_message = '';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.0
	 */
	public function init() {
		if ( self::is_active() ) {
			global $wp_better_emails;

			if ( $wp_better_emails ) {
				$this->add_filter(
					'ms_model_communication_send_message_html_message',
					'html_message'
				);
			}
		}
	}

	/**
	 * Registers the Add-On
	 *
	 * @since  1.0.0
	 * @param  array $list The Add-Ons list.
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		/*
		// Don't register: Not completed yet...

		$list[ self::ID ] = (object) array(
			'name' => __( 'WP Better Emails', MS_TEXT_DOMAIN ),
			'description' => __( 'WP Better Emails integration.', MS_TEXT_DOMAIN ),
		);
		*/
		return $list;
	}

	/**
	 * WP Better email wrapper.
	 *
	 * @since  1.0.0
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