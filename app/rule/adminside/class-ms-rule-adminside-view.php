<?php

class MS_Rule_Adminside_View extends MS_View {

	public function to_html() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Rule_Adminside::RULE_ID );

		$rule_listtable = new MS_Rule_Adminside_ListTable( $rule, $membership );
		$rule_listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'Admin Side Protection', MS_TEXT_DOMAIN ),
				'desc' => __( 'Protected Admin Side pages are only available for members. The below list contains all possible menu items that WordPress knows about - some of these items might not be available on your installation.', MS_TEXT_DOMAIN ),
			),
			MS_Rule_Adminside::RULE_ID,
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