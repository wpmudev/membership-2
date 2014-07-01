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

class MS_Model_Membership_Relationship extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_relationship';
	
	protected static $CLASS_NAME = __CLASS__;
	
	const STATUS_PENDING = 'pending';
	
	const STATUS_ACTIVE = 'active';
	
	const STATUS_TRIAL = 'trial';

	const STATUS_EXPIRED = 'expired';
	
	const STATUS_DEACTIVATED = 'deactivated';
	
	const STATUS_CANCELED = 'canceled';
	
	protected $membership_id;
	
	protected $user_id;
	
	protected $gateway_id;
	
	protected $start_date;
	
	protected $expire_date;
	
	protected $update_date;
	
	protected $trial_expire_date;
	
	protected $status;
	
	/**
	 * Current invoice number.
	 * 
	 * @var $current_invoice_number
	 */
	protected $current_invoice_number = 1;
	
	/**
	 * Move to membership id.
	 * @var int $move_to_id
	 */
	protected $move_to_id;
	
	protected $move_from_id;
	
	/**
	 * Return existing status types.
	 * 
	 * @since 4.0
	 * @return array of status 
	 */
	public static function get_status_types() {
		return apply_filters( 'ms_model_membership_relationship_get_status_types', array(
				self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
				self::STATUS_ACTIVE => __( 'Active', MS_TEXT_DOMAIN ),
				self::STATUS_TRIAL=> __( 'Trial', MS_TEXT_DOMAIN ),
				self::STATUS_EXPIRED => __( 'Expired', MS_TEXT_DOMAIN ),
				self::STATUS_DEACTIVATED => __( 'Deactivated', MS_TEXT_DOMAIN ),
				self::STATUS_CANCELED => __( 'Canceled', MS_TEXT_DOMAIN ),
		));
	}
	
	/**
	 * Create a new membership relationship.
	 *
	 * Search for existing relationship (unique object), creating if not exists.
	 * Set initial status. 
	 * 
	 * @since 4.0
	 * @return MS_Model_Membership_Relationship The created relationship. 
	 */
	public static function create_ms_relationship( $membership_id = 0, $user_id = 0, $gateway_id = 'admin', $move_from_id = 0 ) {
		if( ! MS_Model_Membership::is_valid_membership( $membership_id ) ) {
			return null;
		}
		
		$ms_relationship = self::get_membership_relationship( $user_id, $membership_id );
		
		if( empty( $ms_relationship ) ) {
			$ms_relationship = new self();
			$ms_relationship->membership_id = $membership_id;
			$ms_relationship->user_id = $user_id;
		}
		/**
		 * Reset invoice counter.
		 * The invoice history is keep.
		 */
		elseif( $ms_relationship->status == self::STATUS_DEACTIVATED ) {
			$ms_relationship->invoices = array();
		}
		/** Initial status */
		$ms_relationship->gateway_id = $gateway_id;
		$ms_relationship->move_from_id = $move_from_id;
		$ms_relationship->name = "user_id: $user_id, membership_id: $membership_id, gateway_id: $gateway_id";
		$ms_relationship->description = $ms_relationship->name;
		$ms_relationship->set_start_date();
		$ms_relationship->set_status( self::STATUS_PENDING );
		$ms_relationship->save();

		return apply_filters( 'ms_model_membership_relationship_create_ms_relationship', $ms_relationship );
	}
	
	/**
	 * Save model.
	 * 
	 * @since 4.0
	 * 
	 * Only saves if is not admin user and not visitor.
	 * Don't save visitor and default memberships (auto assigned). 
	 */
	public function save() {
		if( ! empty( $this->user_id ) && ! MS_Model_Member::is_admin_user( $this->user_id ) ) {
			$membership = MS_Model_Membership::load( $this->membership_id );
			if( ! $membership->visitor_membership && ! $membership->default_membership ) {
				parent::save();
			}
		}
	}
	
	/**
	 * Retrieve membership relationships.
	 * 
	 * @since 4.0
	 * 
	 * @return MS_Model_Membership_Relationship[] 
	 */
	public static function get_membership_relationships( $args = null ) {
		
		$args = self::get_query_args( $args );
		
		$query = new WP_Query( $args );
		$posts = $query->get_posts();

		$membership_relationships = array();
		if( ! empty( $posts ) ) {
			foreach( $posts as $post_id ) {
				$membership_relationship = self::load( $post_id );
				if( ! empty( $args['author'] ) ) {
					$membership_relationships[ $membership_relationship->membership_id ] = $membership_relationship;
				}
				else {
					$membership_relationships[ $post_id ] = $membership_relationship;
				}
			}
		}
		return apply_filters( 'ms_model_membership_relationship_get_membership_relationships', $membership_relationships, $args );
	}
	
	/**
	 * Retrieve membership relationship count.
	 * 
	 * @since 4.0
	 * 
	 * @param array $args
	 * @return number The found count.
	 */
	public static function get_membership_relationship_count( $args = null ) {
		
		$args = apply_filters( 'ms_model_membership_relationship_get_membership_relationship_count_args', self::get_query_args( $args ) );
		
		$query = new WP_Query( $args );

		return apply_filters( 'ms_model_membership_relationship_get_membership_relationship_count', $query->found_posts );
	}
	
	/**
	 * Retrieve membership relationship.
	 * 
	 * @since 4.0
	 * 
	 * @param int $user_id The user id 
	 * @return int $membership_id The membership id.
	 */
	public static function get_membership_relationship( $user_id, $membership_id ) {
		
		$args = apply_filters( 'ms_model_membership_relationship_get_membership_relationship_args', self::get_query_args( array( 
				'user_id' => $user_id, 
				'membership_id' => $membership_id, 
				'status' => 'all', 
		) ) );
		$query = new WP_Query( $args );
		$post = $query->get_posts();
		
		$ms_relationship = null;
		if( ! empty( $post[0] ) ) {
			$ms_relationship = self::load( $post[0] );
		}
		
		return apply_filters( 'ms_model_membership_relationship_get_membership_relationship', $ms_relationship );
	}
	
	/**
	 * Create default args to search posts.
	 * 
	 * Merge received args to default ones.
	 * 
	 * @since 4.0
	 * 
	 * @param array $args 
	 * @return array The args.
	 */
	public static function get_query_args( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
				'fields' => 'ids',
		);
		$args = wp_parse_args( $args, $defaults );
		
		if( ! empty( $args['user_id'] ) ) {
			$args['author'] = $args['user_id'];
			unset( $args['user_id'] );
		}
		if( ! empty( $args['membership_id'] ) ) {
			$args['meta_query']['membership_id'] = array(
					'key' => 'membership_id',
					'value' => $args['membership_id'],
			);
			unset( $args['membership_id'] );
		}
		if( ! empty( $args['gateway_id'] ) ) {
			$args['meta_query']['gateway_id'] = array(
					'key' => 'gateway_id',
					'value' => $args['gateway_id'],
			);
			unset( $args['gateway_id'] );
		}
		if( ! empty( $args['status'] ) ) {
			if( 'valid' == $args['status'] ) {
				$args['meta_query']['status'] = array(
						'key' => 'status',
						'value' => self::STATUS_DEACTIVATED,
						'compare' => 'NOT LIKE',
				);
			}
			elseif( 'all' != $args['status'] ) {
				$args['meta_query']['status'] = array(
						'key' => 'status',
						'value' => $args['status'],
						'compare' => 'LIKE',
				);
			}
			unset( $args['status'] );
		}
		else {
			$args['meta_query']['status'] = array(
					'key' => 'status',
					'value' => array( self::STATUS_DEACTIVATED, self::STATUS_PENDING ),
					'compare' => 'NOT IN',
			);
		}
		return apply_filters( 'ms_model_membership_relationship_get_query_args', $args );
	}
	
	/**
	 * Set Membership Relationship start date.
	 *
	 * @since 4.0
	 * 
	 * Also updates trial and expire date.
	 * @param string optional $start_date
	 */
	public function set_start_date( $start_date = null ) {
		$membership = $this->get_membership();
		
		if( MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE == $membership->membership_type ) {
			if( ! empty( $start_date ) ) {
				$this->start_date = $start_date;
				if( $membership->trial_period_enabled ) {
					$this->trial_expire_date = $membership->get_trial_expire_date( $this->start_date );
				}
			}
			else {
				$this->start_date = $membership->period_date_start;
				if( $membership->trial_period_enabled ) {
					$this->trial_expire_date = $membership->get_trial_expire_date( $this->start_date );
				}
				$this->expire_date = $membership->period_date_end;
			}
		}
		else {
			if( ! empty( $start_date ) ) {
				$this->start_date = $start_date;
				if( $membership->trial_period_enabled ) {
					$this->trial_expire_date = $membership->get_trial_expire_date( $this->start_date );
				}
				$this->expire_date = $membership->get_expire_date( $this->start_date );
			}
			else {
				$this->start_date = MS_Helper_Period::current_date();
				if( $membership->trial_period_enabled ) {
					$this->trial_expire_date = $membership->get_trial_expire_date();
				}
				$this->expire_date = $membership->get_expire_date();
			}
		}
		$this->update_date = MS_Helper_Period::current_date();
	}
	
	/**
	 * Set trial expire date.
	 * 
	 * Validate to a date greater than start date.
	 * 
	 * @since 4.0
	 * @param string $trial_expire_date
	 */
	public function set_trial_expire_date( $trial_expire_date ) {
		$membership = $this->get_membership();
		
		if( strtotime( $trial_expire_date ) >= strtotime( $this->start_date ) ) {
			$this->trial_expire_date = $trial_expire_date;
		}
		else {
			$this->trial_expire_date = $membership->get_trial_expire_date( $this->start_date );
		}
		
	}
	
	/**
	 * Set trial expire date.
	 * 
	 * Validate to a date greater than start date.

	 * @since 4.0
	 * @param string $trial_expire_date
	 */
	public function set_expire_date( $expire_date ) {
		$membership = $this->get_membership();
		
		if( strtotime( $expire_date ) >= strtotime( $this->start_date ) ) { 
			if( ! empty( $this->trial_expire_date) && strtotime( $expire_date ) >= strtotime( $this->trial_expire_date ) ) {
				$this->$expire_date = $membership->get_expire_date( $this->start_date );
			}
			$this->expire_date = $expire_date;
		}
		else {
			$this->$expire_date = $membership->get_expire_date( $this->start_date );
		}
		
	}
	
	/**
	 * Get Membership model.
	 * 
	 * @since 4.0
	 * @return MS_Model_Membership
	 */
	public function get_membership() {
		return MS_Model_Membership::load( $this->membership_id );
	}
	
	/**
	 * Get how many days in this membership.
	 * 
	 * @since 4.0
	 * @return string
	 */
	public function get_current_period() {
		return MS_Helper_Period::subtract_dates( MS_Helper_Period::current_date(), $this->start_date );
	}
	
	/**
	 * Get how many days until this membership trial expires.
	 * 
	 * @since 4.0
	 * @return string
	 */
	public function get_remaining_trial_period() {
		return MS_Helper_Period::subtract_dates( MS_Helper_Period::current_date(), $this->trial_expire_date );
	}
	
	/**
	 * Get how many days until this membership expires.
	 * 
	 * @since 4.0
	 * @return string
	 */
	public function get_remaining_period() {
		return MS_Helper_Period::subtract_dates( MS_Helper_Period::current_date(), $this->expire_date );
	}
	
	/**
	 * Calculate pro rate value.
	 * 
	 * Pro rate using remaining membership days.
	 * 
	 * @since 4.0
	 * @return float The pro rate value.
	 */
	public function calculate_pro_rate() {
		$value = 0;
		$trial_value = 0;
		$membership = $this->get_membership();
		if( MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT != $membership->membership_type ) {
			switch( $this->get_status() ) {
				case self::STATUS_TRIAL:
					$remaining = $this->get_remaining_trial_period();
					$total = MS_Helper_Period::subtract_dates(  MS_Helper_Period::current_date(), $this->start_date );
					$trial_value = $remaining->days / $total->days;
					$trial_value *= $membership->trial_price;
				case self::STATUS_ACTIVE:
				case self::STATUS_CANCELED:
					$remaining = $this->get_remaining_period();
					$total = MS_Helper_Period::subtract_dates(  MS_Helper_Period::current_date(), $membership->get_expire_date() );
					$value = $remaining->days / $total->days;
					$value *= $membership->price;
					break;
				default:
					$value = 0;
					break;
			}
		}
		return apply_filters( 'ms_model_membership_relationship_calculate_pro_rate_value', $value + $trial_value, $this );
	}
	
	/**
	 * Set elapsed period of time of membership.
	 * 
	 * @since 4.0
	 * @param int $period_unit The elapsed period unit.
	 * @param string $period_type The elapsed period type.
	 */
	public function set_elapsed_period( $period_unit, $period_type ) {
		if( in_array( $period_type, MS_Helper_Period::get_periods() ) ) {
			$this->start_date = MS_Helper_Period::subtract_interval( $period_unit, $period_type );
		}
	}

	/**
	 * Get gateway model.
	 *
	 * @since 4.0
	 *
	 * @return MS_Model_Gateway
	 */
	public function get_gateway() {
		
		$gateway = null;
		if( 'admin' != $this->gateway_id ) {
			$gateway = MS_Model_Gateway::load( $this->gateway_id );
		}
		
		return $gateway;
	}
	
	/**
	 * Get last invoice for this member membership.
	 * 
	 * Create one invoice if not found.
	 * 
	 * @since 4.0
	 * @param string $status The invoice status to search. 
	 * @return MS_Model_Transaction The invoice if found, and null otherwise.
	 */
	public function get_last_invoice( $status = MS_Model_Transaction::STATUS_BILLED ) {
		$invoice = $this->get_invoice( $this->current_invoice_number, $status );
		if( empty( $invoice ) ) {
			$invoice = $this->create_invoice( $this->current_invoice_number );
		}
		
		return $invoice;
	}

	public function get_next_invoice() {
		$invoice = $this->create_invoice( $this->current_invoice_number + 1, true );
		$invoice->discount = 0;
		$invoice->pro_rate = 0;
		$invoice->notes = array();
		return $invoice;
	}
	
	/**
	 * Get invoice for this member membership.
	 *
	 * @since 4.0
	 * @param string $status The invoice status to search.
	 * @return MS_Model_Transaction The invoice if found, and null otherwise.
	 */
	public function get_invoice( $invoice_number = false, $status = MS_Model_Transaction::STATUS_BILLED ) {
		return apply_filters( 'ms_model_membership_relationship_get_invoice', MS_Model_Transaction::get_transaction(
				$this->user_id,
				$this->membership_id,
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
	public function create_invoice( $invoice_number = false, $update_existing = true ) {
		
		$invoice = null;
		if( $gateway = $this->get_gateway() ) {
			$membership = $this->get_membership();
			$member = MS_Model_Member::load( $this->user_id );
			$invoice_status = MS_Model_Transaction::STATUS_BILLED;
			$notes = null;
			$due_date = null;
			
			switch( $this->status ) {
				default:
				case self::STATUS_PENDING:
					/** trial period */
					if( $membership->trial_period_enabled && 1 == $invoice_number) {
						$due_date = MS_Helper_Period::current_date();
					}
					else {	
						$due_date = $this->trial_expire_date;
					}
					break;
				case self::STATUS_DEACTIVATED:
				case self::STATUS_EXPIRED:
					$due_date = MS_Helper_Period::current_date();
					break;
				case self::STATUS_TRIAL:
				case self::STATUS_ACTIVE:
				case self::STATUS_CANCELED:
					$due_date = $this->expire_date;
					break;
			}

			$invoice = $this->get_invoice( $invoice_number );
			if( ! $update_existing || empty( $invoice ) ) {
				$invoice = MS_Model_Transaction::create_transaction(
						$membership,
						$member,
						$this->gateway_id,
						$invoice_status
				);
			}			
			/** Update invoice info.*/
			$invoice->invoice_number = $invoice_number;
			$invoice->discount = 0;
			if( ! empty ( $this->move_from_id ) && ! MS_Plugin::instance()->addon->multiple_membership && ! empty( $gateway ) && $gateway->pro_rate ) {
					$move_from = MS_Model_Membership_Relationship::load( $this->move_from_id );
					
					/** Status which pro rate is applicable */
					$ms_in_status = apply_filters( 'ms_model_membership_relationship_create_invoice_ms_in_status', array( 
							MS_Model_Membership_Relationship::STATUS_TRIAL,
							MS_Model_Membership_Relationship::STATUS_ACTIVE,
							MS_Model_Membership_Relationship::STATUS_CANCELED,
					) );
					if( $move_from->id > 0 && in_array( $move_from->status, $ms_in_status ) ) {
						$pro_rate = $move_from->calculate_pro_rate();
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
			if( 1 == $invoice_number && $membership->trial_period_enabled ) {
				$invoice->amount = $membership->trial_price;
			}
			else {
				$invoice->amount = $membership->price;
			}
			if( 0 == $invoice->total ) {
				$invoice->status = MS_Model_Transaction::STATUS_PAID;
			}
			$invoice->ms_relationship_id = $this->id;
			$invoice->save();
		}
		
		return apply_filters( 'ms_model_membership_relationship_create_invoice_object', $invoice );
		
	}
	
	 /**
	 * Process transaction.
	 * 
	 * Process transaction status change related to this membership relationship.
	 * Change status accordinly to transaction status.
	 * 
	 * @param MS_Model_Transaction $transaction The Transaction.
	 */
	public function process_transaction( $transaction ) {
		
		$member = MS_Model_Member::load( $this->user_id );
		switch( $transaction->status ) {
			case MS_Model_Transaction::STATUS_BILLED:
				break;
			case MS_Model_Transaction::STATUS_PAID:
				if( $transaction->coupon_id ) {
					$coupon = MS_Model_Coupon::load( $transaction->coupon_id );
					$coupon->remove_coupon_application( $member->id, $transaction->membership_id );
					$coupon->used++;
					$coupon->save();
				}
				$this->set_status( self::STATUS_ACTIVE );
				$this->current_invoice_number = max( $this->current_invoice_number, $transaction->invoice_number );
				$member->active = true;
				break;
			case MS_Model_Transaction::STATUS_REVERSED:
			case MS_Model_Transaction::STATUS_REFUNDED:
			case MS_Model_Transaction::STATUS_DENIED:
			case MS_Model_Transaction::STATUS_DISPUTE:
				$this->set_status( self::STATUS_DEACTIVATED );
				$member->active = false;
				break;
			default:
				do_action( 'ms_model_membership_relationship_process_transaction', $this, $transaction );
				break;
		}
		$member->save();
		$this->save();
	}
	
	/**
	 * Set membership relationship status.
	 * 
	 * Check for status that need membership verification for trial, active and expired.
	 * 
	 * @since 4.0
	 * @param string $status
	 */
	public function set_status( $status ) {
		$allowed_status = array( 
				self::STATUS_DEACTIVATED, 
				self::STATUS_PENDING,
				self::STATUS_CANCELED,
		);
		
		if( ! in_array( $status, $allowed_status ) ){
			$membership = $this->get_membership();
			if( ! empty( $this->trial_expire_date ) && strtotime( $this->trial_expire_date ) >= strtotime( MS_Helper_Period::current_date() ) ) {
				$status = self::STATUS_TRIAL;
			}
			elseif( MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT == $membership->membership_type ) {
				$status = self::STATUS_ACTIVE;
			}
			elseif( ! empty( $this->expire_date ) && strtotime( $this->expire_date ) >= strtotime( MS_Helper_Period::current_date() ) ) {
				$status = self::STATUS_ACTIVE;
			}
			else {
				$status = self::STATUS_EXPIRED;
			}
		}
		if( $this->status != $status ) {
			/** signup */
			if( 'admin' != $this->gateway_id && self::STATUS_PENDING == $this->status && in_array( $status, array( self::STATUS_TRIAL, self::STATUS_ACTIVE ) ) ) {
				MS_Model_News::save_news( $this,  MS_Model_News::TYPE_MS_SIGNUP );
				$transaction = MS_Model_Transaction::get_transaction( $this->user_id, $this->membership_id, MS_Model_Transaction::STATUS_PAID );
				/** Registration completed automated message */
				do_action( 'ms_communications_process_' . MS_Model_Communication::COMM_TYPE_REGISTRATION , $this->user_id, $this->membership_id, $transaction->id );
			}
		}

		if( array_key_exists( $status, self::get_status_types() ) ) {
			$this->status = apply_filters( 'ms_model_membership_relationship_set_status', $status );
		}
	}
	
	/**
	 * Get membership relationship status.
	 * 
	 * Verifies start and end date of a membership and updates status if expired
	 * 
	 * @since 4.0
 	 */
	public function get_status() {

		$allowed_status = array(
				self::STATUS_DEACTIVATED,
				self::STATUS_PENDING,
		);
		
		if( ! in_array( $this->status, $allowed_status ) ){
			$membership = $this->get_membership();
			if( ! empty( $this->trial_expire_date ) && strtotime( $this->trial_expire_date ) >= strtotime( MS_Helper_Period::current_date() ) ) {
				$status = self::STATUS_TRIAL;
			}
			elseif( MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT == $membership->membership_type ) {
				$status = self::STATUS_ACTIVE;
			}
			elseif( ! empty( $this->expire_date ) && strtotime( $this->expire_date ) >= strtotime( MS_Helper_Period::current_date() ) ) {
				$status = self::STATUS_ACTIVE;
			}
			else {
				$status = self::STATUS_EXPIRED;
			}
			/**
			 * If user canceled the membership before expire date, still have access until expires.
			 */
			if( self::STATUS_CANCELED == $this->status ) {
				/** For expired memberships or MEMBERSHIP_TYPE_PERMANENT */
				if( self::STATUS_EXPIRED == $status || MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT == $membership->membership_type ) {
					$status = self::STATUS_DEACTIVATED;
				}
				else {
					$status = self::STATUS_CANCELED;
				}
			}
			if( $status != $this->status ) {
				$this->status = $status;
				$this->save();
			}
		}
		
		return apply_filters( 'membership_model_membership_relationship_status', $this->status, $this );
	}
	
	/**
	 * Returns property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		switch( $property ) {
			case 'status':
				return $this->get_status();
				break;
			default:
				if ( ! property_exists( $this, $property ) ) {
					MS_Helper_Debug::log( "Property doesn't exist: $property.");
				}
				return $this->$property;
				break;
		}
	
	}
	
	/**
	 * Set specific property.
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
				case 'start_date':
					$this->set_start_date( $value );
					break;
				case 'trial_expire_date':
					$this->set_trial_expire_date( $value );
					break;
				case 'expire_date':
					$this->set_expire_date( $value );
					break;
				case 'status':
					$this->set_status( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
	
}