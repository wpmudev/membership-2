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
	 * The flag for registration.
	 *
	 * @var $registration
	 */
	private $registration_enabled = false;

	/**
	 * The flag for login.
	 *
	 * @var $login
	 */
	private $login_enabled = false;

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since 1.1.7
	 */
	public function init() {
		// Only when addon is enabled.
		if ( self::is_active() ) {
			// Configure the settings.
			$this->configure();

			// Only when configured.
			if ( $this->is_configured() ) {
				// Continue only if configured.
				if ( $this->registration_enabled ) {
					// Run captcha validation on registration submit.
					$this->add_filter(
						'ms_model_membership_create_new_user_validation_errors',
						'registration_validation'
					);

					// Add captcha to registration form in multisite.
					$this->add_action(
						'signup_extra_fields',
						'captcha_field'
					);

					// Add captcha to registration form.
					$this->add_action(
						'register_form',
						'captcha_field'
					);
				}

				// Continue only if configured.
				if ( $this->login_enabled ) {
					// Run captcha validation on login.
					$this->add_filter(
						'wp_authenticate_user',
						'login_validation'
					);

					// Add captcha to login form.
					$this->add_action(
						'login_form',
						'captcha_field'
					);

					// Add to BuddyPress registration form.
					$this->add_action(
						'bp_after_signup_profile_fields',
						'captcha_field'
					);

					$this->add_action(
						'login_enqueue_scripts',
						'login_scripts'
					);
				}

				// Register captcha scripts.
				$this->add_action(
					'wp_enqueue_scripts',
					'register_scripts'
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
	 * Configure the properties.
	 *
	 * Configure the flags and API keys from settings.
	 *
	 * @since 1.1.7
	 *
	 * @return void
	 */
	private function configure() {
		// Get the addon settings.
		$settings = self::$settings->get_custom_setting( 'recaptcha' );

		// Get reCaptcha site key.
		$this->site_key = isset( $settings['site_key'] ) ? $settings['site_key'] : '';
		// Get reCaptcha secret key.
		$this->secret_key = isset( $settings['secret_key'] ) ? $settings['secret_key'] : '';
		// Registration form flag.
		$this->registration_enabled = isset( $settings['register'] ) ? mslib3()->is_true( $settings['register'] ) : false;
		// Login form flag.
		$this->login_enabled = isset( $settings['login'] ) ? mslib3()->is_true( $settings['login'] ) : false;
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
	 * Add captcha field to forms.
	 *
	 * Add a hidden field to catch captcha response
	 * and then we can use it to verify the response.
	 *
	 * @since 1.1.7
	 *
	 * @return void
	 */
	public function captcha_field() {
		// Add captcha field as hidden field.
		echo '<p>';
		echo '<input type="hidden" id="ms_recaptcha_response" name="ms_recaptcha_response" value="">';
		echo '</p>';

		// Enqueue recaptcha scripts.
		wp_enqueue_script( 'ms_google_recaptcha' );
		wp_enqueue_script( 'ms_recaptcha' );
	}

	/**
	 * Register captcha scripts.
	 *
	 * Register the Google reCaptcha script library
	 * and then our custom script to store captcha response
	 * to the custom hidden field.
	 * See https://developers.google.com/recaptcha/docs/v3
	 *
	 * @since 1.1.7
	 *
	 * @return void
	 */
	public function register_scripts() {
		// Main Google library.
		wp_register_script(
			'ms_google_recaptcha',
			"https://www.google.com/recaptcha/api.js?render={$this->site_key}",
			array(),
			MS_PLUGIN_VERSION,
			true
		);

		// Custom script to handle captcha response.
		wp_register_script(
			'ms_recaptcha',
			MS_Plugin::instance()->url . 'app/addon/recaptcha/assets/js/recaptcha.js',
			array( 'ms_google_recaptcha' ), // Dependency.
			MS_PLUGIN_VERSION,
			true
		);

		// Site key is required in custom script.
		wp_localize_script( 'ms_recaptcha', 'ms_addon_recaptcha', array(
			'site_key' => $this->site_key,
		) );
	}

	/**
	 * Add reCaptcha scripts to default login form.
	 *
	 * @since 1.1.7
	 *
	 * @return void
	 */
	public function login_scripts() {
		// Register scripts.
		$this->register_scripts();

		// Enqueue recaptcha scripts.
		wp_enqueue_script( 'ms_google_recaptcha' );
		wp_enqueue_script( 'ms_recaptcha' );
	}

	/**
	 * Validate user registration form.
	 *
	 * Validate for recaptch response and if failed
	 * add an error to WP_Error array.
	 *
	 * @param object $errors Errors.
	 *
	 * @since 1.1.7
	 *
	 * @return WP_Error
	 */
	public function registration_validation( $errors ) {
		// Only when enabled.
		if ( $this->registration_enabled ) {
			// We need captcha data when addon is configured.
			if ( empty( $_POST['ms_recaptcha_response'] ) ) {
				$errors->add( 'blank_captcha', __( 'No response', 'membership2' ) );

				return $errors;
			}

			if ( ! $this->validate_response( $_POST['ms_recaptcha_response'] ) ) {
				$errors->add( 'captcha_error', __( 'Captcha validation failed.', 'membership2' ) );
			}
		}

		return $errors;
	}

	/**
	 * Validate login form submit.
	 *
	 * Validate for recaptch response in login form
	 * and return WP_Error for failed validation.
	 * Note: We will validate only if the captch field is found.
	 * Because the used hook (wp_authenticate_user) can be used manually
	 * inside any other plugin. Even our registration form is using it.
	 *
	 * @param object $user WP_User.
	 *
	 * @since 1.1.7
	 *
	 * @return WP_User|WP_Error
	 */
	public function login_validation( $user ) {
		// Only when enabled.
		if ( $this->login_enabled ) {
			// Validate captch only when required.
			if ( isset( $_POST['ms_recaptcha_response'] ) && ! $this->validate_response( $_POST['ms_recaptcha_response'] ) ) {
				$user = new WP_Error( 'captcha_error', __( 'Captcha validation failed.', 'membership2' ) );
			}
		}

		return $user;
	}

	/**
	 * Validate the captcha response in Google.
	 *
	 * Use Google reCaptcha API and validate the
	 * captcha response token.
	 *
	 * @param string $token Captcha response token.
	 *
	 * @return bool
	 */
	private function validate_response( $token ) {
		// To make sure we don't duplicate the request.
		static $valid = null;

		// If we have already verified, no need to do again.
		if ( ! is_null( $valid ) ) {
			return $valid;
		}

		// Date to be sent.
		$data = array(
			'secret'   => $this->secret_key,
			'response' => $token,
		);

		// Send HTTP request to Google.
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array( 'body' => $data )
		);

		// Make sure it's not an error.
		if ( is_wp_error( $response ) ) {
			$valid = false;
		} else {
			// Get the response data.
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			// Status should be true.
			if ( ! isset( $body['success'] ) || mslib3()->is_false( $body['success'] ) ) {
				$valid = false;
			} else {
				$valid = true;
			}
		}

		return $valid;
	}
}
