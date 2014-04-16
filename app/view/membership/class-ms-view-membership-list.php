<?php

class MS_View_Membership_List extends MS_View {

	public function _to_html() {		

		$membership_list = new MS_Model_Membership_List_Table();
		$membership_list->prepare_items();

		ob_start();
		?>
		
		<div class="wrap">
			<h2><?php  _e( 'Memberships', MS_TEXT_DOMAIN ) ; ?>
				<a class="add-new-h2" href="/wp-admin/admin.php?page=membership-edit"><?php _e( 'Add New', MS_TEXT_DOMAIN ); ?></a>
			</h2>
			<?php $membership_list->display(); ?>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
}