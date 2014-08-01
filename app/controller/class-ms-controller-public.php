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
class MS_Controller_Public extends MS_Controller {

	const STEP_CHOOSE_MEMBERSHIP = 'choose_membership';
	const STEP_REGISTER_FORM = 'register_form';
	const STEP_REGISTER_SUBMIT = 'register_submit';
	const STEP_PAYMENT_TABLE = 'payment_table';
	const STEP_GATEWAY_FORM = 'gateway_form';
	const STEP_PROCESS_PURCHASE = 'process_purchase';
	
	private $register_errors;
	
	private $allowed_actions = array( 'signup_process', 'register_user' );
	
	/**
	 * Prepare for Member registration.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {

		do_action( 'ms_controller_public_construct', $this );
		
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		$this->add_filter( 'template_redirect', 'check_for_membership_pages', 1 );
		
		$this->add_filter( 'wp_signup_location', 'signup_location', 999 );
		$this->add_filter( 'register_url', 'signup_location', 999 );
		$this->add_action( 'wp_login', 'propagate_ssl_cookie', 10, 2 );
		
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
	 * @since 4.0.0
	 */	
	public function process_actions() {
		
		$action = $this->get_action();
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
		if( ! empty( $action ) && method_exists( $this, $action ) && in_array( $action, $this->allowed_actions ) ) {
			$this->$action();
		} 
	}
	
	/**
	 * Get action from request.
	 * 
	 * @return string
	 */
	private function get_action() {
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		return apply_filters( 'ms_controller_public_get_action', $action );
	}
	/**
	 * Check pages for the presence of Membership special pages.
	 *
	 * **Hooks Filters: **  
	 *  
	 * * template_redirect
	 *
	 * @since 4.0.0
	 */
	public function check_for_membership_pages() {
		$settings = MS_Model_Settings::load();
		$is_special_page = $settings->is_special_page();
		
		switch( $is_special_page ) {
			case MS_Model_Settings::SPECIAL_PAGE_SIGNUP:
				if( MS_Helper_Membership::MEMBERSHIP_ACTION_CANCEL == $this->get_action() ) {
					$this->membership_cancel();
				}
				else {
					$this->signup_process();
				}
				break;
			case MS_Model_Settings::SPECIAL_PAGE_ACCOUNT:
				$this->add_filter( 'the_content', 'user_account', 1 );
				break;
			case MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS:
				$this->add_filter( 'the_content', 'protected_page', 1 );
				break;
			default:
				break; 
		}
	}
	
	/**
	 * Handle entire signup process.
	 * 
	 * @since 4.0.0
	 */
	public function signup_process() {
		$step = $this->get_signup_step();
		MS_Helper_Debug::log("step:: $step");
		
		switch( $step ) {
			/**
			 * Initial state.
			 */
			case self::STEP_CHOOSE_MEMBERSHIP:
				$this->add_filter( 'the_content', self::STEP_CHOOSE_MEMBERSHIP, 1 );
				break;
			/**
			 * If not registered.
			 */
			case self::STEP_REGISTER_FORM:
				$this->add_filter( 'the_content', self::STEP_REGISTER_FORM, 1 );
				break;
			/**
			 * Process user registration.
			 */
			case self::STEP_REGISTER_SUBMIT:
				$this->register_user();
				break;
			/**
			 * Show payment table.
			 */
			case self::STEP_PAYMENT_TABLE:
				$this->add_filter( 'the_content', self::STEP_PAYMENT_TABLE, 1 );
				break;
			/**
			 * Show gateway extra form.
			 * Handled by MS_Controller_Gateway.
			 */
			case self::STEP_GATEWAY_FORM:
				do_action( 'ms_controller_public_signup_gateway_form', $this );
				break;
			/**
			 * Process the purchase action.
			 * Handled by MS_Controller_Gateway.
			 */	
			case self::STEP_PROCESS_PURCHASE:
				do_action( 'ms_controller_public_signup_process_purchase', $this );
				break;
			default:
				MS_Helper_Debug::log( "No handler for step: $step" );
				break;
		}
	}
	
