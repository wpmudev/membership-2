<?php
/**
 * This file defines the MS_Controller_Registration class.
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
 * Creates the controller for Membership/User registration.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Registration extends MS_Controller {

	private $register_errors;
	
	private $allowed_actions = array( 'membership_signup', 'membership_move', 'membership_cancel', 'register_user' );
	/**
	 * Prepare for Member registration.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {

		/**
		 * Actions to execute when the Registration controller construction starts.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Controller_Addon object.
		 */
		do_action( 'membership_registration_controller_construct_pre_processing', $this );
		
		$this->add_filter( 'wp_signup_location', 'signup_location', 999 );
		$this->add_filter( 'register_url', 'signup_location', 999 );
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
		$this->add_filter( 'the_content', 'check_for_membership_pages_content', 1 );
		
		// Make sure that the registration shortcode form includes the nonce
		$this->add_filter( 'ms_controller_shortcode_membership_register_user_atts', 'add_registration_nonce' );

		// $this->add_action( 'the_posts', 'process_actions', 1 );

	}
	
	
	/**
	 * Make sure that the nonce is added to the registration form shortcode.
	 *
	 * **Hooks Actions: **  
	 *  
	 * * ms_controller_shortcode_membership_register_user_atts
	 *
	 * @todo Decide if we will use the nonce from the signup form. If not, then remove the hook (above too).
	 * @since 4.0.0
	 * @param mixed[] $args Argument array for the shortcode.
	 */	
	function add_registration_nonce( $args ) {
		$args['_wpnonce'] = ! empty( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';
	    return $args;
	}

	/**
	 * Handle URI actions for registration.
	 *
	 * Matches returned 'action' to method to execute.
	 *
	 * **Hooks Actions: **  
	 *  
	 * * template_redirect	
	 *
	 * @todo Sanitize and protect from possible random function calls.
	 *
	 * @since 4.0.0
	 */	
	public function process_actions() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		/** 
		 * If $action is set, then call relevant method.
		 * 
		 * Methods:  
		 *
		 * * membership_signup  
		 * * membership_move  
		 * * membership_cancel  
		 * * register_user  
		 *
		 */
		if( ! empty($action) && method_exists( &$this, $action ) && in_array( $action, $this->allowed_actions ) ) {
			// MS_Helper_Debug::log( 'action: ' . $action );		
			$this->$action();
		} 
	}

	/**
	 * Get the URL the user used to register for a subscription.
	 *
	 * Uses the default registration page unless the registration was embedded on another page (e.g. using a shortcode).
	 *
	 * **Hooks Filters: **  
	 *  
	 * * wp_signup_location  
	 * * register_url  
	 *
	 * @since 4.0.0
	 * @param string $url
	 */
	public function signup_location( $url ) {
		if( ! empty( MS_Plugin::instance()->settings->pages[ MS_Model_Settings::SPECIAL_PAGE_REGISTER ] ) ) {
			$url = get_permalink( MS_Plugin::instance()->settings->pages[ MS_Model_Settings::SPECIAL_PAGE_REGISTER ] );
		}
		
		// MS_Helper_Debug::log( __( "signup_location: {$url}", MS_TEXT_DOMAIN ) );
		
		return apply_filters( 'ms_controller_registration_signup_location', $url );
	}

	/**
	 * Check pages/posts for the presence of Membership registration shortcodes.
	 *
	 * When shortcodes are found then the contents for those pages are loaded.
	 *
	 * **Hooks Filters: **  
	 *  
	 * * the_content
	 *
	 * @todo Check for side effects of using  
	 *  
	 *     remove_filter( 'the_content', 'wpautop' );  
	 *  
	 * 
	 * @since 4.0.0
	 * @param string $content The post content.
	 */
	public function check_for_membership_pages_content( $content ) {
		global $post;
	
		$settings = MS_Plugin::instance()->settings;
		
		if ( empty( $post ) || $post->post_type != 'page' ) {
			return $content;
		}
		
		// MS_Helper_Debug::log( 'Check for membership pages content.' );								
		
		// If we are on the registration page....
		if ( $settings->is_special_page( $post->ID, MS_Model_Settings::SPECIAL_PAGE_REGISTER ) ) {
			// MS_Helper_Debug::log( 'We are on the registration page.' );

			// check if page contains 'ms-membership-register-user' shortcode
			if ( ! MS_Helper_Shortcode::has_shortcode( 'ms-membership-register-user', $content ) ) {
				// MS_Helper_Debug::log( 'NOT using "ms-membership-register-user" shortcode.' );
				
				// There is no shortcode content in there, so override
				if( ! empty( $_REQUEST['action'] ) ) {
					// MS_Helper_Debug::log( 'There is "action".' );
					remove_filter( 'the_content', 'wpautop' );
					$membership_id = 0;
					if( ! empty( $_REQUEST['membership'] ) ) {
						$membership_id = $_REQUEST['membership']; 
					}
					$username = ! empty( $_POST['user_login'] ) ? $_POST['user_login'] : '';
					$email = ! empty( $_POST['user_email'] ) ? $_POST['user_email'] : '';
					$first_name = ! empty( $_POST['first_name'] ) ? $_POST['first_name'] : '';
					$last_name = ! empty( $_POST['last_name'] ) ? $_POST['last_name'] : '';
					$_wpnonce = ! empty( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
					
					// MS_Helper_Debug::log( 'Just loaded up user registration fields and adding a shortcode.' );
					$content .= do_shortcode( "[ms-membership-register-user membership_id='$membership_id' email='$email' username='$username' first_name='$first_name' last_name='$last_name' _wpnonce='$_wpnonce' errors='$this->register_errors']" );
				}
				else {
					// MS_Helper_Debug::log( 'There was NO "action", now call the signup shortcode.' );
					remove_filter( 'the_content', 'wpautop' );
					$content .= do_shortcode( '[ms-membership-signup]' );
				}
			}
		}
		// If we are on the accounts page....
		elseif ( $settings->is_special_page( $post->ID, MS_Model_Settings::SPECIAL_PAGE_ACCOUNT ) ) {
			MS_Helper_Debug::log( "We are on the accounts page." );
			// account page - check if page contains a shortcode
			// if ( strpos( $content, '[ms-membership-account]' ) !== false || 
			// 		strpos( $content, '[ms-membership-upgrade]' ) !== false || 
			// 		strpos( $content, '[ms-membership-renew]' ) !== false ) {
			if ( MS_Helper_Shortcode::has_shortcode( 'ms-membership-account', $content ) ||
			     MS_Helper_Shortcode::has_shortcode( 'ms-membership-upgrade', $content ) ||
			     MS_Helper_Shortcode::has_shortcode( 'ms-membership-renew', $content ) ) {	
			
				MS_Helper_Debug::log( "There be shortcodes!" );
				// There is content in there with the shortcode so just return it
				return $content;
			}
			// There is no shortcode in there, so override
			remove_filter( 'the_content', 'wpautop' );
			$content .= do_shortcode( '[ms-membership-account]' );
			MS_Helper_Debug::log( "We are STILL on the accounts page." );			
		} 
		// If we are on the memberships page....
		elseif ( $settings->is_special_page( $post->ID, MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS ) ) {
			// account page - check if page contains a shortcode
			// if ( strpos( $content, '[ms-membership-upgrade]' ) !== false || strpos( $content, '[ms-memberhship-renew]' ) !== false ) {
			if ( MS_Helper_Shortcode::has_shortcode( 'ms-membership-upgrade', $content ) || 
				 MS_Helper_Shortcode::has_shortcode( 'ms-membership-renew', $content ) ) {
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
		elseif ( $settings->is_special_page( $post->ID, MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS ) ) {
			if ( MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_LOGIN, $content ) ) {
				return $content;
			}
			else {
				// There is no shortcode in there, so override
				remove_filter( 'the_content', 'wpautop' );
				$content .= do_shortcode( '['.MS_Helper_Shortcode::SCODE_LOGIN .']' );
			}
		}
		
		return $content;
	}
	
	/**
	 * Handles Membership signup process.
	 *
	 * @todo Consider how this will work when reversing the signup order. Example, pay fist, then create user.
	 * @todo Handle move scenario.
	 *
	 * @since 4.0.0
	 */
	public function membership_signup() {
		// MS_Helper_Debug::log( 'About to signup...' );
		
		if( MS_Model_Member::is_logged_user() && ! empty( $_GET['membership'] ) && MS_Model_Membership::is_valid_membership( $_GET['membership'] ) ) {
			// MS_Helper_Debug::log( '**: User logged in...' );
			$move_from_id = ! empty ( $_GET['move_from'] ) ? $_GET['move_from'] : 0;
			
			// What is this $_POST['membership_signup']?
			if( ! empty( $_POST['membership_signup'] ) && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['gateway'] )
			// if( ! empty( $_POST['membership_id'] ) && ! empty( $_POST['gateway'] )
				&& ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['gateway'] .'_' . $_POST['membership_id'] ) ) {
				// MS_Helper_Debug::log( '**: Gateway stuff... something is odd here...' );
				$gateway_id = $_POST['gateway'];
				$membership_id = $_POST['membership_id'];
				if( ! MS_Model_Membership::is_valid_membership( $membership_id ) ) {
					return;
				}
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
				// MS_Helper_Debug::log( '**: Other stuff...' );
				$membership_id = $_GET['membership'];
				if( ! MS_Model_Membership::is_valid_membership( $membership_id ) ) {
					return;
				}
				
				$membership = MS_Model_Membership::load( $membership_id );
				$member = MS_Model_Member::get_current_member();
				/**
				 * Free gateway.
				 */
				if( $membership->price == 0 && ! empty( $_GET['membership'] )  && ! empty( $_GET['action'] ) 
										&& ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $_GET['action'] ) ) {
					// MS_Helper_Debug::log( '**: Free stuff...' );		
					// MS_Helper_Debug::log( $_GET );
					$gateway_id = 'free_gateway';
					$gateway = MS_Model_Gateway::factory( $gateway_id );
					$gateway->add_transaction( $membership, $member, MS_Model_Transaction::STATUS_PAID );
					$url = get_permalink( MS_Plugin::instance()->settings->pages[ MS_Model_Settings::SPECIAL_PAGE_WELCOME ] );
					wp_safe_redirect( $url );
				}
				/**
				 * Show payment table.
				 */
				else {
					// MS_Helper_Debug::log( '**: Add payment table...' );
					$this->add_action( 'the_content', 'payment_table', 1 );
				}
			}	
		}
	}
	
	/**
	 * Handles register_user POST action.
	 *
	 * @todo Fix using the nonce from the signup form, rather than creating a new nonce (if possible).
	 * @since 4.0.0
	 */
	public function register_user() {
		if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			return;
		}
		
		// MS_Helper_Debug::log ( 'About to register NEW user.' );
		// MS_Helper_Debug::log ( $_POST );

		try {
			$user = new MS_Model_Member();
			$user->password = $_POST['password'];
			$user->password2 = $_POST['password2'];
			$user->username = $_POST['user_login'];
			$user->email =  $_POST['user_email'];		
			$user->save();
			$user->signon_user();
			if ( has_action( 'ms_controller_registration_register_user_notification' ) ) {
				do_action( 'ms_controller_registration_register_user_notification', $user_id, $this->password );
			}
			else {
				wp_new_user_notification( $user->id, $user->password );
			}
			do_action( 'ms_controller_registration_register_user_complete', $this->register_errors, $user->id );
			
			wp_redirect( add_query_arg( array(
				'action'       => 'membership_signup',
				'membership' => $_POST['membership'],
				'_wpnonce' => wp_create_nonce( 'membership_signup' ),
				// '_wpnonce' => $_POST['_wpnonce'],
			) ) );
		}
		catch( Exception $e ) {
			$this->register_errors = $e->getMessage();
			do_action( 'ms_controller_registration_register_user_error', $this->register_errors );
		}		
	}
	
	/**
	 * Handles membership_move action.
	 * 
	 * @todo Handle move scenario in signup_method or implement own function.
	 * 
	 * @since 4.0.0
	 */
	public function membership_move() {
		$this->membership_signup();
	}
	
	/**
	 * Handles membership_cancel action.
	 * 
	 * @since 4.0.0
	 */	
	public function membership_cancel() {
		if( ! empty( $_GET['membership'] )  && ! empty( $_GET['action'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {
			$membership_id = $_GET['membership'];
			$member = MS_Model_Member::get_current_member();
			$member->cancel_membership( $membership_id );
			$member->save();
			wp_safe_redirect( remove_query_arg( array( 'action', '_wpnonce', 'membership' ) ) ) ;
		}
	}

	/**
	 * Render membership payment information.
	 *
	 * @since 4.0.0
	 */	
	public function payment_table() {
		$membership_id = $_GET['membership'];
		$membership = MS_Model_Membership::load( $membership_id );
		$member = MS_Model_Member::get_current_member();
		
		$data['membership'] = $membership;
		$data['gateways'] = MS_Model_Gateway::get_gateways();
		$data['member'] = $member;
		$data['currency'] = MS_Plugin::instance()->settings->currency;

		$coupon_code = ! empty( $_POST['coupon_code'] ) ? $_POST['coupon_code'] : '';
		$coupon = MS_Model_Coupon::load_by_coupon_code( $coupon_code );
		if( ! empty( $_POST['apply_coupon_code'] ) ) {
			$data['coupon_valid'] = $coupon->is_valid_coupon( $membership->id );
			$coupon->apply_coupon( $membership );
		}
		elseif( ! empty( $_POST['remove_coupon_code'] ) ) {
			$coupon->remove_coupon_application( $membership_id );
			$coupon = $coupon->load( 0 );
		}
		$data['coupon'] = $coupon;
		$view = apply_filters( 'ms_view_registration_payment', new MS_View_Registration_Payment() );
		$view->data = $data;
		echo $view->to_html();
	}

	/**
	 * Handle payment gateway returns
	 *
	 * **Hooks Actions: **  
	 *  
	 * * pre_get_posts
	 *	
	 * @todo Review how this works when we use OAuth API's with gateways.
	 * 
	 * @since 4.0.0
	 * @param mixed $wp_query The WordPress query object
	 */	
	public function handle_payment_return( $wp_query ) {
		if( ! empty( $wp_query->query_vars['paymentgateway'] ) ) {
			MS_Model_Gateway::get_gateways();
			do_action( 'ms_model_gateway_handle_payment_return_' . $wp_query->query_vars['paymentgateway'] );
		}
	}
}