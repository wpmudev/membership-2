<?php

class MS_View_Membership_Rule_CptGroup extends MS_View_Membership_Protected_Content {

	public function to_html() {
		$membership = $this->data['membership'];

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
		$rule_ListTable = new MS_Helper_ListTable_Rule_CptGroup(
			$rule,
			$membership
		);
		$rule_ListTable->prepare_items();

		$header_data = array();
		$header_data['title'] = __( 'Choose which Custom Post Types you want to protect', MS_TEXT_DOMAIN );
		$header_data['desc'] = '';

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			$header_data,
			MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings ">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );

			$rule_ListTable->views();
			$rule_ListTable->search_box( __( 'Post Types', MS_TEXT_DOMAIN ), 'search-cpt' );
			?>
			<form action="" method="post">
				<?php
				$rule_ListTable->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			null,
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

}