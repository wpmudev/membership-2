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
					'title' => __( 'Custom Membership Attributes', 'membership2' ),
					'desc' => __( 'Define custom fields that are available in the Memberships Edit-Page.', 'membership2' ),
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
				__( 'How to use custom attribute values:', 'membership2' ),
				sprintf(
					__( 'Via the %sshortcode%s %s', 'membership2' ),
					'<a href="' . $help_link . '#ms-membership-buy">',
					'</a>',
					'<code>[<b>' . MS_Addon_Attributes::SHORTCODE . '</b> slug="slug" id="..."]</code>'
				),
				sprintf(
					__( 'Via WordPress filter %s', 'membership2' ),
					'<code>$val = apply_filters( "<b>ms_membership_attr</b>", "", "slug", $membership_id );</code>'
				),
				sprintf(
					__( 'Get via php function %s', 'membership2' ),
					'<code>$val = <b>ms_membership_attr</b>( "slug", $membership_id );</code>'
				),
				sprintf(
					__( 'Set via php function %s', 'membership2' ),
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
			'text' => __( 'Simple text field', 'membership2' ),
			'number' => __( 'Numeric field (integer)', 'membership2' ),
			'textarea' => __( 'Multi-line text', 'membership2' ),
			'bool' => __( 'Yes|No', 'membership2' ),
		);

		$field_def = MS_Addon_Attributes::list_field_def();
		$fieldlist = array();
		$fieldlist[] = array(
			__( 'Attribute Title', 'membership2' ),
			__( 'Attribute Slug', 'membership2' ),
			__( 'Attribute Type', 'membership2' ),
			__( 'Attribute Infos', 'membership2' ),
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
				'value' => __( 'New Attribute', 'membership2' ),
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
				'title' => __( 'Attribute Name', 'membership2' ),
				'desc' => __( 'A human readable title of the Attribute.', 'membership2' ),
			),
			'slug' => array(
				'id' => 'slug',
				'class' => 'slug',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Attribute Slug', 'membership2' ),
				'desc' => __( 'You use the slug in the attribute shortcode and in PHP code to access a value.', 'membership2' ),
			),
			'type' => array(
				'id' => 'type',
				'class' => 'type',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Attribute Type', 'membership2' ),
				'desc' => __( 'Decide what kind of data will be stored by the attribute.', 'membership2' ),
				'field_options' => $attribute_types,
			),
			'info' => array(
				'id' => 'info',
				'class' => 'info',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				'title' => __( 'Attribute Infos', 'membership2' ),
				'desc' => __( 'Additional details displayed in the Membership editor. Only Admin users can see this value.', 'membership2' ),
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
							'value' => __( 'Delete', 'membership2' ),
						),
						true
					) .
					MS_Helper_Html::html_element(
						array(
							'id' => 'btn_cancel',
							'class' => 'btn_cancel close',
							'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
							'value' => __( 'Cancel', 'membership2' ),
						),
						true
					) .
					MS_Helper_Html::html_element(
						array(
							'id' => 'btn_save',
							'class' => 'btn_save button-primary',
							'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
							'value' => __( 'Save Attribute', 'membership2' ),
						),
						true
					),
				'class' => 'buttons',
			)
		);

		return $fields;
	}
}