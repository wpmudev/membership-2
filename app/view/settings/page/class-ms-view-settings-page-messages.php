<?php

class MS_View_Settings_Page_Messages extends MS_View_Settings_Edit {

	public function to_html() {
		$settings = $this->data['settings'];
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_PROTECTION_MSG;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'content' => array(
				'editor' => array(
					'id' => 'content',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_CONTENT ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary',
					'data_ms' => array(
						'type' => 'content',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),

			'shortcode' => array(
				'editor' => array(
					'id' => 'shortcode',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected shortcode content.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_SHORTCODE ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary',
					'data_ms' => array(
						'type' => 'shortcode',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),

			'more_tag' => array(
				'editor' => array(
					'id' => 'more_tag',
					'type' => MS_Helper_Html::INPUT_TYPE_WP_EDITOR,
					'title' => __( 'Message displayed when not having access to a protected content under more tag.', MS_TEXT_DOMAIN ),
					'value' => $settings->get_protection_message( MS_Model_Settings::PROTECTION_MSG_MORE_TAG ),
					'field_options' => array( 'editor_class' => 'ms-field-wp-editor' ),
				),
				'save' => array(
					'id' => 'save_content',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Save', MS_TEXT_DOMAIN ),
					'class' => 'button-primary',
					'data_ms' => array(
						'type' => 'more_tag',
						'action' => $action,
						'_wpnonce' => $nonce,
					),
				),
			),
		);

		$fields = apply_filters( 'ms_view_settings_prepare_pages_fields', $fields );

		$membership = $this->data['membership'];
		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$has_more = $rule_more_tag->get_rule_value( MS_Rule_More_Model::CONTENT_ID );

		ob_start();

		MS_Helper_Html::settings_tab_header(
			array( 'title' => __( 'Protection Messages', MS_TEXT_DOMAIN ) )
		);
		?>

		<form class="ms-form" action="" method="post">
			<?php
			MS_Helper_Html::settings_box(
				$fields['content'],
				__( 'Content protection message', MS_TEXT_DOMAIN ),
				'',
				'open'
			);

			MS_Helper_Html::settings_box(
				$fields['shortcode'],
				__( 'Shortcode protection message', MS_TEXT_DOMAIN ),
				'',
				'open'
			);

			if ( $has_more ) {
				MS_Helper_Html::settings_box(
					$fields['more_tag'],
					__( 'More tag protection message', MS_TEXT_DOMAIN ),
					'',
					'open'
				);
			}
			?>
		</form>
		<?php
		return ob_get_clean();
	}

}