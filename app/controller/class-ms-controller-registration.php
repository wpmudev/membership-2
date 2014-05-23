<?php
/**
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

class MS_Controller_Registration extends MS_Controller {
		
	public function __construct() {
		$this->add_filter( 'wp_signup_location', 'signup_location', 999 );
		$this->add_filter( 'register_url', 'signup_location', 999 );
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
		$this->add_filter( 'the_content', 'check_for_membership_pages_content', 1 );
// 		$this->add_action( 'the_posts', 'process_actions', 1 );
	}

	public function process_actions() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		if( method_exists( &$this, $action ) ) {
			$this->$action();
		} 
	}
	
	public function signup_location( $url ) {
		if( ! empty( MS_Plugin::instance()->settings->pages[ MS_Model_Settings::SPECIAL_PAGE_REGISTER ] ) ) {
			$url = get_permalink( MS_Plugin::instance()->settings->pages[ MS_Model_Settings::SPECIAL_PAGE_REGISTER ] );
		}

		return apply_filters( 'ms_controller_registration_signup_location', $url );
	}
	
	public function check_for_membership_pages_content( $content ) {

		global $post;
		
		$settings = MS_Plugin::instance()->settings;
		
		if ( empty( $post ) || $post->post_type != 'page' ) {
			return $content;
		}
		
		if ( $settings->is_special_page( $post->ID, MS_Model_Settings::SPECIAL_PAGE_REGISTER ) ) {
		
			// check if page contains a shortcode
			if ( strpos( $content, '[ms-membership-register-user]' ) === false ) {
				// There is no shortcode content in there, so override
				if( ! empty( $_REQUEST['action'] ) ) {
					remove_filter( 'the_content', 'wpautop' );
					$membership_id = 0;
					if( ! empty( $_REQUEST['membership_id'] ) ) {
						$membership_id = $_REQUEST['membership_id']; 
					}
					$content .= do_shortcode( "[ms-membership-register-user membership_id='$membership_id']" );
				}
				else {
					remove_filter( 'the_content', 'wpautop' );
					$content .= do_shortcode( '[ms-membership-signup]' );
				}
			}
		}
		elseif ( $settings->is_special_page( $post->ID, MS_Model_Settings::SPECIAL_PAGE_ACCOUNT ) ) {
			// account page - check if page contains a shortcode
			if ( strpos( $content, '[ms-membership-account]' ) !== false || 
					strpos( $content, '[ms-membership-upgrade]' ) !== false || 
					strpos( $content, '[ms-membership-renew]' ) !== false ) {
				// There is content in there with the shortcode so just return it
				return $content;
			}
			// There is no shortcode in there, so override
			remove_filter( 'the_content', 'wpautop' );
			$content .= do_shortcode( '[ms-membership-account]' );
		} 
		elseif ( $settings->is_special_page( $post->ID, MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS ) ) {
			// account page - check if page contains a shortcode
			if ( strpos( $content, '[ms-membership-upgrade]' ) !== false || strpos( $content, '[ms-memberhship-renew]' ) !== false ) {
				// There is content in there with the shortcode so just return it
				return $content;
			}
			// There is no shortcode in there, so override
			remove_filter( 'the_content', 'wpautop' );
			if( MS_Model_Member::is_logged_user() ) {
				$content .= do_shortcode( '[ms-membership-signup]' );
			}
			else {
				$content .= do_shortcode( '[ms-membership-login]' );
			}
		}
		return $content;
	}
	
	public function membership_signup() {

		if( MS_Model_Member::is_logged_user() && ! empty( $_GET['membership'] ) && MS_Model_Membership::is_valid_membership( $_GET['membership'] ) ) {
			if( ! empty( $_POST['membership_signup'] ) && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['gateway'] )
				&& ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['gateway'] .'_' . $_POST['membership_id'] ) ) {
				$gateway_id = $_POST['gateway'];
				$membership_id = $_POST['membership_id'];
				$membership = MS_Model_Membership::load( $membership_id );
				/**
				 * Manual gateway.
				 * Other gateways may have IPN return through handle_payment_return function.
				 */
				if( $membership->price > 0 ) {
					$gateway = MS_Model_Gateway::factory( $gateway_id );
					add_action( 'the_content', array( $gateway, 'handle_return'), 1 );
				}
			}
			else {
				$membership_id = $_GET['membership'];
				$membership = MS_Model_Membership::load( $membership_id );
				$member = MS_Model_Member::get_current_member();
				/**
				 * Free gateway.
				 */
				if( $membership->price == 0 && ! empty( $_GET['membership'] )  && ! empty( $_GET['action'] ) 
						&& ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {
					$gateway_id = 'free_gateway';
					$gateway = MS_Model_Gateway::factory( $gateway_id );
					$gateway->add_transaction( $membership, $member, MS_Model_Transaction::STATUS_PAID );
					$url = get_permalink( MS_Plugin::instance()->settings->pages['registration_completed'] );
					wp_safe_redirect( $url );
				}
				/**
				 * Show payment table.
				 */
				else {
					$this->add_action( 'the_content', 'payment_table', 1 );
				}
			}	
		}
	}
	
	public function register_user() {
		if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			return;
		}
