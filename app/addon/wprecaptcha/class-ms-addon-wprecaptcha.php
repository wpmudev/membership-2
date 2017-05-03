<?php
class MS_Addon_Wprecaptcha extends MS_Addon {

	/**
	 * The Add-on ID
	 *
	 * @since  1.0.0
	 */
	const ID = 'wprecaptcha';

	/**
	 * Checks if the current Add-on is enabled
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function is_active() {
		if ( ! self::wp_recaptcha_active()
			&& MS_Model_Addon::is_enabled( self::ID )
		) {
			$model = MS_Factory::load( 'MS_Model_Addon' );
			$model->disable( self::ID );
		}

		return MS_Model_Addon::is_enabled( self::ID );
	}

	/**
	 * Returns the Add-on ID (self::ID).
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function get_id() {
		return self::ID;
	}

	/**
	 * Initializes the Add-on. Always executed.
	 *
	 * @since  1.0.0
	 */
	public function init() {
		if ( self::is_active() ) {
			$this->add_filter(
				'ms_model_membership_create_new_user_validation_errors',
				'check_captcha_validation',
				20, 1
			);
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
		$list[ self::ID ] = (object) array(
			'name' => __( 'WP reCaptcha Integration', 'membership2' ),
			'description' => __( 'Enable WP reCaptcha (inactive) integration.', 'membership2' ),
			'icon' => 'dashicons dashicons-format-chat',
		);

		if ( ! self::wp_recaptcha_active() ) {
			$list[ self::ID ]->description .= sprintf(
				'<br /><b>%s</b>',
				__( 'Activate WP reCaptcha to use this Add-on', 'membership2' )
			);
			$list[ self::ID ]->action = '-';
		} else {
			
			$list[ self::ID ]->description = sprintf(
				'<b>%s</b>',
				__( 'WP reCaptcha integrated', 'membership2' )
			);
		}

		return $list;
	}

	/**
	 * Returns true, when the WP_reCaptcha plugin is activated.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	static public function wp_recaptcha_active() {
		return class_exists( 'WP_reCaptcha' );
	}

	public function check_captcha_validation( $errors ) {

		if ( empty( $_POST['g-recaptcha-response'] ) || empty( $_POST['recaptcha_challenge_field'] ) ) {
			$errors->add( 'blank_captcha', __( 'No response', 'membership2' ) );
			return $errors;
		}

		$reCaptchaLib = null;

		if ( ! empty( $_POST['g-recaptcha-response'] ) ){
			$reCaptchaLib = new WP_reCaptcha_NoCaptcha();
		} else if ( ! empty( $_POST['recaptcha_challenge_field'] ) ){
			$reCaptchaLib = new WP_reCaptcha_ReCaptcha();
		}
		
		if ( $reCaptchaLib != null ) {
			if ( ! $reCaptchaLib->check() ) {
				$errors->add( 'captcha_wrong', $response->error );
			} else {
				$errors->add( 'captcha_error', __( 'General Error', 'membership2' ) );
			}
		} else {
			$errors->add( 'captcha_error', __( 'Response Error', 'membership2' ) );
		}

		return $errors;
	}
}
