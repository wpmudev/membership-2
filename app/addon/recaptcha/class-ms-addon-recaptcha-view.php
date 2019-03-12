<?php

class MS_Addon_Recaptcha_View extends MS_View {

	/**
	 * Returns the HTML code of the Settings form.
	 *
	 * @since 1.1.7
	 *
	 * @return string
	 */
	public function render_tab() {
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
	 * @return array
	 */
	protected function prepare_fields() {
		$api_status = MS_Addon_Mailchimp::get_api_status();
		$settings   = $this->data['settings'];

		$action       = MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;
		$registration = $settings->get_custom_setting( 'recaptcha', 'register_form' );
		$login        = $settings->get_custom_setting( 'recaptcha', 'login_form' );

		$fields = array(
			'site_key'      => array(
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
			'secret_key'    => array(
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
			'separator'     => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'register_form' => array(
				'id'        => 'register_form',
				'name'      => 'custom[recaptcha][register_form]',
				'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title'     => __( 'Registration Form', 'membership2' ),
				'desc'      => '',
				'value'     => mslib3()->is_true( $registration ),
				'ajax_data' => array(
					'group'  => 'recaptcha',
					'field'  => 'register_form',
					'action' => $action,
				),
			),
			'login_form'    => array(
				'id'        => 'login_form',
				'name'      => 'custom[recaptcha][login_form]',
				'type'      => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title'     => __( 'Login Form', 'membership2' ),
				'desc'      => '',
				'value'     => mslib3()->is_true( $login ),
				'ajax_data' => array(
					'group'  => 'recaptcha',
					'field'  => 'login_form',
					'action' => $action,
				),
			),
		);

		return $fields;
	}
}
