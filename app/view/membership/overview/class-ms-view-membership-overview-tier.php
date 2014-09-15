<?php

class MS_View_Membership_Overview_Tier extends MS_View_Membership_Overview {

	protected $data;
	
	public function available_content_panel() {
		?>
			<div class="ms-overview-available-content-wrapper">
				<h3 class="hndle"><span><?php _e( 'Available Content', MS_TEXT_DOMAIN ); ?></span></h3>
				<div><?php printf( __( 'This is Protected Content which <b>%s</b> members has access to.', MS_TEXT_DOMAIN ), $this->data['membership']->name ); ?></div>
				<div>
					<?php 
						MS_Helper_Html::html_input( array(
							'id' => 'setup_tiers',
							'type' => MS_Helper_Html::TYPE_HTML_LINK,
							'title' => __( 'Setup Tiers', MS_TEXT_DOMAIN ),
							'value' => __( 'Setup Tiers', MS_TEXT_DOMAIN ), 
							'url' => add_query_arg( array( 'step' => MS_Controller_Membership::STEP_SETUP_MS_TIERS, 'membership_id' => $this->data['membership']->id ) ),
							'class' => 'ms-link-button button',
						) );
					?>
				</div>
				<?php if( $this->data['child_membership']->is_valid() ): ?>
					<div class='ms-wrap wrap'>
						<div class="ms-tabs-title">
							<span class="ms-tabs"><?php _e( 'Tier Level:', MS_TEXT_DOMAIN );?></span>
							<span><?php _e( 'Accessible Content:', MS_TEXT_DOMAIN );?></span>
						</div>
						<?php
							$active_tab = MS_Helper_Html::html_admin_vertical_tabs( $this->data['tabs'] );
						?>
					</div>
					<div class='ms-settings'>
						<?php 
							$membership = $this->data['child_membership'];
							$visitor_membership = MS_Model_Membership::get_visitor_membership();
							$rule_types = MS_Model_Rule::get_rule_types();
							foreach( $rule_types as $rule_type ) {
								if( $visitor_membership->get_rule( $rule_type )->has_rules() ) {
									$this->content_box( $membership->get_rule( $rule_type ) );
								}
							} 
						
						?>
					</div>
				<?php endif; ?>	
			</div>
		<?php 
	}
	
	private function content_box( $rule ) {
		$rule_titles = MS_Model_Rule::get_rule_type_titles();
		$title = $rule_titles[ $rule->rule_type ];

		?>
			<div class="ms-overview-content-box-wrapper">
				<div class="ms-title">
					<?php echo sprintf( '%s (%s):', $title, $rule->count_rules() );;?>
				</div>
				<?php foreach( $rule->rule_value as $id => $has_access ): ?>
					<?php if( $has_access ): ?>
						<?php MS_Helper_Html::content_desc( $rule->get_content( $id ) ) ;?>
					<?php endif; ?>
				<?php endforeach;?>
				<div class="ms-protection-edit-wrapper">
					<?php 
						MS_Helper_Html::html_input( array(
							'id' => 'edit_' . $rule->rule_type,
							'type' => MS_Helper_Html::TYPE_HTML_LINK,
							'title' => $title,
							'value' => sprintf( __( 'Edit %s Restrictions', MS_TEXT_DOMAIN ), $title ), 
							'url' => add_query_arg( array( 
									'step' => MS_Controller_Membership::STEP_ACCESSIBLE_CONTENT, 
									'tab' => $rule->rule_type,
									'membership_id' => $this->data['child_membership']->id, 
							) ),
							'class' => 'ms-link-button button',
						) );
					?>
				</div>
			</div>
		<?php 
	}
}