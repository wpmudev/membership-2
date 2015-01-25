<?php

class MS_Rule_MenuItem_View extends MS_View {

	/**
	 * Set up the view to display the tab contents when required
	 *
	 * @since  1.1.0
	 */
	public function register() {
		$this->add_filter(
			'ms_view_protectedcontent_define-menuitem',
			'handle_render_callback', 10, 2
		);
	}

	/**
	 * Tells Protected Content Admin to display this form to manage this rule.
	 *
	 * @since 1.1.0
	 *
	 * @param array $callback (Invalid callback)
	 * @param array $data The data collection.
	 * @return array Correct callback.
	 */
	public function handle_render_callback( $callback, $data ) {
		$this->data = $data;
		$callback = array( $this, 'to_html' );

		return $callback;
	}

	public function to_html() {
		$membership = $this->data['membership'];

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

		$rule_listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'Menu Items', MS_TEXT_DOMAIN ),
				'desc' => __( 'Protect individual menu items.', MS_TEXT_DOMAIN ),
			),
			MS_Model_Rule::RULE_TYPE_MENU,
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
			?>
		</div>
		<?php

		MS_Helper_Html::settings_footer();

		return ob_get_clean();
	}

}