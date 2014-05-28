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
	
	const STATUS_CANCELED = 'canceled';
	
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
	
	protected $gateway_id;
	
	protected $membership_id;
	
	protected $amount;
	
	protected $currency;
	
	protected $status;
	
	protected $due_date;
	
	protected $notes;
	
	protected $expire_date;
	
	protected $invoice;
	
	protected $taxable;
	
	protected $tax_rate;
	
	protected $tax_name;
	
	protected $total;
	
	protected $timestamp;

	public static function get_status() {
		return apply_filters( 'ms_model_transaction_get_status', array(
				self::STATUS_BILLED => __( 'Billed', MS_TEXT_DOMAIN ),
				self::STATUS_PAID => __( 'Paid', MS_TEXT_DOMAIN ),
				self::STATUS_CANCELED => __( 'Canceled', MS_TEXT_DOMAIN ),
				self::STATUS_FAILED => __( 'Failed', MS_TEXT_DOMAIN ),
				self::STATUS_REVERSED => __( 'Reversed', MS_TEXT_DOMAIN ),
				self::STATUS_REFUNDED => __( 'Refunded', MS_TEXT_DOMAIN ),
				self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
				self::STATUS_DISPUTE => __( 'Dispute', MS_TEXT_DOMAIN ),
				self::STATUS_DENIED => __( 'Denied', MS_TEXT_DOMAIN ),
			) 
		);
	}
	
	public function get_transaction_count( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query($args);
		return $query->found_posts;
		
	}
	
	public static function get_transactions( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$transactions = array();
		foreach ( $items as $item ) {
			$transactions[] = self::load( $item->ID );	
		}
		return $transactions;
	}
	
	/**
	 * Load transaction using external ID.
	 *  
	 * @param string $external_id
	 * @return MS_Model_Transaction, null if not found.
	 */
	public static function load_by_external_id( $external_id ) {
		$args = array(
				'meta_query' => array(
						array(
							'key'     => 'external_id',
							'value'   => $external_id,
						),
				)
		);
		$query = new WP_User_Query( $args );
		
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
	 * @param MS_Model_Membership $membership
	 * @param MS_Model_Member $member
	 * @param string $status
	 */
	public static function create_transaction( $membership, $member, $gateway_id, $status = self::STATUS_BILLED ) {
	
		$transaction = new self();
		$transaction->gateway_id = $gateway_id;
		$transaction->membership_id = $membership->id;
		$transaction->amount = $membership->price;
		$transaction->status = $status;
		$transaction->user_id = $member->id;
		$transaction->name = $gateway_id . ' transaction';
		$transaction->description = $gateway_id;
		$transaction->timestamp = time();
		$tax = MS_Plugin::instance()->settings->tax;
		$transaction->tax_name = $tax['tax_name'];
		$transaction->tax_rate = $tax['tax_rate'];
		$transaction->save();
	
		$member->add_transaction( $transaction->id );
		$member->save();
		
		return $transaction;
	}
	
	/**
	 * Process transaction status change.
	 * 
	 * @todo better handle status change other than paid.   
	 * @param string $status The status to change
	 * @param bool $force Process status change even if status already has the new value. 
	 */
	public function process_transaction( $status, $force = false ) {
		if(  array_key_exists( $status, self::get_status() ) && ( $this->status != $status || $force ) ) {
			$this->status = $status;
			$member = MS_Model_Member::load( $this->user_id );
			switch( $status ) {
				case self::STATUS_PAID:
					$member->add_membership( $this->membership_id, $this->gateway_id );
					break;
				case self::STATUS_REVERSED:
				case self::STATUS_REFUNDED:
				case self::STATUS_DENIED:
				case self::STATUS_DISPUTE:
					if( defined( 'MS_MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION' ) && MS_MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION == true ) {
						$member->active = false;
					}
					break;
			}
			$member->save();
		}
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
					return $this->amount + $this->tax_rate/100 * $this->amount; 
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
					break;
				case 'status':
					if( array_key_exists( $value, self::get_status() ) ) {
						$this->$property = $value;
					}
					break;
				case 'due_date':
				case 'expire_date':
					$this->$property = $this->validate_date( $value );
					break;
				case 'taxable':
					$this->$property = $this->validate_bool( $value );
					break;
				case 'amount':
				case 'tax_rate':
					$this->$property = floatval( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}