	/**
	 * Get signup process step (multi step form).
	 *
	 * @since 4.0.0
	 */
	private function get_signup_step() {
		$steps = array( 
				 self::STEP_CHOOSE_MEMBERSHIP,
				 self::STEP_REGISTER_FORM,
				 self::STEP_REGISTER_SUBMIT,
				 self::STEP_PAYMENT_TABLE,
				 self::STEP_GATEWAY_FORM,
				 self::STEP_PROCESS_PURCHASE,
		);
		
		if( ! empty( $_POST['step'] ) && in_array( $_POST['step'], $steps ) ) {
			$step = $_POST['step'];
		}
		/** Initial step */
		else {
			$step = self::STEP_CHOOSE_MEMBERSHIP;
		}
		
		if( self::STEP_PAYMENT_TABLE == $step ) {
			if( ! MS_Model_Member::is_logged_user() ) {
				$step = self::STEP_REGISTER_FORM;
			}
			if( empty( $_REQUEST['membership_id'] ) || ! MS_Model_Membership::is_valid_membership( $_REQUEST['membership_id'] ) ) {
				$step = self::STEP_CHOOSE_MEMBERSHIP;
			}
		}
		
		if( self::STEP_CHOOSE_MEMBERSHIP == $step && ! empty( $_GET['membership_id'] ) ) {
			$step = self::STEP_PAYMENT_TABLE;
		}
		return $step;
	}
	
