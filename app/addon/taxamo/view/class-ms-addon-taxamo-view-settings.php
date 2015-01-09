<?php

class MS_Addon_Taxamo_View_Settings extends MS_View {

	public function render_tab() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-wrap">
			<div class="ms-settings">
				<?php
				MS_Helper_Html::settings_tab_header(
					array( 'title' => __( 'Taxamo Settings', MS_TEXT_DOMAIN ) )
				);
				?>

				<form action="" method="post">
					<?php MS_Helper_Html::settings_box( $fields ); ?>
				</form>
				<?php MS_Helper_Html::settings_footer( null, false ); ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		echo '' . $html;
	}

	public function prepare_fields() {
		$settings = $this->data['settings'];

		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;

		$fields = array(
			'public_key' => array(
				'id' => 'public_key',
				'name' => 'custom[taxamo][public_key]',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Public Token', MS_TEXT_DOMAIN ),
				'value' => $settings->get_custom_setting( 'taxamo', 'public_key' ),
				'class' => 'ms-text-medium',
				'data_ms' => array(
					'group' => 'taxamo',
					'field' => 'public_key',
					'action' => $action,
				),
			),

			'private_key' => array(
				'id' => 'private_key',
				'name' => 'custom[taxamo][private_key]',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Private Token', MS_TEXT_DOMAIN ),
				'value' => $settings->get_custom_setting( 'taxamo', 'private_key' ),
				'class' => 'ms-text-medium',
				'data_ms' => array(
					'group' => 'taxamo',
					'field' => 'private_key',
					'action' => $action,
				),
			),
		);

		return $fields;
	}
}