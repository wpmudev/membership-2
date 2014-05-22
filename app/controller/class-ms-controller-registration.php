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
	
	private $allowed_actions = array( 
			MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_SIGNUP,
			MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_MOVE,
			MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_CANCEL,
	);
	
	public function __construct() {
		$this->add_action( 'template_redirect', 'process_actions', 1 );
		$this->add_action( 'pre_get_posts', 'handle_payment_return', 1 );
	}

	public function process_actions() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		if( in_array( $action, $this->allowed_actions ) && method_exists( &$this, $action ) ) {
			$this->$action();
		} 
	}
	
	public function membership_signup() {
		if( ! empty( $_GET['membership'] ) && MS_Model_Membership::is_valid_membership( $_GET['membership'] ) ) {
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