<?php

class MS_View_Shortcode_Membership_Register_User extends MS_View {
	
	protected $data;
	
	protected $fields;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		$permalink = get_permalink();
		$login_link = array(
			'title' => __( 'Login', MS_TEXT_DOMAIN ),
			'url' => wp_login_url( add_query_arg( array( 'action' => 'registeruser', 'membership_id' => $this->data['membership_id'] ), $permalink ) ),
			'class' => 'alignleft',
			'value' => __( 'Already have a user account?', MS_TEXT_DOMAIN ),
		);
		?>
		<div class="ms-membership-form-wrapper">
			<?php $this->render_errors() ?>
			<form id="ms-shortcode-register-user-form" class="form-membership" action="<?php echo add_query_arg( 'action', 'register_user', $permalink ) ?>" method="post">
				<legend><?php _e( 'Create an Account', 'membership' ) ?></legend>
				<?php foreach( $this->fields as $field ): ?>
					<div class="ms-form-element">
						<?php MS_Helper_Html::html_input( $field );?>
					</div>
				<?php endforeach;?>
				<?php do_action( 'ms_view_shortcode_membership_register_user_presubmit_content' ); ?>
				<?php do_action( 'signup_extra_fields', $this->error ); ?>
			</form>
			<?php  MS_Helper_Html::html_link( $login_link ); ?>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	
	public function prepare_fields() {
		$data = $this->data;
		
		$this->fields = array(
				'membership' => array(
						'id' => 'membership',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $data['membership_id'],
				),
				'first_name' => array(
						'id' => 'first_name',
						'title' => __( 'First Name', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $data['first_name'],
				),
				'last_name' => array(
						'id' => 'last_name',
						'title' => __( 'Last Name', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $data['last_name'],
				),
				'user_login' => array(
						'id' => 'user_login',
						'title' => __( 'Choose a Username', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $data['username'],
				),
				'user_email' => array(
						'id' => 'user_email',
						'title' => __( 'Email Address', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $data['email'],
				),
				'password' => array(
						'id' => 'password',
						'title' => __( 'Password', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
// 						'desc' => __( 'Hint: The password should be at least 5 characters long. To make it stronger, use upper and lower case letters, numbers and symbols like ! " ? $ % ^ &amp; ).', MS_TEXT_DOMAIN ),
						'value' => '',
				),
				'password2' => array(
						'id' => 'password2',
						'title' => __( 'Confirm Password', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
						'value' => '',
				),
				'register' => array(
						'id' => 'register',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Register My Account', MS_TEXT_DOMAIN ),
				),
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $data['_wpnonce'],
				),
				
		);
	}
	/**
	 * Renders error messages.
	 *
	 * @access private
	 */
	private function render_errors() {
		if( ! empty( $this->data['errors'] ) ) {
		?>
			<div class="ms-alert-box ms-alert-error">
				<?php echo $this->data['errors']; ?>
			</div>
		<?php
		}
	}

}