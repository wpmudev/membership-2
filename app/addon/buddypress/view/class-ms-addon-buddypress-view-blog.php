<?php

class MS_Addon_Buddypress_View_Blog extends MS_View {

	protected $title;

	public function render_rule_tab() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Addon_BuddyPress::RULE_ID_BLOG );
		$list_table = new MS_Addon_Buddypress_Helper_Listtable_Blog( $rule );
		$list_table->prepare_items();

		ob_start();
		?>
			<div class="ms-settings">
				<h3><?php
					_e( 'BuddyPress blogs ', MS_TEXT_DOMAIN );
					echo esc_html( $this->title );
				?></h3>
				<div class="settings-description ms-description"><?php
					_e( 'Select the comment settings below that you would like to give access to as part of this membership. Commenting access is turned off by default.', MS_TEXT_DOMAIN );
				?></div>
				<?php MS_Helper_Html::html_separator(); ?>

				<?php $list_table->views(); ?>
				<form action="" method="post">
					<?php $list_table->display(); ?>
				</form>
			</div>
		<?php
		$html = ob_get_clean();

		echo '' . $html;
	}
}