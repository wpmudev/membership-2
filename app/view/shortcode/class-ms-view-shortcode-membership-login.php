<?php

class MS_View_Shortcode_Membership_Login extends MS_View {

	public function to_html() {
		$html = '';
		$form = '';

		if ( MS_Model_Member::is_logged_user() ) {
			return $this->logout_form();
		}
		elseif ( 'resetpass' == @$this->data['action'] ) {
			return $this->reset_form();
		}
		else {
			extract( $this->data );
			if ( empty( $redirect ) ) {
				$redirect = MS_Helper_Utility::get_current_url();
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

			// Load the ajax script that handles the Ajax login functions.
			wp_enqueue_script( 'ms-ajax-login' );

			wp_localize_script(
				'ms-ajax-login',
				'ms_ajax_login',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'loadingmessage' => __( 'Please wait...', MS_TEXT_DOMAIN ),
					'errormessage' => __( 'Request failed, please try again.', MS_TEXT_DOMAIN ),
				)
			);
		}
		// Remove linebreaks to bypass the "wpautop" filter.
		$html = str_replace( array( "\r\n", "\r", "\n" ), '', $html );

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
				<?php _e( 'Please log in to access this page.', MS_TEXT_DOMAIN ); ?>
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

	/**
	 * Returns HTML partial that contains password-reset form.
	 * Based on WordPress core code from wp-login.php
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	private function reset_form() {
		ob_start();

		$rp_login = wp_unslash( @$_GET['login'] );
		$rp_key = wp_unslash( @$_GET['key'] );
		$err_msg = false;

		// Get the user object and validate the key.
		if ( $rp_login && $rp_key ) {
			$user = check_password_reset_key( $rp_key, $rp_login );
		} else {
			$user = false;
		}

		// If the user was not found then redirect to an error page.
		if ( ! $user || is_wp_error( $user ) ) {
			if ( $user && $user->get_error_code() === 'expired_key' ) {
				$err_msg = __( 'The password-reset key is already expired.', MS_TEXT_DOMAIN );
			}
			else {
				$err_msg = __( 'The password-reset key is invalid or missing.', MS_TEXT_DOMAIN );
			}
			return sprintf(
				'<p>%s</p><p><a href="%s">%s</a>',
				$err_msg,
				remove_query_arg( array( 'action', 'key', 'login' ) ),
				__( 'Request a new password-reset key', MS_TEXT_DOMAIN )
			);
		} else {
			// If the user provided a new password, then check it now.
			if ( isset( $_POST['pass1'] ) && $_POST['pass1'] != $_POST['pass2'] ) {
				$err_msg = __( 'The passwords do not match.', MS_TEXT_DOMAIN );
			}
		}

		// This action is documented in wp-login.php
		do_action( 'validate_password_reset', $err_msg, $user );

		if ( ! $err_msg
			&& isset( $_POST['pass1'] ) && ! empty( $_POST['pass1'] )
		) {
			reset_password( $user, $_POST['pass1'] );

			// All done!
			return __( 'Your Password has been reset.' );
		}

		wp_enqueue_script( 'utils' );
		wp_enqueue_script( 'user-profile' );

		if ( $err_msg ) {
			echo '<p class="error">' . $err_msg . '</p>';
		}
		?>
		<form name="resetpassform" id="resetpassform" action="" method="post" autocomplete="off">
			<input type="hidden" id="user_login" value="<?php echo esc_attr( $rp_login ); ?>" autocomplete="off" />

			<p>
				<label for="pass1"><?php _e( 'New password' ) ?><br />
				<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" /></label>
			</p>
			<p>
				<label for="pass2"><?php _e( 'Confirm new password' ) ?><br />
				<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" /></label>
			</p>

			<div id="pass-strength-result" class="hide-if-no-js"><?php _e( 'Strength indicator' ); ?></div>
			<p class="description indicator-hint"><?php _e( 'Hint: The password should be at least seven characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ % ^ &amp; ).' ); ?></p>

			<br class="clear" />

			<?php
			// This action is documented in wp-login.php
			do_action( 'resetpass_form', $user );
			?>
			<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Reset Password' ); ?>" /></p>
		</form>
		<?php
		return ob_get_clean();
	}
}