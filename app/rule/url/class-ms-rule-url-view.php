<?php

class MS_Rule_Url_View extends MS_View {

	public function to_html() {
		$membership = MS_Model_Membership::get_base();
		$rule = $membership->get_rule( MS_Rule_Url::RULE_ID );

		$listtable = new MS_Rule_Url_ListTable( $rule );
		$listtable->prepare_items();

		if ( $listtable->list_shows_base_items() ) {
			$add_fields = array(
				'url' => array(
					'id' => 'url_value',
					'title' => __( 'Add new URL Address', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'class' => 'ms-text-medium',
				),
				'url_add' => array(
					'id' => 'url_add',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Protect this URL', MS_TEXT_DOMAIN ),
					'button_type' => 'button',
				),
				'url_action' => array(
					'name' => 'rule_action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => MS_Rule_Url::ACTION_ADD,
				),
				'url_nonce' => array(
					'name' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce( MS_Rule_Url::ACTION_ADD ),
				),
			);
		}

		$header_data = apply_filters(
			'ms_view_membership_protectedcontent_header',
			array(
				'title' => __( 'URL Restriction', MS_TEXT_DOMAIN ),
				'desc' => array(
					__( 'Specify URLs that you want to protect.', MS_TEXT_DOMAIN ),
					__( 'e.g. <b>example.com/protectme/</b> will protect all URLs that contain <b>example.com/protectme/</b>, including any child page.', MS_TEXT_DOMAIN ),
				),
				'class' => '',
			),
			MS_Rule_Url::RULE_ID,
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php MS_Helper_Html::settings_tab_header( $header_data ); ?>

			<form action="" method="post">
			<?php
			if ( $listtable->list_shows_base_items() ) {
				foreach ( $add_fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
			}
			?>
			</form>

			<?php
			$listtable->views();
			$listtable->search_box();
			?>
			<form action="" method="post">
				<?php
				$listtable->display();

				do_action(
					'ms_view_membership_protectedcontent_footer',
					MS_Rule_Url::RULE_ID,
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