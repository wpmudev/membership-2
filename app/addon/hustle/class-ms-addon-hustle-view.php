<?php

/**
 * Hustle View
 *
 * @since 1.1.2
 */
class MS_Addon_Hustle_View extends MS_View {

	/**
	 * Returns the HTML code of the Settings form.
	 *
	 * @since  1.1.2
	 * @return string
	 */
	public function render_tab() {

		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Hustle Integration', 'membership2' ) )
			);

			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	/**
	 * Prepare fields that are displayed in the form.
	 *
	 * @since  1.1.2
	 * @return array
	 */
	protected function prepare_fields() {
		$settings 		= $this->data['settings'];
		$action 		= MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;
		$fields = array(
			'hustle_providers' => array(
				'id' 			=> 'hustle_provider',
				'name' 			=> 'custom[hustle][hustle_provider]',
				'type' 			=> MS_Helper_Html::INPUT_TYPE_SELECT,
				'class' 		=> 'ms-text-large',
				'title' 		=> __( 'Available Hustle Integrations', 'membership2' ),
				'field_options' => MS_Addon_Hustle::hustle_providers(),
				'value' 		=> $settings->get_custom_setting( 'hustle', 'hustle_provider' ),
				'ajax_data' 	=> array(
					'group' 		=> 'hustle',
					'field' 		=> 'hustle_provider',
					'action' 		=> $action,
				),
			),
		);

		return $fields;
	}
}
?>