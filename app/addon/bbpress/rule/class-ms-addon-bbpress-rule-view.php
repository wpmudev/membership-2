<?php

class MS_Addon_Bbpress_Rule_View extends MS_View {

	public function to_html() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Addon_Bbpress_Rule::RULE_ID );

		$listtable = new MS_Addon_Bbpress_Rule_Listtable( $rule, $membership );
		$listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'bbPress', MS_TEXT_DOMAIN ),
				'desc' => __( 'Protect the following bbPress content to members only.', MS_TEXT_DOMAIN ),
			),
			MS_Addon_Bbpress_Rule::RULE_ID,
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

			$listtable->views();
			?>
			<form action="" method="post">
				<?php $listtable->display(); ?>
			</form>
		</div>
		<?php
		MS_Helper_Html::settings_footer();

		return ob_get_clean();
	}

}