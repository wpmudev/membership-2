<?php

class MS_Rule_Url_View extends MS_View_Membership_ProtectedContent {

	public function to_html() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_URL_GROUP );

		$fields = array(
			'strip_query_string' => array(
				'id' => 'strip_query_string',
				'title' => __( 'Strip query strings from URL', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $rule->strip_query_string,
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'strip_query_string',
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
					'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
				),
			),

			'is_regex' => array(
				'id' => 'is_regex',
				'title' => __( 'Is regular expression', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $rule->is_regex,
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'is_regex',
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
					'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
				),
			),

			'rule_value' => array(
				'id' => 'rule_value',
				'title' => __( 'Page URLs', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				'value' => implode( PHP_EOL, $rule->rule_value ),
				'class' => 'ms-textarea-medium',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'rule_value',
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
					'_wpnonce' => wp_create_nonce( MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD ),
				),
			),
			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		$fields = apply_filters(
			'ms_view_membership_protectedcontent_get_tab_urlgroup_fields',
			$fields
		);

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'URL Protection', MS_TEXT_DOMAIN ),
				'desc' => __( 'Protected URLs can be accessed by members only.', MS_TEXT_DOMAIN ),
				'class' => '',
			),
			MS_Model_Rule::RULE_TYPE_URL_GROUP,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			?>

			<form action="" method="post" class="ms-form ms-group">
				<?php MS_Helper_Html::settings_box( $fields ); ?>
			</form>

			<?php
			MS_Helper_Html::settings_footer(
				array( $fields['step'] ),
				$this->data['show_next_button']
			);

			MS_Helper_Html::settings_box(
				array(
					array(
						'id'    => 'url_test',
						'title'  => __( 'Enter an URL to test against rules in the group', MS_TEXT_DOMAIN ),
						'type'  => MS_Helper_Html::INPUT_TYPE_TEXT,
						'class' => 'widefat',
					),
				),
				__( 'Test URL group', MS_TEXT_DOMAIN )
			);
			?>
			<div id="url-test-results-wrapper"></div>
		</div>
		<?php
		return ob_get_clean();
	}

}