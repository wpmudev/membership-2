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

class MS_Model_Invoice extends MS_Model_Transaction {
	
	public static $POST_TYPE = 'ms_transaction';
	
	protected static $CLASS_NAME = __CLASS__;
	
	public function get_current_invoice( $ms_relationship ) {
		switch( $ms_relationship->status ) {
			/**
			 * Initial payment.
			 */
			case MS_Model_Membership_Relationship::STATUS_PENDING:
			case MS_Model_Membership_Relationship::STATUS_DEACTIVATED:
			case MS_Model_Membership_Relationship::STATUS_EXPIRED:
				$invoice = self::create_invoice( $ms_relationship, $this->current_invoice_number, true );
				break;
			/**
			 * Renew payment.
			 */
			case MS_Model_Membership_Relationship::STATUS_TRIAL:
			case MS_Model_Membership_Relationship::STATUS_ACTIVE:
			case MS_Model_Membership_Relationship::STATUS_CANCELED:
				$invoice = self::create_invoice( $ms_relationship, $this->current_invoice_number );
				break;
			
		}
		
		return apply_filters( 'ms_model_invoice_get_current_invoice', $invoice );
	}
	
	public function get_next_invoice( $ms_relationship ) {
		$invoice = self::create_invoice( $ms_relationship, $ms_relationship->current_invoice_number + 1 );
		$invoice->discount = 0;
		$invoice->pro_rate = 0;
		$invoice->notes = array();
		return apply_filters( 'ms_model_invoice_get_previous_invoice', $invoice );
	}

	public function get_previous_invoice( $ms_relationship ) {
		$invoice = self::get_invoice( $ms_relationship, $ms_relationship->current_invoice_number - 1, false );
		return apply_filters( 'ms_model_invoice_get_next_invoice', $invoice );
	}
	
	/**
	 * Get invoice for this member membership.
	 *
	 * @since 4.0
	 * @param string $status The invoice status to search.
	 * @return MS_Model_Transaction The invoice if found, and null otherwise.
	 */
	public function get_invoice( $ms_relationship, $invoice_number = false, $status = MS_Model_Transaction::STATUS_BILLED ) {
		return apply_filters( 'ms_model_membership_relationship_get_invoice', MS_Model_Transaction::get_transaction(
				$ms_relationship->user_id,
				$ms_relationship->membership_id,
				$status,
				$invoice_number
		) );
	}
	
	/**
	 * Create invoice.
	 *
	 * Create a new invoice using the membership information.
	 *
	 * @since 4.0
	 * @param optional int $is_trial_period For trial period.
	 * @param optional int $update_existing Update an existing invoice instead of creating a new one.
	 */
	public static function create_invoice( $ms_relationship, $invoice_number = false, $trial_period = false, $reuse_existing = true ) {
	
		$invoice = null;
		if( $gateway = $ms_relationship->get_gateway() ) {
			$membership = $ms_relationship->get_membership();
			$member = MS_Model_Member::load( $ms_relationship->user_id );
			$invoice_status = MS_Model_Transaction::STATUS_BILLED;
			$notes = null;
			$due_date = null;
				
			switch( $ms_relationship->status ) {
				default:
				case MS_Model_Membership_Relationship::STATUS_PENDING:
					/** trial period */
					if( $membership->trial_period_enabled && $trial_period ) {
						$due_date = MS_Helper_Period::current_date();
					}
					else {
						$due_date = $ms_relationship->trial_expire_date;
					}
					break;
				case MS_Model_Membership_Relationship::STATUS_DEACTIVATED:
				case MS_Model_Membership_Relationship::STATUS_EXPIRED:
					$due_date = MS_Helper_Period::current_date();
					break;
				case MS_Model_Membership_Relationship::STATUS_TRIAL:
				case MS_Model_Membership_Relationship::STATUS_ACTIVE:
				case MS_Model_Membership_Relationship::STATUS_CANCELED:
					$due_date = $ms_relationship->expire_date;
					break;
			}
	
			$invoice = self::get_invoice( $ms_relationship, $invoice_number );
			if( ! $reuse_existing || empty( $invoice ) ) {
				$invoice = MS_Model_Transaction::create_transaction( $ms_relationship );
			}
			/** Update invoice info.*/
			$invoice->invoice_number = $invoice_number;
			$invoice->discount = 0;
			if( ! empty ( $ms_relationship->move_from_id ) && ! MS_Plugin::instance()->addon->multiple_membership && ! empty( $gateway ) && $gateway->pro_rate ) {
				$move_from = MS_Model_Membership_Relationship::load( $ms_relationship->move_from_id );
					
				if( $move_from->id > 0 && $pro_rate = $move_from->calculate_pro_rate() ) {
					$invoice->pro_rate = $pro_rate;
					$notes[] = sprintf( __( 'Pro rate discount: %s %s. ', MS_TEXT_DOMAIN ), $invoice->currency, $pro_rate );
				}
			}
			if( $coupon = MS_Model_Coupon::get_coupon_application( $member->id, $membership->id ) ) {
				$invoice->coupon_id = $coupon->id;
				$discount = $coupon->get_discount_value( $membership );
				$invoice->discount = $discount;
				$notes[] = sprintf( __( 'Coupon %s, discount: %s %s. ', MS_TEXT_DOMAIN ), $coupon->code, $invoice->currency, $discount );
			}
			$invoice->notes = $notes;
			$invoice->due_date = $due_date;
				
			/** Check for trial period in the first period. */
			if( $membership->trial_period_enabled && $trial_period ) {
				$invoice->amount = $membership->trial_price;
				$invoice->trial_period = true;
			}
			else {
				$invoice->amount = $membership->price;
				$invoice->trial_period = false;
			}
			
			if( 0 == $invoice->total ) {
				$invoice->status = self::STATUS_PAID;
			}
			$invoice->ms_relationship_id = $ms_relationship->id;
			$invoice->save();
		}
	
		return apply_filters( 'ms_model_membership_relationship_create_invoice_object', $invoice );
	
	}
}