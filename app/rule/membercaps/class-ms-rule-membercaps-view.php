<?php

class MS_Rule_MemberCaps_View extends MS_View {

	public function to_html() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Rule_MemberCaps::RULE_ID );

		$rule_listtable = new MS_Rule_MemberCaps_ListTable( $rule, $membership );
		$rule_listtable->prepare_items();

		$header_data = array();
		$header_data['title'] = __( 'Member Capabilities', MS_TEXT_DOMAIN );
		$header_data['desc'] = __( 'Protected Capabilities will be removed from all users. Use individual Memberships to grant Capabilities again for Members only.', MS_TEXT_DOMAIN );

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			$header_data,
			MS_Rule_MemberCaps::RULE_ID,
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

			$rule_listtable->views();
			$rule_listtable->search_box( __( 'Capability', MS_TEXT_DOMAIN ) );
			?>
			<form action="" method="post">
				<?php
				$rule_listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Rule_MemberCaps::RULE_ID,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer();
		return ob_get_clean();
	}

}