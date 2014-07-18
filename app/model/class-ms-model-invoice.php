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

class MS_Model_Invoice extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_invoice';
	
	protected static $CLASS_NAME = __CLASS__;
	
	const STATUS_BILLED = 'billed';
	
	const STATUS_PAID = 'paid';
	
	const STATUS_FAILED = 'failed';
	
	const STATUS_PENDING = 'pending';
	
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
	
	protected $trial_period;
	
	protected $pro_rate;
	
	private $total;
	
	/**
	 * Get invoice status.
	 *
	 * Used to verify allowed status.
	 * 
	 * @since 4.0
	 */
	public static function get_status() {
		return apply_filters( 'ms_model_invoice_get_status', array(
				self::STATUS_BILLED => __( 'Billed', MS_TEXT_DOMAIN ),
				self::STATUS_PAID => __( 'Paid', MS_TEXT_DOMAIN ),
				self::STATUS_FAILED => __( 'Failed', MS_TEXT_DOMAIN ),
				self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
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
	public static function get_invoice_count( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
		);
		$args = apply_filters( 'ms_model_invoice_get_invoice_count_args', wp_parse_args( $args, $defaults ) );
		
		$query = new WP_Query( $args );
		
		return apply_filters( 'ms_model_invoice_get_invoice_count', $query->found_posts );
		
	}
	
	/**
	 * Get invoices.
	 *
	 * @since 4.0
	 * @param mixed $args The arguments to select data.
	 */
	public static function get_invoices( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'post_status' => 'any',
				'fields' => 'ids',
				'order' => 'DESC',
		);
		$args = apply_filters( 'ms_model_invoice_get_invoices_args', wp_parse_args( $args, $defaults ) );
		
		$query = new WP_Query( $args );
		
		$items = $query->get_posts();
		
		$invoices = array();
		foreach ( $items as $item ) {
			$invoices[] = self::load( $item );	
		}
		return apply_filters( 'ms_model_invoice_get_invoices', $invoices );
	}

	/**
	 * Get specific invoice.
	 *
	 * Get invoice of a user and membership.
	 * 
	 * @since 4.0
	 * 
	 * @param int $ms_relatiobship_id The membership relationship id.
	 * @param int $invoice_number The invoice number.
	 * @param string $status The status of the transaction.
	 * @return MS_Model_Invoice The found invoice or null if not found.
	 */
	public static function get_invoice( $ms_relationship_id, $invoice_number = null, $status = null ) {
		$args = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
				'fields' => 'ids',
				'order' => 'DESC',
		);
	
		$args['meta_query']['ms_relationship_id'] = array(
				'key'     => 'ms_relationship_id',
				'value'   => $ms_relationship_id,
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
		
		$args = apply_filters( 'ms_model_invoice_get_invoice_args', $args );
		$query = new WP_Query( $args );
		$item = $query->get_posts();
		
		$invoice = null;
		if( ! empty( $item[0] ) ) {
			$invoice = self::load( $item[0] );
		}
		return apply_filters( 'ms_model_invoice_get_invoice', $invoice );
	}
	
	/**
	 * Load invoice using external ID.
	 * 
	 * @since 4.0
	 *  
	 * @param string $external_id
	 * @param string $gateway_id
	 * @return MS_Model_Invoice, null if not found.
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
		
		$args = apply_filters( 'ms_model_invoice_load_by_external_id_args', $args );
		$query = new WP_Query( $args );
		
		$item = $query->get_posts();
		$invoice = null;

		if( ! empty( $item[0] ) ) {
			$invoice = self::load( $item[0]->ID );
		}
		
		return apply_filters( 'ms_model_invoice_load_by_external_id', $invoice );
	}
	
	/**
	 * Add invoice notes.
	 * 
	 * @since 4.0.0
	 * 
	 * @param string $notes
	 */
	public function add_notes( $notes ) {
		$this->notes[] = apply_filters( 'ms_model_invoice_add_notes', $notes );
	}

	/**
	 * Get current member membership invoice.
	 * 
	 * The current invoice is not paid one.
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @param optional boolean $update_existing True to overwrite existing invoice or false to create a new one.
	 * @param optional string $status The invoice status to find.
	 * @return MS_Model_Invoice
	 */
	public static function get_current_invoice( $ms_relationship, $update_existing = true, $status = null ) {
		
		$invoice = self::get_invoice( $ms_relationship->id, $ms_relationship->current_invoice_number, $status );
		if( empty( $invoice ) || $update_existing ) {
			$invoice = self::create_invoice( $ms_relationship, $ms_relationship->current_invoice_number );
		}
		
		return apply_filters( 'ms_model_invoice_get_current_invoice', $invoice );
	}
	
	/**
	 * Get next invoice for the membership.
	 * 
	 * @since 4.0
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @param boolean $update_existing
	 * @return MS_Model_Invoice
	 */
	public static function get_next_invoice( $ms_relationship, $update_existing = true ) {

		$invoice = self::get_invoice( $ms_relationship->id, $ms_relationship->current_invoice_number + 1 );
		if( empty( $invoice ) || $update_existing ) {
			$invoice = self::create_invoice( $ms_relationship, $ms_relationship->current_invoice_number + 1 );
		}
		
		$invoice->discount = 0;
		$invoice->pro_rate = 0;
		$invoice->notes = array();
		
		return apply_filters( 'ms_model_invoice_get_next_invoice', $invoice );
	}

	/**
	 * Get previous invoice for the membership.
	 * 
	 * @since 4.0
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @param optional string $status The invoice status to find.
	 * @return MS_Model_Invoice
	 */
	public static function get_previous_invoice( $ms_relationship, $status = null ) {
		
		$invoice = self::get_invoice( $ms_relationship->id, $ms_relationship->current_invoice_number - 1, $status );
		return apply_filters( 'ms_model_invoice_get_previous_invoice', $invoice );
	}
	
	/**
	 * Create invoice.
	 *
	 * Create a new invoice using the membership information.
	 *
	 * @since 4.0
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership to create invoice for.
	 * @param optional int $invoice_number The invoice number.
	 * @param optional int $update_existing Update an existing invoice instead of creating a new one.
	 */
	public static function create_invoice( $ms_relationship, $invoice_number = false, $update_existing = true ) {
	
		$membership = $ms_relationship->get_membership();
		
		if( ! MS_Model_Membership::is_valid_membership( $membership->id ) ) {
			throw new Exception( 'Invalid Membership.' );
		}
		
		$invoice = null;
		if( $gateway = $ms_relationship->get_gateway() ) {
			$member = MS_Model_Member::load( $ms_relationship->user_id );
			$invoice_status = self::STATUS_BILLED;
			$notes = null;
			$due_date = null;
				
			if( empty( $invoice_number ) ) {
				$invoice_number = $ms_relationship->current_invoice_number;
			}
			
			/** Search for existing invoice */
			if( $update_existing ) {
				$invoice = self::get_invoice( $ms_relationship->id, $invoice_number );
			}
			
			/** No existing invoice, create a new one. */
			if( empty( $invoice ) ) {
				$invoice = apply_filters( 'ms_model_invoice', new self() );
			}
			$tax = MS_Plugin::instance()->settings->tax;
				
			/** Update invoice info.*/
			$invoice->ms_relationship_id = $ms_relationship->id;
			$invoice->gateway_id = $ms_relationship->gateway_id;
			$invoice->status = $invoice_status;
			$invoice->membership_id = $membership->id;
			$invoice->currency = MS_Plugin::instance()->settings->currency;
			$invoice->user_id = $member->id;
			$invoice->name = apply_filters( 'ms_model_invoice_name', sprintf( __( 'Invoice for %s - %s', MS_TEXT_DOMAIN ), $membership->name, $member->username ) );
			$invoice->description = apply_filters( 'ms_model_invoice_description', $ms_relationship->get_payment_description() );
			$invoice->tax_name = $tax['tax_name'];
			$invoice->tax_rate = $tax['tax_rate'];

			$invoice->invoice_number = $invoice_number;
			$invoice->discount = 0;
			
			/** Calc pro rate discount if moving from another membership. */
			if( $ms_relationship->move_from_id ) {
				$move_from = MS_Model_Membership_Relationship::get_membership_relationship( $ms_relationship->user_id, $ms_relationship->move_from_id );
				if( ! empty( $move_from->id ) && $gateway->pro_rate && $pro_rate = self::calculate_pro_rate( $move_from ) ) {
					$invoice->pro_rate = $pro_rate;
					$notes[] = sprintf( __( 'Pro rate discount: %s %s. ', MS_TEXT_DOMAIN ), $invoice->currency, $pro_rate );
				}
			}
			/** Apply coupon discount. */
			if( $coupon = MS_Model_Coupon::get_coupon_application( $member->id, $membership->id ) ) {
				$invoice->coupon_id = $coupon->id;
				$discount = $coupon->get_discount_value( $membership );
				$invoice->discount = $discount;
				$notes[] = sprintf( __( 'Coupon %s, discount: %s %s. ', MS_TEXT_DOMAIN ), $coupon->code, $invoice->currency, $discount );
			}
			$invoice->notes = $notes;
			
			/** Due date calculation.*/
			switch( $ms_relationship->status ) {
				default:
				case MS_Model_Membership_Relationship::STATUS_PENDING:
				case MS_Model_Membership_Relationship::STATUS_EXPIRED:
				case MS_Model_Membership_Relationship::STATUS_DEACTIVATED:
					$due_date = MS_Helper_Period::current_date();
					break;
				case MS_Model_Membership_Relationship::STATUS_TRIAL:
					$due_date = $ms_relationship->trial_expire_date;
					break;
				case MS_Model_Membership_Relationship::STATUS_ACTIVE:
				case MS_Model_Membership_Relationship::STATUS_CANCELED:
					$due_date = $ms_relationship->expire_date;
					break;
			}
			$invoice->due_date = $due_date;
				
			/** Check for trial period in the first period. */
			if( $ms_relationship->is_trial_eligible() && $invoice_number == $ms_relationship->current_invoice_number ) {
				$invoice->amount = $membership->trial_price;
				$invoice->trial_period = true;
			}
			else {
				$invoice->amount = $membership->price;
				$invoice->trial_period = false;
			}
			
			/** Total is calculated discounting coupon and pro-rating. */
			if( 0 == $invoice->get_total() ) {
				$invoice->status = self::STATUS_PAID;
			}
				
			$invoice->save();
		}

		return apply_filters( 'ms_model_membership_relationship_create_invoice_object', $invoice );
	}
	
	/**
	 * Calculate pro rate value.
	 *
	 * Pro rate using remaining membership days.
	 *
	 * @since 4.0
	 * @return float The pro rate value.
	 */
	public static function calculate_pro_rate( $ms_relationship ) {
		$value = 0;
		$membership = $ms_relationship->get_membership();
		
		if( ! MS_Model_Addon::is_active( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) && MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT != $membership->membership_type ) {
			$invoice = self::get_previous_invoice( $ms_relationship );
			if( ! empty( $invoice ) && self::STATUS_PAID == $invoice->status ) {
				switch( $ms_relationship->get_status() ) {
					case MS_Model_Membership_Relationship::STATUS_TRIAL:
						if( $invoice->trial_period ) {
							$remaining = $ms_relationship->get_remaining_trial_period();
							$total = MS_Helper_Period::subtract_dates(  $ms_relationship->trial_expire_date, $ms_relationship->start_date );
							$value = $remaining->days / $total->days;
							$value *= $invoice->total;
						}
						break;
					case MS_Model_Membership_Relationship::STATUS_ACTIVE:
					case MS_Model_Membership_Relationship::STATUS_CANCELED:
						if( ! $invoice->trial_period ) {
							$remaining = $ms_relationship->get_remaining_period();
							$total = MS_Helper_Period::subtract_dates( $ms_relationship->expire_date, $ms_relationship->start_date );
							$value = $remaining->days / $total->days;
							$value *= $invoice->total;
						}
						break;
					default:
						$value = 0;
						break;
				}
			}
		}

		return apply_filters( 'ms_model_invoice_calculate_pro_rate_value', $value, $ms_relationship );
	}
	
	/**
	 * Get invoice total.
	 * 
	 * Discounting coupon and pro-rating.
	 * Add taxes
	 */
	public function get_total() {
		$this->total = $this->amount + $this->tax_rate/100 * $this->amount - $this->discount - $this->pro_rate;
		if( $this->total < 0 ) {
			$this->total = 0;
		}
		return apply_filters( 'ms_model_invoice_get_total', $this->total );
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
					return $this->get_total();
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
					$this->get_total();
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
	
	/**
	 * Register and Filter the custom post type.
	 *
	 * @since 4.0.0
	 * @param object $this The MS_Plugin object.
	 */
	public static function register_post_type() {
		register_post_type( self::$POST_TYPE, apply_filters( 'ms_register_post_type_' . self::$POST_TYPE, array(
				'description' => __( 'user invoices', MS_TEXT_DOMAIN ),
				'public' => true,
				'show_ui' => false,
				'show_in_menu' => false,
				'has_archive' => false,
				'publicly_queryable' => true,
				'supports' => false,
// 				'capability_type' => apply_filters( self::$POST_TYPE, '_capability', 'post' ),
				'hierarchical' => false,
		) ) );
	}
}