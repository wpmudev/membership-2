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
	
		$edit_link = array(
				'id' => 'bbpress_rule_edit',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( 'Manage Protected bbPress Forums', MS_TEXT_DOMAIN ),
				'url' => sprintf( 'admin.php?page=%s&tab=%s', MS_Controller_Plugin::MENU_SLUG . '-setup', MS_Integration_Bbpress::RULE_TYPE_BBPRESS ),
		);
		
		$title = __( 'bbPress', MS_TEXT_DOMAIN );
		if( empty( $this->data['protected_content'] ) ) {
			$desc = sprintf( __( 'Give access to protected bbPress content to %s members.', MS_TEXT_DOMAIN ), $this->data['membership']->name );
		}
		else {
			$desc = __( 'Protect the following bbPress content to members only. ', MS_TEXT_DOMAIN );
		}
		ob_start();
		?>
			<div class='ms-settings'>
				<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
				<hr />
				
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
				<?php if( empty( $this->data['protected_content'] ) ): ?>
					<div class="ms-protection-edit-link">
						<?php MS_Helper_Html::html_input( $edit_link );?>
					</div>
				<?php endif;?>
			</div>
			<?php 
				MS_Helper_Html::settings_footer( 
						array( 'fields' => array( $fields['step'] ) ),
						true,
						$this->data['hide_next_button']
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