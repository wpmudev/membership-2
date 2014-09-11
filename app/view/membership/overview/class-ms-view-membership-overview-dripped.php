<?php

class MS_View_Membership_Overview_Dripped extends MS_View_Membership_Overview {

	protected $data;
	
	public function available_content_panel() {
		$available = array();
		$soon = array();
		?>
		<div class="ms-overview-available-content-wrapper">
			<h3 class="hndle"><span><?php _e( 'Available Content', MS_TEXT_DOMAIN ); ?></span></h3>
			<div><?php echo sprintf( __( 'This is Protected Content which %s members has access to', MS_TEXT_DOMAIN ), $this->data['membership']->name ); ?></div>
			<?php 
					$membership = $this->data['membership'];
					$visitor_membership = MS_Model_Membership::get_visitor_membership();
					$rule_types = array( MS_Model_Rule::RULE_TYPE_PAGE, MS_Model_Rule::RULE_TYPE_POST );
					foreach( $rule_types as $rule_type ) {
						if( $visitor_membership->get_rule( $rule_type )->has_rules() ) {
							$rule = $membership->get_rule( $rule_type );
							foreach( $rule->rule_value as $id => $has_access ) {
								if( $rule->has_dripped_rules( $id ) ) {
									$content = array(
											'title' => $rule->get_content( $id ),
											'avail_date' => $rule->get_dripped_avail_date( $id ),
									);
									if( $rule->has_dripped_access( MS_Helper_Period::current_date(), $id ) ) {
										$available[] = $content;
									}
									else {
										$soon[] = $content;
 									}
								}
							}
						}
					}
				?>
			<div class="ms-overview-panel-wrapper ms-available-soon">
				<div class="ms-title">
					<?php _e( 'Soon to be available content:', MS_TEXT_DOMAIN ) ;?>
				</div>
				<?php $this->content_panel( $soon ); ?>
				<div class="ms-protection-edit-wrapper">
					<?php MS_Helper_Html::html_input( array(
							'id' => 'edit_dripped',
							'type' => MS_Helper_Html::TYPE_HTML_LINK,
							'title' => __( 'Edit Dripped Content', MS_TEXT_DOMAIN ),
							'value' => __( 'Edit Dripped Content', MS_TEXT_DOMAIN ), 
							'url' => add_query_arg( array( 'step' => MS_Controller_Membership::STEP_SETUP_DRIPPED ) ),
							'class' => 'ms-link-button button',
					) );?>
				</div>
			</div>
			<div class="ms-overview-panel-wrapper ms-available">
				<div class="ms-title">
					<?php _e( 'Already available content:', MS_TEXT_DOMAIN ) ;?>
				</div>
			</div>
			<?php $this->content_panel( $available ); ?>
		</div>
		<?php 
	}
	
	private function content_panel( $contents ) {
		?>
		<table>
			<tr>
				<th><?php _e( 'Post / Page Title', MS_TEXT_DOMAIN ); ?></th>
				<th><?php _e( 'Content Available', MS_TEXT_DOMAIN ); ?></th>
			</tr>
			<?php foreach( $contents as $id => $content ): ?>
				<tr>
					<td><?php echo $content['title']; ?></td>
					<td><?php echo $content['avail_date']; ?></td>
				</td>
			<?php endforeach;?>
		</table>
		<?php 
	}
}