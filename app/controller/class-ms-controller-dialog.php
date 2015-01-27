<?php
/**
 * This file defines the MS_Controller_Dialog class.
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
 */

/**
 * Controller to manage Membership popup dialogs.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 *
 * @return object
 */
class MS_Controller_Dialog extends MS_Controller {

	/**
	 * Prepare the Dialog manager.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct();

		// Listen to Ajax requests that want to display a popup.
		$this->add_action( 'wp_ajax_ms_dialog', 'ajax_dialog' );

		// Listen to Ajax requests that submit form data.
		$this->add_action( 'wp_ajax_ms_submit', 'ajax_submit' );

		// Login.
		$this->add_action( 'wp_ajax_nopriv_ms_login', 'ajax_login' );
		$this->add_action( 'wp_ajax_nopriv_ms_lostpass', 'ajax_lostpass' );
	}

	/**
	 * Ajax handler. Returns the HTML code of an popup dialog.
	 * The process is terminated after this handler.
	 *
	 * @since  1.0.0
	 * @access public
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
	 * @access public
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
	 * @access public
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
		WDev()->load_post_fields(
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

		// Nonce is checked, get the POST data and sign user on
		$info = array(
			'user_login' => @$_POST['username'],
			'user_password' => @$_POST['password'],
			'remember' => (bool) @$_POST['remember'],
		);

		$user_signon = wp_signon( $info, false );
		if ( is_wp_error( $user_signon ) ) {
			$resp['error'] = __( 'Wrong username or password', MS_TEXT_DOMAIN );
		} else {
			$member = MS_Factory::load( 'MS_Model_Member', $user_signon->ID );

			// Also used in class-ms-model-member.php (signon_user)
			wp_set_current_user( $member->id );
			wp_set_auth_cookie( $member->id );
			do_action( 'wp_login', $member->username, $user_signon );
			do_action( 'ms_model_member_signon_user', $user_signon, $member );

			$resp['loggedin'] = true;
			$resp['success'] = __( 'Logging in...', MS_TEXT_DOMAIN );
		}

		$this->respond( $resp );
	}

	/**
	 * Ajax handler. Used by shortcode `ms-membership-login` to recover password
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function ajax_lostpass() {
		global $wpdb, $wp_hasher;
		$resp = array();

		// First check the nonce, if it fails the function will break
		check_ajax_referer( 'ms-ajax-lostpass' );

		// Nonce is checked, get the POST data and sign user on
		$errors = new WP_Error();

		if ( empty( $_POST['user_login'] ) ) {
			$resp['error'] = __( 'Enter a username or e-mail address.', MS_TEXT_DOMAIN );
		} else if ( strpos( $_POST['user_login'], '@' ) ) {
			$user_data = get_user_by( 'email', trim( $_POST['user_login'] ) );
			if ( empty( $user_data ) ) {
				$resp['error'] = __( 'There is no user registered with that email address.', MS_TEXT_DOMAIN );
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
			$resp['error'] = __( 'Invalid username or e-mail.', MS_TEXT_DOMAIN );
			$this->respond( $resp );
		}

		// Redefining user_login ensures we return the right case in the email.
		$user_login = $user_data->user_login;
		$user_email = $user_data->user_email;

		do_action( 'retreive_password', $user_login ); // Legacy (misspelled)
		do_action( 'retrieve_password', $user_login );

		$allow = apply_filters( 'allow_password_reset', true, $user_data->ID );

		if ( ! $allow ) {
			$resp['error'] = __( 'Password reset is not allowed for this user', MS_TEXT_DOMAIN );
			$this->respond( $resp );
		}
		else if ( is_wp_error( $allow ) ) {
			return $allow;
		}

		// Generate something random for a password reset key.
		$key = wp_generate_password( 20, false );

		do_action( 'retrieve_password_key', $user_login, $key );

		// Now insert the key, hashed, into the DB.
		if ( empty( $wp_hasher ) ) {
			require_once ABSPATH . WPINC . '/class-phpass.php';
			$wp_hasher = new PasswordHash( 8, true );
		}
		$hashed = $wp_hasher->HashPassword( $key );
		$wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_login ) );

		MS_Model_Pages::create_missing_pages();
		$reset_url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_ACCOUNT );
		$reset_url = add_query_arg(
			array(
				'action' => MS_Controller_Frontend::ACTION_VIEW_RESETPASS,
				'key' => $key,
				'login' => rawurlencode( $user_login ),
			),
			$reset_url
		);

		$message = __( 'Someone requested that the password be reset for the following account:' ) . "\r\n\r\n";
		$message .= network_home_url( '/' ) . "\r\n\r\n";
		$message .= sprintf( __( 'Username: %s' ), $user_login ) . "\r\n\r\n";
		$message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "\r\n\r\n";
		$message .= __( 'To reset your password, visit the following address:' ) . "\r\n\r\n";
		$message .= '<' . $reset_url . ">\r\n";

		if ( is_multisite() ) {
			$blogname = $GLOBALS['current_site']->site_name;
		} else {
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		}

		$title = sprintf( __( '[%s] Password Reset' ), $blogname );

		$title = apply_filters( 'retrieve_password_title', $title );
		$message = apply_filters( 'retrieve_password_message', $message, $key, $reset_url );

		if ( $message && ! wp_mail( $user_email, wp_specialchars_decode( $title ), $message ) ) {
			$resp['error'] = __( 'The e-mail could not be sent.' ) . '<br />' .
				__( 'Possible reason: your host may have disabled the mail() function.' );
		} else {
			$resp['success'] = __( 'Check your e-mail for the confirmation link.', MS_TEXT_DOMAIN );
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