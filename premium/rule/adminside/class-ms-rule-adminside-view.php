<?php

class MS_Rule_Adminside_View extends MS_View {

	public function to_html() {
		$membership = MS_Model_Membership::get_base();
		$rule = $membership->get_rule( MS_Rule_Adminside::RULE_ID );

		$rule_listtable = new MS_Rule_Adminside_ListTable( $rule );
		$rule_listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'Protect Admin Side pages', 'membership2' ),
				'desc' => array(
					__( 'Note that WordPress also restricts access to pages before Content Protection is applied.', 'membership2' ),
					__( 'Tip: Combine with the <b>User Role</b> rules to grant your members additional permission if required!', 'membership2' ),
				),
			),
			MS_Rule_Adminside::RULE_ID,
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );

			$rule_listtable->views();
			$rule_listtable->search_box();
			?>
			<form action="" method="post">
				<?php
				$rule_listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Rule_Adminside::RULE_ID,
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