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

	private $register_errors;
	
	private $allowed_actions = array( 'membership_signup', 'membership_move', 'membership_renew', 'membership_cancel', 'register_user' );
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
		do_action( 'ms_controller_public_construct_pre_processing', $this );
		
		$this->add_filter( 'wp_signup_location', 'signup_location', 999 );
		$this->add_filter( 'register_url', 'signup_location', 999 );
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
		$this->add_filter( 'the_content', 'check_for_membership_pages_content', 1 );
		
		$this->add_action( 'wp_login', 'propagate_ssl_cookie', 10, 2 );
		
		/** Enqueue styles and scripts used  */
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts');

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
		if( ! empty( $action ) && method_exists( $this, $action ) && in_array( $action, $this->allowed_actions ) ) {
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
		$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_REGISTER ) );
		
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
		
		$is_special_page = $settings->is_special_page( $post->ID );
		switch( $is_special_page ) {
			case MS_Model_Settings::SPECIAL_PAGE_REGISTER:
				/** If no register shortcode in the content, add it. */
				if ( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_REGISTER_USER, $content ) ) {
					
					remove_filter( 'the_content', 'wpautop' );
					$step = $this->get_register_step();
					MS_Helper_Debug::log($step);
					switch( $step ) {
						case 'choose_membership':
							$content .= do_shortcode( '['. MS_Helper_Shortcode::SCODE_SIGNUP .']' );
							break;
						case 'register_form':
							$content .= do_shortcode( "[" . MS_Helper_Shortcode::SCODE_REGISTER_USER . " errors='{$this->register_errors}']" );
							break;
						default:
							MS_Helper_Debug::log( "No handler for step: $step" );
							break;
					}
				}
				break;
			case MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS:
				remove_filter( 'the_content', 'wpautop' );
				if( MS_Model_Member::is_logged_user() ) {
					if( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_SIGNUP, $content ) ) {
						$content .= do_shortcode( '['. MS_Helper_Shortcode::SCODE_SIGNUP .']' );
					}
				}
				else {
					if( ! MS_Helper_Shortcode::has_shortcode( MS_Helper_Shortcode::SCODE_LOGIN, $content ) ) {
						$content .= do_shortcode( '[' . MS_Helper_Shortcode::SCODE_LOGIN . ']' );
					}
				}
				break;
			case MS_Model_Settings::SPECIAL_PAGE_ACCOUNT:
				break;
			case MS_Model_Settings::SPECIAL_PAGE_NO_ACCESS:
				break;
			default:
				break; 
		}
		return $content;
	}
	
	/**
	 * Handles register_user POST action.
	 *
	 * @since 4.0.0
	 */
	public function register_user() {
		if ( 'register_submit' == $this->get_register_step() ) {
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
				$_POST['step'] = 'payment_form';
				$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS ) );
				wp_redirect( add_query_arg( array(
							'action'       => 'membership_signup',
							'membership' => $_POST['membership'],
							'_wpnonce' => wp_create_nonce( 'membership_signup' ),
						), 
						$url
				 ) );
			}
			catch( Exception $e ) {
				$this->register_errors = $e->getMessage();
				/** step back */
				$_POST['step'] = 'register_form';
				
				MS_Helper_Debug::log( $this->register_errors );
				do_action( 'ms_controller_registration_register_user_error', $this->register_errors );
			}
		}
	}
	
	/**
	 * Get register process step (multi step form).
	 *
	 * @since 4.0.0
	 */
	private function get_register_step() {
		$steps = array( 'choose_membership', 'register_form', 'register_submit' );
		if( ! empty( $_POST['step'] ) && in_array( $_POST['step'], $steps ) ) {
			$step = $_POST['step'];
		}
		/** Selected a membership level to signup to, show register form.*/
		elseif( ! empty( $_GET['action'] ) ) {
			$step = 'register_form';
		}
		/** Initial step.*/
		else {
			$step = 'choose_membership';
		}
		return $step;
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
			
			/**
			 * Allow Free gateway verify if is a free membership ( price = 0 ).
			 * Other gateways may hook to ms_model_gateway_handle_payment_return_{$gateway_id} action.
			 */
			$gateway = apply_filters( 'ms_model_gateway_free', MS_Model_Gateway_Free::load() );
			$gateway->process_free_memberships();
			
			switch( $this->get_signup_step() ) {
				/**
				 * Show payment table.
				 */
				case 'payment_table':
					$this->add_action( 'the_content', 'payment_table', 1 );
					break;
				/**
				 * Show gateway extra form.
				 */
				case 'extra_form':
					$this->add_action( 'the_content', 'gateway_form', 1 );
					break;
				/**
				 * Process payment.
				 */
				case 'process_purchase':
					if( ! empty( $_POST['gateway'] ) && MS_Model_Gateway::is_valid_gateway( $_POST['gateway'] ) && ! empty( $_POST['ms_relationship_id'] ) && 
						! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'],  $_POST['gateway'] .'_' . $_POST['ms_relationship_id'] ) ) {
						 
						$ms_relationship = MS_Model_Membership_Relationship::load( $_POST['ms_relationship_id'] );
						
						$gateway_id = $_POST['gateway'];
						$gateway = apply_filters( 'ms_model_gateway', MS_Model_Gateway::factory( $gateway_id ), $gateway_id );
						$gateway->process_purchase( $ms_relationship );
					}
					break;
			}
		}
	}
	
	/**
	 * Get signup process step (multi step form).
	 *
	 * @since 4.0.0
	 */
	private function get_signup_step() {
		$steps = array( 'payment_table', 'extra_form', 'process_purchase' );
		if( ! empty( $_POST['step'] ) && in_array( $_POST['step'], $steps ) ) {
			$step = $_POST['step'];
		}
		else {
			$step = 'payment_table';
		}
		return $step;
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
	
	public function membership_renew() {
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
			
			$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_MEMBERSHIPS ) );
			wp_safe_redirect( $url );
			exit;
		}
	}

	/**
	 * Render membership payment information.
	 *
	 * @since 4.0.0
	 */
	public function payment_table() {
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $_GET['action'] ) ) {
			$membership_id = $_GET['membership'];
			$membership = MS_Model_Membership::load( $membership_id );
			$member = MS_Model_Member::get_current_member();
			$move_from_id = ! empty ( $_GET['move_from'] ) ? $_GET['move_from'] : 0;

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
	}
	
	/**
	 * Handles gateway extra form to commit payments.
	 * 
	 * @since 4.0.0
	 */	
	public function gateway_form() {
		
		$data = array();
		
		if( ! empty( $_POST['gateway'] ) && MS_Model_Gateway::is_valid_gateway( $_POST['gateway'] ) && ! empty( $_POST['ms_relationship_id'] ) ) {
			$data['gateway'] = $_POST['gateway'];
			$data['ms_relationship_id'] = $_POST['ms_relationship_id'];
			$ms_relationship = MS_Model_Membership_Relationship::load( $_POST['ms_relationship_id'] );
			switch( $_POST['gateway'] ) {
				case MS_Model_Gateway::GATEWAY_AUTHORIZE:
					$user_id = get_current_user_id();
					$view = apply_filters( 'ms_view_gateway_authorize', new MS_View_Gateway_Authorize() );
					$gateway = apply_filters( 'ms_model_gateway_authorize', MS_Model_Gateway_Authorize::load() );
					$data['countries'] = $gateway->get_country_codes();
					$data['cim_profiles'] = $gateway->get_cim_profile( $user_id, $ms_relationship->membership_id );
					$data['cim_payment_profile_id'] = $gateway->get_cim_payment_profile_id( $user_id );
					$data['auth_error'] = ! empty( $_POST['auth_error'] ) ? $_POST['auth_error'] : '';
					break;
				default:
					break;
			}
			$view = apply_filters( 'ms_view_gateway_form', $view );
		}
		
		$view->data = apply_filters( 'ms_view_gateway_form_data', $data );
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
			do_action( 'ms_model_gateway_handle_payment_return_' . $wp_query->query_vars['paymentgateway'] );
		}
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
	
	/**
	 * Adds CSS and javascript
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		/**
		 * Extra gateway form.
		 */
		if( 'extra_form' == $this->get_signup_step() ) {
			wp_enqueue_style('jquery-chosen');
			
			wp_enqueue_script('jquery-chosen');
			wp_enqueue_script('jquery-validate');
			wp_enqueue_script( 'ms-view-gateway-authorize',  MS_Plugin::instance()->url. 'app/assets/js/ms-view-gateway-authorize.js', array( 'jquery' ), MS_Plugin::instance()->version );
		}
	}
}