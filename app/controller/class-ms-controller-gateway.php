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
 * Primary Gateway controller.
 *
 * **Note :** This one is not like other admin settings controllers.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Gateway extends MS_Controller {
	
	private $allowed_actions = array( 'change_card', 'purchase_button' );
	
	/**
	 * Prepare the gateway controller.
	 * 
	 * @since 4.0.0
	 */
	public function __construct() {
		$this->add_action( 'template_redirect', 'process_actions', 1 );
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
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		if( ! empty( $action ) && method_exists( $this, $action ) && in_array( $action, $this->allowed_actions ) ) {
			$this->$action();
		}
	}
	
	public function change_card() {

		if( ! empty( $_REQUEST['gateway_id'] ) && $gateway = MS_Model_Gateway::factory( $_REQUEST['gateway_id'] ) ) {
			switch( $gateway->id ) {
				case MS_Model_Gateway::GATEWAY_STRIPE:
					$view = new MS_View_Gateway_Stripe_Card();
					$member = MS_Model_Member::get_current_member();
					$data['member'] = $member;
					$data['publishable_key'] = $gateway->get_publishable_key();
					$data['stripe'] = $gateway->get_gateway_profile_info( $member );
					if( ! empty( $_POST['stripeToken'] ) ) {
						$gateway->add_card( $member, $_POST['stripeToken'] );
						wp_safe_redirect( add_query_arg( array( 'msg' => 1 ) ) );
					}
						
					break;
				case MS_Model_Gateway::GATEWAY_AUTHORIZE:
					break;
				default:
					break;
			}
			$view = apply_filters( 'ms_view_gateway_change_card', $view );
			$view->data = apply_filters( 'ms_view_gateway_form_data', $data );
			add_action( 'the_content', array( &$view, 'to_html' ) );
		}
	}
}