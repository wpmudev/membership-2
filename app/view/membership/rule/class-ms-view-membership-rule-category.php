<?php

class MS_View_Membership_Rule_Category extends MS_View_Membership_Protected_Content {

	public function to_html() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$rule_cat = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$category_rule_list_table = new MS_Helper_List_Table_Rule_Category(
			$rule_cat,
			$membership
		);
		$category_rule_list_table->prepare_items();

		$rule_cpt = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
		$cpt_rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type_Group(
			$rule_cpt,
			$membership
		);
		$cpt_rule_list_table->prepare_items();

		$parts = array();

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$parts['category'] = __( 'Categories', MS_TEXT_DOMAIN );
		}
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$parts['cpt_group'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
		}

		$header_data = array();
		$header_data['title'] = sprintf(
			__( 'Choose which %s you want to protect', MS_TEXT_DOMAIN ),
			implode( '/', $parts )
		);
		$header_data['desc'] = '';

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			$header_data,
			MS_Model_Rule::RULE_TYPE_CATEGORY,
			array(
				'membership' => $this->data['membership'],
				'parts' => $parts,
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings ">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) : ?>
				<div class="ms-group">
					<div class="inside">
						<div class="wpmui-field-label">
							<?php _e( 'Protect Categories:', MS_TEXT_DOMAIN ); ?>
						</div>
						<?php
						$category_rule_list_table->views();
						$category_rule_list_table->search_box( __( 'Categories', MS_TEXT_DOMAIN ), 'search-cat' );
						?>
						<form action="" method="post">
							<?php
							$category_rule_list_table->display();

							do_action(
								'ms_view_membership_protected_content_footer',
								MS_Model_Rule::RULE_TYPE_CATEGORY,
								$this
							);

							MS_Helper_Html::html_separator();
							?>
						</form>
					</div>
				</div>
			<?php
			endif;

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) : ?>
				<div class="ms-group">
					<div class="inside">
						<div class="wpmui-field-label">
							<?php _e( 'Protect Custom Post Types:', MS_TEXT_DOMAIN ); ?>
						</div>

						<?php
						$cpt_rule_list_table->views();
						$cpt_rule_list_table->search_box( __( 'Post Types', MS_TEXT_DOMAIN ), 'search-cpt' );
						?>
						<form action="" method="post">
							<?php
							$cpt_rule_list_table->display();

							do_action(
								'ms_view_membership_protected_content_footer',
								MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
								$this
							);
							?>
						</form>
					</div>
				</div>
			<?php
			endif;
			?>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			null,
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

}