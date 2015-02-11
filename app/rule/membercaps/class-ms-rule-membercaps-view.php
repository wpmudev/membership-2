<?php

class MS_Rule_MemberCaps_View extends MS_View {

	public function to_html() {
		$membership = MS_Model_Membership::get_base();
		$rule = $membership->get_rule( MS_Rule_MemberCaps::RULE_ID );

		$rule_listtable = new MS_Rule_MemberCaps_ListTable( $rule );
		$rule_listtable->prepare_items();

		$header_data = array();
		$header_data['title'] = __( 'Assign WordPress Capabilities to your Members', MS_TEXT_DOMAIN );
		$header_data['desc'] = array(
			__( 'Fine-tune member permissions by assigning certain Capabilities to each Membership. All Members of that Membership are granted the specified Capabilities.', MS_TEXT_DOMAIN ),
			__( 'Important: All users that are not inside these Memberships will be striped of any Protected Capability!', MS_TEXT_DOMAIN ),
		);

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			$header_data,
			MS_Rule_MemberCaps::RULE_ID,
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