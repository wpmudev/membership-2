<?php

class MS_View_Membership_Rule_MemberCaps extends MS_View_Membership_Protected_Content {

	public function to_html() {
		$fields = $this->get_control_fields();
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MEMBERCAPS );

		$rule_ListTable = new MS_Helper_ListTable_Rule_Membercaps( $rule, $membership );
		$rule_ListTable->prepare_items();

		$header_data = array();
		$header_data['title'] = __( 'Member Capabilities', MS_TEXT_DOMAIN );
		$header_data['desc'] = __( 'Protected Capabilities will be removed from all users. Use individual Memberships to grant Capabilities again for Members only.', MS_TEXT_DOMAIN );

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			$header_data,
			MS_Model_Rule::RULE_TYPE_MEMBERCAPS,
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

			$rule_ListTable->views();
			$rule_ListTable->search_box( __( 'Capability', MS_TEXT_DOMAIN ) );
			?>
			<form action="" method="post">
				<?php
				$rule_ListTable->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_MEMBERCAPS,
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