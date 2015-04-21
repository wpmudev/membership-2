<?php

class MS_View_Membership_News extends MS_View {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * @since 1.0
	 *
	 * @return object
	 */
	public function to_html() {
		$list_table = MS_Factory::create( 'MS_Helper_ListTable_Event' );
		$list_table->prepare_items();

		if ( isset( $_REQUEST['membership_id'] ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $_REQUEST['membership_id'] );
			$title = sprintf(
				__( '%s News', MS_TEXT_DOMAIN ),
				sprintf(
					'<span class="ms-membership" style="background-color:%2$s">%1$s</span>',
					esc_html( $membership->name ),
					$membership->get_color()
				)
			);
			$url = esc_url_raw(
				add_query_arg( array( 'step' => MS_Controller_Membership::STEP_OVERVIEW ) )
			);
			$back_link = array(
				'id' => 'back',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( '&raquo; Back to Overview', MS_TEXT_DOMAIN ),
				'url' => $url,
				'class' => 'wpmui-field-button button',
			);
		} else {
			$title = __( 'Membership News', MS_TEXT_DOMAIN );
			$back_link = '';
		}

		ob_start();
		?>

		<div class="wrap ms-wrap ms-membership-news">
			<?php
			MS_Helper_Html::settings_header(
				array( 'title' => $title )
			);

			MS_Helper_Html::html_element( $back_link );

			$list_table->views();
			?>
			<form action="" method="post">
				<?php $list_table->search_box(); ?>
				<?php $list_table->display(); ?>
			</form>
		</div>

		<?php
		$html = ob_get_clean();
		echo '' . $html;
	}
}
