<?php

class MS_Rule_Adminside_View extends MS_View {

	/**
	 * Set up the view to display the tab contents when required
	 *
	 * @since  1.1.0
	 */
	public function register() {
		$this->add_filter(
			'ms_view_protectedcontent_define-adminside',
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
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_ADMINSIDE );

		$rule_listtable = new MS_Rule_Adminside_ListTable( $rule, $membership );
		$rule_listtable->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'Admin Side Protection', MS_TEXT_DOMAIN ),
				'desc' => __( 'Protected Admin Side pages are only available for members. The below list contains all possible menu items that WordPress knows about - some of these items might not be available on your installation.', MS_TEXT_DOMAIN ),
			),
			MS_Model_Rule::RULE_TYPE_ADMINSIDE,
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
					MS_Model_Rule::RULE_TYPE_ADMINSIDE,
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