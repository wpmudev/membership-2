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
	
	private $allowed_actions = array( 'join_membership' );
	
	public function __construct() {
		$this->add_action( 'the_content', 'process_actions', 1 );
	}

	public function process_actions( $content ) {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';
		if( in_array( $action, $this->allowed_actions ) && method_exists( &$this, $action ) ) {
			return $this->$action();
		}
		return $content; 
	}
	
	public function join_membership() {
		$data['gateways'] = MS_Model_Gateway::get_gateways();
		$view = apply_filters( 'ms_view_registration_payment', new MS_View_Registration_Payment() );
		$view->data = $data;
		return $view->to_html();
	}
}