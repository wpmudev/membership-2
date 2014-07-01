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

class MS_Model_Transaction extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_transaction';
	
	protected static $CLASS_NAME = __CLASS__;
	
	const STATUS_BILLED = 'billed';
	
	const STATUS_PAID = 'paid';
	
	const STATUS_FAILED = 'failed';
	
	const STATUS_REVERSED = 'reversed';
	
	const STATUS_REFUNDED = 'refunded';
	
	const STATUS_PENDING = 'pending';
	
	const STATUS_DISPUTE = 'dispute';
	
	const STATUS_DENIED = 'denied';

	/**
	 * External transaction ID.
	 * 
	 * Used to link 3rd party transaction ID to $this->id
	 * @var $external_id
	 */
	protected $external_id;
	
	protected $external_info;
	
	protected $gateway_id;
	
	protected $membership_id;
	
	protected $user_id;
	
	protected $ms_relationship_id;
	
	protected $coupon_id;
	
	protected $currency;
	
	protected $amount;
	
	protected $discount;
	
	protected $status;
	
	protected $due_date;
	
	protected $notes;
		
	protected $invoice_number;
	
	protected $taxable;
	
	protected $tax_rate;
	
	protected $tax_name;
	
	protected $total;
	
	protected $trial_period;
	
	protected $pro_rate;
	
	protected $timestamp;

	/**
	 * Get transaction status.
	 *
	 * Used to verify allowed status.
	 * 
	 * @since 4.0
	 */
	public static function get_status() {
		return apply_filters( 'ms_model_transaction_get_status', array(
				self::STATUS_BILLED => __( 'Billed', MS_TEXT_DOMAIN ),
				self::STATUS_PAID => __( 'Paid', MS_TEXT_DOMAIN ),
				self::STATUS_FAILED => __( 'Failed', MS_TEXT_DOMAIN ),
				self::STATUS_REVERSED => __( 'Reversed', MS_TEXT_DOMAIN ),
				self::STATUS_REFUNDED => __( 'Refunded', MS_TEXT_DOMAIN ),
				self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
				self::STATUS_DISPUTE => __( 'Dispute', MS_TEXT_DOMAIN ),
				self::STATUS_DENIED => __( 'Denied', MS_TEXT_DOMAIN ),
			) 
		);
	}
	
	/**
	 * Get transaction count.
	 *
	 * @since 4.0
	 * @param mixed $args The arguments to select data.
	 */
	public function get_transaction_count( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query( $args );
		
		return $query->found_posts;
		
	}
	
	/**
	 * Get transactions.
	 *
	 * @since 4.0
	 * @param mixed $args The arguments to select data.
	 */
	public static function get_transactions( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'post_status' => 'any',
				'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query( $args );
		
		$items = $query->get_posts();
		
		$transactions = array();
		foreach ( $items as $item ) {
			$transactions[] = self::load( $item->ID );	
		}
		return $transactions;
	}

	/**
	 * Get specific transaction.
	 *
	 * Get transaction of a user and membership.
	 * 
	 * @since 4.0
	 * 
	 * @param int $user_id The user id.
	 * @param int $membership_id The membership id.
	 * @param string $status The status of the transaction.
	 * @return MS_Model_Transaction The found transaction or null if not found.
	 */
	public static function get_transaction( $user_id, $membership_id, $status = null, $invoice_number = null ) {
		$args = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
				'fields' => 'ids',
				'order' => 'DESC',
		);
	
		$args['author'] = $user_id;
		$args['meta_query']['membership_id'] = array(
				'key'     => 'membership_id',
				'value'   => $membership_id,
		);
		if( ! empty( $status ) ) {
			$args['meta_query']['status'] = array(
					'key'     => 'status',
					'value'   => $status,
			);
		}
		if( ! empty( $invoice_number ) ) {
			$args['meta_query']['invoice_number'] = array(
					'key'     => 'invoice_number',
					'value'   => $invoice_number,
			);
		}
		$query = new WP_Query( $args );
	
		$item = $query->get_posts();
	
		$transaction = null;
		if( ! empty( $item[0] ) ) {
			$transaction = self::load( $item[0] );
		}
		return $transaction;
	}
	
	/**
	 * Load transaction using external ID.
	 * 
	 *  @since 4.0
	 *  
	 * @param string $external_id
	 * @param string $gateway_id
	 * @return MS_Model_Transaction, null if not found.
	 */
	public static function load_by_external_id( $external_id, $gateway_id ) {
		$args = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 1,
				'meta_query' => array(
						array(
							'key'     => 'external_id',
							'value'   => $external_id,
						),
						array(
							'key'     => 'gateway_id',
							'value'   => $gateway_id,
						),
				)
		);
		$query = new WP_Query( $args );
		
		$item = $query->get_posts();
		$transaction = null;

		if( ! empty( $item[0] ) ) {
			$transaction = self::load( $item[0]->ID );
		}
		
		return $transaction;
	}
	
	/**
	 * Create new transaction
	 * 
	 * @since 4.0
	 * 
	 * @param MS_Model_Membership $membership
	 * @param MS_Model_Member $member
	 * @param string $status
	 */
	public static function create_transaction( $membership, $member, $gateway_id, $status = self::STATUS_BILLED ) {
	
		if( ! MS_Model_Membership::is_valid_membership( $membership->id ) ) {
			return;
		}
		$tax = MS_Plugin::instance()->settings->tax;
		
		$transaction = apply_filters( 'ms_model_transaction', new self() );
		$transaction->gateway_id = $gateway_id;
		$transaction->membership_id = $membership->id;
		$transaction->currency = MS_Plugin::instance()->settings->currency;
		$transaction->amount = $membership->price;
		$transaction->status = $status;
		$transaction->user_id = $member->id;
		$transaction->name = apply_filters( 'ms_model_transaction_name', sprintf( '%s %s - %s' , __( "Invoice for" ), $membership->name, $member->username ) );
		$transaction->description = apply_filters( 'ms_model_transaction_description', $membership->get_payment_description() );
		$transaction->timestamp = time();
		$transaction->tax_name = $tax['tax_name'];
		$transaction->tax_rate = $tax['tax_rate'];
		$transaction->save();
			
		return $transaction;
	}
	
	public function add_notes( $notes ) {
		$this->notes[] = $notes;
	}
	/**
	 * Returns property associated with the render.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'total':
					$this->total = $this->amount + $this->tax_rate/100 * $this->amount - $this->discount - $this->pro_rate;
					return $this->total; 
					break;
				case 'invoice':
					return $this->id;
					break;
				default:
					return $this->$property;
					break;
			}
		}
	}
	
	/**
	 * Set specific property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'name':
				case 'currency':
				case 'notes':
				case 'tax_name':
					$this->$property = sanitize_text_field( $value );
				case 'notes':
					if( is_array( $value ) ) {
						$this->notes = array_map( 'sanitize_text_field', $value );
					}
					else {
						$this->notes = array( sanitize_text_field( $value ) ); 
					}
					break;
				case 'status':
					if( array_key_exists( $value, self::get_status() ) ) {
						$this->$property = $value;
					}
					break;
				case 'due_date':
					$this->$property = $this->validate_date( $value );
					break;
				case 'taxable':
					$this->$property = $this->validate_bool( $value );
					break;
				case 'amount':
				case 'tax_rate':
				case 'discount':
				case 'pro_rate':
					$this->$property = floatval( $value );
					$this->total = $this->amount + $this->tax_rate/100 * $this->amount - $this->discount;
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}