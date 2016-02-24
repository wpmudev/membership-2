<?php
/**
 * Controller to manage Membership popup dialogs.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 *
 * @return object
 */
class MS_Controller_Dialog extends MS_Controller {

	/**
	 * Prepare the Dialog manager.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function __construct() {
		parent::__construct();

		// Listen to Ajax requests that want to display a popup.
		$this->add_ajax_action( 'ms_dialog', 'ajax_dialog' );

		// Listen to Ajax requests that submit form data.
		$this->add_ajax_action( 'ms_submit', 'ajax_submit' );

		// Login. For IE this hook is listening to guests + logged in users.
                /**
                 * This is moved to main plugin file to avoid IE11 and EDGE browser issue
                 */
		//$this->add_ajax_action( 'ms_login', 'ajax_login', true, true );
		$this->add_ajax_action( 'ms_lostpass', 'ajax_lostpass', true, true );
	}

	/**
	 * Ajax handler. Returns the HTML code of an popup dialog.
	 * The process is terminated after this handler.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function ajax_dialog() {
		$data = '';

		if ( isset( $_REQUEST['dialog'] ) ) {
			$dialog = $_REQUEST['dialog'];
			$dlg = MS_Factory::create( 'MS_' . $dialog );

			$dlg->prepare();

			$data = array(
				'id' => $dialog,
				'title' => $dlg->title,
				'content' => $dlg->content,
				'height' => $dlg->height,
				'width' => $dlg->width,
				'modal' => $dlg->modal,
			);
		}

		$this->respond( $data );
	}

	/**
	 * Ajax handler. Handles incoming form data that was submitted via ajax.
	 * Typically this form is displayed inside a popup.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function ajax_submit() {
		$data = '';

		if ( isset( $_REQUEST['dialog'] ) ) {
			$dialog = $_REQUEST['dialog'];
			$dlg = MS_Factory::create( 'MS_' . $dialog );
			$data = $dlg->submit();
		}

		$this->respond( $data );
	}

	/**
	 * Ajax handler. Used by shortcode `ms-membership-login` to login via ajax.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function ajax_login() {
		$resp = array();

		// First check the nonce, if it fails the function will break
		check_ajax_referer( 'ms-ajax-login' );

		/*
		 * The login fields have alternative names:
		 * - username or log
		 * - password or pwd
		 * - remember or rememberme
		 */
		lib3()->array->equip_post(
			'username',
			'password',
			'remember',
			'log',
			'pwd',
			'rememberme'
		);

		if ( empty( $_POST['username'] ) && ! empty( $_POST['log'] ) ) {
			$_POST['username'] = $_POST['log'];
		}
		if ( empty( $_POST['password'] ) && ! empty( $_POST['pwd'] ) ) {
			$_POST['password'] = $_POST['pwd'];
		}
		if ( empty( $_POST['remember'] ) && ! empty( $_POST['rememberme'] ) ) {
			$_POST['remember'] = $_POST['rememberme'];
		}

		lib3()->array->equip_post( 'username', 'password', 'remember' );
		lib3()->array->strip_slashes( $_POST, 'password' );

		// Nonce is checked, get the POST data and sign user on
		$info = array(
			'user_login' => $_POST['username'],
			'user_password' => $_POST['password'],
			'remember' => (bool) $_POST['remember'],
		);

		$user_signon = wp_signon( $info, false );
		if ( is_wp_error( $user_signon ) ) {
			$resp['error'] = __( 'Wrong username or password', 'membership2' );
		} else {
			$member = MS_Factory::load( 'MS_Model_Member', $user_signon->ID );

			// Also used in class-ms-model-member.php (signon_user)
			wp_set_current_user( $member->id );
			wp_set_auth_cookie( $member->id );
			do_action( 'wp_login', $member->username, $user_signon );
			do_action( 'ms_model_member_signon_user', $user_signon, $member );

			$resp['loggedin'] = true;
			$resp['success'] = __( 'Logging in...', 'membership2' );

			/**
			 * Allows a custom redirection after login.
			 * Empty value will use the default redirect option of the login form.
			 *
			 * @since  1.0.0
			 */
                        $enforce = false;
                        if( isset( $_POST['redirect_to'] ) ) {
                            $resp['redirect'] = apply_filters( 'ms-ajax-login-redirect', $_POST['redirect_to'], $member );
                        }else{
                            $resp['redirect'] = apply_filters(
				'ms_url_after_login',
				$_POST['redirect_to'],
				$enforce
                            );
                        }
			
		}

		$this->respond( $resp );
	}

	/**
	 * Ajax handler. Used by shortcode `ms-membership-login` to recover password
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function ajax_lostpass() {
		$resp = array();

		// First check the nonce, if it fails the function will break
		check_ajax_referer( 'ms-ajax-lostpass' );

		// Nonce is checked, get the POST data and sign user on
		$errors = new WP_Error();

		if ( empty( $_POST['user_login'] ) ) {
			$resp['error'] = __( 'Enter a username or e-mail address.', 'membership2' );
		} else if ( strpos( $_POST['user_login'], '@' ) ) {
			$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
			if ( empty( $user_data ) ) {
				$resp['error'] = __( 'There is no user registered with that email address.', 'membership2' );
			}
		} else {
			$login = trim( $_POST['user_login'] );
			$user_data = get_user_by( 'login', $login );
		}

		do_action( 'lostpassword_post' );

		if ( ! empty( $resp['error'] ) ) {
			$this->respond( $resp );
		}

		if ( ! $user_data ) {
			$resp['error'] = __( 'Invalid username or e-mail.', 'membership2' );
			$this->respond( $resp );
		}

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		do_action( 'retreive_password', $user_login ); // Legacy (misspelled)
		do_action( 'retrieve_password', $user_login );

		$allow = apply_filters( 'allow_password_reset', true, $user_data->ID );

		if ( ! $allow ) {
			$resp['error'] = __( 'Password reset is not allowed for this user', 'membership2' );
			$this->respond( $resp );
		} elseif ( is_wp_error( $allow ) ) {
			return $allow;
		}

		// Save an event about the password reset; also send the email template.
		$member = MS_Factory::load( 'MS_Model_Member', $user_data->ID );
		MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RESETPASSWORD, $member );

		// Send our default email if the user does not have a custom email template in place.
		if ( ! apply_filters( 'ms_sent_reset_password_email', false ) ) {
			// Get a new reset-key.
			$reset = $member->new_password_reset_key();

			$schema = is_ssl() ? 'https' : 'http';

			$message = sprintf(
				__( 'Someone has requested a password reset for the following account: %sIf this was a mistake, just ignore this email and nothing will happen.%s %s', 'membership2' ),
				"\r\n\r\n" . network_home_url( '/', $schema ) . "\r\n\r\n" .
				sprintf( __( 'Username: %s', 'membership2' ), $user_login ) . "\r\n\r\n",
                                "\r\n\r\n" . __( 'To reset your password, visit the following address:', 'membership2' ) . "\r\n",
				"\r\n<" . $reset->url . ">\r\n"
			);

			if ( is_multisite() ) {
				$blogname = $GLOBALS['current_site']->site_name;
			} else {
				$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			}

			$title = sprintf( __( '[%s] Password Reset' ), $blogname );

			$title = apply_filters( 'retrieve_password_title', $title );
			$message = apply_filters( 'retrieve_password_message', $message, $reset->key, $reset->url );

			if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
				$resp['error'] = __( 'The e-mail could not be sent.' ) . '<br />' .
					__( 'Possible reason: your host may have disabled the mail() function.' );
			} else {
				$resp['success'] = __( 'Check your e-mail for the confirmation link.', 'membership2' );
			}
		} else {
			$resp['success'] = __( 'Check your e-mail for the confirmation link.', 'membership2' );
		}

		$this->respond( $resp );
	}

	/**
	 * Output Ajax response (in JSON format) and terminate the process.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $resp The data to output.
	 */
	private function respond( $resp ) {
		echo json_encode( $resp );
		exit();
	}

};