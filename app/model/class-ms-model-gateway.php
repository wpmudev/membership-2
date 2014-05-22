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

class MS_Model_Gateway extends MS_Model_Option {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id = 'gateway';
	
	protected $name = 'Abstract Gateway';
	
	protected $description = 'Abstract Gateway Desc';
	
	protected $active = false;
	
	protected $is_single = true;
	
	protected $payment_button;
	
	protected static $gateways;
	
	public function after_load() {
		if( $this->active ) {
			$this->add_action( 'ms_view_registration_payment_form', 'purchase_button', 10, 2 );
			$this->add_action( "ms_model_gateway_handle_payment_return_{$this->id}", 'handle_return' );
		}
	}
	
	public static function get_gateways() {
		if( empty( self::$gateways ) ) {
			self::$gateways = array(
				'free_gateway' => MS_Model_Gateway_Free::load(),
				'manual_gateway' => MS_Model_Gateway_Manual::load(),
			);
		}
		return apply_filters( 'ms_model_gateway_get_gateways' , self::$gateways );
	}
	
	public static function factory( $gateway_id ) {
		$gateway = null;
		
		$gateways = self::get_gateways();
		if( array_key_exists( $gateway_id, $gateways ) ) {
			$gateway = $gateways[ $gateway_id ];
		}
		
		return apply_filters( 'ms_model_gateway_factory', $gateway, $gateway_id );
	}
	
	public function purchase_button( $membership, $member ) {
		
	}
	
	public function handle_return() {
		
	}
	
	public function add_transaction( $membership, $member, $status ) {
		
		$transaction = new MS_Model_Transaction();
		$transaction->gateway_id = $this->id;
		$transaction->membership_id = $membership->id;
		$transaction->amount = $membership->price;
		$transaction->status = $status;
		$transaction->user_id = $member->id;
		$transaction->name = $this->name . ' transaction';
		$transaction->description = $this->description;
		$transaction->save();
		
		if( MS_Model_Transaction::STATUS_PAID == $status ) {
			$member->add_membership( $membership->id, $this->id );
		}
		
		$member->add_transaction( $transaction->id );
		$member->save();
	}
	
}