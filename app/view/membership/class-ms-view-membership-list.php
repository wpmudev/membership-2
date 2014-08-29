<?php

class MS_View_Membership_List extends MS_View {
	
	public function to_html() {		

		$membership_list = new MS_Helper_List_Table_Membership();
		$membership_list->prepare_items();

		$create_new_button = array(
			'id' => 'create_new_ms_button',
			'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value' => __( 'Create New Membership', MS_TEXT_DOMAIN ),
		);
		ob_start();
		?>
		
		<div class="wrap ms-wrap">
			<?php 
				MS_Helper_Html::settings_header( array(
					'title' => __( 'Memberships', MS_TEXT_DOMAIN ),			
				) ); 
			?>
			<form action="" method="post">
				<div class="ms-list-table-wrapper">
					<?php MS_Helper_Html::html_input( $create_new_button );?>
					<?php $membership_list->display(); ?>
				</div>
			</form>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
}