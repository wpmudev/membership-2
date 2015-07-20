<?php
class MS_View_Shortcode_RegisterUser extends MS_View {

	/**
	 * Returns the HTML code.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_html() {
		$fields = $this->prepare_fields();

		// When redirecting to login form we want to keep the previously submitted form data.
		$url_data = $_POST;
		$url_data['do-login'] = '1';
		$login_url = esc_url_raw( add_query_arg( $url_data ) );

		if ( ! empty( $_REQUEST['do-login'] ) ) {
			$register_url = esc_url_raw( remove_query_arg( 'do-login' ) );

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

		$register_button = array(
			'id' => 'register',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => $this->data['label_register'],
		);

		$title = $this->data['title'];
		ob_start();

		$reg_url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );
		$reg_url = esc_url_raw(
			add_query_arg( 'action', 'register_user', $reg_url )
		);

		// Default WP action hook
		do_action( 'before_signup_form' );
		?>
		<div class="ms-membership-form-wrapper">
			<?php $this->render_errors(); ?>
			<form
				id="ms-shortcode-register-user-form"
				class="form-membership"
				action="<?php echo esc_url( $reg_url ); ?>"
				method="post">

				<?php wp_nonce_field( $this->data['action'] ); ?>
				<?php if ( ! empty( $title ) ) : ?>
					<legend>
						<?php echo '' . $title; ?>
					</legend>
				<?php endif; ?>

				<?php foreach ( $fields as $field ) {
					if ( MS_Helper_Html::INPUT_TYPE_HIDDEN == $field['type'] ) {
						MS_Helper_Html::html_element( $field );
					} else {
						?>
						<div class="ms-form-element ms-form-element-<?php echo esc_attr( $field['id'] ); ?>">
							<?php MS_Helper_Html::html_element( $field ); ?>
						</div>
						<?php
					}
				}

				echo '<div class="ms-extra-fields">';

				/**
				 * Trigger default WordPress action to allow other plugins
				 * to add custom fields to the registration form.
				 *
				 * signup_extra_fields Defined in wp-signup.php which is used
				 *              for Multisite signup process.
				 *
				 * register_form Defined in wp-login.php which is only used for
				 *              Single site registration process.
				 *
				 * @since  1.0.0
				 */
				if ( is_multisite() ) {
					$empty_error = new WP_Error();
					do_action( 'signup_extra_fields', $empty_error );
				} else {
					do_action( 'register_form' ); // Always on the register form.
				}

				echo '</div>';

				MS_Helper_Html::html_element( $register_button );

				if ( is_wp_error( $this->error ) ) {
					/**
					 * Display registration errors.
					 *
					 * @since  1.0.0
					 */
					do_action( 'registration_errors', $this->error );
				}
				?>
			</form>
			<?php
			if ( $this->data['loginlink'] ) {
				MS_Helper_Html::html_link( $login_link );
			}
			?>
		</div>
		<?php
		// Default WP action hook.
		do_action( 'signup_blogform', array() );
		do_action( 'after_signup_form' );

		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return apply_filters(
			'ms_shortcode_register',
			$html,
			$this->data
		);
	}

	/**
	 * Prepares the fields that are displayed in the form.
	 *
	 * @since  1.0.0
	 * @return array
	 */
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
				'title' => $data['label_first_name'],
				'placeholder' => $data['hint_first_name'],
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $data['first_name'],
			),

			'last_name' => array(
				'id' => 'last_name',
				'title' => $data['label_last_name'],
				'placeholder' => $data['hint_last_name'],
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $data['last_name'],
			),

			'username' => array(
				'id' => 'username',
				'title' => $data['label_username'],
				'placeholder' => $data['hint_username'],
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $data['username'],
			),

			'email' => array(
				'id' => 'email',
				'title' => $data['label_email'],
				'placeholder' => $data['hint_email'],
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $data['email'],
			),

			'password' => array(
				'id' => 'password',
				'title' => $data['label_password'],
				'placeholder' => $data['hint_password'],
				'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
				'value' => '',
			),

			'password2' => array(
				'id' => 'password2',
				'title' => $data['label_password2'],
				'placeholder' => $data['hint_password2'],
				'type' => MS_Helper_Html::INPUT_TYPE_PASSWORD,
				'value' => '',
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