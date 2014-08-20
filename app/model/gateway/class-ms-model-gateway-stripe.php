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

class MS_Model_Gateway_Stripe extends MS_Model_Gateway {
	
	protected static $CLASS_NAME = __CLASS__;
	
	public static $instance;
	
	protected $id = self::GATEWAY_STRIPE;
	
	protected $name = 'Stripe Gateway';
	
	protected $description = 'Stripe gateway integration';
	
	protected $manual_payment = false;
	
	protected $active;
	
	protected $pro_rate = true;
	
	protected $test_secret_key;
	
	protected $secret_key;
	
	protected $test_publishable_key;
	
	protected $publishable_key;
	
	protected $mode;
	
	/**
	 * Processes purchase action.
	 *
	 * @since 4.0
	 *
	 * @access public
	 */
	public function process_purchase( $ms_relationship ) {

		$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );
		$invoice = $ms_relationship->get_current_invoice();
		
		if( ! empty( $_POST['stripeToken'] ) ) {
			$token = $_POST['stripeToken'];
			$this->load_stripe_lib();
			
			$customer = $this->get_stripe_customer( $member );
			if( empty( $customer ) ) {
				$customer = Stripe_Customer::create( array(
						'card' => $token,
						'email' => $member->email,
				) );
				$this->save_customer_id( $member, $customer->id );
			}
			else {
				$this->add_card( $member, $token );
				$customer->save();
			}
			
			if( 0 == $invoice->total ) {
				$this->process_transaction( $invoice );
			}
			else {
				$charge = Stripe_Charge::create( array(
						'amount' => $invoice->total * 100, // Amount in cents!
						'currency' => strtolower( $invoice->currency ),
						'customer' => $customer->id,
						'description' => $invoice->name,
				) );
				if( true == $charge->paid ) {
					$invoice->external_id = $charge->id;
					$invoice->status = MS_Model_Invoice::STATUS_PAID;
					$invoice->save();
					$this->process_transaction( $invoice );
				}
			}
		}
		else {
			throw new Exception( __( 'Stripe gateway token not found.', MS_TEXT_DOMAIN ) );
		}
		return $invoice;
	}
	
	/**
	 * Request automatic payment to the gateway.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function request_payment( $ms_relationship ) {
	
		$member = $ms_relationship->get_member();
		$invoice = $ms_relationship->get_current_invoice();
	
		if( MS_Model_Invoice::STATUS_PAID != $invoice->status ) { 
			try {
				$this->load_stripe_lib();
				
				$customer = $this->get_stripe_customer( $member );
				if( ! empty( $customer ) ) {
					if( 0 == $invoice->total ) {
						$this->process_transaction( $invoice );
					}
					else {
	
						$charge = Stripe_Charge::create( array(
								'amount' => $invoice->total * 100, // Amount in cents!
								'currency' => strtolower( $invoice->currency ),
								'customer' => $customer->id,
								'description' => $invoice->name,
						) );
						if( true == $charge->paid ) {
							$invoice->external_id = $charge->id;
							$invoice->status = MS_Model_Invoice::STATUS_PAID;
							$invoice->save();
							$this->process_transaction( $invoice );
						}
					}
				}
				else {
					MS_Helper_Debug::log( "Stripe customer is empty for user $member->username" );
				}
			}
			catch( Exception $e ) {
				MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_FAILED, $ms_relationship );
				MS_Helper_Debug::log( $e->getMessage() );
			}
		}
	}
	
	/**
	 * Get Member's Stripe Customer Object.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param MS_Model_Member $member The member.
	 */
	protected function get_stripe_customer( $member ) {
		$customer_id = $this->get_customer_id( $member );
		$customer = null;
		if( ! empty( $customer_id ) ) {
			$customer = Stripe_Customer::retrieve( $customer_id );
		}
		return apply_filters( 'ms_model_gateway_stripe_get_stripe_customer', $customer );
	}
	
	/**
	 * Get Member's Stripe customer_id.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param MS_Model_Member $member The member.
	 */
	protected function get_customer_id( $member ) {
		$customer_id = $member->get_gateway_profile( $this->id, 'customer_id' );
		return apply_filters( 'ms_model_gateway_stripe_get_customer_id', $customer_id );
	}
	
	/**
	 * Save Stripe customer id to user meta.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param MS_Model_Member $member The member.
	 */
	protected function save_customer_id( $member, $customer_id ) {
		$member->set_gateway_profile( $this->id, 'customer_id', $customer_id );
		$member->save();
	}
	
	/**
	 * Save card info to user meta.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param MS_Model_Member $member The member.
	 */
	protected function save_card_info( $member ) {
		$customer = $this->get_stripe_customer( $member );
		$card = $customer->cards->retrieve( $customer->default_card );

		$member->set_gateway_profile( $this->id, 'card_exp', gmdate( "Y-m-t", strtotime( "{$card->exp_year}-{$card->exp_month}-01") ) );
		$member->set_gateway_profile( $this->id, 'card_num', $card->last4 );
		
		$member->save();
	}
	
	/**
	 * Add card info to strip customer profile.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param MS_Model_Member $member The member.
	 * @param strin $token The stripe card token.
	 */
	public function add_card( $member, $token ) {
		$this->load_stripe_lib();

		$customer = $this->get_stripe_customer( $member );
		$card = $customer->cards->create( array( 'card' => $token ) );
		$customer->default_card = $card->id;
		$customer->save();
		$this->save_card_info( $member );
	}

	
	/**
	 * Load Stripe lib.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 */
	protected function load_stripe_lib(){
		require_once MS_Plugin::instance()->dir . '/lib/stripe-php/lib/Stripe.php';
		$secret_key = $this->get_secret_key();
		Stripe::setApiKey( $secret_key );
	}
	
	/**
	 * Get Stripe publishable key.
	 *
	 * @since 4.0.0
	 *
	 */
	public function get_publishable_key() {
		$publishable_key = null;
		if( self::MODE_LIVE == $this->mode ) {
			$publishable_key = $this->publishable_key;
		}
		else {
			$publishable_key = $this->test_publishable_key;
		}
		return $publishable_key;
	}
	
	/**
	 * Get Stripe secret key.
	 *
	 * @since 4.0.0
	 *
	 */
	protected function get_secret_key() {
		$secret_key = null;
		if( self::MODE_LIVE == $this->mode ) {
			$secret_key = $this->secret_key;
		}
		else {
			$secret_key = $this->test_secret_key;
		}
		return $secret_key;
	}
}
