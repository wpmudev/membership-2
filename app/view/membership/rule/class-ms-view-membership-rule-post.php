<?php

class MS_View_Membership_Rule_Post extends MS_View_Membership_Protected_Content {

	public function to_html() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(
				'title' => __( 'Choose Posts you want to protect', MS_TEXT_DOMAIN ),
				'desc' => '',
			),
			MS_Model_Rule::RULE_TYPE_POST,
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

			$rule_list_table->views();
			$rule_list_table->search_box( __( 'Posts', MS_TEXT_DOMAIN ) );
			?>
			<form action="" method="post">
				<?php
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_POST,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

}