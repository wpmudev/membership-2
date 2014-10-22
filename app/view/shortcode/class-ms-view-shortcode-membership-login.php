<?php

class MS_View_Shortcode_Membership_Login extends MS_View {

	public function to_html() {
		$html = '';
		$form = '';

		if ( MS_Model_Member::is_logged_user() ) {
			return $this->logout_form();
		}
		else {
			extract( $this->data );
			if ( empty( $redirect ) ) {
				$redirect = MS_Helper_Utility::get_current_page_url();
			}

			// Build the Login Form.
			$form .= $prefix;
			$form .= $this->login_form( $redirect );
			$form .= $this->lostpass_form();

			if ( ! empty( $lostpass ) ) {
				$form .= sprintf( '<a href="%s">%s</a>', esc_url( $lostpass ), __( 'Lost password?', MS_TEXT_DOMAIN ) );
			}

			// Wrap form in optional wrappers.
			if ( ! empty( $wrapwith ) ) {
				$form .= sprintf( '<%s class="%s">', esc_attr( $wrapwith ), esc_attr( $wrapwithclass ) );
				$form = sprintf(
					'<%1$s class="%2$s">%3$s</%1$s>',
					esc_attr( $wrapwith ),
					esc_attr( $wrapwithclass ),
					$form
				);
			}
			if ( ! empty( $item ) ) {
				$form = sprintf(
					'<%1$s class="%2$s">%3$s</%1$s>',
					esc_attr( $item ),
					esc_attr( $itemclass ),
					$form
				);
			}
			if ( ! empty( $holder ) ) {
				$form = sprintf(
					'<%1$s class="%2$s">%3$s</%1$s>',
					esc_attr( $holder ),
					esc_attr( $holderclass ),
					$form
				);
			}

			// Complete the HTML output.
			if ( $header ) {
				$html .= $this->login_header_html();
			}
			$html .= $form;

			if ( $register ) {
				$html .= wp_register( '', '', false );
			}
		}

		// Load the ajax script that handles the Ajax login functions.
		wp_enqueue_script( 'ms-ajax-login' );

		wp_localize_script(
			'ms-ajax-login',
			'ms_ajax_login',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'loadingmessage' => __( 'Please wait...', MS_TEXT_DOMAIN ),
			)
		);

