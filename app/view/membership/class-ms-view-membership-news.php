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
		$list_table = MS_Factory::create( 'ms_helper_listtable_Event' );
		$list_table->prepare_items();

		ob_start();
		?>

		<div class="wrap ms-wrap">
			<?php MS_Helper_Html::settings_header(
				array( 'title' => __( 'Membership News', MS_TEXT_DOMAIN ) )
			); ?>
			<?php $list_table->views(); ?>
			<form action="" method="post">
				<?php $list_table->search_box( __( 'Search user', MS_TEXT_DOMAIN ), 'search' ); ?>
				<?php $list_table->display(); ?>
			</form>
		</div>

		<?php
		$html = ob_get_clean();
		echo apply_filters( 'ms_view_membership_news_to_html', $html );
	}
}
