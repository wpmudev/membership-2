<?php

class MS_Rule_Content_View extends MS_View {

	/**
	 * Set up the view to display the tab contents when required
	 *
	 * @since  1.1.0
	 */
	public function register() {
		$this->add_filter(
			'ms_view_protectedcontent_define-content',
			'handle_render_callback', 10, 4
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
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CONTENT );

		$rule_listtable = new MS_Rule_Content_ListTable( $rule, $membership );
		$rule_listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'Comments & More Tag', MS_TEXT_DOMAIN ),
				'desc' => __( 'Decide how to protect Comments and More Tag contents.', MS_TEXT_DOMAIN ),
			),
			MS_Model_Rule::RULE_TYPE_CONTENT,
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
			$rule_listtable->search_box( __( 'Pages', MS_TEXT_DOMAIN ) );
			?>
			<form action="" method="post">
				<?php
				$rule_listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Model_Rule::RULE_TYPE_CONTENT,
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