		return $html;
	}

	/**
	 * Returns HTML partial with the header of the login form.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function login_header_html() {
		extract( $this->data );

		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<legend><?php echo esc_html( $title ); ?></legend>
			<?php if ( $show_note ) : ?>
			<div class="ms-alert-box ms-alert-error">
				<?php _e( 'You are not currently logged in. Please login to access the page.', MS_TEXT_DOMAIN ); ?>
			</div>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns HTML partial with the actual login form.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $redirect_to URL to redirect to right after login.
	 * @return string
	 */
	private function login_form( $redirect_to = null ) {
		if ( empty( $redirect_to ) ) {
			// Default redirect is back to the current page
			$redirect_to = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		}

		$defaults = array(
			'redirect' => $redirect_to,
			'form_id' => 'loginform',
			'label_username' => __( 'Username' ),
			'label_password' => __( 'Password' ),
			'label_remember' => __( 'Remember Me' ),
			'label_log_in' => __( 'Log In' ),
			'id_username' => 'user_login',
			'id_password' => 'user_pass',
			'id_remember' => 'rememberme',
			'id_submit' => 'wp-submit',
			'remember' => true,
			'value_username' => '',
			'value_remember' => false, // Set this to true to default the "Remember me" checkbox to checked
		);

		/**
		 * Filter the default login form output arguments.
		 *
		 * @since 3.0.0
		 *
		 * @see wp_login_form()
		 *
		 * @param array $defaults An array of default login form arguments.
		 */
		$args = wp_parse_args( $this->data, apply_filters( 'login_form_defaults', $defaults ) );

		extract( $args );

		$show_form = $lost_pass ? 'display:none' : '';

		ob_start();
		?>
		<form name="<?php echo esc_attr( $form_id ); ?>" id="<?php echo esc_attr( $form_id ); ?>" action="login" method="post" style="<?php echo esc_attr( $show_form ); ?>">
			<div class="form">
				<?php wp_nonce_field( 'ms-ajax-login' ); ?>
				<?php echo apply_filters( 'login_form_top', '', $args ); ?>
				<p class="login-username">
					<label for="<?php echo esc_attr( $id_username ); ?>"><?php echo esc_html( $label_username ); ?></label>
					<input type="text" name="log" id="<?php echo esc_attr( $id_username ); ?>" class="input" value="<?php echo esc_attr( $value_username ); ?>" size="20">
				</p>
				<p class="login-password">
					<label for="<?php echo esc_attr( $id_password ); ?>"><?php echo esc_html( $label_password ); ?></label>
					<input type="password" name="pwd" id="<?php echo esc_attr( $id_password ); ?>" class="input" value="" size="20">
				</p>
				<?php echo apply_filters( 'login_form_middle', '', $args ); ?>
				<?php if ( $remember ) : ?>
				<p class="login-remember">
					<label>
						<input name="rememberme" type="checkbox" id="<?php echo esc_attr( $id_remember ); ?>" value="forever" <?php checked( $value_remember ); ?> />
						<?php echo esc_html( $label_remember ); ?>
					</label>
				</p>
				<?php endif; ?>
				<p class="login-submit">
					<input type="submit" name="wp-submit" id="<?php echo esc_attr( $id_submit ); ?>" class="button-primary" value="<?php echo esc_attr( $label_log_in ); ?>" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect ); ?>" />
				</p>
				<?php echo apply_filters( 'login_form_bottom', '', $args ); ?>
				<div class="status" style="display:none"></div>
			</div>
			<div class="nav">
				<a class="lost" href="#lostpassword"><?php _e( 'Lost your password?' ); ?></a>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the HTML partial of the lost-password form
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function lostpass_form() {
		$defaults = array(
			'form_id' => 'lostpasswordform',
			'label_username' => __( 'Username or E-mail:' ),
			'label_reset' => __( 'Get New Password' ),
			'id_username' => 'user_login',
			'id_submit' => 'wp-submit',
			'value_username' => '',
		);

		/**
		 * Filter the default login form output arguments.
		 *
		 * @since 3.0.0
		 *
		 * @see wp_login_form()
		 *
		 * @param array $defaults An array of default login form arguments.
		 */
		$args = wp_parse_args( $this->data, apply_filters( 'login_form_defaults', $defaults ) );

		extract( $args );

		$show_form = $lost_pass ? '' : 'display:none';

		ob_start();

		do_action( 'lost_password' );
		?>
		<form name="<?php echo esc_attr( $form_id ); ?>" id="<?php echo esc_attr( $form_id ); ?>" action="lostpassword" method="post" style="<?php echo esc_attr( $show_form ); ?>">
			<div class="form">
				<?php wp_nonce_field( 'ms-ajax-lostpass' ); ?>
				<p class="lostpassword-username">
					<label for="<?php echo esc_attr( $id_username ); ?>" ><?php echo esc_html( $label_username ); ?></label>
					<input type="text" name="user_login" id="<?php echo esc_attr( $id_username ); ?>" class="input" value="<?php echo esc_attr( $value_username ); ?>" size="20" />
				</p>
				<?php
				/**
				 * Fires inside the lostpassword <form> tags, before the hidden fields.
				 *
				 * @since 2.1.0
				 */
				do_action( 'lostpassword_form' ); ?>
				<p class="submit">
					<input type="submit" name="wp-submit" id="<?php echo esc_attr( $id_submit ); ?>" class="button-primary" value="<?php echo esc_attr( $label_reset ); ?>" />
				</p>
				<div class="status" style="display:none"></div>
			</div>
			<p class="nav">
				<a class="login" href="#login"><?php _e( 'Log in' ); ?></a>
			</p>
		</form>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns HTML partial that contains the logout form
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function logout_form() {
		$html = sprintf(
			'<a class="login_button" href="%s">%s</a>',
			wp_logout_url( home_url() ),
			_e( 'Logout' )
		);

		return $html;
	}
}