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
					$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $tabs );
				
					/** Call the appropriate form to render. */
					$render_callback =  apply_filters( 'ms_view_membership_setup_dripped_render_callback', array( $this, 'render_' . str_replace('-', '_', $active_tab ) ), $active_tab, $this->data );
					call_user_func( $render_callback );
				?>
			</div>
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
	
	public function render_post() {
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