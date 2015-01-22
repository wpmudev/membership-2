<?php

class MS_View_Membership_Rule_Category extends MS_View_Membership_Protected_Content {

	public function to_html() {
		$membership = $this->data['membership'];

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$rule_ListTable = new MS_Helper_ListTable_Rule_Category(
			$rule,
			$membership
		);
		$rule_ListTable->prepare_items();

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$parts['category'] = __( 'Categories', MS_TEXT_DOMAIN );
		}

		$header_data = array();
		$header_data['title'] = __( 'Choose which Categories you want to protect', MS_TEXT_DOMAIN );
		$header_data['desc'] = '';

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			$header_data,
			MS_Model_Rule::RULE_TYPE_CATEGORY,
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
			$rule_ListTable->search_box( __( 'Categories', MS_TEXT_DOMAIN ), 'search-cat' );
			?>
			<form action="" method="post">
				<?php
				$rule_ListTable->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_CATEGORY,
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