_ms_debug_log($_POST);
		$required = array(
				'user_login' => __( 'Username', 'membership' ),
				'user_email' => __( 'Email address', 'membership' ),
				'password'   => __( 'Password', 'membership' ),
				'password2'  => __( 'Password confirmation', 'membership' ),
		);
		
		$this->_register_errors = new WP_Error();
		foreach ( $required as $key => $message ) {
			if ( empty( $_POST[$key] ) ) {
				$this->_register_errors->add( $key, __( 'Please ensure that the ', 'membership' ) . "<strong>" . $message . "</strong>" . __( ' information is completed.', 'membership' ) );
			}
		}
		
		if ( $_POST['password'] != $_POST['password2'] ) {
			$this->_register_errors->add( 'passmatch', __( 'Please ensure the passwords match.', 'membership' ) );
		}
		
		if ( !validate_username( $_POST['user_login'] ) ) {
			$this->_register_errors->add( 'usernamenotvalid', __( 'The username is not valid, sorry.', 'membership' ) );
		}
		
		if ( username_exists( sanitize_user( $_POST['user_login'] ) ) ) {
			$this->_register_errors->add( 'usernameexists', __( 'That username is already taken, sorry.', 'membership' ) );
		}
		
		if ( !is_email( $_POST['user_email'] ) ) {
			$this->_register_errors->add( 'emailnotvalid', __( 'The email address is not valid, sorry.', 'membership' ) );
		}
		
		if ( email_exists( $_POST['user_email'] ) ) {
			$this->_register_errors->add( 'emailexists', __( 'That email address is already taken, sorry.', 'membership' ) );
		}
		
		$this->_register_errors = apply_filters( 'membership_subscription_form_before_registration_process', $this->_register_errors );
		
		$result = apply_filters( 'wpmu_validate_user_signup', array(
				'user_name' => $_POST['user_login'],
				'orig_username' => $_POST['user_login'],
				'user_email' => $_POST['user_email'],
				'errors' => $this->_register_errors
		) );
		
		$this->_register_errors = $result['errors'];
		
		// Hack for now - eeek
		$anyerrors = $this->_register_errors->get_error_code();
		if ( empty( $anyerrors ) ) {
			// No errors so far - error reporting check for final add user *note $error should always be an error object becuase we created it as such.
			$user_id = wp_create_user( sanitize_user( $_POST['user_login'] ), $_POST['password'], $_POST['user_email'] );
		
			if ( is_wp_error( $user_id ) ) {
				$this->_register_errors->add( 'userid', $user_id->get_error_message() );
			} else {
				$member = Membership_Plugin::factory()->get_member( $user_id );
				if ( !headers_sent() ) {
					$user = @wp_signon( array(
							'user_login'    => $_POST['user_login'],
							'user_password' => $_POST['password'],
							'remember'      => true,
					) );
		
					if ( is_wp_error( $user ) && method_exists( $user, 'get_error_message' ) ) {
						$this->_register_errors->add( 'userlogin', $user->get_error_message() );
					} else {
						// Set the current user up
						wp_set_current_user( $user_id );
					}
				} else {
					// Set the current user up
					wp_set_current_user( $user_id );
				}
		
				if ( has_action( 'membership_susbcription_form_registration_notification' ) ) {
					do_action( 'membership_susbcription_form_registration_notification', $user_id, $_POST['password'] );
				} else {
					wp_new_user_notification( $user_id, $_POST['password'] );
				}
			}
		
			do_action( 'membership_subscription_form_registration_process', $this->_register_errors, $user_id );
		} else {
			do_action( 'membership_subscription_form_registration_process', $this->_register_errors, 0 );
		}
		
		// Hack for now - eeek
		$anyerrors = $this->_register_errors->get_error_code();
		if ( empty( $anyerrors ) ) {
			// redirect to payments page
			wp_redirect( add_query_arg( array(
			'action'       => 'subscriptionsignup',
			'subscription' => $subscription,
			) ) );
			exit;
		}
		
	}
	public function membership_cancel() {
		if( ! empty( $_GET['membership'] )  && ! empty( $_GET['action'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {
			$membership_id = $_GET['membership'];
			$member = MS_Model_Member::get_current_member();
			$member->drop_membership( $membership_id );
			$member->save();
			wp_safe_redirect( remove_query_arg( array( 'action', '_wpnonce', 'membership' ) ) ) ;
		}
	}
	
	public function payment_table() {
		$membership_id = $_GET['membership'];
		$membership = MS_Model_Membership::load( $membership_id );
		$member = MS_Model_Member::get_current_member();
		
		$data['membership'] = $membership;
		$data['gateways'] = MS_Model_Gateway::get_gateways();
		$data['member'] = $member;
		$data['currency'] = MS_Plugin::instance()->settings->currency;
		$view = apply_filters( 'ms_view_registration_payment', new MS_View_Registration_Payment() );
		$view->data = $data;
		echo $view->to_html();
	}
	
	public function handle_payment_return( $wp_query ) {
		if( ! empty( $wp_query->query_vars['paymentgateway'] ) ) {
			MS_Model_Gateway::get_gateways();
			do_action( 'ms_model_gateway_handle_payment_return_' . $wp_query->query_vars['paymentgateway'] );
		}
	}
}