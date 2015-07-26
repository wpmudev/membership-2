<?php

class MS_Addon_Attributes_View_Membership extends MS_View {

	/**
	 * Returns the HTML code of the Settings form.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function render_tab() {
		$fields = $this->prepare_fields();

		$manage_url = MS_Controller_Plugin::get_admin_url(
			'settings',
			array( 'tab' => MS_Addon_Attributes::ID )
		);

		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => __( 'Custom Membership Attributes', MS_TEXT_DOMAIN ),
					'desc' => sprintf(
						__( 'You can manage custom attributes in %sthe plugin settings%s.', MS_TEXT_DOMAIN ),
						'<a href="' . $manage_url . '">',
						'</a>'
					),
				)
			);

			echo '<div class="ms-attributes">';
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			echo '</div>';
			?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	/**
	 * Prepare fields that are displayed in the form.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	protected function prepare_fields() {
		$membership = $this->data['membership'];
		$action = MS_Addon_Attributes::AJAX_ACTION_SAVE_ATTRIBUTE;

		$fields = array();
		$field_def = MS_Addon_Attributes::list_field_def();

		foreach ( $field_def as $field ) {
			$field_type = MS_Helper_Html::INPUT_TYPE_TEXT;
			$value = MS_Addon_Attributes::get_attr(
				$field->slug,
				$membership
			);

			switch ( $field->type ) {
				case 'number':
					$field_type = MS_Helper_Html::INPUT_TYPE_NUMBER;
					break;

				case 'textarea':
					$field_type = MS_Helper_Html::INPUT_TYPE_TEXT_AREA;
					break;
			}

			$fields[] = array(
				'id' => $field->slug,
				'title' => $field->title,
				'desc' => $field->info,
				'type' => $field_type,
				'value' => $value,
				'ajax_data' => array(
					'action' => $action,
					'_wpnonce' => wp_create_nonce( $action ),
					'field' => $field->slug,
					'membership_id' => $membership->id,
				),
			);
		}

		return $fields;
	}
}