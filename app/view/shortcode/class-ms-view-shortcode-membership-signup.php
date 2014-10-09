<?php

class MS_View_Shortcode_Membership_Signup extends MS_View {
	
	protected $data;
	
	protected $fields;
	
	public function to_html() {
		ob_start();
		?>
			<div class="ms-membership-form-wrapper">
				<legend><?php _e( 'Membership Levels', MS_TEXT_DOMAIN ) ?></legend>
				<p class="ms-alert-box <?php echo count( $this->data['ms_relationships'] > 0 ) ? 'ms-alert-success' : ''; ?>">
					<?php
						if( count( $this->data['ms_relationships'] ) > 0 ) {
							
	 						_e( 'Your current subscriptions are listed here. You can renew, cancel or upgrade your subscriptions by using the forms below.', MS_TEXT_DOMAIN );
	 						
	 						foreach( $this->data['ms_relationships'] as $membership_id => $ms_relationship ){
	 							$msg = $ms_relationship->get_status_description();
	 							$membership = MS_Factory::load( 'MS_Model_Membership', $ms_relationship->membership_id );
	 							
	 							switch( $ms_relationship->status ) {
	 								case MS_Model_Membership_Relationship::STATUS_CANCELED:
	 									$this->membership_box_html( $membership, MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW, $msg, $ms_relationship );
	 									break;
	 								case MS_Model_Membership_Relationship::STATUS_EXPIRED:
	 									$this->membership_box_html( $membership, MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW, $msg, $ms_relationship );
	 									break;
	 								case MS_Model_Membership_Relationship::STATUS_TRIAL:
	 								case MS_Model_Membership_Relationship::STATUS_ACTIVE:
	 									$this->membership_box_html( $membership, MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL, $msg, $ms_relationship );
	 									break;
	 								case MS_Model_Membership_Relationship::STATUS_PENDING:
	 									$this->membership_box_html( $membership, MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP, $msg, $ms_relationship );
	 									break;
	 								default:
	 									$this->membership_box_html( $ms_relationship, MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL, $msg, $ms_relationship );
	 									break; 
	 							}
	 						}
	 					}
	 					else {
	 						_e( 'We have the following subscriptions available for our site. To join, simply click on the <strong>Sign Up</strong> button and then complete the registration details.', MS_TEXT_DOMAIN );
	 					}
					?>
				</p>
				<?php
					if( $this->data['member']->has_membership() && ! empty( $this->data['memberships'] ) ) {
						?>
		 					<legend class="ms-move-from"> 
		 						<?php 
		 							if( empty( $this->data['move_from_id'] ) ) {
										echo __( 'Add Membership Level', MS_TEXT_DOMAIN ); 										
									} 
									else {
										echo __( 'Change Membership Level', MS_TEXT_DOMAIN ); 										
									}
								?>
		 					</legend>
		 				<?php 
	 				}
				?>	
				<div class="ms-form-price-boxes">
					<?php do_action( 'ms_view_shortcode_membership_signup_form_before_memberships' ); ?>
					<?php
						if( ! empty( $this->data['move_from_id'] ) ) {
							$action = MS_Helper_Membership::MEMBERSHIP_ACTION_MOVE;
						}
						else {
							$action = MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP;	
						}

						foreach( $this->data['memberships'] as $membership ) {
							$this->membership_box_html( $membership, $action );
						}
					?>
					<?php do_action( 'ms_view_shortcode_membership_signup_form_after_memberships' ) ?>
				</div>
			</div>
			<div style='clear:both;'></div>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	
	private function membership_box_html( $membership, $action, $msg = null, $ms_relationship = null ) {

		$this->prepare_fields( $membership->id, $action );
		?>
		<form class="ms-membership-form" method="post">
			<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
			<?php 
				foreach( $this->fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
			?>
			<div id="ms-membership-wrapper-<?php echo $membership->id; ?>" class="ms-membership-details-wrapper">
				<div class="ms-top-bar">
					<h4><span class="ms-title"><?php echo $membership->name; ?></span></h4>
				</div>
				<div class="ms-price-details">
					<div><?php echo $membership->description; ?></div>
					
					<?php if( $msg ): ?>
						<div class="ms-bottom-msg"><?php echo $msg; ?></div>
					<?php endif;?>
					
				</div>
				<div class="ms-bottom-bar">
					<?php
						$class = apply_filters( 'ms_view_shortcode_membership_signup_form_button_class', "ms-signup-button $action" );
						
						$button = array(
							'id' => 'submit',
							'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
							'value' => esc_html( $this->data[ "{$action}_text" ] ),
							'class' => $class,
						);
						if( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL == $action ) {
							$button = apply_filters( 'ms_view_shortcode_membership_signup_cancel_button', $button, $ms_relationship, $this );
						}
						MS_Helper_Html::html_element( $button );
					?>
				</div>
			</div>
		</form>
		<?php 
	}
	
	private function prepare_fields( $membership_id, $action ) {
		
		$this->fields = array(
			'membership_id' => array(
					'id' => 'membership_id',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $membership_id,
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
			'step' => array(
					'id' => 'step',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['step'],
			),
		);

		if( ! empty( $this->data['move_from_id'] ) ) {
			$this->fields['move_from_id'] = array(
				'id' => 'move_from_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['move_from_id'],
			);
		}
		
		if( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL == $action ) {
			$this->fields['action']['value'] = $action;
			unset( $this->fields['step'] );
		}
	}
}