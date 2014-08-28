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
	}
}