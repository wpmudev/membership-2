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
 * @since 1.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Gateway extends MS_Controller {
	
	const AJAX_ACTION_TOGGLE_GATEWAY = 'toggle_gateway';
	
	const AJAX_ACTION_UPDATE_GATEWAY = 'update_gateway';
	
	private $allowed_actions = array( 'update_card', 'purchase_button', 9 );
	
	/**
	 * Prepare the gateway controller.
	 * 
	 * @since 1.0
	 */
	public function __construct() {
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		
		$this->add_action( 'ms_controller_gateway_settings_render_view', 'gateway_settings_edit' );
		
		$this->add_action( 'ms_view_shortcode_invoice_purchase_button', 'purchase_button' );
		$this->add_action( 'ms_view_frontend_payment_purchase_button', 'purchase_button' );
		$this->add_action( 'ms_controller_frontend_signup_gateway_form', 'gateway_form_mgr', 1 );
		$this->add_action( 'ms_controller_frontend_signup_process_purchase', 'process_purchase', 1 );
		
		$this->add_action( 'ms_view_shortcode_account_card_info', 'card_info' );
		
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
		
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_GATEWAY, 'toggle_ajax_action' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_GATEWAY, 'ajax_action_update_gateway' );
		
		$this->add_action( 'ms_controller_frontend_enqueue_scripts', 'enqueue_scripts');
		
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
	 * @since 1.0
	 */
	public function process_actions() {
		$action = $this->get_action();
		if( ! empty( $action ) && method_exists( $this, $action ) && in_array( $action, $this->allowed_actions ) ) {
			$this->$action();
		}
	}
	
	/**
	 * Handle Ajax toggle action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_toggle_gateway
	 *
	 * @since 1.0
	 */
	public function toggle_ajax_action() {
		$msg = 0;
		
		$fields = array( 'gateway_id' );
		if( $this->verify_nonce() && $this->validate_required( $fields ) && $this->is_admin_user() ) {
			$msg = $this->gateway_list_do_action( 'toggle_activation', array( $_POST['gateway_id'] ) );
		}
		
		echo $msg;
		exit;
	}
	
	/**
	 * Handle Ajax update gateway action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_update_gateway
	 *
	 * @since 1.0
	 */
	public function ajax_action_update_gateway() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
	
		$fields = array( 'action', 'gateway_id', 'field' );
		if( $this->verify_nonce() && $this->validate_required( $fields ) && isset( $_POST['value'] ) && $this->is_admin_user() ) {
			$msg = $this->gateway_list_do_action( $_POST['action'], array( $_POST['gateway_id'] ), array( $_POST['field'] => $_POST['value'] ) );
		}
		echo $msg;
		exit;
	}
	
	/**
	 * Show gateway settings page.
	 *
	 *
	 * **Hooks Actions: **
	 *
	 * * ms_controller_gateway_settings_render_view
	 *
	 * @since 1.0
	 */
	public function gateway_settings_edit( $gateway_id ) {
		if( ! empty( $gateway_id ) && MS_Model_Gateway::is_valid_gateway( $gateway_id ) ) {
			switch( $gateway_id ) {
				case MS_Model_Gateway::GATEWAY_MANUAL:
					$view = MS_Factory::create( 'MS_View_Gateway_Manual_Settings' );
					break;
				case MS_Model_Gateway::GATEWAY_PAYPAL_SINGLE:
				case MS_Model_Gateway::GATEWAY_PAYPAL_STANDARD:
					$view = MS_Factory::create( 'MS_View_Gateway_Paypal_Settings' );
					break;
				case MS_Model_Gateway::GATEWAY_AUTHORIZE:
					$view = MS_Factory::create( 'MS_View_Gateway_Authorize_Settings' );
					break;
				case MS_Model_Gateway::GATEWAY_STRIPE:
					$view = MS_Factory::create( 'MS_View_Gateway_Stripe_Settings' );
					break;
				default:
					$view = MS_Factory::create( 'MS_View_Gateway_Settings' );
					break;
			}
			$data = array();
			$data['model'] = MS_Model_Gateway::factory( $gateway_id );
			$data['action'] = 'edit';

			$view->data = apply_filters( 'ms_view_gateway_settings_edit_data', $data );
			$view = apply_filters( 'ms_view_gateway_settings_edit', $view, $gateway_id ); ;
			$view->render();
		}
	}
	
	/**
	 * Handle Payment Gateway list actions.
	 *
	 * @since 1.0
	 *
	 * @param string $action The action to execute.
	 * @param int[] $gateways The gateways IDs to process.
	 * @param mixed[] $fields The data to process.
	 */
	public function gateway_list_do_action( $action, $gateways, $fields = null ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
	
		foreach( $gateways as $gateway_id ) {
			$gateway = MS_Model_Gateway::factory( $gateway_id );
			switch( $action ) {
				case 'toggle_activation':
					$gateway->active = ! $gateway->active;
					$gateway->save();
					$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
					break;
				case 'edit':
				case 'update_gateway':
					foreach( $fields as $field => $value ) {
						$gateway->$field = $value;
					}
					$gateway->save();
					
					/** $settings->is_global_payments_set is used to hide global payment settings in the membership setup payment step */ 
					if( $gateway->is_configured() ) {
						$settings = MS_Factory::load( 'MS_Model_Settings' );
						$settings->is_global_payments_set = true;
						$settings->save();
					}
					$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
					break;
			}
		}
	
		return $msg;
	}
	
	/**
	 * Show gateway purchase button.
	 *
	 *
	 * **Hooks Actions: **
	 *
	 * * ms_view_frontend_payment_purchase_button
	 *
	 * @since 1.0
	 */
	public function purchase_button( $ms_relationship ) {
		/** Get only active gateways */
		$gateways = MS_Model_Gateway::get_gateways( true );
		
		/** show gateway purchase button for every active gateway */
		foreach( $gateways as $gateway ) {
			$view = null;

			$data['ms_relationship'] = $ms_relationship;
			$data['gateway'] = $gateway;
			$data['step'] = 'process_purchase';
			
			$membership = $ms_relationship->get_membership();
			
			/** Free membership, show only free gateway */
			if( 0 == $membership->price || $membership->is_free ) {
				if( MS_Model_Gateway::GATEWAY_FREE != $gateway->id ) {
					continue;
				}
			}
			/** Skip free gateway */
			elseif( MS_Model_Gateway::GATEWAY_FREE == $gateway->id ) {
				continue;
			}
				
			switch( $gateway->id ) {
				case MS_Model_Gateway::GATEWAY_AUTHORIZE:
					$view = MS_Factory::create( 'MS_View_Gateway_Authorize_Button' );
					/** 
					 *  set additional step for authorize.net (gateway specific form)
					 *  @todo change to use popup, instead of another step (like stripe)
					 */
					$data['step'] = 'gateway_form';
					break;
				case MS_Model_Gateway::GATEWAY_PAYPAL_SINGLE:
					$view = MS_Factory::create( 'MS_View_Gateway_Paypal_Single_Button' );
					break;
				case MS_Model_Gateway::GATEWAY_PAYPAL_STANDARD:
					$view = MS_Factory::create( 'MS_View_Gateway_Paypal_Standard_Button' );
					break;
				case MS_Model_Gateway::GATEWAY_STRIPE:
					$view = MS_Factory::create( 'MS_View_Gateway_Stripe_Button' );
					break;
				case MS_Model_Gateway::GATEWAY_FREE:
				case MS_Model_Gateway::GATEWAY_MANUAL:
				default:
					$view = MS_Factory::create( 'MS_View_Gateway_Button' );
					break;
			}
			if( ! empty( $view ) ) {
				$view = apply_filters( 'ms_view_gateway_button', $view, $gateway->id );
				$view->data = apply_filters( 'ms_view_gateway_button_data', $data, $gateway->id );
				echo $view->to_html();
			}
		}
		
	}
	
	/**
	 * Set hook to handle gateway extra form to commit payments.
	 *
	 * **Hooks Actions: **
	 * * ms_controller_frontend_signup_gateway_form
	 *
	 * @since 1.0
	 */
	public function gateway_form_mgr() {
		$this->add_filter( 'the_content', 'gateway_form', 10 );
		/** Enqueue styles and scripts used  */
		$this->add_action( 'wp_enqueue_scripts', 'enqueue_scripts' );
	}
	
	/**
	 * Handles gateway extra form to commit payments.
	 *
	 * **Hooks Filters: **
	 * * the_content
	 *
	 * @since 1.0
	 */
	public function gateway_form() {
	
		$data = array();
	
		$fields = array( 'gateway', 'ms_relationship_id' );
		if( $this->validate_required( $fields ) && MS_Model_Gateway::is_valid_gateway( $_POST['gateway'] ) ) {
			$data['gateway'] = $_POST['gateway'];
			$data['ms_relationship_id'] = $_POST['ms_relationship_id'];
			$ms_relationship = MS_Factory::load( 'MS_Model_Membership_Relationship', $_POST['ms_relationship_id'] );
			switch( $_POST['gateway'] ) {
				case MS_Model_Gateway::GATEWAY_AUTHORIZE:
					$member = $ms_relationship->get_member();
					$view = MS_Factory::create( 'MS_View_Gateway_Authorize_Form' );
					$gateway = MS_Model_Gateway::factory( MS_Model_Gateway::GATEWAY_AUTHORIZE );
					$data['countries'] = $gateway->get_country_codes();
					
					$data['action'] = $this->get_action();
					/** Only new card option available on update card action.*/
					if( 'update_card' == $this->get_action() ) {
						$data['cim_profiles'] = array();
					}
					/** show existing credit card. */
					else {
						$data['cim_profiles'] = $gateway->get_cim_profile( $member );
					}
						
					$data['cim_payment_profile_id'] = $gateway->get_cim_payment_profile_id( $member );
					$data['auth_error'] = ! empty( $_POST['auth_error'] ) ? $_POST['auth_error'] : '';
					break;
				default:
					break;
			}
			$view = apply_filters( 'ms_view_gateway_form', $view );
			$view->data = apply_filters( 'ms_view_gateway_form_data', $data );
			echo $view->to_html();
		}
	}
	
	/**
	 * Process purchase using gateway.
	 *
	 * **Hooks Actions: **
	 * * ms_controller_frontend_signup_process_purchase
	 * 
	 * @since 1.0
	 */
	public function process_purchase() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$fields = array( 'gateway', 'ms_relationship_id' );	

		if( $this->validate_required( $fields ) && MS_Model_Gateway::is_valid_gateway( $_POST['gateway'] ) &&
				$this->verify_nonce( $_POST['gateway'] .'_' . $_POST['ms_relationship_id'] ) ) {

			$ms_relationship = MS_Factory::load( 'MS_Model_Membership_Relationship', $_POST['ms_relationship_id'] );
	
			$gateway_id = $_POST['gateway'];
			$gateway = MS_Model_Gateway::factory( $gateway_id );
			try {
				$invoice = $gateway->process_purchase( $ms_relationship );

				/** If invoice is successfully paid, redirect to welcome page */
				if( MS_Model_Invoice::STATUS_PAID == $invoice->status ) {
					$url = $settings->get_special_page_url( MS_Model_Settings::SPECIAL_PAGE_WELCOME, false, true );
					$url = add_query_arg( array( 'ms_relationship_id' => $ms_relationship->id ), $url );
					wp_safe_redirect( $url );
					exit;
				}
				/** For manual gateway payments */
				else{
					$this->add_action( 'the_content', 'purchase_info_content' );
				}
			} 
			catch ( Exception $e ) {
				MS_Helper_Debug::log( $e->getMessage() );
				switch( $gateway_id ) {
					case MS_Model_Gateway::GATEWAY_AUTHORIZE:
						$_POST['auth_error'] = $e->getMessage();
						/** call action to step back */
						do_action( 'ms_controller_frontend_signup_gateway_form' );
						break;
					case MS_Model_Gateway::GATEWAY_STRIPE:
						$_POST['error'] = sprintf( __( 'Error: %s', MS_TEXT_DOMAIN ), $e->getMessage() );
						/** Hack to send the error message back to the payment_table. */
						MS_Plugin::instance()->controller->controllers['frontend']->add_action( 'the_content', 'payment_table', 1 );
						break;
					default:
						do_action( 'ms_controller_gateway_form_error', $e );
						$this->add_action( 'the_content', 'purchase_error_content' );
						break; 
				}
			}
		}
		else {
			$this->add_action( 'the_content', 'purchase_error_content' );
		}
		
		/** Hack to show signup page in case of errors*/
		global $wp_query;
		$wp_query->query_vars['page_id'] = $settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_SIGNUP );
		$wp_query->query_vars['post_type'] = 'page';
	}
	
	/**
	 * Show signup page with custom content.
	 * 
	 * This is used by manual gateway (overridden) to show payment info.
	 *
	 * **Hooks Actions: **
	 * * the_content
	 *
	 * @since 1.0
	 */
	public function purchase_info_content( $content ) {
		$content = apply_filters( 'ms_controller_gateway_purchase_info_content', $content );
		return $content;
	}
	
	/**
	 * Show error message in the signup page.
	 * 
	 * **Hooks Actions: **
	 * * the_content
	 *
	 * @since 1.0
	 */
	public function purchase_error_content( $content ) {
		$content = apply_filters( 'ms_controller_gateway_purchase_error_content', 
				__( 'Sorry, your signup request has failed. Try again.', MS_TEXT_DOMAIN ), $content );
		return $content;
	}
	
	/**
	 * Handle payment gateway return IPNs.
	 *
	 * Used by Paypal gateways.
	 * 
	 * **Hooks Actions: **
	 *
	 * * pre_get_posts
	 *
	 * @todo Review how this works when we use OAuth API's with gateways.
	 *
	 * @since 1.0
	 * @param mixed $wp_query The WordPress query object
	 */
	public function handle_payment_return( $wp_query ) {
		if( ! empty( $wp_query->query_vars['paymentgateway'] ) ) {
			do_action( 'ms_model_gateway_handle_payment_return_' . $wp_query->query_vars['paymentgateway'] );
		}
	}
	
	/**
	 * Show gateway credit card information.
	 *
	 * If a card is used, show it in account's page.
	 *
	 * **Hooks Actions: **
	 *
	 * * ms_view_shortcode_account_card_info
	 *
	 * @since 1.0
	 */
	public function card_info( $data = null ) {
		if( ! empty( $data['gateway'] ) && is_array( $data['gateway'] ) ) {
			$gateways = array();
			foreach( $data['gateway'] as $ms_relationship_id => $gateway ) {
				/** avoid duplicates */
				if( ! in_array( $gateway->id, $gateways ) ) {
					$gateways[] = $gateway->id;
				}
				else {
					continue;
				}
				$view = null; 
				switch( $gateway->id ) {
					case MS_Model_Gateway::GATEWAY_STRIPE:
						$member = MS_Model_Member::get_current_member();
						$data['stripe'] = $member->get_gateway_profile( $gateway->id );
						if( empty( $data['stripe']['card_exp'] ) ) {
							continue 2;
						}
						$view = MS_Factory::create( 'MS_View_Gateway_Stripe_Card' );
						$data['member'] = $member;
						$data['publishable_key'] = $gateway->get_publishable_key();
						$data['ms_relationship_id'] = $ms_relationship_id;
						$data['gateway'] = $gateway;
						break;
					case MS_Model_Gateway::GATEWAY_AUTHORIZE:
						$member = MS_Model_Member::get_current_member();
						$data['authorize'] = $member->get_gateway_profile( $gateway->id );
						if( empty( $data['authorize']['card_exp'] ) ) {
							continue 2;
						}
						$view = MS_Factory::create( 'MS_View_Gateway_Authorize_Card' );
						$data['member'] = $member;
						$data['ms_relationship_id'] = $ms_relationship_id;
						$data['gateway'] = $gateway;
						break;
					default:
						break;
				}
				if( ! empty( $view ) ) {
					$view = apply_filters( 'ms_view_gateway_change_card', $view, $gateway->id );
					$view->data = apply_filters( 'ms_view_gateway_change_card_data', $data, $gateway->id );
					echo $view->to_html();
				}
			}
		}
	}
	
	/**
	 * Handle update credit card information in gateway.
	 *
	 * Used to change credit card info in account's page.
	 *
	 * **Hooks Actions: **
	 *
	 * * template_redirect
	 *
	 * @since 1.0
	 */
	public function update_card() {
		if( ! empty( $_POST['gateway'] ) ) {
			$gateway = MS_Model_Gateway::factory( $_POST['gateway'] );
			$member = MS_Model_Member::get_current_member();
			switch( $gateway->id ) {
				case MS_Model_Gateway::GATEWAY_STRIPE:
					if( ! empty( $_POST['stripeToken'] ) && $this->verify_nonce() ) {
						$gateway->add_card( $member, $_POST['stripeToken'] );
						if( ! empty( $_POST['ms_relationship_id'] ) ) {
							$ms_relationship = MS_Factory::load( 'MS_Model_Membership_Relationship', $_POST['ms_relationship_id'] );
							MS_Model_Event::save_event( MS_Model_Event::TYPE_UPDATED_INFO, $ms_relationship );
						}
						wp_safe_redirect( add_query_arg( array( 'msg' => 1 ) ) );
					}
					break;
				case MS_Model_Gateway::GATEWAY_AUTHORIZE:
					if( $this->verify_nonce() ) {
						do_action( 'ms_controller_frontend_signup_gateway_form', $this );
					}
					elseif( ! empty( $_POST['ms_relationship_id'] ) && $this->verify_nonce( $_POST['gateway'] .'_' . $_POST['ms_relationship_id'] ) ) {
						$gateway->update_cim_profile( $member );
						$gateway->save_card_info( $member );
						if( ! empty( $_POST['ms_relationship_id'] ) ) {
							$ms_relationship = MS_Factory::load( 'MS_Model_Membership_Relationship', $_POST['ms_relationship_id'] );
							MS_Model_Event::save_event( MS_Model_Event::TYPE_UPDATED_INFO, $ms_relationship );
						}
						wp_safe_redirect( add_query_arg( array( 'msg' => 1 ) ) );
					}
					break;
				default:
					break;
			}
		}
	}
	
	/**
	 * Adds CSS and javascript
	 *
	 * @since 1.0
	 *
	 * @return void
	 */
	public function enqueue_scripts( $step = null ) {
		
		$url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		$gateway_id = ! empty( $_POST['gateway'] ) ? $_POST['gateway'] : null;
		switch( $step ) {
			case MS_Controller_Frontend::STEP_GATEWAY_FORM:
				if( MS_Model_Gateway::GATEWAY_AUTHORIZE == $gateway_id ) {
					wp_enqueue_style('jquery-chosen');
					
					wp_enqueue_script('jquery-chosen');
					wp_enqueue_script('jquery-validate');
					wp_enqueue_script( 'ms-view-gateway-authorize', $url . 'app/assets/js/ms-view-gateway-authorize.js', array( 'jquery' ), $version );
				}
				break;
			case MS_Controller_Frontend::STEP_PAYMENT_TABLE:
				wp_enqueue_script( 'ms-view-gateway-stripe', $url . 'app/assets/js/ms-view-gateway-stripe.js', array( 'jquery' ), $version );
				break;
		}
	}
	
}