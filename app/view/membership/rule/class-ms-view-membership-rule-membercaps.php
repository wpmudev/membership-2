<?php

class MS_View_Membership_Rule_Membercaps extends MS_View_Membership_Protected_Content {

	public function to_html() {
		$fields = $this->get_control_fields();

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
			// The Member-Roles are only available in Accessible content.
			return;
		}

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MEMBERCAPS );

		$rule_list_table = new MS_Helper_List_Table_Rule_Membercaps( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = array();
		if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
			$header_data['title'] = __( 'Member Capabilities', MS_TEXT_DOMAIN );
			$header_data['desc'] = __( 'Protected Capabilities will be removed from all users. Use individual Memberships to grant Capabilities again for Members only.', MS_TEXT_DOMAIN );
		} else {
			$header_data['title'] = __( 'User Roles', MS_TEXT_DOMAIN );
			$header_data['desc'] = __( 'Protected User Roles can be assigned to a Membership. When they are not used in a Membership then these settings here have no effect.', MS_TEXT_DOMAIN );
		}

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

			$rule_list_table->views();
			$rule_list_table->search_box( __( 'Capability', MS_TEXT_DOMAIN ) );
			?>
			<form action="" method="post">
				<?php
				$rule_list_table->display();

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