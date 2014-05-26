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
	
	/**
	 * Prepare for Member registration.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		$this->add_filter( 'wp_signup_location', 'signup_location', 999 );
		$this->add_filter( 'register_url', 'signup_location', 999 );
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
		$this->add_filter( 'the_content', 'check_for_membership_pages_content', 1 );
// 		$this->add_action( 'the_posts', 'process_actions', 1 );
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
		if( method_exists( &$this, $action ) ) {
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
		
		if ( $settings->is_special_page( $post->ID, MS_Model_Settings::SPECIAL_PAGE_REGISTER ) ) {
		
			// check if page contains a shortcode
			if ( strpos( $content, '[ms-membership-register-user]' ) === false ) {
				// There is no shortcode content in there, so override
				if( ! empty( $_REQUEST['action'] ) ) {
					remove_filter( 'the_content', 'wpautop' );
					$membership_id = 0;
					if( ! empty( $_REQUEST['membership'] ) ) {
						$membership_id = $_REQUEST['membership']; 
					}
					$username = ! empty( $_POST['user_login'] ) ? $_POST['user_login'] : '';
					$email = ! empty( $_POST['user_email'] ) ? $_POST['user_email'] : '';
					$first_name = ! empty( $_POST['first_name'] ) ? $_POST['first_name'] : '';
					$last_name = ! empty( $_POST['last_name'] ) ? $_POST['last_name'] : '';
					
					$content .= do_shortcode( "[ms-membership-register-user membership_id='$membership_id' email='$email' username='$username' first_name='$first_name' last_name='$last_name' errors='$this->register_errors']" );
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
	
	/**
	 * Handles Membership signup process.
	 *
	 * @todo Consider how this will work when reversing the signup order. Example, pay fist, then create user.
	 * @todo Handle move scenario.
	 *
	 * @since 4.0.0
	 */
	public function membership_signup() {
		if( MS_Model_Member::is_logged_user() && ! empty( $_GET['membership'] ) && MS_Model_Membership::is_valid_membership( $_GET['membership'] ) ) {
			$move_from_id = ! empty ( $_GET['move_from'] ) ? $_GET['move_from'] : 0;
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
	
	/**
	 * Handles register_user POST action.
	 *
	 * @since 4.0.0
	 */
	public function register_user() {
		if ( $_SERVER['REQUEST_METHOD'] != 'POST' ) {
			return;
		}

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
			$member->drop_membership( $membership_id );
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