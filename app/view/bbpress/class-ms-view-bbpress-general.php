<?php

class MS_View_Bbpress_General extends MS_View {

	protected $fields = array();
	
	protected $title;
	
	protected $data;
	
	public function render_rule_tab() {
		$fields = $this->get_control_fields();
	
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Integration_Bbpress::RULE_TYPE_BBPRESS );
		$rule_list_table = new MS_Helper_List_Table_Rule_Bbpress( $rule, $membership );
		$rule_list_table->prepare_items();
	
		$title = __( 'bbPress', MS_TEXT_DOMAIN );
		$desc = __( 'Protect the following bbPress content to members only. ', MS_TEXT_DOMAIN );
	
		ob_start();
		?>
			<div class='ms-settings'>
				<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
				<hr />
				
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $fields['step'] ) ),
						true,
						$this->data['initial_setup']
				); 
			?>
		<?php
		
		$html = ob_get_clean();
		echo apply_filters( 'ms_view_bbpress_general_render_tab_shortcode', $html );
	}
	
	public function get_control_fields() {
		$membership = $this->data['membership'];
		$nonce = wp_create_nonce( $this->data['action'] );
		$action = $this->data['action'];
	
		$fields = array(
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $action,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['step'],
				),
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $nonce,
				),
	
		);
		return apply_filters( 'ms_view_bbpress_general_get_control_fields', $fields );
	}
}