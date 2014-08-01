<?php
/**
 * This file defines the MS_Controller_Gateway class.
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
 * Gateway controller.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Gateway extends MS_Controller {
	
	private $allowed_actions = array( 'update_card', 'purchase_button' );
	
	/**
	 * Prepare the gateway controller.
	 * 
	 * @since 4.0.0
	 */
	public function __construct() {
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		$this->add_action( 'ms_controller_public_signup_gateway_form', 'gateway_form_mgr', 1 );
		$this->add_action( 'ms_controller_public_signup_process_purchase', 'process_purchase', 1 );
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
		$this->add_action( 'ms_view_shortcode_account_card_info', 'card_info' );
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
		if( ! empty( $action ) && method_exists( $this, $action ) && in_array( $action, $this->allowed_actions ) ) {
			$this->$action();
		}
	}
	
	public function card_info( $data = null ) {
		if( ! empty( $data['gateway'] ) && $gateway = $data['gateway'] ) {
			switch( $gateway->id ) {
				case MS_Model_Gateway::GATEWAY_STRIPE:
					$view = new MS_View_Gateway_Stripe_Card();
					$member = MS_Model_Member::get_current_member();
					$data['member'] = $member;
					$data['publishable_key'] = $gateway->get_publishable_key();
					$data['stripe'] = $gateway->get_gateway_profile_info( $member );
					break;
				case MS_Model_Gateway::GATEWAY_AUTHORIZE:
					break;
				default:
					break;
			}
			$view = apply_filters( 'ms_view_gateway_change_card', $view );
			$view->data = apply_filters( 'ms_view_gateway_form_data', $data );
			echo $view->to_html();
		}
	}
	
	public function update_card() {
		if( ! empty( $_POST['gateway_id'] ) && $this->verify_nonce() ) {
			$gateway = MS_Model_Gateway::factory( $_POST['gateway_id'] );
			switch( $gateway->is_valid() ) {
				case MS_Model_Gateway::GATEWAY_STRIPE:
					if( ! empty( $_POST['stripeToken'] ) ) {
						$member = MS_Model_Member::get_current_member();
						$gateway->add_card( $member, $_POST['stripeToken'] );
						wp_safe_redirect( add_query_arg( array( 'msg' => 1 ) ) );
					}
					break;
				case MS_Model_Gateway::GATEWAY_AUTHORIZE:
					break;
				default:
					break;
			}
		}
	}
	
	/**
	 * Set hook to handle gateway extra form to commit payments.
	 *
	 * **Hooks Actions: **
	 * * ms_controller_public_signup_gateway_form
	 *
	 * @since 4.0.0
	 */
	public function gateway_form_mgr() {
		$this->add_filter( 'the_content', 'gateway_form', 1 );
		/** Enqueue styles and scripts used  */
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts');
	}
	
	/**
	 * Handles gateway extra form to commit payments.
	 *
	 * **Hooks Filters: **
	 * * the_content
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
	 * Process purchase using gateway.
	 *
	 * **Hooks Actions: **
	 * * ms_controller_public_signup_process_purchase
	 * 
	 * @since 4.0.0
	 */
	public function process_purchase() {
		$settings = MS_Plugin::instance()->settings;
		
		if( ! empty( $_POST['gateway'] ) && MS_Model_Gateway::is_valid_gateway( $_POST['gateway'] ) && ! empty( $_POST['ms_relationship_id'] ) &&
				$this->verify_nonce( $_POST['gateway'] .'_' . $_POST['ms_relationship_id'] ) ) {
	
			$ms_relationship = MS_Model_Membership_Relationship::load( $_POST['ms_relationship_id'] );
	
			$gateway_id = $_POST['gateway'];
			$gateway = apply_filters( 'ms_model_gateway', MS_Model_Gateway::factory( $gateway_id ), $gateway_id );
			try {
				$invoice = $gateway->process_purchase( $ms_relationship );

				if( MS_Model_Invoice::STATUS_PAID == $invoice->status ) {
					$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) );
					wp_safe_redirect( $url );
					exit;
				}
				else{
					$this->add_action( 'the_content', 'purchase_info_content' );
				}
			} 
			catch ( Exception $e ) {
				if( MS_Model_Gateway::GATEWAY_AUTHORIZE == $gateway_id ) {
					$_POST['auth_error'] = $e->getMessage();
					do_action( 'ms_controller_public_signup_gateway_form', $this );
				}
				$this->add_action( 'the_content', 'purchase_error_content' );
			}
		}
		else {
			$this->add_action( 'the_content', 'purchase_error_content' );
		}
		
		global $wp_query;
		$wp_query->query_vars['page_id'] = $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_SIGNUP );
		$wp_query->query_vars['post_type'] = 'page';
	}
	
	public function purchase_info_content( $content ) {
		$content = apply_filters( 'ms_controller_gateway_purchase_info_content', $content );
		return $content;
	}
	
	public function purchase_error_content( $content ) {
		$content = apply_filters( 'ms_controller_gateway_purchase_error_content', 
				__( 'Sorry, your signup request has failed. Try again.', MS_TEXT_DOMAIN ), $content );
		return $content;
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
	 * Adds CSS and javascript
	 *
	 * @since 4.0.0
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_style('jquery-chosen');
			
		wp_enqueue_script('jquery-chosen');
		wp_enqueue_script('jquery-validate');
		wp_enqueue_script( 'ms-view-gateway-authorize',  MS_Plugin::instance()->url. 'app/assets/js/ms-view-gateway-authorize.js', array( 'jquery' ), MS_Plugin::instance()->version );
	}
	
}