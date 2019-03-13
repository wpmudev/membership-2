<?php

/**
 * Class MS_Addon_Recaptcha_View.
 */
class MS_Addon_Recaptcha_View extends MS_View {

	/**
	 * Returns the HTML code of the Settings form.
	 *
	 * @since 1.1.7
	 *
	 * @return void
	 */
	public function render_tab() {
		// Prepare fields.
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Google reCaptcha v3', 'membership2' ) )
			);

			$description = sprintf(
				'<div>' . __( 'You have to %1$sregister your site%2$s, and get required keys from Google reCaptcha v3.', 'membership2' ) . '</div>',
				'<a href="https://www.google.com/recaptcha/admin" target="_blank">',
				'</a>'
			);

			MS_Helper_Html::settings_box_header( '', $description );

			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			MS_Helper_Html::settings_box_footer();
			?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	/**
	 * Prepare fields that are displayed in the form.
	 *
	 * @since 1.1.7
	 *
	 * @return array
	 */
	protected function prepare_fields() {
		// Settings.
		$settings = $this->data['settings'];
		// Action.
		$action       = MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;
		$registration = $settings->get_custom_setting( 'recaptcha', 'register' );
		$login        = $settings->get_custom_setting( 'recaptcha', 'login' );

		$fields = array(
			'site_key'   => array(
				'id'        => 'site_key',
				'name'      => 'custom[recaptcha][site_key]',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'Site Key', 'membership2' ),
				'value'     => $settings->get_custom_setting( 'recaptcha', 'site_key' ),
				'class'     => 'ms-text-large',
				'ajax_data' => array(
					'group'  => 'recaptcha',
					'field'  => 'site_key',
					'action' => $action,
				),
			),
			'secret_key' => array(
				'id'        => 'secret_key',
				'name'      => 'custom[recaptcha][secret_key]',
				'type'      => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title'     => __( 'Secret Key', 'membership2' ),
				'value'     => $settings->get_custom_setting( 'recaptcha', 'secret_key' ),
				'class'     => 'ms-text-large',
				'ajax_data' => array(
					'group'  => 'recaptcha',
					'field'  => 'secret_key',
					'action' => $action,
				),
			),
			'separator'  => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'register'   => array(
				'id'        => 'register_form',
				'name'      => 'custom[recaptcha][register]',
				'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title'     => __( 'Registration Form', 'membership2' ),
				'desc'      => __( 'Enable Google reCaptcha in registration form.', 'membership2' ),
				'value'     => mslib3()->is_true( $registration ),
				'class' => 'inp-before',
				'ajax_data' => array(
					'group'  => 'recaptcha',
					'field'  => 'register',
					'action' => $action,
				),
			),
			'login'      => array(
				'id'        => 'login_form',
				'name'      => 'custom[recaptcha][login]',
				'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title'     => __( 'Login Form', 'membership2' ),
				'desc'      => __( 'Enable Google reCaptcha in login form.', 'membership2' ),
				'value'     => mslib3()->is_true( $login ),
				'class' => 'inp-before',
				'ajax_data' => array(
					'group'  => 'recaptcha',
					'field'  => 'login',
					'action' => $action,
				),
			),
		);

		return $fields;
	}
}
