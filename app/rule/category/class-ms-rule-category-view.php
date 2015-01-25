<?php

class MS_Rule_Category_View extends MS_View {

	/**
	 * Set up the view to display the tab contents when required
	 *
	 * @since  1.1.0
	 */
	public function register() {
		$this->add_filter(
			'ms_view_protectedcontent_define-category',
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

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$rule_listtable = new MS_Rule_Category_ListTable(
			$rule,
			$membership
		);
		$rule_listtable->prepare_items();

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$parts['category'] = __( 'Categories', MS_TEXT_DOMAIN );
		}

		$header_data = array();
		$header_data['title'] = __( 'Choose which Categories you want to protect', MS_TEXT_DOMAIN );
		$header_data['desc'] = '';

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
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

			$rule_listtable->views();
			$rule_listtable->search_box( __( 'Categories', MS_TEXT_DOMAIN ), 'search-cat' );
			?>
			<form action="" method="post">
				<?php
				$rule_listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Model_Rule::RULE_TYPE_CATEGORY,
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