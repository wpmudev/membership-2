<?php

/**
 * Class MS_Addon_Recaptcha.
 */
class MS_Addon_Recaptcha extends MS_Addon {

	/**
	 * The add-on ID
	 *
	 * @since 1.1.7
	 */
	const ID = 'recaptcha';

	/**
	 * Site key for recaptcha.
	 *
	 * @var $site_key
	 */
	private $site_key;

	/**
	 * Secret key for recaptcha.
	 *
	 * @var $secret_key
	 */
	private $secret_key;

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.1.7
	 */
	public function init() {
		// Only when addon is enabled.
		if ( self::is_active() ) {
			// Get reCaptcha API keys.
			$this->site_key   = self::$settings->get_custom_setting( 'recaptcha', 'site_key' );
			$this->secret_key = self::$settings->get_custom_setting( 'recaptcha', 'secret_key' );

			// Only when configured.
			if ( $this->is_configured() ) {
				// Run captcha validation.
				$this->add_filter(
					'ms_model_membership_create_new_user_validation_errors',
					'captcha_validation',
					20, 1
				);
			}

			// Captcha settings tab.
			$this->add_filter(
				'ms_controller_settings_get_tabs',
				'settings_tabs',
				10, 2
			);

			// Captcha settings content.
			$this->add_filter(
				'ms_view_settings_edit_render_callback',
				'manage_render_callback',
				10, 3
			);
		}
	}

	/**
	 * Returns the add-on ID .
	 *
	 * @since 1.1.7
	 *
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Checks if the add-on is enabled.
	 *
	 * @since 1.1.7
	 *
	 * @return bool
	 */
	public static function is_active() {
		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Check if add-on is configured properly.
	 *
	 * Valid Google API keys are required for this
	 * add-on to function. Check if those keys are
	 * updated.
	 *
	 * @since 1.1.7
	 *
	 * @return bool
	 */
	public function is_configured() {
		// We need both keys
		return ( ! empty( $this->site_key ) && ! empty( $this->secret_key ) );
	}

	/**
	 * Registers the add-on to M2.
	 *
	 * @since 1.1.7
	 *
	 * @param array $list The Add-Ons list.
	 *
	 * @return array The updated Add-Ons list.
	 */
	public function register( $list ) {
		$list[ self::ID ] = (object) array(
			'name'        => __( 'Google reCaptcha', 'membership2' ),
			'description' => __( 'Enable Google reCaptcha integration in registration form and login form.', 'membership2' ),
			'icon'        => 'dashicons dashicons-shield',
		);

		return $list;
	}

	/**
	 * Add reCaptcha settings tab in settings page.
	 *
	 * @since  1.1.7
	 *
	 * @filter ms_controller_membership_get_tabs
	 *
	 * @param array $tabs The current tabs.
	 *
	 * @return array The filtered tabs.
	 */
	public function settings_tabs( $tabs ) {
		$tabs[ self::ID ] = array(
			'title' => __( 'reCaptcha', 'membership2' ),
			'url'   => MS_Controller_Plugin::get_admin_url(
				'settings',
				array( 'tab' => self::ID )
			),
		);

		return $tabs;
	}

	/**
	 * Add recaptcha views callback.
	 *
	 * @since  1.1.7
	 *
	 * @filter ms_view_settings_edit_render_callback
	 *
	 * @param array  $callback The current function callback.
	 * @param string $tab      The current membership rule tab.
	 * @param array  $data     The data shared to the view.
	 *
	 * @return array The filtered callback.
	 */
	public function manage_render_callback( $callback, $tab, $data ) {
		// Only for this add-on.
		if ( self::ID == $tab ) {
			$view       = MS_Factory::load( 'MS_Addon_Recaptcha_View' );
			$view->data = $data;
			$callback   = array( $view, 'render_tab' );
		}

		return $callback;
	}

	/**
	 * Validate user registration form.
	 *
	 * Validate for recaptch response.
	 *
	 * @param object $errors Errors.
	 *
	 * @since 1.1.7
	 *
	 * @return mixed
	 */
	public function registration_validation( $errors ) {
		// Registration form setting.
		$enabled = self::$settings->get_custom_setting( 'recaptcha', 'register_form' );
		// Continue only if configured.
		if ( ! $this->is_configured() || mslib3()->is_false( $enabled ) ) {
			return $errors;
		}

		// We need captcha data when addon is configured.
		if ( empty( $_POST['ms_recaptcha_response'] ) ) {
			$errors->add( 'blank_captcha', __( 'No response', 'membership2' ) );

			return $errors;
		}

		if ( ! $this->validate_response( $_POST['ms_recaptcha_response'] ) ) {
			$errors->add( 'captcha_error', __( 'Captcha validation failed.', 'membership2' ) );
		}

		return $errors;
	}

	/**
	 * Validate user registration form.
	 *
	 * Validate for recaptch response.
	 *
	 * @param object $errors Errors.
	 *
	 * @since 1.1.7
	 *
	 * @return mixed
	 */
	public function login_validation( $errors ) {
		// Registration form setting.
		$enabled = self::$settings->get_custom_setting( 'recaptcha', 'register_form' );
		// Continue only if configured.
		if ( ! $this->is_configured() || mslib3()->is_false( $enabled ) ) {
			return $errors;
		}

		// We need captcha data when addon is configured.
		if ( empty( $_POST['ms_recaptcha_response'] ) ) {
			$errors->add( 'blank_captcha', __( 'No response', 'membership2' ) );

			return $errors;
		}

		if ( ! $this->validate_response( $_POST['ms_recaptcha_response'] ) ) {
			$errors->add( 'captcha_error', __( 'Captcha validation failed.', 'membership2' ) );
		}

		return $errors;
	}

	private function validate_response( $captcha_response ) {
		$data = array(
			'secret'   => $this->secret_key,
			'response' => $captcha_response,
		);

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify?secret={$sgr_secret_key}&response={$recaptcha_response}',
			array( 'body' => $data )
		);

		if ( is_wp_error( $response ) ) {
			return false;
		} else {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( ! isset( $body['success'] ) || mslib3()->is_false( $body['success'] ) ) {
				return false;
			}
		}

		return true;
	}
}
