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
	
	/**
	 * @deprecated
	 * @var unknown
	 */
	protected $transaction_ids = array();
	
	protected $user_id;
	
	protected $gateway_id;
	
	protected $start_date;
	
	protected $expire_date;
	
	protected $update_date;
	
	protected $trial_expire_date;
	
	protected $status;
	
	/**
	 * Move to membership id.
	 * @var int $move_to_id
	 */
	protected $move_to_id;
	
	protected $move_from_id;
	
	public function __construct() {
		
	}
	
	/**
	 * @deprecated
	 * @param number $membership_id
	 * @param number $user_id
	 * @param number $gateway_id
	 * @param string $transaction
	 */
	public function __construct1( $membership_id = 0, $user_id = 0, $gateway_id = 0, $transaction = null ) {
		
		if( ! MS_Model_Membership::is_valid_membership( $membership_id ) ) {
			return;
		}
		$this->membership_id = $membership_id;
		$this->user_id = $user_id;
		$this->gateway_id = $gateway_id;
		if( $transaction ) {
			$this->set_status( self::STATUS_PENDING );
			$this->add_transaction( $transaction );
		}
		else {
			$this->set_status( self::STATUS_ACTIVE );
		}
		$this->set_start_date();
		$this->name = "user_id: $user_id, membership_id: $membership_id, gateway_id: $gateway_id";
		$this->description = $this->name;
	}
	
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
		
		/** Initial status */
		$ms_relationship->gateway_id = $gateway_id;
		$ms_relationship->move_from_id = $move_from_id;
		$ms_relationship->name = "user_id: $user_id, membership_id: $membership_id, gateway_id: $gateway_id";
		$ms_relationship->description = $ms_relationship->name;
		$ms_relationship->set_start_date();
		$ms_relationship->set_status( self::STATUS_PENDING );
		if( 'admin' == $gateway_id ) {
			$ms_relationship->set_status( self::STATUS_ACTIVE );
		}
		else {
			$ms_relationship->create_invoice();
		}

		return $ms_relationship;
	}
	
	/**
	 * Save model.
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
	 * @param array $args
	 * @return number The found count.
	 */
	public static function get_membership_relationship_count( $args = null ) {
		
		$args = self::get_query_args( $args );
		
		$query = new WP_Query( $args );

		return $query->found_posts;
	}
	
	/**
	 * Retrieve membership relationship.
	 * 
	 * @param int $user_id The user id 
	 * @return int $membership_id The membership id.
	 */
	public static function get_membership_relationship( $user_id, $membership_id ) {
		
		$args = self::get_query_args( array( 
				'user_id' => $user_id, 
				'membership_id' => $membership_id, 
				'status' => 'all', 
		) );
		$query = new WP_Query( $args );
		$post = $query->get_posts();
		
		$ms_relationship = null;
		if( ! empty( $post[0] ) ) {
			$ms_relationship = self::load( $post[0] );
		}
		
		return $ms_relationship;
	}
	
	/**
	 * Create default args to search posts.
	 * 
	 * Merge received args to default ones.
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
		return apply_filters( 'ms_model_membership_relationship_get_membership_relationships_args', $args );
	}
	
	/**
	 * Set Membership Relationship start date.
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
	 * @return MS_Model_Membership
	 */
	public function get_membership() {
		return MS_Model_Membership::load( $this->membership_id );
	}
	
	/**
	 * Get how many days in this membership.
	 * 
	 * @return string
	 */
	public function get_current_period() {
		return MS_Helper_Period::subtract_dates( MS_Helper_Period::current_date(), $this->start_date );
	}
	
	/**
	 * Get how many days until this membership trial expires.
	 * @return string
	 */
	public function get_remaining_trial_period() {
		return MS_Helper_Period::subtract_dates( MS_Helper_Period::current_date(), $this->trial_expire_date );
	}
	
	/**
	 * Get how many days until this membership expires.
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
	 * @return MS_Model_Gateway
	 */
	public function get_gateway() {
		
		$gateway = null;
		if( 'admin' != $this->gateway_id ) {
			$gateway = MS_Model_Gateway::load( $this->gateway_id );
		}
		
		return $gateway;
	}
	
	public function create_invoice() {
		
		if( $gateway = $this->get_gateway() ) {
			$membership = $this->get_membership();
			$member = MS_Model_Member::load( $this->user_id );
			$transaction_status = MS_Model_Transaction::STATUS_BILLED;
			$notes = null;
			
			switch( $this->status ) {
				default:
				case self::STATUS_PENDING:
				case self::STATUS_DEACTIVATED:
				case self::STATUS_EXPIRED:
					$due_date = MS_Helper_Period::current_date();
					break;
				case self::STATUS_TRIAL:
					$due_data = $this->trial_expire_date;
					break;
				case self::STATUS_ACTIVE:
				case self::STATUS_CANCELED:
					$due_data = $this->expire_date;
					break;
			}
			
			$pricing = $this->get_pricing_info();
			
			/** Search for existing invoice */
			$transaction = MS_Model_Transaction::get_transaction( $this->user_id, $this->membership_id, $transaction_status );
			if( ! $transaction ) {
				$transaction = MS_Model_Transaction::create_transaction(
						$membership,
						$member,
						$this->gateway_id,
						$transaction_status
				);
			}
			if(  ! MS_Plugin::instance()->addon->multiple_membership && ! empty( $this->move_from_id ) ) {
				if( $pricing['pro_rate'] ) {
					$transaction->discount = $pricing['pro_rate'];
					$notes .= sprintf( __( 'Pro rate discount: %s %s. ', MS_TEXT_DOMAIN ), $transaction->currency, $pricing['pro_rate'] );
				}
			}
			if( ! empty( $pricing['coupon_valid'] ) ) {
				$coupon = $pricing['coupon'];
				$transaction->coupon_id = $coupon->id;
				$transaction->discount += $pricing['discount'];
				$notes .= sprintf( __( 'Coupon %s, discount: %s %s. ', MS_TEXT_DOMAIN ), $coupon->code, $transaction->currency, $pricing['discount'] );
			}
			$transaction->notes = $notes;
			$transaction->due_date = $due_date;
			
			if( self::STATUS_PENDING == $this->status && $membership->trial_period_enabled ) {
				$transaction->amount = $pricing['trial_price'];
			}
			else {
				$transaction->amount = $pricing['price'];
			}
			$transaction->save();
			
			$this->process_transaction( $transaction );
		}
		
	}
	
	/**
	 * Get pricing information.
	 *
	 * Calculates final price of the membership using coupons, pro-rate and trial information.
	 *
	 * @since 4.0
	 *
	 * @access public
	 */
	public function get_pricing_info() {
		$membership = $this->get_membership();
		$member = MS_Model_Member::load( $this->user_id );
		$gateway = $this->get_gateway();
		
		$pricing = array();
		$pricing['currency'] = MS_Plugin::instance()->settings->currency;
		$pricing['move_from_id'] = $this->move_from_id;
		$pricing['discount'] = 0;
		$pricing['pro_rate'] = 0;
		$pricing['trial_price'] = $membership->trial_price;
		$pricing['price'] = $membership->price;
	
		if( ! empty ( $this->move_from_id ) && ! empty( $gateway ) && $gateway->pro_rate ) {
			$pricing['pro_rate'] = $member->membership_relationships[ $this->move_from_id ]->calculate_pro_rate();
		}
	
		if( $coupon = MS_Model_Coupon::get_coupon_application( $member->id, $membership->id ) ) {
			MS_Helper_Debug::log("get_pricing_info");
			$pricing['coupon_valid'] = $coupon->is_valid_coupon( $membership->id );
			$pricing['discount'] =  $coupon->get_discount_value( $membership );
		}
		else {
			MS_Helper_Debug::log("get_pricing_info noooooooo");
			$coupon = new MS_Model_Coupon();
		}
		$pricing['coupon'] = $coupon;
	
		$price = ( $membership->trial_period_enabled ) ? $membership->trial_price : $membership->price;
		if( $membership->trial_period_enabled ) {
			$pricing['trial_price'] = $membership->trial_price - $pricing['discount'] - $pricing['pro_rate'];
			$pricing['trial_price'] = max( $pricing['trial_price'], 0 );
		}
		else {
			$pricing['price'] = $membership->price - $pricing['discount'] - $pricing['pro_rate'];
			$pricing['price'] = max( $pricing['price'], 0 );
		}
		$pricing['total'] = $price - $pricing['discount'] - $pricing['pro_rate'];
	
		return $pricing;
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
				if( $this->coupon_id ) {
					$coupon = MS_Model_Coupon::load( $this->coupon_id );
					$coupon->remove_coupon_application( $member->id, $membership->id );
					$coupon->used++;
					$coupon->save();
				}
				$this->set_status( self::STATUS_ACTIVE );
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
	 * Add transaction.
	 * 
	 * @deprecated
	 * Add transaction related to this membership relationship.
	 * Change status accordinly to transaction status.
	 * 
	 * @param int $transaction_id The Transaction Id to add.
	 */
	public function add_transaction( $transaction ) {
		if( ! in_array( $transaction->id, $this->transaction_ids ) ) {
			$this->transaction_ids[] = $transaction->id;
		}
		if( MS_Model_Transaction::STATUS_PAID == $transaction->status ) {
			$this->set_status( self::STATUS_ACTIVE );
		}
		else {
			switch( $this->get_status() ) {
				case self::STATUS_CANCELED:
				case self::STATUS_DEACTIVATED:
				case self::STATUS_TRIAL:
				case self::STATUS_ACTIVE:
					break;
				default:
				case self::STATUS_PENDING:
					$this->set_status( self::STATUS_PENDING );
					break;
			}
		}
		
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