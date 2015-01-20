<?php

class MS_View_Membership_Rule_Adminside extends MS_View_Membership_Protected_Content {

	public function to_html() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_ADMINSIDE );

		$rule_list_table = new MS_Helper_List_Table_Rule_Adminside( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(
				'title' => __( 'Admin Side Protection', MS_TEXT_DOMAIN ),
				'desc' => __( 'Protected Admin Side pages are only available for members. The below list contains all possible menu items that WordPress knows about - some of these items might not be available on your installation.', MS_TEXT_DOMAIN ),
			),
			MS_Model_Rule::RULE_TYPE_ADMINSIDE,
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
			$rule_list_table->search_box();
			?>
			<form action="" method="post">
				<?php
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_ADMINSIDE,
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