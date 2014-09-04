<?php

class MS_View_Membership_Overview extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$membership = $this->data['membership'];
		
		ob_start();
		?>
				
		<div class="wrap ms-wrap">
			<?php 
				MS_Helper_Html::settings_header( array(
					'title' => sprintf( __( '%s Overview', MS_TEXT_DOMAIN ), $membership->name ),
					'desc' => __( "Here you can view a quick summary of this membership, and alter any of it's details.", MS_TEXT_DOMAIN ), 
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
			<div class="ms-overview-news-wrapper">
				<h3 class="hndle"><span><?php _e( 'News:', MS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<?php if( ! empty( $this->data['events'] ) ): ?>
						<table>
							<tr>
								<th><?php _e( 'Date', MS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Member', MS_TEXT_DOMAIN ); ?></th>
								<th><?php _e( 'Event', MS_TEXT_DOMAIN ); ?></th>
							</tr>
							<?php foreach( $this->data['events'] as $event ): ?>
								<tr>
									<td><?php echo date( MS_Helper_Period::DATE_TIME_FORMAT, strtotime( $event->post_modified ) ); ?></td>
									<td><?php echo MS_Model_Member::get_username( $event->user_id ); ?></td>
									<td><?php echo $event->description; ?></td>
								</td>
							<?php endforeach;?>
						</table>
					<?php else: ?>
						<p><?php _e( 'There will be some interesting news here when your site gets going.', MS_TEXT_DOMAIN ); ?>		
					<?php endif;?>
					<br class="clear">
				</div>
			</div>
		<?php 
	}
	
	public function members_panel() {
		?>
			<div class="ms-overview-members-wrapper">
				<h3 class="hndle"><span><?php _e( 'Members:', MS_TEXT_DOMAIN ); ?></span></h3>
				<div class="inside">
					<div><?php _e( 'Active Members');?></div>
					<?php if( ! empty( $this->data['members'] ) ): ?>
						<?php foreach( $this->data['members'] as $member ): ?>
							<div class="ms-overview-member-name"><?php echo $member->username; ?></div>
						<?php endforeach;?>
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
					<?php MS_Helper_Html::html_input( array(
							'id' => 'edit_' . $rule->rule_type,
							'type' => MS_Helper_Html::TYPE_HTML_LINK,
							'title' => $title,
							'value' => sprintf( __( 'Edit %s Restrictions', MS_TEXT_DOMAIN ), $title ), 
							'url' => add_query_arg( array( 'step' => MS_Controller_Membership::STEP_ACCESSIBLE_CONTENT, 'tab' => $rule->rule_type ) ),
							'class' => 'ms-link-button button',
					) );?>
				</div>
			</div>
		<?php 
	}
}