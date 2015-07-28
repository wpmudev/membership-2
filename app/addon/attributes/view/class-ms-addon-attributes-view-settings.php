<?php

class MS_Addon_Attributes_View_Settings extends MS_View {

	/**
	 * Returns the HTML code of the Settings form.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function render_tab() {
		$groups = $this->prepare_fields();

		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array(
					'title' => __( 'Custom Membership Attributes', MS_TEXT_DOMAIN ),
					'desc' => __( 'Define custom fields that are available in the Memberships Edit-Page.', MS_TEXT_DOMAIN ),
				)
			);

			foreach ( $groups as $key => $fields ) {
				echo '<div class="ms-group ms-group-' . esc_attr( $key ) . '">';
				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				echo '</div>';
			}
			MS_Helper_Html::html_separator();

			$help_link = MS_Controller_Plugin::get_admin_url(
				'help',
				array( 'tab' => 'shortcodes' )
			);

			printf(
				'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ul>',
				__( 'How to use custom attribute values:', MS_TEXT_DOMAIN ),
				sprintf(
					__( 'Via the %sshortcode%s %s', MS_TEXT_DOMAIN ),
					'<a href="' . $help_link . '#ms-membership-buy">',
					'</a>',
					'<code>[<b>' . MS_Addon_Attributes::SHORTCODE . '</b> slug="slug" id="..."]</code>'
				),
				sprintf(
					__( 'Via WordPress filter %s', MS_TEXT_DOMAIN ),
					'<code>$val = apply_filters( "<b>ms_membership_attr</b>", "", "slug", $membership_id );</code>'
				),
				sprintf(
					__( 'Get via php function %s', MS_TEXT_DOMAIN ),
					'<code>$val = <b>ms_membership_attr</b>( "slug", $membership_id );</code>'
				),
				sprintf(
					__( 'Set via php function %s', MS_TEXT_DOMAIN ),
					'<code><b>ms_membership_attr_set</b>( "slug", $val, $membership_id );</code>'
				)
			);
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
		$action_save = MS_Addon_Attributes::AJAX_ACTION_SAVE_SETTING;
		$action_delete = MS_Addon_Attributes::AJAX_ACTION_DELETE_SETTING;

		$attribute_types = array(
			'text' => __( 'Simple text field', MS_TEXT_DOMAIN ),
			'number' => __( 'Numeric field (integer)', MS_TEXT_DOMAIN ),
			'textarea' => __( 'Multi-line text', MS_TEXT_DOMAIN ),
			'bool' => __( 'Yes|No', MS_TEXT_DOMAIN ),
		);

		$field_def = MS_Addon_Attributes::list_field_def();
		$fieldlist = array();
		$fieldlist[] = array(
			__( 'Attribute Title', MS_TEXT_DOMAIN ),
			__( 'Attribute Slug', MS_TEXT_DOMAIN ),
			__( 'Attribute Type', MS_TEXT_DOMAIN ),
			__( 'Attribute Infos', MS_TEXT_DOMAIN ),
		);
		foreach ( $field_def as $field ) {
			$fieldlist[] = array(
				$field->title,
				'<code>' . $field->slug. '</code>',
				$field->type,
				$field->info,
			);
		}

		$fields = array();

		$fields['fields'] = array(
			'add_field' => array(
				'id' => 'add_field',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'New Attribute', MS_TEXT_DOMAIN ),
				'class' => 'add_field',
			),
			'fieldlist' => array(
				'id' => 'fieldlist',
				'type' => MS_Helper_Html::TYPE_HTML_TABLE,
				'value' => $fieldlist,
				'field_options' => array(
					'head_row' => true,
				),
				'class' => 'field-list',
			),
		);

		$fields['editor no-auto-init'] = array(
			'title' => array(
				'id' => 'title',
				'class' => 'title',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Attribute Name', MS_TEXT_DOMAIN ),
				'desc' => __( 'A human readable title of the Attribute.', MS_TEXT_DOMAIN ),
			),
			'slug' => array(
				'id' => 'slug',
				'class' => 'slug',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Attribute Slug', MS_TEXT_DOMAIN ),
				'desc' => __( 'You use the slug in the attribute shortcode and in PHP code to access a value.', MS_TEXT_DOMAIN ),
			),
			'type' => array(
				'id' => 'type',
				'class' => 'type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Attribute Type', MS_TEXT_DOMAIN ),
				'desc' => __( 'Decide what kind of data will be stored by the attribute.', MS_TEXT_DOMAIN ),
				'field_options' => $attribute_types,
			),
			'info' => array(
				'id' => 'info',
				'class' => 'info',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				'title' => __( 'Attribute Infos', MS_TEXT_DOMAIN ),
				'desc' => __( 'Additional details displayed in the Membership editor. Only Admin users can see this value.', MS_TEXT_DOMAIN ),
			),
			'old_slug' => array(
				'id' => 'old_slug',
				'class' => 'old_slug',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			),
			'action_save' => array(
				'id' => 'action_save',
				'class' => 'action_save',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_save,
			),
			'nonce_save' => array(
				'id' => 'nonce_save',
				'class' => 'nonce_save',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_save ),
			),
			'action_delete' => array(
				'id' => 'action_delete',
				'class' => 'action_delete',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action_delete,
			),
			'nonce_delete' => array(
				'id' => 'nonce_delete',
				'class' => 'nonce_delete',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action_delete ),
			),
			'buttons' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' =>
					MS_Helper_Html::html_element(
						array(
							'id' => 'btn_delete',
							'class' => 'btn_delete button-link danger',
							'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
							'value' => __( 'Delete', MS_TEXT_DOMAIN ),
						),
						true
					) .
					MS_Helper_Html::html_element(
						array(
							'id' => 'btn_cancel',
							'class' => 'btn_cancel close',
							'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
							'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
						),
						true
					) .
					MS_Helper_Html::html_element(
						array(
							'id' => 'btn_save',
							'class' => 'btn_save button-primary',
							'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
							'value' => __( 'Save Attribute', MS_TEXT_DOMAIN ),
						),
						true
					),
				'class' => 'buttons',
			)
		);

		return $fields;
	}
}