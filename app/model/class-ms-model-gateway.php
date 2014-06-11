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
	
	protected $pro_rate = false;
	
	protected $pay_button_url;
	
	protected $upgrade_button_url;
	
	protected $cancel_button_url;
	
	protected static $gateways;
	
	public function after_load() {
		if( $this->active ) {
			$this->add_action( 'ms_view_registration_payment_form', 'purchase_button', 10, 4 );
			$this->add_action( "ms_model_gateway_handle_payment_return_{$this->id}", 'handle_return' );
		}
	}
	
	public static function get_gateways() {
		if( empty( self::$gateways ) ) {
			self::$gateways = array(
				'free_gateway' => MS_Model_Gateway_Free::load(),
				'manual_gateway' => MS_Model_Gateway_Manual::load(),
				'paypal_standard_gateway' => MS_Model_Gateway_Paypal_Standard::load(),
				'paypal_single_gateway' => MS_Model_Gateway_Paypal_Single::load(),
			);
		}
		return apply_filters( 'ms_model_gateway_get_gateways' , self::$gateways );
	}
	
	public static function get_gateway_names() {
		$gateways = self::get_gateways();
		$names = array();
		foreach( $gateways as $gateway ) {
			$names[ $gateway->id ] = $gateway->name;
		}
		return apply_filters( 'ms_model_gateway_get_gateway_names' , $names );
	}
	
	public static function is_valid_gateway( $gateway_id ) {
		return apply_filters( 'ms_model_gateway_is_valid_gateway', array_key_exists( $gateway_id, self::get_gateways() ) );
	}
	
	public static function factory( $gateway_id ) {
		$gateway = null;
		
		if( self::is_valid_gateway( $gateway_id ) ) {
			$gateways = self::get_gateways();
			$gateway = $gateways[ $gateway_id ];
		}
		
		return apply_filters( 'ms_model_gateway_factory', $gateway, $gateway_id );
	}
	
	public function purchase_button( $membership, $member ) {
		
	}
	
	public function handle_return() {
		
	}
	
	/**
	 * Url that fires handle_return of this gateway.
	 * 
	 * @todo Use pretty permalinks structure like /ms-payment-return/{$this->id}
	 * @return string The return url.
	 */
	public function get_return_url() {
		return apply_filters( 'ms_model_gateway_get_return_url', site_url( '?paymentgateway=' . $this->id ), $this->id );
	}
	
	public function build_custom( $user_id, $membership_id, $amount, $move_from_id = 0, $coupon_id = 0 ) {
	
		$custom = array(
				time(),
				$user_id,
				$membership_id,
				$move_from_id,
				$coupon_id,
				md5( 'MEMBERSHIP' . $amount ),
		);
	
		return apply_filters( 'ms_model_gateway_build_custom', implode( ':', $custom ), $custom );
	}
	
	public function add_transaction( $membership, $member, $status, $move_from_id = 0, $coupon_id = 0, $external_id = null, $notes = null ) {
		
		if( ! MS_Model_Membership::is_valid_membership( $membership->id ) ) {
			return;
		}
		
		$transaction = MS_Model_Transaction::create_transaction( $membership, $member, $this->id, $status );
		if( $this->pro_rate && ! empty( $member->membership_relationship[ $move_from_id ] ) ) {
			$pro_rate = $member->membership_relationship[ $move_from_id ]->calulate_pro_rate();
			$transaction->discount = $pro_rate;
			$notes .= sprintf( __( 'Pro rate discount: %s %s. ', MS_TEXT_DOMAIN ), $transaction->currency, $pro_rate );
		}

		if( ! empty( $coupon_id ) ) {
			$coupon = MS_Model_Coupon::load( $coupon_id );
			$discount = $coupon->get_coupon_application( $member->id, $membership->id );
			$coupon->remove_coupon_application( $member->id, $membership->id );
			$coupon->used++;
			$coupon->save();
			$transaction->discount += $discount; 
			$notes .= sprintf( __( 'Coupon %s, discount: %s %s. ', MS_TEXT_DOMAIN ), $coupon->code, $transaction->currency, $discount );
		}
		$transaction->external_id = $external_id;
		$transaction->notes = $notes;
		$transaction->due_date = MS_Helper_Period::current_date();
		$transaction->process_transaction( $status, true );
		$transaction->save();
	}
	
	/**
	 * Validate specific property before set.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'id':
				case 'name':
					break;
				case 'description':
				case 'pay_button_url':
				case 'upggrade_button_url':
				case 'cancel_button_url':
					$this->$property = sanitize_text_field( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}