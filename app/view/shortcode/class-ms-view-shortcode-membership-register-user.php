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
			'url' => wp_login_url( add_query_arg( array( 'step' => 'choose_membership' ), $permalink ) ),
			'class' => 'alignleft',
			'value' => __( 'Already have a user account?', MS_TEXT_DOMAIN ),
		);
		?>
		<div class="ms-membership-form-wrapper">
			<?php $this->render_errors() ?>
			<form id="ms-shortcode-register-user-form" class="form-membership" action="<?php echo add_query_arg( 'action', 'register_user', $permalink ) ?>" method="post">
				<?php wp_nonce_field( $this->data['action'] ); ?>
				<legend><?php _e( 'Create an Account', MS_TEXT_DOMAIN ); ?></legend>
				<?php foreach( $this->fields as $field ): ?>
					<div class="ms-form-element">
						<?php MS_Helper_Html::html_element( $field );?>
					</div>
				<?php endforeach;?>
				<?php do_action( 'ms_view_shortcode_membership_register_user_after_fields' ); ?>
				<?php do_action( 'ms_view_shortcode_membership_register_user_extra_fields', $this->error ); ?>
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
				'membership_id' => array(
						'id' => 'membership_id',
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
				'username' => array(
						'id' => 'username',
						'title' => __( 'Choose a Username', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $data['username'],
				),
				'email' => array(
						'id' => 'email',
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
				'action' => array(
						'id' => 'action',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $data['action'],
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $data['step'],
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