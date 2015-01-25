<?php

class MS_Rule_ReplaceLocation_View extends MS_View {

	public function to_html() {
		$membership = $this->data['membership'];

		$rule_menu = $membership->get_rule( MS_Rule_ReplaceLocation::RULE_ID );
		$rule_listtable = new MS_Rule_ReplaceLocation_ListTable(
			$rule_menu,
			$membership
		);

		$rule_listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'Menus', MS_TEXT_DOMAIN ),
				'desc' => __( 'Replace or protect WordPress menus.', MS_TEXT_DOMAIN ),
			),
			MS_Rule_ReplaceLocation::RULE_ID,
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
					MS_Rule_ReplaceLocation::RULE_ID,
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