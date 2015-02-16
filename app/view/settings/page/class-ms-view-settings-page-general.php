<?php

class MS_View_Settings_Page_General extends MS_View_Settings_Edit {

	public function to_html() {
		$settings = $this->data['settings'];

		$fields = array(
			'plugin_enabled' => array(
				'id' => 'plugin_enabled',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Content Protection', MS_TEXT_DOMAIN ),
				'desc' => __( 'This setting toggles the content protection on this site.', MS_TEXT_DOMAIN ),
				'value' => MS_Plugin::is_enabled(),
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'plugin_enabled',
				),
			),

			'hide_admin_bar' => array(
				'id' => 'hide_admin_bar',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Hide admin toolbar', MS_TEXT_DOMAIN ),
				'desc' => __( 'Hide the admin toolbar for non administrator users.', MS_TEXT_DOMAIN ),
				'value' => $settings->hide_admin_bar,
				'data_ms' => array(
					'action' => MS_Controller_Settings::AJAX_ACTION_TOGGLE_SETTINGS,
					'setting' => 'hide_admin_bar',
				),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_general_fields', $fields );
		$setup = MS_Factory::create( 'MS_View_Settings_Page_Setup' );

		ob_start();

		MS_Helper_Html::settings_tab_header();
		?>

		<form action="" method="post" class="cf">
			<div class="cf">
				<div class="ms-half">
					<?php MS_Helper_Html::html_element( $fields['plugin_enabled'] ); ?>
				</div>
				<div class="ms-half">
					<?php MS_Helper_Html::html_element( $fields['hide_admin_bar'] ); ?>
				</div>
			</div>
			<?php
			MS_Helper_Html::html_separator();
			MS_Helper_Html::html_element( $setup->html_full_form() );
			?>
		</form>
		<?php
		return ob_get_clean();
	}

}