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
	
	protected $id = 'free_gateway';
	
	protected $name = 'Free Gateway';
	
	protected $description = 'Free Memberships';
	
	protected $is_single = true;
	
	public function process_payment( $membership_id, $member ) {
		$member->add_membership( $membership_id, $this->id );
		$transaction = new MS_Model_Transaction();
		$transaction->gateway_id = $this->id;
		$transaction->amount = 0;
		$transaction->status = MS_Model_Transaction::STATUS_PAID;
		$transaction->user_id = $member->id;
		$transaction->name = $this->name . ' transaction';
		$transaction->description = $this->description;
		$transaction->save();
		$member->add_transaction( $transaction->id );
		$member->save();
	}
}
