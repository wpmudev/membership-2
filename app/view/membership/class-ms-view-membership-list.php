<?php

class MS_View_Membership_List extends MS_View {

	public function to_html() {		

		$membership_list = new MS_Helper_List_Table_Membership();
		$membership_list->prepare_items();

		ob_start();
		?>
		
		<div class="wrap">
			<h2><?php  _e( 'Memberships', MS_TEXT_DOMAIN ) ; ?>
				<a class="add-new-h2" href="/wp-admin/admin.php?page=membership-edit"><?php _e( 'Add New', MS_TEXT_DOMAIN ); ?></a>
			</h2>
			<form id="setting_form" action="" method="post">
				<?php $membership_list->display(); ?>
			</form>/
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
}