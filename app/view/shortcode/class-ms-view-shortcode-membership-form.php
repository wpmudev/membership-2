<?php

class MS_View_Shortcode_Membership_Form extends MS_View {
	
	protected $data;
	
	public function to_html() {
		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<legend><?php _e( 'Your Membership', MS_TEXT_DOMAIN ) ?></legend>
			<p class="ms-alert-box">
				<?php _e( 'We have the following subscriptions available for our site. To join, simply click on the <strong>Sign Up</strong> button and then complete the registration details.', MS_TEXT_DOMAIN ); ?>
			</p>
	
			<form class="ms-membership-form" method="post">
				<div class="ms-form-price-boxes">
					<?php do_action( 'ms_membership_form_before_memberships' ); ?>
					<?php foreach( $this->data['memberships'] as $membership ) : ?>
						<div id="ms-membership-wrapper-<?php echo $membership->id ?>" class="ms-membership-details-wrapper">
							<div class="ms-top-bar">
								<span class="ms-title"><?php echo $membership->name; ?></span>
							</div>
							<div class="ms-price-details"><?php echo $membership->description; ?></div>
							<div class="ms-bottom-bar">
								<span class="ms-link">
								<?php
									$link = add_query_arg( array( 'action' => $this->data['action'], 'membership' => $membership->id ) );
									$class = apply_filters( 'ms_membership_form_button_class', 'ms-signup-button' );
								?>
								<a href="<?php echo esc_url( $link ) ?>" class="<?php echo $class; ?>">
									<?php echo esc_html( $this->data['signup_text'] ); ?>
								</a>
								</span>
							</div>
						</div>
					<?php endforeach; ?>
					<?php do_action( 'ms_membership_form_after_memberships' ) ?>
				</div>
			</form>
		</div>
		<div style='clear:both;'></div>
		<?php 
		$html = ob_get_clean();
		echo $html;
	}
}