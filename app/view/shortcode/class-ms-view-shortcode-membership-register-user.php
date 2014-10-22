<?php

class MS_View_Shortcode_Membership_Register_User extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();

		#$ms_page = MS_Factory::load( 'MS_Model_Pages' )->get_ms_page( MS_Model_Pages::MS_PAGE_REGISTER );
		#$permalink = get_permalink( $ms_page->id );

		// When redirecting to login form we want to keep the previously submitted form data.
		$url_data = $_POST;
		$url_data['do-login'] = '1';
		$login_url = add_query_arg( $url_data );

		if ( '1' == @$_REQUEST['do-login'] ) {
			$register_url = remove_query_arg( 'do-login' );

			$back_link = array(
				'url' => $register_url,
				'class' => 'alignleft',
				'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
			);

			$html = do_shortcode(
				sprintf(
					'[%s show_note=false title="%s"]',
					MS_Helper_Shortcode::SCODE_LOGIN,
					__( 'Login', MS_TEXT_DOMAIN )
				)
			);
			$html .= MS_Helper_Html::html_link( $back_link, true );
			return $html;
		}

		$login_link = array(
			'title' => __( 'Login', MS_TEXT_DOMAIN ),
			'url' => $login_url,
			'class' => 'alignleft',
			'value' => __( 'Already have a user account?', MS_TEXT_DOMAIN ),
		);

		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<?php $this->render_errors(); ?>
			<form id="ms-shortcode-register-user-form" class="form-membership" action="<?php echo esc_url( add_query_arg( 'action', 'register_user' ) ); ?>" method="post">
				<?php wp_nonce_field( $this->data['action'] ); ?>
				<legend>
					<?php _e( 'Create an Account', MS_TEXT_DOMAIN ); ?>
				</legend>
				<?php foreach ( $fields as $field ) { ?>
					<div class="ms-form-element">
						<?php MS_Helper_Html::html_element( $field ); ?>
					</div>
				<?php }

				do_action( 'ms_view_shortcode_membership_register_user_after_fields' );
				do_action( 'ms_view_shortcode_membership_register_user_extra_fields', $this->error );
				?>
			</form>
			<?php MS_Helper_Html::html_link( $login_link ); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function prepare_fields() {
		$data = $this->data;

		$fields = array(
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

		return $fields;
	}

	/**
	 * Renders error messages.
	 *
	 * @access private
	 */
	private function render_errors() {
		if ( ! empty( $this->data['errors'] ) ) {
			?>
			<div class="ms-alert-box ms-alert-error">
				<?php echo $this->data['errors']; ?>
			</div>
			<?php
		}
	}

}