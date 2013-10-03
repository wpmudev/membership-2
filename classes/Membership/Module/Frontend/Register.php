<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * The module handles user registration process.
 *
 * @since 3.4.5
 *
 * @category Membership
 * @package Module
 * @subpackage Frontend
 */
class Membership_Module_Frontend_Register extends Membership_Module_Frontend {

	const NAME = __CLASS__;

	/**
	 * Determines whether shortcode has been rendered or not.
	 *
	 * @since 3.4.5
	 *
	 * @access private
	 * @var boolean
	 */
	private $_rendered = false;

	/**
	 * Submission errors.
	 *
	 * @since 3.4.5
	 *
	 * @access private
	 * @var WP_Error
	 */
	private $_errors;

	/**
	 * Constructor.
	 *
	 * @since 3.4.5
	 *
	 * @access public
	 * @param Membership_Plugin $plugin The current plugin instance.
	 */
	public function __construct( Membership_Plugin $plugin ) {
		parent::__construct( $plugin );

		$this->_errors = new WP_Error();

		$this->_add_action( 'template_redirect', 'check_register_page' );
		$this->_add_shortcode( 'membership-registration-form', 'get_registration_form' );
	}

	/**
	 * Checks if we about to render or process registration page submission.
	 *
	 * @since 3.4.5
	 * @action template_redirect
	 *
	 * @access public
	 * @global array $M_options The array of membership options.
	 */
	public function check_register_page() {
		global $M_options;

		$registration_page_id = isset( $M_options['registration_page'] ) ? $M_options['registration_page'] : null;
		if ( !is_page() || !( $page = get_queried_object() ) || $page->ID != $registration_page_id ) {
			return;
		}

		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			ob_start();
			$complete = filter_input( INPUT_POST, 'type' ) == 'bp'
				? $this->_handle_bp_form_submission()
				: $this->_handle_simple_form_submission();
			ob_end_clean();

			if ( $complete ) {
				wp_safe_redirect( home_url() );
				exit;
			}
		}