	/**
	 * Show choose membership form.
	 * 
	 * Search for signup shortcode, injecting if not found.
	 * 
	 * **Hooks Filters: **  
	 * * the_content
	 * 
	 * @since 4.0.0
	 * 
	 * @param string $content
	 * @return string
	 */
	public function choose_membership( $content ) {
		remove_filter( 'the_content', 'wpautop' );
		
		if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_SIGNUP, $content ) ) {
			$content .= do_shortcode( '['. MS_Helper_Shortcode::SCODE_SIGNUP .']' );
		}
		
		return $content;
	}
	
	/**
	 * Show register user form.
	 * 
	 * Search for register user shortcode, injecting if not found.
	 * 
	 * **Hooks Filters: **  
	 * * the_content
	 * 
	 * @since 4.0.0
	 * @param string $content
	 * @return string
	 */
	public function register_form( $content ) {
		remove_filter( 'the_content', 'wpautop' );
		
		if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_REGISTER_USER, $content ) ) {
			$content .= do_shortcode( "[" . MS_Helper_Shortcode::SCODE_REGISTER_USER . " errors='{$this->register_errors}']" );
		}

		return $content;
	}
	
	/**
	 * Handles register user submit.
	 * 
	 * On validation errors, step back to register form.
	 *
	 * @since 4.0.0
	 */
	public function register_user() {
		if( ! $this->verify_nonce() ) {
			MS_Helper_Debug::log( "nonce not verified" );
			return;
		}
		try {
			$user = new MS_Model_Member();
			foreach( $_POST as $field => $value ) {
				$user->$field = $value;
			}
			$user->save();
			$user->signon_user();
			if ( ! MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_REGISTERED, $user ) ) {
				wp_new_user_notification( $user->id, $user->password );
			}
			do_action( 'ms_controller_registration_register_user_complete', $user );

			/** Go to membership signup payment form. */
			$this->add_action( 'the_content', self::STEP_PAYMENT_TABLE, 1 );
		}
		catch( Exception $e ) {
			$this->register_errors = $e->getMessage();
			MS_Helper_Debug::log( $this->register_errors );
			
			/** step back */
			$this->add_action( 'the_content', self::STEP_REGISTER_FORM, 1 );
			do_action( 'ms_controller_registration_register_user_error', $this->register_errors );
		}
	}
	
	/**
	 * Render membership payment information.
	 *
	 * **Hooks Filters: **  
	 * * the_content
	 * 
	 * @since 4.0.0
	 */
	public function payment_table() {

		$membership_id = $_REQUEST['membership_id'];
		$membership = MS_Model_Membership::load( $membership_id );
		$member = MS_Model_Member::get_current_member();
		$move_from_id = ! empty ( $_REQUEST['move_from_id'] ) ? $_REQUEST['move_from_id'] : 0;

		if( ! empty( $_POST['coupon_code'] ) ) {
			$coupon = MS_Model_Coupon::load_by_coupon_code( $_POST['coupon_code'] );
			if( ! empty( $_POST['remove_coupon_code'] ) ) {
				$coupon->remove_coupon_application( $member->id, $membership_id );
				$coupon = new MS_Model_Coupon();
			}
			elseif( ! empty( $_POST['apply_coupon_code'] ) ) {
				if( $coupon->is_valid_coupon() ) {
					$coupon->save_coupon_application( $membership );
					$data['coupon_valid'] = true;
				}
				else {
					$data['coupon_valid'] = false;
				}
			}
		}
		else {
			$coupon = new MS_Model_Coupon();
		}
			
		$ms_relationship = MS_Model_Membership_Relationship::create_ms_relationship( $membership_id, $member->id, '', $move_from_id );

		$data['coupon'] = $coupon;
		$invoice = $ms_relationship->get_current_invoice();
		$data['invoice'] = $invoice;
		if( $invoice->coupon_id ) {
			$data['coupon'] = MS_Model_Coupon::load( $invoice->coupon_id );
		}
			
		$data['membership'] = $membership;
		$data['member'] = $member;
		$data['ms_relationship'] = $ms_relationship;
			
		$view = apply_filters( 'ms_view_registration_payment', new MS_View_Registration_Payment() );
		$view->data = $data;

		echo $view->to_html();

	}
	
	/**
	 * Handles membership_cancel action.
	 *
	 * @since 4.0.0
	 */
	public function membership_cancel() {
		if( ! empty( $_POST['membership_id'] ) && $this->verify_nonce() ) {
			$membership_id = $_POST['membership_id'];
			$member = MS_Model_Member::get_current_member();
			$member->cancel_membership( $membership_id );
			$member->save();
				
			$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_SIGNUP ) );
			wp_safe_redirect( $url );
			exit;
		}
	}
	
	/**
	 * Show user account page.
	 *
	 * Search for account shortcode, injecting if not found.
	 *
	 * **Hooks Filters: **
	 * * the_content
	 *
	 * @since 4.0.0
	 *
	 * @param string $content
	 * @return string
	 */
	public function user_account( $content ) {
		remove_filter( 'the_content', 'wpautop' );
		
		if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_MS_ACCOUNT, $content ) ) {
			$content .= do_shortcode( '['. MS_Helper_Shortcode::SCODE_MS_ACCOUNT .']' );
		}
	
		return $content;
	}
	
	/**
	 * Show protected page.
	 *
	 * Search for login shortcode, injecting if not found.
	 *
	 * **Hooks Filters: **
	 * * the_content
	 *
	 * @since 4.0.0
	 *
	 * @param string $content
	 * @return string
	 */
	public function protected_page( $content ) {
		if( ! empty( MS_Plugin::instance()->settings->protection_message['content'] ) ) {
			$content .= MS_Plugin::instance()->settings->protection_message['content'];
		}

		if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_LOGIN, $content ) ) {
			$content .= do_shortcode( '['.MS_Helper_Shortcode::SCODE_LOGIN .']' );
		}
			
		return $content;
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
		$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_SIGNUP ) );
	
		return apply_filters( 'ms_controller_registration_signup_location', $url );
	}
	
	/**
	 * Propagates SSL cookies when user logs in.
	 *
	 * @since 4.0.0
	 * @action wp_login 10 2
	 *
	 * @access public
	 * @param type $login
	 * @param WP_User $user
	 */
	public function propagate_ssl_cookie( $login, WP_User $user ) {
		if ( ! is_ssl() ) {
			wp_set_auth_cookie( $user->ID, true, true );
		}
	}
	
}