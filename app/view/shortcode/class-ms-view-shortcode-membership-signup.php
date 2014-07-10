<?php

class MS_View_Shortcode_Membership_Signup extends MS_View {
	
	protected $data;
	
	public function to_html() {
		ob_start();
		?>
			<div class="ms-membership-form-wrapper">
				<legend><?php _e( 'Your Membership', MS_TEXT_DOMAIN ) ?></legend>
				<p class="ms-alert-box <?php echo count( $this->data['ms_relationships'] > 0 ) ? 'ms-alert-success' : ''; ?>">
					<?php
						if( count( $this->data['ms_relationships'] ) > 0 ) {
	 						_e( 'Your current subscriptions are listed here. You can renew, cancel or upgrade your subscriptions by using the forms below.', MS_TEXT_DOMAIN );
	 						foreach( $this->data['ms_relationships'] as $membership_id => $membership_relationship ){
	 							switch( $membership_relationship->status ) {
	 								case MS_Model_Membership_Relationship::STATUS_CANCELED:
	 									$msg = __( 'Membership canceled, valid until it expires on: ', MS_TEXT_DOMAIN ) . $membership_relationship->expire_date;
	 									$this->membership_box_html( MS_Model_Membership::load( $membership_id ), MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW, $msg );
	 									break;
	 								case MS_Model_Membership_Relationship::STATUS_EXPIRED:
	 									$msg = __( 'Membership expired on: ', MS_TEXT_DOMAIN ) . $membership_relationship->expire_date;
	 									$this->membership_box_html( MS_Model_Membership::load( $membership_id ), MS_Helper_Membership::MEMBERSHIP_ACTION_RENEW, $msg );
	 									break;
	 								case MS_Model_Membership_Relationship::STATUS_TRIAL:
	 								case MS_Model_Membership_Relationship::STATUS_ACTIVE:
	 									$msg = __( 'Membership expires on: ', MS_TEXT_DOMAIN ) . $membership_relationship->expire_date;
	 									$this->membership_box_html( MS_Model_Membership::load( $membership_id ), MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL, $msg );
	 									break;
	 								case MS_Model_Membership_Relationship::STATUS_PENDING:
	 									$msg = __( 'Pending payment', MS_TEXT_DOMAIN );
	 									$this->membership_box_html( MS_Model_Membership::load( $membership_id ), MS_Helper_Membership::MEMBERSHIP_ACTION_SIGNUP, $msg );
	 									break;
	 								default:
	 									$this->membership_box_html( MS_Model_Membership::load( $membership_id ), MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL );
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
					if( $this->data['member']->is_member() && ! empty( $this->data['memberships'] ) ) {
						?>
		 					<legend class="ms-upgrade-from"> 
		 						<?php 
		 							if( ! empty( $this->data['move_from_id'] ) ) {
										echo __( 'Add membership', MS_TEXT_DOMAIN ); 										
									} 
									else {
										echo __( 'Change membership', MS_TEXT_DOMAIN ); 										
									}
								?>
		 					</legend>
		 				<?php 
	 				}
				?>	
				<form class="ms-membership-form" method="post">
					<div class="ms-form-price-boxes">
						<?php do_action( 'ms_membership_form_before_memberships' ); ?>
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
						<?php do_action( 'ms_membership_form_after_memberships' ) ?>
					</div>
				</form>
			</div>
			<div style='clear:both;'></div>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	
	private function membership_box_html( $membership, $action, $msg = null ) {
		?>
		<div id="ms-membership-wrapper-<?php echo $membership->id; ?>" class="ms-membership-details-wrapper">
			<div class="ms-top-bar">
				<span class="ms-title"><?php echo $membership->name; ?></span>
			</div>
			<div class="ms-price-details">
				<?php echo $membership->description; ?>
			</div>
			<div class="ms-bottom-bar">
				<span class="ms-link">
				<?php if( $msg ): ?>
					<span class="ms-bottom-msg"><?php echo $msg; ?></span>
				<?php endif;?>
				<?php
					$query_args = array( 'action' => $action, 'membership' => $membership->id ) ;
					if( ! empty( $this->data['move_from_id'] ) ) {
						$query_args[ 'move_from' ] = $this->data['move_from_id']; 
					}
					$link = wp_nonce_url( add_query_arg( $query_args ), $action );
					$class = apply_filters( 'ms_membership_form_button_class', 'ms-signup-button' );
					
					$gateway_id = ! empty( $this->data['member']->membership_relationship[ $membership->id ]->gateway_id ) 
										? $this->data['member']->membership_relationship[ $membership->id ]->gateway_id
										: $membership->gateway_id;
					$button_html = apply_filters( "ms_view_shortcode_membership_signup_button_html_{$action}_{$gateway_id}", 
						sprintf( 
							'<a href="%s" class="%s">%s</a>', 
							esc_url( $link ),
							$class,
							esc_html( $this->data[ "{$action}_text" ] )		
						),
						$membership,
						$this->data['member']
					);
					echo $button_html;
				?>
				</span>
			</div>
		</div>
		<?php 
	}
}