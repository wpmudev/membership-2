<?php

class MS_View_Buddypress_Group extends MS_View {

	protected $fields = array();

	protected $title;

	protected $data;

	public function render_rule_tab() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Integration_Buddypress::RULE_TYPE_BUDDYPRESS_GROUP );
		$list_table = new MS_Helper_List_Table_Rule_Buddypress_Blog( $rule );
		$list_table->prepare_items();

		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php
					_e( 'Buddypress groups ', MS_TEXT_DOMAIN );
					echo $this->title;
				?></h3>
				<div class="settings-description ms-description"><?php
					_e( 'Select the comment settings below that you would like to give access to as part of this membership. Commenting access is turned off by default.', MS_TEXT_DOMAIN );
				?></div>
				<div class="ms-separator"></div>

				<?php $list_table->views(); ?>
				<form action="" method="post">
					<?php $list_table->display(); ?>
				</form>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
}