		// setup actions and filters
		$this->_add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );
		$this->_add_filter( 'the_content', 'append_regitration_form', 12 );
	}

	/**
	 * Enqueues scripts and styles required by registration form.
	 *
	 * @since 3.4.5
	 * @action wp_enqueue_scripts
	 *
	 * @access public
	 */
	public function enqueue_scripts() {
		global $M_options;

		// don't load our styles if theme uses it's own styles for registration form
		if ( current_theme_supports( 'membership_subscription_form' ) ) {
			return;
		}

		$this->_enqueue_form_scripts();
		wp_enqueue_style( 'subscriptionformcss', MEMBERSHIP_ABSURL . 'membershipincludes/css/subscriptionform.css', array(), Membership_Plugin::VERSION );

		if ( $M_options['formtype'] == 'new' ) {
			// pop up registration form
			wp_enqueue_style( 'fancyboxcss', MEMBERSHIP_ABSURL . 'membershipincludes/js/fancybox/jquery.fancybox-1.3.4.css', array(), null );
			wp_enqueue_style( 'popupmemcss', MEMBERSHIP_ABSURL . 'membershipincludes/css/popupregistration.css', array(), Membership_Plugin::VERSION );

			wp_enqueue_script( 'fancyboxjs', MEMBERSHIP_ABSURL . 'membershipincludes/js/fancybox/jquery.fancybox-1.3.4.pack.js', array( 'jquery' ), null, true );
			wp_enqueue_script( 'popupmemjs', MEMBERSHIP_ABSURL . 'membershipincludes/js/popupregistration.js', array( 'jquery' ), Membership_Plugin::VERSION, true );

			wp_localize_script( 'popupmemjs', 'membership', array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'registernonce' => wp_create_nonce( 'membership_register' ),
				'loginnonce'    => wp_create_nonce( 'membership_login' ),
				'regproblem'    => __( 'Problem with registration.', Membership_Plugin::NAME ),
				'logpropblem'   => __( 'Problem with Login.', Membership_Plugin::NAME ),
				'regmissing'    => __( 'Please ensure you have completed all the fields', Membership_Plugin::NAME ),
				'regnomatch'    => __( 'Please ensure passwords match', Membership_Plugin::NAME ),
				'logmissing'    => __( 'Please ensure you have entered an username or password', Membership_Plugin::NAME )
			) );
		}
	}

	/**
	 * Renders registration form.
	 *
	 * @since 3.4.5
	 * @filter the_content 12
	 *
	 * @access public
	 * @param string $the_content The content of the registration page.
	 * @return string The updated content of the page with registration form in it.
	 */
	public function append_regitration_form( $the_content ) {
		if ( !$this->_rendered ) {
			$the_content .= $this->get_registration_form();
		}

		return $the_content;
	}

	/**
	 * Returns registration form HTML.
	 *
	 * @since 3.4.5
	 * @shortcode membership-registration-form
	 *
	 * @access public
	 */
	public function get_registration_form() {
		$this->_rendered = true;

		$render = new Membership_Render_Page_Registration();
		$render->errors = $this->_errors->get_error_messages();

		return $render->to_html();
	}

	/**
	 * Handles simple form submission.
	 *
	 * @since 3.4.5
	 *
	 * @access private
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	private function _handle_simple_form_submission() {
		$required = array(
			'user_login' => __( 'Username', 'membership' ),
			'user_email' => __( 'Email address', 'membership' ),
			'password'   => __( 'Password', 'membership' ),
			'password2'  => __( 'Password confirmation', 'membership' ),
		);

		foreach ( $required as $key => $message ) {
			if ( empty( $_POST[$key] ) ) {
				$this->_errors->add( $key, sprintf( __( 'Please ensure that the %s information is completed.', 'membership' ), "<strong>{$message}</strong>" ) );
			}
		}

		$user_login = filter_input( INPUT_POST, 'user_login' );
		$user_login_sanitized = sanitize_user( $user_login );
		$user_email = filter_input( INPUT_POST, 'user_email' );
		$password = filter_input( INPUT_POST, 'password' );
		$password2 = filter_input( INPUT_POST, 'password2' );

		// check passwords equality
		if ( $password && $password2 && $password != $password2 ) {
			$this->_errors->add( 'passmatch', __( 'Please ensure the passwords match.', 'membership' ) );
		}

		// validate user login
		if ( !validate_username( $user_login ) ) {
			$this->_errors->add( 'usernamenotvalid', __( 'The username is not valid, sorry.', 'membership' ) );
		} elseif ( username_exists( $user_login_sanitized ) ) {
			$this->_errors->add( 'usernameexists', __( 'That username is already taken, sorry.', 'membership' ) );
		}

		// validate user email
		if ( !is_email( $user_email ) ) {
			$this->_errors->add( 'emailnotvalid', __( 'The email address is not valid, sorry.', 'membership' ) );
		} else if ( email_exists( $user_email ) ) {
			$this->_errors->add( 'emailexists', __( 'That email address is already taken, sorry.', 'membership' ) );
		}

		do_action( 'membership_validate_user_registration', $this->_errors );

		$result = apply_filters( 'wpmu_validate_user_signup', array(
			'user_name'     => $user_login,
			'orig_username' => $user_login,
			'user_email'    => $user_email,
			'errors'        => $this->_errors
		) );

		if ( isset( $result['errors'] ) && is_wp_error( $result['errors'] ) ) {
			$this->_errors = $result['errors'];
		}

		$anyerrors = $this->_errors->get_error_codes();
		if ( empty( $anyerrors ) ) {
			$user_id = wp_create_user( $user_login_sanitized, $password, $user_email );
			if ( is_wp_error( $user_id ) ) {
				$this->_errors->add( 'userid', $user_id->get_error_message() );
			} else {
				if ( defined( 'MEMBERSHIP_DEACTIVATE_USER_ON_REGISTRATION' ) && filter_var( MEMBERSHIP_DEACTIVATE_USER_ON_REGISTRATION, FILTER_VALIDATE_BOOLEAN ) ) {
					$member = new M_Membership( $user_id );
					$member->deactivate();
				} else {
					wp_signon( array(
						'user_login'    => $user_login,
						'user_password' => $password,
						'remember'      => true
					) );
					wp_set_current_user( $user_id );
				}

				if ( has_action( 'membership_registration_notification' ) ) {
					do_action( 'membership_registration_notification', $user_id, $password );
				} else {
					wp_new_user_notification( $user_id, $password );
				}

				do_action( 'membership_registration_complete', $user_id );
				return true;
			}

		}

		do_action( 'membership_registration_failed', $this->_errors );
		return false;
	}

	/**
	 * Handles BuddyPress form submission.
	 *
	 * @since 3.4.5
	 *
	 * @access private
	 * @global BuddyPress $bp The BuddyPress instance.
	 * @return boolean TRUE on success, otherwise FALSE.
	 */
	private function _handle_bp_form_submission() {
		global $bp;
		if ( !$bp ) {
			return;
		}

		$required = array(
			'signup_username'         => __( 'Username', 'membership' ),
			'signup_email'            => __( 'Email address', 'membership' ),
			'signup_password'         => __( 'Password', 'membership' ),
			'signup_password_confirm' => __( 'Password confirmation', 'membership' ),
		);

		foreach ( $required as $key => $message ) {
			if ( empty( $_POST[$key] ) ) {
				$this->_errors->add( $key, sprintf( __( 'Please ensure that the %s information is completed.', 'membership' ), "<strong>{$message}</strong>" ) );
			}
		}

		$user_login = filter_input( INPUT_POST, 'signup_username' );
		$user_login_sanitized = sanitize_user( $user_login );
		$user_email = filter_input( INPUT_POST, 'signup_email' );
		$password = filter_input( INPUT_POST, 'signup_password' );
		$password2 = filter_input( INPUT_POST, 'signup_password_confirm' );

		// check passwords equality
		if ( $password && $password2 && $password != $password2 ) {
			$this->_errors->add( 'passmatch', __( 'Please ensure the passwords match.', 'membership' ) );
		}

		// validate user login
		if ( !validate_username( $user_login ) ) {
			$this->_errors->add( 'usernamenotvalid', __( 'The username is not valid, sorry.', 'membership' ) );
		} elseif ( username_exists( $user_login_sanitized ) ) {
			$this->_errors->add( 'usernameexists', __( 'That username is already taken, sorry.', 'membership' ) );
		}

		// validate user email
		if ( !is_email( $user_email ) ) {
			$this->_errors->add( 'emailnotvalid', __( 'The email address is not valid, sorry.', 'membership' ) );
		} else if ( email_exists( $user_email ) ) {
			$this->_errors->add( 'emailexists', __( 'That email address is already taken, sorry.', 'membership' ) );
		}

		// Initial fix provided by user: cmurtagh - modified to add extra checks and rejigged a bit
		// Run the buddypress validation
		do_action( 'bp_signup_validate' );

		// Add any errors to the action for the field in the template for display.
		if ( !empty( $bp->signup->errors ) ) {
			foreach ( (array) $bp->signup->errors as $fieldname => $error_message ) {
				$this->_errors->add( $fieldname, $error_message );
			}
		}

		$meta_array = array();

		// xprofile required fields
		/* Now we've checked account details, we can check profile information */
		if ( function_exists( 'bp_is_active' ) && bp_is_active( 'xprofile' ) ) {
			/* Make sure hidden field is passed and populated */
			if ( isset( $_POST['signup_profile_field_ids'] ) && !empty( $_POST['signup_profile_field_ids'] ) ) {
				/* Let's compact any profile field info into an array */
				$profile_field_ids = explode( ',', $_POST['signup_profile_field_ids'] );

				/* Loop through the posted fields formatting any datebox values then validate the field */
				foreach ( (array) $profile_field_ids as $field_id ) {
					if ( !isset( $_POST['field_' . $field_id] ) ) {
						if ( isset( $_POST['field_' . $field_id . '_day'] ) )
							$_POST['field_' . $field_id] = strtotime( $_POST['field_' . $field_id . '_day'] . $_POST['field_' . $field_id . '_month'] . $_POST['field_' . $field_id . '_year'] );
					}

					/* Create errors for required fields without values */
					if ( xprofile_check_is_required_field( $field_id ) && empty( $_POST['field_' . $field_id] ) ) {
						$field = new BP_Xprofile_Field( $field_id );
						$this->_errors->add( $field->name, sprintf( __( 'Please ensure that the %s information is completed.', 'membership' ), "<strong>{$field->name}</strong>" ) );
					}

					$meta_array[$field_id] = $_POST['field_' . $field_id];
				}
			}
		}

		do_action( 'membership_validate_user_registration', $this->_errors );

		// Hack for now - eeek
		$anyerrors = $this->_errors->get_error_code();
		if ( empty( $anyerrors ) ) {
			// No errors so far - error reporting check for final add user *note $error should always be an error object becuase we created it as such.
			$user_id = wp_create_user( $user_login_sanitized, $password, $user_email );

			if ( is_wp_error( $user_id ) ) {
				$this->_errors->add( 'userid', $user_id->get_error_message() );
			} else {
				if ( defined( 'MEMBERSHIP_DEACTIVATE_USER_ON_REGISTRATION' ) && filter_var( MEMBERSHIP_DEACTIVATE_USER_ON_REGISTRATION, FILTER_VALIDATE_BOOLEAN ) ) {
					$member = new M_Membership( $user_id );
					$member->deactivate();
				} else {
					wp_signon( array(
						'user_login'    => $user_login,
						'user_password' => $password,
						'remember'      => true,
					) );
					wp_set_current_user( $user_id );
				}

				if ( has_action( 'membership_registration_notification' ) ) {
					do_action( 'membership_registration_notification', $user_id, $password );
				} else {
					wp_new_user_notification( $user_id, $password );
				}

				// Add the bp filter for usermeta signup
				$meta_array = apply_filters( 'bp_signup_usermeta', $meta_array );
				if ( function_exists( 'xprofile_set_field_data' ) ) {
					foreach ( (array)$meta_array as $field_id => $field_content ) {
						xprofile_set_field_data( $field_id, $user_id, $field_content );
					}
				}

				do_action( 'membership_registration_complete', $user_id );
				do_action( 'bp_complete_signup' );
				return true;
			}
		}

		do_action( 'membership_registration_failed', $this->_errors );
		return false;
	}

}