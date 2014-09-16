<?php

class MS_View_Membership_Accessible_Content extends MS_View_Membership_Setup_Protected_Content {

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
						'bread_crumbs' => $this->data['bread_crumbs'],
					) ); 
				?>
				<?php
					$active_tab = $this->data['active_tab']; 
					MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );
				
					/** Call the appropriate form to render. */
					$render_callback =  apply_filters( 'ms_view_membership_accessible_content_render_tab_callback', array( $this, 'render_tab_' . str_replace('-', '_', $active_tab ) ), $active_tab, $this->data );
					call_user_func( $render_callback );
				?>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function render_tab_category() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$category_rule_list_table = new MS_Helper_List_Table_Rule_Category( $rule, $membership );
		$category_rule_list_table->prepare_items();
		
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
		$cpt_rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type_Group( $rule, $membership );
		$cpt_rule_list_table->prepare_items();
		
		$title = array();
		$desc = '';
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$title['category'] = __( 'Categories', MS_TEXT_DOMAIN );
		}
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$title['cpt_group'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
		}
		$desc = sprintf( __( 'Give access to protected %s to %s members.', MS_TEXT_DOMAIN ), implode( ' & ', $title ), $membership->name );
		$title = sprintf( __( '%s Access', MS_TEXT_DOMAIN ), implode( ', ', $title ) );
		ob_start();
		?>
			<div class='ms-settings'>
				<?php MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) ); ?>
				<hr />
				<?php if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ): ?>
					<div class="ms-list-table-wrapper ms-list-table-half">
						<div class="ms-field-input-label">
							<?php _e( 'Protected Categories:', MS_TEXT_DOMAIN );?>
						</div>
						<?php $category_rule_list_table->display(); ?>
					</div>
				<?php endif; ?>
				<?php if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ): ?>
					<div class="ms-list-table-wrapper ms-list-table-half">
						<div class="ms-field-input-label">
							<?php _e( 'Protected Custom Post Types:', MS_TEXT_DOMAIN );?> 
						</div>
						<?php $cpt_rule_list_table->display(); ?>
					</div>
				<?php endif; ?>
			</div>
			<?php MS_Helper_Html::settings_footer(); ?>
		<?php 	
		$html = ob_get_clean();
		echo $html;	
	}
	

}