<?php

class MS_Rule_CptGroup_View extends MS_View {

	public function to_html() {
		$membership = MS_Model_Membership::get_base();

		$rule = $membership->get_rule( MS_Rule_CptGroup::RULE_ID );
		$rule_listtable = new MS_Rule_CptGroup_ListTable( $rule );
		$rule_listtable->prepare_items();

		$header_data = array();
		$header_data['title'] = __( 'Choose which Custom Post Types you want to protect', MS_TEXT_DOMAIN );
		$header_data['desc'] = '';

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			$header_data,
			MS_Rule_CptGroup::RULE_ID,
			$this
		);

		ob_start();
		?>
		<div class="ms-settings ">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );

			$rule_listtable->views();
			$rule_listtable->search_box( __( 'Post Types', MS_TEXT_DOMAIN ), 'search-cpt' );
			?>
			<form action="" method="post">
				<?php
				$rule_listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Rule_CptGroup::RULE_ID,
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