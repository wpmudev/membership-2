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

/**
 * Invoice model.
 *
 * Persisted by parent class MS_Model_Custom_Post_Type.
 * 
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Invoice extends MS_Model_Custom_Post_Type {
	
	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	public static $POST_TYPE = 'ms_invoice';
	public $post_type = 'ms_invoice';
	
	/**
	 * Invoice status constants.
	 *
	 * @since 1.0.0
	 * 
	 * @see $status property.
	 * @var string
	 */
	const STATUS_BILLED = 'billed';
	const STATUS_PAID = 'paid';
	const STATUS_FAILED = 'failed';
	const STATUS_PENDING = 'pending';
	const STATUS_DENIED = 'denied';
	
	/**
	 * External transaction ID.
	 * 
	 * Used to link 3rd party transaction ID to $this->id
	 * 
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	protected $external_id;
	
	/**
	 * Gateway ID.
	 *
	 * Gateway used to pay this invoice. 
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $gateway_id;
	
	/**
	 * Membership ID.
	 *
	 * Invoice for membership. 
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $membership_id;
	
	/**
	 * User ID.
	 *
	 * Invoice for this user/member.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $user_id;
	
	/**
	 * Membership Relationship ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $ms_relationship_id;
	
	/**
	 * Coupon ID.
	 * 
	 * Used coupon ID.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $coupon_id;
	
	/**
	 * Currency of this invoice.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $currency;
	
	/**
	 * Amount value not including discounts.
	 *
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected $amount;
	
	/**
	 * Discount value.
	 *
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected $discount;
	
	/**
	 * Pro rate value.
	 *
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected $pro_rate;
	
	/**
	 * Total value.
	 * 
	 * Includes discount, pro-rating, tax.
	 *
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected $total;
	
	/**
	 * Inovoice status.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $status;
	
	/**
	 * Invoice for trial period.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $trial_period;
	
	/**
	 * Invoice due date.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $due_date;
	
	/**
	 * Invoice notes.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $notes;

	/**
	 * Invoice number.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	protected $invoice_number;
	
	/**
	 * Is taxable invoice.
	 *
	 * @todo For further versions.
	 * 
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	protected $taxable;
	
	/**
	 * Tax rate value.
	 *
	 * @todo For further versions.
	 * 
	 * @since 1.0.0
	 *
	 * @var float
	 */
	protected $tax_rate;
	
	/**
	 * Tax name.
	 *
	 * @todo For further versions.
	 * 
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $tax_name;
		
	/**
	 * Get invoice status types.
	 *
	 * @since 1.0.0
	 */
	public static function get_status_types() {
		
		return apply_filters( 'ms_model_invoice_get_status_types', array(
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
	 * @since 1.0.0
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 */
	public static function get_invoice_count( $args = null ) {
		
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
		);
		$args = apply_filters( 'ms_model_invoice_get_invoice_count_args', wp_parse_args( $args, $defaults ) );
		
		$query = new WP_Query( $args );
		
		return apply_filters( 'ms_model_invoice_get_invoice_count', $query->found_posts, $args );
	}
	
	/**
	 * Get invoices.
	 *
	 * @since 1.0.0
	 * 
	 * @param mixed $args The arguments to select data.
	 */
	public static function get_invoices( $args = null ) {
		
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'post_status' => 'any',
				'fields' => 'ids',
				'order' => 'DESC',
				'orderby' => 'ID',
		);
		$args = apply_filters( 'ms_model_invoice_get_invoices_args', wp_parse_args( $args, $defaults ) );
		
		$query = new WP_Query( $args );
		
		$items = $query->get_posts();
		
		$invoices = array();
		foreach ( $items as $item ) {
			$invoices[] = MS_Factory::load( 'MS_Model_Invoice', $item );	
		}
		return apply_filters( 'ms_model_invoice_get_invoices', $invoices, $args );
	}

	/**
	 * Get specific invoice.
	 *
	 * Get invoice of a user and membership.
	 * 
	 * @since 1.0.0
	 * 
	 * @param int $ms_relatiobship_id The membership relationship id.
	 * @param int $invoice_number Optional. The invoice number. Get the current number if null.
	 * @param string $status Optional. The invoice status.
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
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $item[0] );
		}
		
		return apply_filters( 'ms_model_invoice_get_invoice', $invoice, $ms_relationship_id, $invoice_number, $status );
	}
	
	/**
	 * Load invoice using external ID.
	 * 
	 * @since 1.0.0
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
			$invoice = MS_Factory::load( 'MS_Model_Invoice', $item[0]->ID );
		}
		
		return apply_filters( 'ms_model_invoice_load_by_external_id', $invoice , $external_id, $gateway_id );
	}
	
	/**
	 * Add invoice notes.
	 * 
	 * @since 1.0.0
	 * 
	 * @param string $notes
	 */
	public function add_notes( $notes ) {
		
		$this->notes[] = apply_filters( 'ms_model_invoice_add_notes', $notes, $this );
	}

	/**
	 * Get notes array as string.
	 * 
	 * @since 1.0.0
	 * 
	 * @return string The notes as text description. 
	 */
	public function get_notes_desc() {
		
		$desc = is_array( $this->notes ) ? implode( '\n', $this->notes ) : $this->notes;
		
		return apply_filters( 'ms_model_invoice_get_notes_desc', $desc, $this );
	}
	
	/**
	 * Get current member membership invoice.
	 * 
	 * The current invoice is the not paid one. Every time a invoice is paid,
	 * the current invoice number is incremented.  
	 * 
	 * @since 1.0.0
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @param boolean $update_existing Optional. True to overwrite existing invoice or false to create a new one if doesn't exist.
	 * @param string $status Optional. The invoice status to find.
	 * @return MS_Model_Invoice
	 */
	public static function get_current_invoice( $ms_relationship, $update_existing = true, $status = null ) {
		
		$invoice = self::get_invoice( $ms_relationship->id, $ms_relationship->current_invoice_number, $status );
		if( empty( $invoice ) || $update_existing ) {
			$invoice = self::create_invoice( $ms_relationship, $ms_relationship->current_invoice_number );
		}
		
		return apply_filters( 'ms_model_invoice_get_current_invoice', $invoice, $ms_relationship, $update_existing, $status );
	}
	
	/**
	 * Get next invoice for the membership.
	 * 
	 * @since 1.0.0
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @param boolean $update_existing Optional. True to overwrite existing invoice or false to create a new one if doesn't exist.
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
		
		return apply_filters( 'ms_model_invoice_get_next_invoice', $invoice, $ms_relationship, $update_existing );
	}

	/**
	 * Get previous invoice for the membership.
	 * 
	 * @since 1.0.0
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @param optional string $status The invoice status to find.
	 * @return MS_Model_Invoice
	 */
	public static function get_previous_invoice( $ms_relationship, $status = null ) {
		
		$invoice = self::get_invoice( $ms_relationship->id, $ms_relationship->current_invoice_number - 1, $status );
		
		return apply_filters( 'ms_model_invoice_get_previous_invoice', $invoice, $ms_relationship, $status );
	}
	
	/**
	 * Create invoice.
	 *
	 * Create a new invoice using the membership information.
	 *
	 * @since 1.0.0
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership to create invoice for.
	 * @param int $invoice_number Optional. The invoice number.
	 * @param boolean $update_existing Optional. True to overwrite existing invoice or false to create a new one if doesn't exist.
	 */
	public static function create_invoice( $ms_relationship, $invoice_number = false, $update_existing = true ) {
	
		$membership = $ms_relationship->get_membership();
		
		if( ! MS_Model_Membership::is_valid_membership( $membership->id ) ) {
			throw new Exception( 'Invalid Membership.' );
		}
		
		$invoice = null;
		$member = MS_Factory::load( 'MS_Model_Member', $ms_relationship->user_id );
		$invoice_status = self::STATUS_BILLED;
		$notes = null;
		$due_date = null;
			
		if( empty( $invoice_number ) ) {
			$invoice_number = $ms_relationship->current_invoice_number;
		}
		
		/* Search for existing invoice */
		if( $update_existing ) {
			$invoice = self::get_invoice( $ms_relationship->id, $invoice_number );
		}
		
		/* No existing invoice, create a new one. */
		if( empty( $invoice ) ) {
			$invoice = apply_filters( 'ms_model_invoice', new self() );
		}
		$tax = MS_Plugin::instance()->settings->tax;
			
		/* Update invoice info.*/
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
		
		/* Calc pro rate discount if moving from another membership. */
		if(  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_PRO_RATE) && $ms_relationship->move_from_id ) {
			$move_from = MS_Model_Membership_Relationship::get_membership_relationship( $ms_relationship->user_id, $ms_relationship->move_from_id );
			if( ! empty( $move_from->id ) && ! empty( $gateway ) && $gateway->pro_rate && $pro_rate = self::calculate_pro_rate( $move_from ) ) {
				$invoice->pro_rate = $pro_rate;
				$notes[] = sprintf( __( 'Pro rate discount: %s %s. ', MS_TEXT_DOMAIN ), $invoice->currency, $pro_rate );
			}
		}
		/* Apply coupon discount. */
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_COUPON ) && $coupon = MS_Model_Coupon::get_coupon_application( $member->id, $membership->id ) ) {
			$invoice->coupon_id = $coupon->id;
			$discount = $coupon->get_discount_value( $ms_relationship );
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
			
		/* Check for trial period in the first period. */
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

		return apply_filters( 'ms_model_membership_relationship_create_invoice', $invoice, $ms_relationship, $invoice_number, $update_existing );
	}
	
	/**
	 * Calculate pro rate value.
	 *
	 * Pro rate using remaining membership days. For further versions.
	 *
	 * @since 1.0.0
	 * 
	 * @return float The pro rate value.
	 */
	public static function calculate_pro_rate( $ms_relationship ) {
		
		$value = 0;
		$membership = $ms_relationship->get_membership();
		
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) && MS_Model_Membership::PAYMENT_TYPE_PERMANENT != $membership->payment_type ) {
			$invoice = self::get_previous_invoice( $ms_relationship );
			if( ! empty( $invoice ) && self::STATUS_PAID == $invoice->status ) {
				switch( $ms_relationship->get_status() ) {
					case MS_Model_Membership_Relationship::STATUS_TRIAL:
						if( $invoice->trial_period ) {
							$remaining_days = $ms_relationship->get_remaining_trial_period();
							$total_days = MS_Helper_Period::subtract_dates(  $ms_relationship->trial_expire_date, $ms_relationship->start_date );
							$value = $remaining_days / $total_days;
							$value *= $invoice->total;
						}
						break;
					case MS_Model_Membership_Relationship::STATUS_ACTIVE:
					case MS_Model_Membership_Relationship::STATUS_CANCELED:
						if( ! $invoice->trial_period ) {
							$remaining_days = $ms_relationship->get_remaining_period();
							$total_days = MS_Helper_Period::subtract_dates( $ms_relationship->expire_date, $ms_relationship->start_date );
							$value = $remaining_days / $total_days;
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
	 * Add taxes.
	 * 
	 * @since 1.0.0
	 */
	public function get_total() {
		
		$this->total = $this->amount + $this->tax_rate/100 * $this->amount - $this->discount - $this->pro_rate;
		
		if( $this->total < 0 ) {
			$this->total = 0;
		}
		return apply_filters( 'ms_model_invoice_get_total', $this->total, $this );
	}
	
	/**
	 * Returns property associated with the render.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		
		$value = null;
		
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'total':
					$value = $this->get_total();
					break;
				case 'invoice':
					$value = $this->id;
					break;
				default:
					$value = $this->$property;
					break;
			}
		}
		
		return apply_filters( 'ms_model_invoice__get', $value, $property, $this );
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
					if( array_key_exists( $value, self::get_status_types() ) ) {
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
		
		do_action( 'ms_model_invoice__set_after', $property, $value, $this );
	}
	
	/**
	 * Returns register custom post type args.
	 *
	 * @since 1.0.0
	 */
	public static function get_register_post_type_args() {
		return apply_filters( 'ms_register_post_type_' . self::$POST_TYPE, array(
				'description' => __( 'user invoices', MS_TEXT_DOMAIN ),
				'public' => true,
				'show_ui' => false,
				'show_in_menu' => false,
				'has_archive' => false,
				'publicly_queryable' => true,
				'supports' => false,
				'hierarchical' => false,
		) );
	}
}