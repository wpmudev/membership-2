<?php

class MS_View_Membership_Accessible_Content extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$tabs = $this->data['tabs'];
		ob_start();
		
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap wrap'>
				<?php 
					MS_Helper_Html::settings_header( array(
						'title' => __( 'Accessible content', MS_TEXT_DOMAIN ),
						'title_icon_class' => 'fa fa-cog',
						'desc' => sprintf( __( 'Setup which Protected Content is available to %s members.', MS_TEXT_DOMAIN ), $this->data['membership']->name ),
					) ); 
				?>
				<?php
					$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
				
					/** Call the appropriate form to render. */
					$render_callback =  apply_filters( 'ms_view_membership_edit_render_callback', array( $this, 'render_' . str_replace('-', '_', $active_tab ) ), $active_tab, $this->data );
					call_user_func( $render_callback );
				?>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
		
	}
	public function render_category() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$category_rule_list_table = new MS_Helper_List_Table_Rule_Category( $rule, $membership );
		$category_rule_list_table->prepare_items();
		
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
		$cpt_rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type_Group( $rule, $membership );
		$cpt_rule_list_table->prepare_items();
		
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php echo __( 'Categories, Custom Post Types Access', MS_TEXT_DOMAIN ); ?></h3>
				<div class="settings-description">
					<?php echo sprintf( __( 'Give access to protected Categories & Custom Post Types to %s members.', MS_TEXT_DOMAIN ), $membership->name ); ?>
				</div>
				<hr />			
				<div class="ms-list-table-wrapper ms-list-table-half">
					<div class="ms-field-input-label">
						<?php _e( 'Protected Categories:', MS_TEXT_DOMAIN );?>
					</div>
					<?php $category_rule_list_table->display(); ?>
				</div>
				<div class="ms-list-table-wrapper ms-list-table-half">
					<div class="ms-field-input-label">
						<?php _e( 'Protected Custom Post Types:', MS_TEXT_DOMAIN );?> 
					</div>
					<?php $cpt_rule_list_table->display(); ?>
				</div>
			</div>
			<?php MS_Helper_Html::settings_footer(); ?>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	
	public function render_page() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_PAGE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $rule, $membership );
		$rule_list_table->prepare_items();
	
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php _e( 'Page access for ', MS_TEXT_DOMAIN ); ?></h3>
				<div class="settings-description">
					<?php echo sprintf( __( 'Give access to protected Pages to %s members. ', MS_TEXT_DOMAIN ), $membership->name ); ?>
				</div>
				<hr />
				
				<?php $rule_list_table->views(); ?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
				</form>
			</div>
			<?php MS_Helper_Html::settings_footer(); ?>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
}