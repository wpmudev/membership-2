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

class MS_Model_Gateway_Free extends MS_Model_Gateway {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected static $instance;
	
	protected $id = self::GATEWAY_FREE;
	
	protected $name = 'Free Gateway';
	
	protected $description = 'Free Memberships';
	
	protected $manual_payment = true;
	
	protected $active = true;
	
	/**
	 * Process free memberships.
	 * 
	 * Verify membership price and add it to the user if is free.
	 * 
	 * @since 4.0.0
	 */
	public function process_free_memberships() {
		
		if( ! empty( $_GET['membership'] )  && ! empty( $_GET['action'] ) &&
			! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $_GET['action'] ) ) {
			
			$membership_id = $_GET['membership'];
			$membership = MS_Model_Membership::load( $membership_id );
			
			if( ! MS_Model_Membership::is_valid_membership( $membership_id ) || $membership->price != 0 ) {
				return;
			}

			$move_from_id = ! empty ( $_GET['move_from'] ) ? $_GET['move_from'] : 0;
			$member = MS_Model_Member::get_current_member();

			$ms_relationship = $member->add_membership( $membership_id, $this->id, $move_from_id );
			$invoice = $ms_relationship->get_previous_invoice();
			$this->process_transaction( $invoice );
			
			$url = get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) );
			wp_safe_redirect( $url );
		}
		
	}
}
