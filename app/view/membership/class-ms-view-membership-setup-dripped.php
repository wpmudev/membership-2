<?php

class MS_View_Membership_Setup_Dripped extends MS_View {

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
						'title' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
						'title_icon_class' => 'fa fa-cog',
						'desc' => sprintf( __( 'Setup which Protected Content will become available to %s members.', MS_TEXT_DOMAIN ), $this->data['membership']->name ),
						'bread_crumbs' => $this->data['bread_crumbs'],
					) ); 
				?>
				<?php
					$action = MS_Controller_Membership::AJAX_ACTION_UPDATE_MEMBERSHIP;
					$nonce = wp_create_nonce( $action );
					$membership = $this->data['membership'];
					
					MS_Helper_Html::html_element( array(
								'id' => 'dripped_type',
								'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
								'value' => $membership->dripped_type,
								'field_options' => MS_Model_Rule::get_dripped_types(),
								'class' => 'ms-dripped-type ms-ajax-update',
								'data_ms' => array(
										'membership_id' => $membership->id,
										'field' => 'dripped_type',
										'action' => $action,
										'_wpnonce' => $nonce,
								),
					) );
				?>
				<div class="clear"></div>
				<?php 
					$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
				
					/** Call the appropriate form to render. */
					$render_callback =  apply_filters( 'ms_view_membership_setup_dripped_render_tab_callback', array( $this, 'render_tab_' . str_replace( '-', '_', $active_tab ) ), $active_tab, $this->data );
					call_user_func( $render_callback );
				?>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
		
	}
	
	public function render_tab_page() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_PAGE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $rule, $membership );
		$rule_list_table->prepare_items();
	
		ob_start();
		?>
			<div class='ms-settings'>
				<h3><?php _e( 'Pages', MS_TEXT_DOMAIN ); ?></h3>
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
	
	public function render_tab_post() {
		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $rule, $membership );
		$rule_list_table->prepare_items();
	
		ob_start();
		?>
				<div class='ms-settings'>
					<h3><?php _e( 'Posts', MS_TEXT_DOMAIN ); ?></h3>
					<div class="settings-description">
						<?php echo sprintf( __( 'Give access to protected Posts to %s members. ', MS_TEXT_DOMAIN ), $membership->name ); ?>
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