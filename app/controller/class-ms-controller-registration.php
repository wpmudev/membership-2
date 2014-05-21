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
	
	private $allowed_actions = array( 'membership_signup' );
	
	public function __construct() {
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
	}

	public function process_actions( $content ) {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		if( in_array( $action, $this->allowed_actions ) && method_exists( &$this, $action ) ) {
			return $this->$action();
		}
		return $content; 
	}
	
	public function membership_signup() {
		if( ! empty( $_GET['membership'] ) ) {
			if( ! empty( $_POST['membership_signup'] ) && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['gateway'] )
				&& ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['gateway'] .'_' . $_POST['membership_id'] ) ) {
				
				$gateway_id = $_POST['gateway'];
				$membership_id = $_POST['membership_id'];
				$membership = MS_Model_Membership::load( $membership_id );
				/**
				 * Manual gateway.
				 */
				if( $membership->price > 0 ) {
					$gateway = MS_Model_Gateway::factory( $gateway_id );
					$gateway->handle_return();
				}
			}
			else {
				$membership_id = $_GET['membership'];
				$membership = MS_Model_Membership::load( $membership_id );
				$member = MS_Model_Member::get_current_member();
				/**
				 * Free gateway.
				 */
				if( $membership->price == 0 ) {
					$gateway_id = 'free_gateway';
					$gateway = MS_Model_Gateway::factory( $gateway_id );
					$gateway->process_payment( $membership_id, $member );
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
		else {
			return "Membership not found.";
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
			do_action( 'ms_model_gateway_handle_payment_return_' . $wp_query->query_vars['paymentgateway'] );
		}
	}
}