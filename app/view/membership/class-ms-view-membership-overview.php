<?php

class MS_View_Membership_Overview extends MS_View {

	protected $data;
	
	public function to_html() {
		$membership = $this->data['membership'];
		
		$toggle = array(
				'id' => 'ms-toggle-' . $membership->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $membership->active,
				'class' => '',
				'data_ms' => array(
						'action' => MS_Controller_Membership::AJAX_ACTION_TOGGLE_MEMBERSHIP,
						'field' => 'active',
						'membership_id' => $membership->id,
				),
		);
		$status_class = '';
		if( $membership->active ) {
			$status_class = 'ms-active';
		}
		ob_start();
		?>
				
		<div class="wrap ms-wrap">
			<div class="ms-membership-status-wrapper">
				<?php MS_Helper_Html::html_element( $toggle ); ?>
				<div id='ms-membership-status' class="ms-membership-status <?php echo $status_class; ?>">
					<?php 
						printf( '<div class="ms-active"><span>%s </span><span id="ms-membership-status-text" class="ms-ok">%s</span></div>', 
							__( 'Membership is', MS_TEXT_DOMAIN ),
							__( 'Active', MS_TEXT_DOMAIN ) 
						); 
					?>
					<?php 
						printf( '<div><span>%s </span><span id="ms-membership-status-text" class="ms-nok">%s</span></div>', 
							__( 'Membership is', MS_TEXT_DOMAIN ),
							__( 'Disabled', MS_TEXT_DOMAIN ) 
						); 
					?>
				</div>
			</div>
			<?php 
				MS_Helper_Html::settings_header( array(
					'title' => sprintf( __( '%s Overview', MS_TEXT_DOMAIN ), $membership->name ),
					'desc' => __( "Here you can view a quick summary of this membership, and alter any of it's details.", MS_TEXT_DOMAIN ),
					'bread_crumbs' => $this->data['bread_crumbs'], 
				) ); 
			?>
			<div class="clear"></div>
			<hr />
			<?php $this->news_panel(); ?>
			<?php $this->members_panel(); ?>
			<?php $this->available_content_panel(); ?>
			<div class="clear"></div>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function news_panel() {
		?>
			<div class="ms-overview-panel-wrapper ms-overview-news-wrapper">
				<h3 class="hndle"><span><?php _e( 'News:', MS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<?php if( ! empty( $this->data['events'] ) ): ?>
						<table>
							<tr>
								<th><?php _e( 'Date', MS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'User', MS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Event', MS_TEXT_DOMAIN ); ?></th>
							</tr>
							<?php foreach( $this->data['events'] as $event ): ?>
								<tr>
									<td><?php echo date( MS_Helper_Period::PERIOD_FORMAT, strtotime( $event->post_modified ) ); ?></td>
									<td><?php echo MS_Model_Member::get_username( $event->user_id ); ?></td>
									<td><?php echo $event->description; ?></td>
								</td>
							<?php endforeach;?>
						</table>
						<div class="ms-news-view-wrapper">
							<?php 
								MS_Helper_Html::html_element( array(
										'id' => 'view_news',
										'type' => MS_Helper_Html::TYPE_HTML_LINK,
										'value' => __( 'View More News', MS_TEXT_DOMAIN ), 
										'url' => add_query_arg( array( 'step' => MS_Controller_Membership::STEP_NEWS ) ),
										'class' => 'ms-link-button button',
								) );
							?>
						</div>
					<?php else: ?>
						<p><?php _e( 'There will be some interesting news here when your site gets going.', MS_TEXT_DOMAIN ); ?>		
					<?php endif;?>
					<br class="clear">
				</div>
			</div>
		<?php 
	}
	
	public function members_panel() {
		$count = count( $this->data['members'] );
		?>
			<div class="ms-overview-panel-wrapper ms-overview-members-wrapper">
				<h3 class="hndle"><span><?php printf( __( 'Members (%s):', MS_TEXT_DOMAIN ), $count ); ?></span></h3>
				<div class="inside">
					<div><?php _e( 'Active Members');?></div>
					<?php if( $count > 0 ): ?>
						<?php foreach( $this->data['members'] as $member ): ?>
							<div class="ms-overview-member-name"><?php echo $member->username; ?></div>
						<?php endforeach;?>
						<div class="ms-member-edit-wrapper">
							<?php 
								MS_Helper_Html::html_element( array(
										'id' => 'edit_members',
										'type' => MS_Helper_Html::TYPE_HTML_LINK,
										'value' => __( 'Edit Members', MS_TEXT_DOMAIN ), 
										'url' => admin_url( 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG . '-members' ),
										'class' => 'ms-link-button button',
								) );
							?>
						</div>
					<?php else: ?>
						<p><?php _e( 'No members yet.', MS_TEXT_DOMAIN ); ?>		
					<?php endif;?>
					<br class="clear">
				</div>
			</div>
		<?php 
	}
	
	public function available_content_panel() {
		?>
			<div class="ms-overview-available-content-wrapper">
				<h3 class="hndle"><span><?php _e( 'Available Content', MS_TEXT_DOMAIN ); ?></span></h3>
				<div><?php echo sprintf( __( 'This is Protected Content which %s members has access to', MS_TEXT_DOMAIN ), $this->data['membership']->name ); ?></div>
				<?php 
					$membership = $this->data['membership'];
					$visitor_membership = MS_Model_Membership::get_visitor_membership();
					$rule_types = MS_Model_Rule::get_rule_types();
					foreach( $rule_types as $rule_type ) {
						if( $visitor_membership->get_rule( $rule_type )->has_rules() ) {
							$this->content_box( $membership->get_rule( $rule_type ) );
						}
					} 
				
				?>
				
			</div>
		<?php 
	}
	
	private function content_box( $rule ) {
		$rule_titles = MS_Model_Rule::get_rule_type_titles();
		$title = $rule_titles[ $rule->rule_type ];
		$contents = $rule->get_contents( array( 'protected_content' => 1 ) );
		?>
			<div class="ms-overview-content-box-wrapper">
				<div class="ms-title">
					<?php echo sprintf( '%s (%s):', $title, $rule->count_rules() );?>
				</div>
				<?php foreach( $contents as $content ): ?>
					<?php if( $content->access ): ?>
						<?php MS_Helper_Html::content_desc( $content->name ) ;?>
					<?php endif; ?>
				<?php endforeach;?>
				<div class="ms-protection-edit-wrapper">
					<?php 
						MS_Helper_Html::html_element( array(
							'id' => 'edit_' . $rule->rule_type,
							'type' => MS_Helper_Html::TYPE_HTML_LINK,
							'title' => $title,
							'value' => sprintf( __( 'Edit %s Restrictions', MS_TEXT_DOMAIN ), $title ), 
							'url' => add_query_arg( array( 'step' => MS_Controller_Membership::STEP_ACCESSIBLE_CONTENT, 'tab' => $rule->rule_type ) ),
							'class' => 'ms-link-button button',
						) );
					?>
				</div>
			</div>
		<?php 
	}
}