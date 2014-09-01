<?php

class MS_View_Membership_List extends MS_View {
	
	public function to_html() {		

		$membership_list = new MS_Helper_List_Table_Membership();
		$membership_list->prepare_items();

		$create_new_button = array(
			'id' => 'create_new_ms_button',
			'type' => MS_Helper_Html::TYPE_HTML_LINK,
			'url' => add_query_arg( array( 'step' => MS_Controller_Membership::STEP_CHOOSE_MS_TYPE ), remove_query_arg( 'membership' ) ),
			'value' => __( 'Create New Membership', MS_TEXT_DOMAIN ),
			'class' => 'button',
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