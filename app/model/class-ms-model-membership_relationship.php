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
	
	const MEMBERSHIP_STATUS_ACTIVE = 'active';
	
	const MEMBERSHIP_STATUS_TRIAL = 'trial';

	const MEMBERSHIP_STATUS_EXPIRED = 'expired';
	
	const MEMBERSHIP_STATUS_DEACTIVATED = 'deactivated';
	
	const MEMBERSHIP_STATUS_CANCELED = 'canceled';
	
	const MEMBERSHIP_ACTION_SIGNUP = 'membership_signup';
	
	const MEMBERSHIP_ACTION_MOVE = 'membership_move';
	
	const MEMBERSHIP_ACTION_CANCEL = 'membership_cancel';
	
	const MEMBERSHIP_ACTION_RENEW = 'membership_renew';

	protected $membership_id;
	
	protected $transaction_id;
	
	protected $user_id;
	
	protected $gateway_id;
	
	protected $start_date;
	
	protected $expire_date;
	
	protected $update_date;
	
	protected $trial_expire_date;
	
	protected $status;
	
	public function __construct( $membership_id = 0, $user_id = 0, $gateway_id = 0, $transaction_id = 0 ) {
		
		if( ! MS_Model_Membership::is_valid_membership( $membership_id ) ) {
			return;
		}
		$this->membership_id = $membership_id;
		$this->user_id = $user_id;
		$this->gateway_id = $gateway_id;
		$this->transaction_id = $transaction_id;
		$this->set_start_date();
		$this->name = "user_id: $user_id, membership_id: $membership_id, gateway_id: $gateway_id, transaction_id: $transaction_id";
		$this->description = $this->name;
		$this->get_status();
	}

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
				$membership_relationships[ $membership_relationship->membership_id ] = $membership_relationship;
			}
		}
		return apply_filters( 'ms_model_membership_relationship_get_membership_relationships', $membership_relationships, $args );
	}
	
	public static function get_membership_relationship_count( $args = null ) {
		
		$args = self::get_query_args( $args );
		
		$query = new WP_Query( $args );

		return $query->found_posts;
	}
	
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
			if( 'all' != $args['status'] ) {
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
					'value' => self::MEMBERSHIP_STATUS_DEACTIVATED,
					'compare' => 'NOT LIKE',
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
	public function get_start_date() {
		return $this->start_date;
	}
	
	public function get_membership() {
		return MS_Model_Membership::load( $this->membership_id );
	}
	
	public function move( $move_from_id, $move_to_id ) {
		$membership = MS_Model_Membership::load( $move_to_id );
		
		$this->membership_id = $move_to_id;
		$this->set_start_date( $this->start_date );
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

	public function calculate_pro_rate() {
		$value = 0;
		$trial_value = 0;
		$membership = $this->get_membership();
		if( MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT != $membership->membership_type ) {
			switch( $this->get_status() ) {
				case self::MEMBERSHIP_STATUS_TRIAL:
					$remaining = $this->get_remaining_trial_period();
					$total = MS_Helper_Period::subtract_dates(  MS_Helper_Period::current_date(), $this->start_date );
					$trial_value = $remaining->days / $total->days;
					$trial_value *= $membership->trial_price;
				case self::MEMBERSHIP_STATUS_ACTIVE:
				case self::MEMBERSHIP_STATUS_CANCELED:
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
	 * Get membership status.
	 * 
	 *  Verifies start and end date of a membership.
	 */
	public function get_status() {

		if( self::MEMBERSHIP_STATUS_DEACTIVATED == $this->status ) {
			$status = self::MEMBERSHIP_STATUS_DEACTIVATED;
		}
		else {
			$membership = $this->get_membership();
			if( ! empty( $this->trial_expire_date ) && strtotime( $this->trial_expire_date ) >= strtotime( MS_Helper_Period::current_date() ) ) {
				$status = self::MEMBERSHIP_STATUS_TRIAL;
			}
			elseif( empty( $this->expire_date ) ) {
				$status = self::MEMBERSHIP_STATUS_ACTIVE;
			}
			elseif( ! empty( $this->expire_date ) && strtotime( $this->expire_date ) >= strtotime( MS_Helper_Period::current_date() ) ) {
				$status = self::MEMBERSHIP_STATUS_ACTIVE;
			}
			else {
				$status = self::MEMBERSHIP_STATUS_EXPIRED;
			}
			/**
			 * If user canceled the membership before expire date, still have access until expires.
			 */
			if( self::MEMBERSHIP_STATUS_CANCELED == $this->status && self::MEMBERSHIP_STATUS_EXPIRED != $status ) {
				$status = self::MEMBERSHIP_STATUS_CANCELED; 
			}
			$this->status = $status;
		}
		
		return apply_filters( 'membership_model_membership_relationship_status', $status, $this );
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
				return $this->$property = $this->get_status();
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
				default:
					$this->$property = $value;
					break;
			}
		}
	}
	
}