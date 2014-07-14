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
	
	const STRIPE_PROFILE_ID_USER_META = 'ms_stripe_profile_id';
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected $id = self::GATEWAY_STRIPE;
	
	protected $name = 'Stripe Gateway';
	
	protected $description = 'Stripe gateway integration';
	
	protected $manual_payment = false;
	
	protected $active = true;
	
	protected $pro_rate = true;
	
	protected $test_secret_key;
	
	protected $secret_key;
	
	protected $test_publishable_key;
	
	protected $publishable_key;
	
	protected $mode;
	
	public function purchase_button( $ms_relationship ) {
		$fields = array(
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->id,
				),
				'ms_relationship_id' => array(
						'id' => 'ms_relationship_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $ms_relationship->id,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'process_purchase',
				),
		);
		$invoice = $ms_relationship->get_current_invoice();
		$member = MS_Model_Member::get_current_member();
		
		$publishable_key = null;
		if( self::MODE_LIVE == $this->mode ) {
			$publishable_key = $this->publishable_key;
		}
		else {
			$publishable_key = $this->test_publishable_key;
		}
		
		?>
			<form action="" method="post">
				<?php wp_nonce_field( "{$this->id}_{$ms_relationship->id}" ); ?>
				<?php 
					foreach( $fields as $field ) {
						MS_Helper_Html::html_input( $field ); 
					}
				?>
				<script
				    src="https://checkout.stripe.com/checkout.js" class="stripe-button"
				    data-key="<?php echo $publishable_key; ?>"
				    data-amount="<?php echo $invoice->total * 100; //amount in cents ?>"
				    data-name="<?php echo bloginfo( 'name' ); ?>"
				    data-description="<?php echo $invoice->description; ?>"
				    data-currency="<?php echo $invoice->currency; ?>"
				    data-panel-label="<?php echo $this->pay_button_url; ?>"
				    data-email="<?php echo $member->email; ?>"
				    >
			  	</script>
			</form>
		<?php
	}

	/**
	 * Processes purchase action.
	 *
	 * @since 4.0
	 *
	 * @access public
	 */
	public function process_purchase( $ms_relationship ) {
	
		$member = MS_Model_Member::load( $ms_relationship->user_id );
		$invoice = $ms_relationship->get_current_invoice();
		
		if( ! empty( $_POST['stripeToken'] ) ) {
			$token = $_POST['stripeToken'];
			try {
				$this->load_stripe_lib();
				
				$customer = self::get_stripe_customer( $member->id );
				if( empty( $customer ) ) {
					$customer = Stripe_Customer::create( array(
							'card' => $token,
							'email' => $member->email,
					) );
					MS_Helper_Debug::log( $customer );
					self::save_profile_id( $member->id, $customer->id );
				}
				else {
					$customer->card = $token;
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
					MS_Helper_Debug::log( $charge );
					if( true == $charge->paid ) {
						$invoice->external_id = $charge->id;
						$invoice->status = MS_Model_Invoice::STATUS_PAID;
						$invoice->save();
						$this->process_transaction( $invoice );
					}
				}
			}
			catch( Exeption $e ) {
			    MS_Helper_Debug::log( $e->getMessage() );
			    $_POST['stripe_error'] = $e->getMessage();
			    MS_Plugin::instance()->controller->controllers['registration']->add_action( 'the_content', 'payment_table', 1 );
			} 
		}
		else {
			MS_Helper_Debug::log( $e->getMessage() );
			/** Hack to send the error message back to the gateway_form. */
			$_POST['stripe_error'] = $e->getMessage();
			MS_Plugin::instance()->controller->controllers['registration']->add_action( 'the_content', 'payment_table', 1 );
		}				
	}
	
	public function request_payment( $ms_relationship ) {
	
		$member = MS_Model_Member::load( $ms_relationship->user_id );
		$invoice = $ms_relationship->get_current_invoice();
	
		if( MS_Model_Invoice::STATUS_PAID != $invoice->status && ! $this->manual_payment ) { 
			try {
				$this->load_stripe_lib();
				
				$customer = self::get_stripe_customer( $member->id );
				if( ! empty( $customer ) ) {
					$charge = Stripe_Charge::create( array(
							'amount' => $invoice->total * 100, // Amount in cents!
							'currency' => strtolower( $invoice->currency ),
							'customer' => $customer->id,
							'description' => $invoice->name,
					) );
					MS_Helper_Debug::log( $charge );
					if( true == $charge->paid ) {
						$invoice->external_id = $charge->id;
						$invoice->status = MS_Model_Invoice::STATUS_PAID;
						$invoice->save();
						$this->process_transaction( $invoice );
					}
				}
				else {
					MS_Helper_Debug::log( "Stripe customer is empty for user $member->username" );
				}
	
			}
			catch( Exception $e ) {
				MS_Helper_Debug::log( $e->getMessage() );
			}
		}
	}
	
	public static function get_stripe_customer( $user_id ) {
		$profile_id = self::get_profile_id( $user_id );
		$customer = null;
		if( ! empty( $profile_id ) ) {
			$customer = Stripe_Customer::retrieve( $profile_id );
		}
		return apply_filters( 'ms_model_gateway_stripe_get_stripe_customer', $customer );
	}
	
	public static function get_profile_id( $user_id ) {
		return apply_filters( 'ms_model_gateway_stripe_get_profile_id', get_user_meta( $user_id, self::STRIPE_PROFILE_ID_USER_META, true ) );
	}
	
	/**
	 * Save Stripe profile id to user meta.
	 *
	 * @since 4.0.0
	 *
	 * @access protected
	 * @param int $user_id The user Id.
	 */
	protected function save_profile_id( $user_id, $profile_id ) {
		update_user_meta( $user_id, self::STRIPE_PROFILE_ID_USER_META, $profile_id );
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
		$secret_key = null;
		if( self::MODE_LIVE == $this->mode ) {
			$secret_key = $this->secret_key;
		}
		else {
			$secret_key = $this->test_secret_key;
		}
		Stripe::setApiKey( $secret_key );
	}
}
