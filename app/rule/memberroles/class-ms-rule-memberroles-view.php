<?php

class MS_Rule_MemberRoles_View extends MS_View {

	public function to_html() {
		$membership = MS_Model_Membership::get_base();
		$rule = $membership->get_rule( MS_Rule_MemberRoles::RULE_ID );

		$rule_listtable = new MS_Rule_MemberRoles_ListTable( $rule );
		$rule_listtable->prepare_items();

		$header_data['title'] = __( 'Assign WordPress User Roles to your Members', 'membership2' );
		$header_data['desc'] = array(
			__( 'When assigning a Membership to any role, then this role will be added to all members of that Membership. You can even assign multiple roles to a single Membership.', 'membership2' ),
			__( 'For security reasons the Administrator role cannot be assigned to a Membership.', 'membership2' ),
		);

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			$header_data,
			MS_Rule_MemberRoles::RULE_ID,
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );

			$rule_listtable->views();
			$rule_listtable->search_box( __( 'Capability', 'membership2' ) );
			?>
			<form action="" method="post">
				<?php
				$rule_listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Rule_MemberRoles::RULE_ID,
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