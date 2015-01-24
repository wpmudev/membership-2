<?php

class MS_Rule_Content_View extends MS_View_Membership_ProtectedContent {

	public function to_html() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$menu_protection = $this->data['settings']->menu_protection;

		$rule_comment = $membership->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT );
		$val_comment = $rule_comment->get_rule_value( MS_Rule_Comment_Model::CONTENT_ID );

		$fields = array(
			'comment' => array(
				'id' => 'comment',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Visitors have:', MS_TEXT_DOMAIN ),
				'value' => $val_comment,
				'field_options' => $rule_comment->get_content_array(),
				'class' => 'chosen-select',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_COMMENT,
					'values' => MS_Rule_Comment_Model::CONTENT_ID,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		if ( 'item' === $menu_protection ) {
			$rule_menu = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MENU );
			$rule_listtable = new MS_Rule_MenuItem_ListTable(
				$rule_menu,
				$membership,
				$this->data['menu_id']
			);

			$fields['menu_id'] = array(
				'id' => 'menu_id',
				'title' => __( 'Protect menu items', MS_TEXT_DOMAIN ),
				'desc' => __( 'Select menu to protect:', MS_TEXT_DOMAIN ),
				'value' => $this->data['menu_id'],
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $this->data['menus'],
				'class' => 'chosen-select',
			);
			$fields['rule_menu'] = array(
				'id' => 'rule_menu',
				'name' => 'rule',
				'value' => 'menu',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			);
		}

		$fields = apply_filters(
			'ms_view_membership_protectedcontent_get_tab_comment_fields',
			$fields
		);

		$rule_listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'Comments, More Tag', MS_TEXT_DOMAIN ),
				'desc' => __( 'Decide how to protect Comments and More Tag contents.', MS_TEXT_DOMAIN ),
			),
			MS_Model_Rule::RULE_TYPE_COMMENT,
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
			?>

			<div class="ms-group">
					<?php
					MS_Helper_Html::html_element( $fields['content'] );
					MS_Helper_Html::save_text();

					do_action(
						'ms_view_membership_protectedcontent_footer',
						MS_Model_Rule::RULE_TYPE_CONTENT,
						$this
					);
					?>
			</div>

			<?php MS_Helper_Html::html_separator(); ?>

			<div class="ms-group ms-group-menu ms-protect-<?php echo esc_attr( $menu_protection ); ?>">
				<div class="ms-inside">

				<?php if ( 'item' === $menu_protection ) {
					$menu_url = add_query_arg( array( 'menu_id' => $this->data['menu_id'] ) );
					?>
					<form id="ms-menu-form" method="post">
						<?php MS_Helper_Html::html_element( $fields['menu_id'] ); ?>
					</form>

					<form id="ms-menu-form" method="post" action="<?php echo '' . $menu_url; ?>">
						<?php
						MS_Helper_Html::html_element( $fields['rule_menu'] );
						$rule_listtable->views();
						$rule_listtable->display();
						?>
					</form>
					<?php

					do_action(
						'ms_view_membership_protectedcontent_footer',
						MS_Model_Rule::RULE_TYPE_MENU,
						$this
					);

				} else { ?>
					<p><em>
						<?php _e( 'No options available. Menu access is defined on membership-level.', MS_TEXT_DOMAIN ); ?>
					</em></p>
				<?php } ?>
				</div>
			</div>

		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

}