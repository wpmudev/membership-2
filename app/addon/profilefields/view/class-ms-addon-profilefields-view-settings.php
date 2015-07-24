<?php
/**
 * Settings view of the Add-on
 *
 * @since  1.0.1.0
 */
class MS_Addon_Profilefields_View_Settings extends MS_View {

	/**
	 * Return the Form HTML code.
	 *
	 * @since  1.0.1.0
	 * @return string
	 */
	public function render_tab() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Profile Fields Settings', MS_TEXT_DOMAIN ) )
			);
			?>

			<form action="" method="post">
				<?php MS_Helper_Html::settings_box( $fields ); ?>
			</form>
			<?php MS_Helper_Html::settings_footer(); ?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	/**
	 * Returns the field specifications for the form.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	public function prepare_fields() {
		$settings = $this->data['settings'];
		$action = 'save';

		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;
		$profile_fields = MS_Addon_Profilefields::list_fields();

		$reg_config = $settings->get_custom_setting( 'profilefields', 'register' );
		$edit_config = $settings->get_custom_setting( 'profilefields', 'profile' );
		$was_initialized = false;

		$registration_options = array(
			'off' => '-',
			'optional' => __( 'Optional', MS_TEXT_DOMAIN ),
			'required' => __( 'Required', MS_TEXT_DOMAIN ),
		);

		$profile_options = array(
			'off' => '-',
			'readonly' => __( 'Read-only', MS_TEXT_DOMAIN ),
			'optional' => __( 'Optional', MS_TEXT_DOMAIN ),
			'required' => __( 'Required', MS_TEXT_DOMAIN ),
		);

		$fields = array();
		$rows = array();
		$rows[] = array(
			__( 'Field Name', MS_TEXT_DOMAIN ),
			__( 'Registration Form', MS_TEXT_DOMAIN ),
			__( 'Profile Form', MS_TEXT_DOMAIN ),
		);

		// Prepare the rows inside the table. Each row is a profile field.
		foreach ( $profile_fields as $id => $details ) {
			// Registration form options.
			if ( empty( $reg_config[$id] ) && ! empty( $details['default_reg'] ) ) {
				$reg_config[$id] = $details['default_reg'];
				$was_initialized = true;
			}

			if ( isset( $reg_config[$id] ) ) {
				$value_reg = $reg_config[$id];
			} else {
				$value_reg = '';
			}

			if ( isset( $details['allowed_reg'] ) && is_array( $details['allowed_reg'] ) ) {
				$reg_options = array();
				foreach ( $details['allowed_reg'] as $key ) {
					$reg_options[$key] = $registration_options[$key];
				}
			} else {
				$reg_options = $registration_options;
			}

			$field_reg = MS_Helper_Html::html_element(
				array(
					'id' => 'register[' . $id . ']',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $value_reg,
					'field_options' => $reg_options,
				),
				true
			);

			// Profile form options.
			if ( empty( $edit_config[$id] ) && ! empty( $details['default_edit'] ) ) {
				$edit_config[$id] = $details['default_edit'];
				$was_initialized = true;
			}

			if ( isset( $edit_config[$id] ) ) {
				$value_edit = $edit_config[$id];
			} else {
				$value_edit = '';
			}

			if ( isset( $details['allowed_edit'] ) && is_array( $details['allowed_edit'] ) ) {
				$edit_options = array();
				foreach ( $details['allowed_edit'] as $key ) {
					$edit_options[$key] = $profile_options[$key];
				}
			} else {
				$edit_options = $profile_options;
			}

			$field_edit = MS_Helper_Html::html_element(
				array(
					'id' => 'profile[' . $id . ']',
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $value_edit,
					'field_options' => $edit_options,
				),
				true
			);

			// Build the row.
			$rows[] = array(
				$details['label'],
				$field_reg,
				$field_edit,
			);
		}

		$fields[] = array(
			'id' => 'fieldlist',
			'type' => MS_Helper_Html::TYPE_HTML_TABLE,
			'value' => $rows,
			'field_options' => array(
				'head_row' => true,
			),
		);

		$fields[] = array(
			'id' => 'action',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $action,
		);

		$fields[] = array(
			'id' => '_wpnonce',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => wp_create_nonce( $action ),
		);

		$fields[] = array(
			'id' => 'save',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
		);

		// Save changes in case fields were initialized.
		if ( $was_initialized ) {
			$settings->set_custom_setting( 'profilefields', 'register', $reg_config );
			$settings->set_custom_setting( 'profilefields', 'profile', $edit_config );
			$settings->save();
		}

		return $fields;
	}
}