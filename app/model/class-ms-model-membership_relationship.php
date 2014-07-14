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
	
	const STATUS_TRIAL_EXPIRED = 'trial_expired';

	const STATUS_EXPIRED = 'expired';
	
	const STATUS_DEACTIVATED = 'deactivated';
	
	const STATUS_CANCELED = 'canceled';
	
	protected $membership_id;
	
	protected $user_id;
	
	protected $gateway_id;
	
	protected $start_date;
	
	protected $expire_date;
	
	/**
	 * @deprecated
	 */
	protected $update_date;
	
	protected $trial_expire_date;
	
	protected $trial_period_completed;
	
	protected $status;
	
	/**
	 * Current invoice number.
	 * 
	 * @var $current_invoice_number
	 */
	protected $current_invoice_number = 1;
	
	protected $move_from_id;
	
	private $membership;
	
	/**
	 * Don't save theses fields to usermeta 
	 *
	 * @since 4.0.0
	 */
	protected static $ignore_fields = array( 'membership', 'actions', 'filters' );
	
	
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
				self::STATUS_TRIAL => __( 'Trial', MS_TEXT_DOMAIN ),
				self::STATUS_TRIAL_EXPIRED => __( 'Trial Expired', MS_TEXT_DOMAIN ),
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
		
		/** Try to reuse existing db record to keep history. */
		$ms_relationship = self::get_membership_relationship( $user_id, $membership_id );
		
		/** Not found, create a new one. */
		if( empty( $ms_relationship ) ) {
			$ms_relationship = new self();
			$ms_relationship->membership_id = $membership_id;
			$ms_relationship->user_id = $user_id;
			$ms_relationship->status = self::STATUS_PENDING;
		}
		
		/** Always update these fields. */
		$ms_relationship->move_from_id = $move_from_id;
		$ms_relationship->gateway_id = $gateway_id;
		
		/** Set initial state. */
		switch( $ms_relationship->status ) {
			/**
			 * The invoice/transaction history is keep (using the membership_relationship_id ).
			 */
			case self::STATUS_DEACTIVATED:
// 				$ms_relationship->current_invoice_number = 1;
				$ms_relationship->set_status( self::STATUS_PENDING );
				
			/** Initial status */
			default:
			case self::STATUS_PENDING:
				$ms_relationship->name = "user_id: $user_id, membership_id: $membership_id";
				$ms_relationship->description = $ms_relationship->name;
				$ms_relationship->set_start_date();
				$ms_relationship->trial_period_completed = false;
				break;
			case self::STATUS_TRIAL:
			case self::STATUS_TRIAL_EXPIRED:
			case self::STATUS_ACTIVE:
			case self::STATUS_EXPIRED:
			case self::STATUS_CANCELED:
				/** Once a member or have tried the membership, not eligible to another trial period, unless the relationship is permanetly deleted. */
				$ms_relationship->trial_period_completed = true;
				break;
		}
		
		if( 'admin' == $gateway_id ) {
			$ms_relationship->set_status( self::STATUS_ACTIVE );
		}
		else {
			$ms_relationship->get_status();
		}
		$ms_relationship->save();

		return apply_filters( 'ms_model_membership_relationship_create_ms_relationship', $ms_relationship );
	}
	
	/**
	 * Cancel membership.
	 *
	 * @since 4.0.0
	 */
	public function cancel_membership() {
	
		do_action( 'ms_model_membership_relationship_cancel_membership', $this );
	
		try {
			$gateway = $this->get_gateway();
			$gateway->cancel_membership( $this );

			/** Canceling in trial period will change the expired date. */
			if( self::STATUS_TRIAL == $this->status ) {
				$this->expire_date = $this->trial_expire_date;
// 				$this->trial_expire_date = null;
			}
				
			$this->status = self::STATUS_CANCELED;
			$this->save();
				
			MS_Model_News::save_news( $this, MS_Model_News::TYPE_MS_CANCEL );
		}
		catch (Exception $e) {
				
			MS_Helper_Debug::log( '[Error canceling membership]: '. $e->getMessage() );
		}
	
	}
	
	/**
	 * Deactivate membership.
	 * 
	 * Cancel membership and move to deactivated state.
	 *
	 * @since 4.0.0
	 */
	public function deactivate_membership() {
	
		do_action( 'ms_model_membership_relationship_deactivate_membership', $this );
	
		try {
			$this->cancel_membeshipr();
			
			$this->status = self::STATUS_DEACTIVATED;
			$this->save();
				
			MS_Model_News::save_news( $this,  MS_Model_News::TYPE_MS_DEACTIVATE );
		}
		catch (Exception $e) {
				
			MS_Helper_Debug::log( '[Error canceling membership]: '. $e->getMessage() );
		}
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
	 * Verify if the member can use the trial period.
	 * 
	 *
	 * @since 4.0
	 *
	 */
	public function is_trial_eligible() {
		$membership = $this->get_membership();
		return apply_filter( 'ms_model_membership_relationship_is_trial_eligible', ! $this->trial_period_completed && $membership->trial_period_enabled );
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
		$this->trial_expire_date = null;
		
		if( ! empty( $start_date ) ) {
			$this->start_date = $start_date;
		}
		elseif( MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE == $membership->membership_type ) {
			$this->start_date = $membership->period_date_start;
		}
		else {
			$this->start_date = MS_Helper_Period::current_date();
		}
	}
	
	/**
	 * Set trial expire date.
	 * 
	 * Validate to a date greater than start date.
	 * 
	 * @since 4.0
	 * @param string $trial_expire_date
	 */
	public function set_trial_expire_date( $trial_expire_date = null ) {
		if( ! empty( $trial_expire_date ) && strtotime( $trial_expire_date ) >= strtotime( $this->start_date ) ) {
			$this->trial_expire_date = $trial_expire_date;
		}
		else {
			$this->trial_expire_date = $this->calc_trial_expire_date( $this->start_date );
		}
		
	}
	
	/**
	 * Set trial expire date.
	 * 
	 * Validate to a date greater than start date and trial expire date.

	 * @since 4.0
	 * @param string $trial_expire_date
	 */
	public function set_expire_date( $expire_date = null ) {
		if( ! empty( $expire_date ) && strtotime( $expire_date ) >= strtotime( $this->start_date ) && 
			( ! empty( $this->trial_expire_date) && strtotime( $expire_date ) >= strtotime( $this->trial_expire_date ) ) ) {
			$this->expire_date = $expire_date;
		}
		else {
			$this->expire_date = $this->calc_expire_date( $this->start_date );
		}
		
	}
	
	/**
	 * Calculate trial expire date.
	 * 
	 * Based in the membership definition.
	 * 
	 * @param string $start_date
	 * @return string date
	 */
	public function calc_trial_expire_date( $start_date = null ) {
		$membership = $this->get_membership();
		$trial_expire_date = null;
		
		if( empty( $start_date ) ) {
			$start_date = $this->start_date;
		}
		
		if( $this->is_trial_eligible() ) {
			if( MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE == $membership->membership_type ) {
				$trial_expire_date = MS_Helper_Period::add_interval( $membership->trial_period['period_unit'], $membership->trial_period['period_type'] , $membership->period_date_start );
			}
			else {
				$trial_expire_date = MS_Helper_Period::add_interval( $membership->trial_period['period_unit'], $membership->trial_period['period_type'] , $start_date );
			}
		}
		else {
			$trial_expire_date = $start_date;
		}
		return apply_filters( 'ms_model_membership_relationship_calc_trial_expire_date', $trial_expire_date );
	}
	
	/**
	 * Calculate expire date.
	 * 
	 * Based in the membership definition
	 * 
	 * @param string $start_date
	 * @return string date
	 */
	public function calc_expire_date( $start_date = null ) {
		$membership = $this->get_membership();
		$gateway = $this->get_gateway();

		$trial_expire_date = $this->calc_trial_expire_date( $start_date );
		$expire_date = null;
		
		/** When in trial period and gateway does not send automatic recurring payment, the expire date is equal to trial expire date. */
		if( $this->is_trial_eligible() && $gateway->manual_payment ) {
			$expire_date = $trial_expire_date;
		}
		else {
			switch( $membership->membership_type ){
				case MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT:
					$expire_date = false;
					break;
				case MS_Model_Membership::MEMBERSHIP_TYPE_FINITE:
					$expire_date = MS_Helper_Period::add_interval( $membership->period['period_unit'], $membership->period['period_type'], $trial_expire_date );
					break;
				case MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE:
					$expire_date = $membership->period_date_end;
					break;
				case MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING:
					$expire_date = MS_Helper_Period::add_interval( $membership->pay_cycle_period['period_unit'], $membership->pay_cycle_period['period_type'], $trial_expire_date );
					break;
			}
		}

		return apply_filters( 'ms_model_membership_relationship_calc_expire_date', $expire_date );
	}
	
	/**
	 * Configure the membership period dates.
	 *
	 * Set initial membership period or renew periods.
	 *
	 * @since 4.0.0
	 */
	public function config_period() {
	
		do_action( 'ms_model_membership_relationship_config_period', $this );
	
		switch( $this->status ) {
			case self::STATUS_DEACTIVATED:
			case self::STATUS_PENDING:
				/** Set initial start, trial and expire date. */
				$this->set_start_date();
				$this->set_trial_expire_date();
				$this->set_expire_date();
				break;
			case self::STATUS_EXPIRED:
			case self::STATUS_CANCELED:
			case self::STATUS_ACTIVE:
				$this->trial_period_completed = true;
				/** Renew period */
				$this->expire_date = $this->calc_expire_date( $this->expire_date );
				break;
			case self::STATUS_TRIAL:
			case self::STATUS_TRIAL_EXPIRED:
				$this->trial_period_completed = true;
				/** Confirm expire date. */
				$this->expire_date = $this->calc_expire_date( $this->start_date );
				break;
			default:
				do_action( 'ms_model_membership_relationship_config_period_for_status_' . $this->status, $this );
				break;
		}
	}
	
	/**
	 * Get Membership model.
	 * 
	 * @since 4.0
	 * @return MS_Model_Membership
	 */
	public function get_membership() {
		if( empty ( $this->membership->id ) ) {
			$this->membership = MS_Model_Membership::load( $this->membership_id ); 
		}
		return apply_filters( 'ms_model_membership_relationship_get_membership', $this->membership );
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
		
		return MS_Model_Gateway::factory( $this->gateway_id );
	}
	
	/**
	 * Get current membership invoice.
	 * 
	 * @since 4.0.0
	 * 
	 * @return MS_Model_Invoice
	 */
	public function get_current_invoice() {
		return MS_Model_Invoice::get_current_invoice( $this );
	}
	
	/**
	 * Get next membership invoice.
	 * 
	 * @since 4.0.0
	 * 
	 * @return MS_Model_Invoice
	 */
	public function get_next_invoice() {
		return MS_Model_Invoice::get_next_invoice( $this );
	}
	
	/**
	 * Get previous membership invoice.
	 * 
	 * @since 4.0.0
	 * 
	 * @return MS_Model_Invoice
	 */
	public function get_previous_invoice() {
		return MS_Model_Invoice::get_previous_invoice( $this );
	}
	
	/**
	 * Get payment information description.
	 *
	 * A more .
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @return boolean
	 */
	public function get_payment_description() {
	
		$currency = MS_Plugin::instance()->settings->currency;
	
		$membership = $this->get_membership();
		$desc = sprintf( __( 'You will pay %s %s ', MS_TEXT_DOMAIN ), $currency, number_format( $membership->price, 2 ) );

		switch( $membership->membership_type ){
			case MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT:
				$desc .= __( 'for permanent access.', MS_TEXT_DOMAIN );
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_FINITE:
				$desc .= sprintf( __( 'for access until %s.', MS_TEXT_DOMAIN ), $this->expire_date );
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE:
				$desc .= sprintf( __( 'to access from %s to %s.', MS_TEXT_DOMAIN ), $this->start_date, $this->expire_date );
				break;
			case MS_Model_Membership::MEMBERSHIP_TYPE_RECURRING:
				$desc .= sprintf( __( 'each %s %s.', MS_TEXT_DOMAIN ), $membership->pay_cycle_period['period_unit'], $membership->pay_cycle_period['period_type'] );
				break;
		}
	
		if( $this->is_trial_eligible() ) {
			$desc .= sprintf( __( ' <br />In the trial period of %s %s, you will pay %s %s.', MS_TEXT_DOMAIN ),
					$membership->trial_period['period_unit'],
					$membership->trial_period['period_type'],
					$currency,
					number_format( $membership->trial_price, 2 )
			);
		}
	
		return $desc;
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
		
		/** These status are not validated, and promptly assigned */
		$allowed_status = array( 
				self::STATUS_DEACTIVATED, 
				self::STATUS_PENDING,
				self::STATUS_CANCELED,
				self::STATUS_TRIAL_EXPIRED,
		);
		
		if( ! in_array( $status, $allowed_status ) ){
			$status = $this->calculate_status();
		}

		if( $status != $this->status && array_key_exists( $status, self::get_status_types() ) ) {
			/** signup */
			if( 'admin' != $this->gateway_id && self::STATUS_PENDING == $this->status && in_array( $status, array( self::STATUS_TRIAL, self::STATUS_ACTIVE ) ) ) {
				MS_Model_News::save_news( $this,  MS_Model_News::TYPE_MS_SIGNUP );
			}
				
			$this->status = apply_filters( 'ms_model_membership_relationship_set_status', $status );
			$this->save();
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

		/** No further validations for these status */
		$allowed_status = array(
				self::STATUS_DEACTIVATED,
				self::STATUS_PENDING,
				self::STATUS_TRIAL_EXPIRED,
		);
		
		if( ! in_array( $this->status, $allowed_status ) ) {
			$status = $this->calculate_status();
		}
		
		if( $status != $this->status && array_key_exists( $status, self::get_status_types() ) ) {
			$this->status = $status;
			$this->save();
		}
		
		return apply_filters( 'membership_model_membership_relationship_get_status', $this->status, $this );
	}
	
	/**
	 * Calculate the membership status.
	 * 
	 * Calculate status for the membership verifying the start date, trial exire date and expire date.
	 * 
	 * @since 4.0.0
	 */
	public function calculate_status() {
		$membership = $this->get_membership();
		$status = null;
		if( ! empty( $this->trial_expire_date ) && strtotime( $this->trial_expire_date ) >= strtotime( MS_Helper_Period::current_date() ) ) {
			$status = self::STATUS_TRIAL;
		}
		elseif( ! empty( $this->trial_expire_date ) && $this->trial_expire_date == $this->expire_date &&
				strtotime( $this->trial_expire_date ) > strtotime( MS_Helper_Period::current_date() ) ) {
			$status = self::STATUS_TRIAL_EXPIRED;
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
			/** For expired memberships or MEMBERSHIP_TYPE_PERMANENT deactivate it immediately. */
			if( self::STATUS_EXPIRED == $status || MS_Model_Membership::MEMBERSHIP_TYPE_PERMANENT == $membership->membership_type ) {
				$status = self::STATUS_DEACTIVATED;
			}
			else {
				$status = self::STATUS_CANCELED;
			}
		}
		
		return apply_filters( 'membership_model_membership_relationship_validate_status', $status, $this );
	}
	
	/**
	 * Check membership status.
	 *
	 * Execute actions when time/period condition are met.
	 * E.g. change membership status, add communication to queue, create invoices.
	 *
	 * @since 4.0.0
	 *
	 * @access public
	 */
	public function check_membership_status() {
	
		$comms = MS_Model_Communication::load_communications();
		$invoice_before_days = 5;//@todo create a setting to configure this period.
		$deactivate_expired_after_days = 30; //@todo create a setting to configure this period.
		$deactivate_pending_after_days = 30; //@todo create a setting to configure this period.
		$deactivate_trial_expired_after_days = 5; //@todo create a setting to configure this period.
	
		$expire = $this->get_remaining_period();
		$trial_expire = $this->get_remaining_trial_period();
		
		do_action( 'ms_model_plugin_check_membership_status_' . $this->status, $this, $expire );
		switch( $this->status ) {
			case self::STATUS_PENDING:
					break;
			case self::STATUS_TRIAL:
				/** Send trial end communication. */
				$comm = $comms[ MS_Model_Communication::COMM_TYPE_BEFORE_TRIAL_FINISHES ];
				if( $comm->enabled ) {
					$days = MS_Helper_Period::get_period_in_days( $comm->period );
					if( ! $trial_expire->invert && $days == $trial_expire->days ) {
						$comm->add_to_queue( $this->id );
					}
				}
				break;
			case self::STATUS_TRIAL_EXPIRED:
				$invoice = $this->get_current_invoice();
					
				/** Request payment to the gateway (for gateways that allows it). */
				$gateway = $this->get_gateway();
				$gateway->request_payment( $this );
					
				/** Deactivate expired memberships after a period of time. */
				if( $trial_expire->invert && $trial_expire->days > $deactivate_trial_expired_after_days ) {
					$this->set_status( self::STATUS_DEACTIVATED );

					/** Move membership to configured membership. */
					$membership = $this->get_membership();
					if( MS_Model_Membership::is_valid_membership( $membership->on_end_membership_id ) ) {
						$member = MS_Model_Member::load( $this->user_id );
						$member->add_membership( $membership->on_end_membership_id );
					}
				}
				break;
				/**
				 * Send period end communication.
				 * Deactivate expired memberships after $deactivate_expired_after_days.
				 * Create invoice.
				 */
			case self::STATUS_ACTIVE:
			case self::STATUS_EXPIRED:
			case self::STATUS_CANCELED:
				do_action( 'ms_model_plugin_check_membership_status_' . $this->status, $this );
					
				/** Create next invoice before expire date.*/
				if( ! $expire->invert && $expire->days < $invoice_before_days ) {
					$invoice = $this->get_next_invoice();
				}
					
				/** Configure communication messages.*/
				$comms_active = array(
						$comms[ MS_Model_Communication::COMM_TYPE_BEFORE_FINISHES ],
						$comms[ MS_Model_Communication::COMM_TYPE_FINISHED ],
						$comms[ MS_Model_Communication::COMM_TYPE_AFTER_FINISHES ],
				);
				foreach( $comms_active as $comm ) {
					if( $comm->enabled ) {
						$days = MS_Helper_Period::get_period_in_days( $comm->period );
						if( ! $expire->invert && $days == $expire->days ) {
							$comm->add_to_queue( $this->id );
						}
					}
				}
					
				/** Request payment to the gateway (for gateways that allows it) when time comes (expired). */
				if( $expire->invert ) {
					$gateway = $this->get_gateway();
					$gateway->request_payment( $this );
					/** Refresh status after payment */
					$expire = $this->get_remaining_period();
				}
					
				/** Deactivate expired memberships after a period of time. */
				if( $expire->invert && $expire->days > $deactivate_expired_after_days ) {
					$this->set_status( self::STATUS_DEACTIVATED );

					/** Move membership to configured membership. */
					$membership = $this->get_membership();
					if( MS_Model_Membership::is_valid_membership( $membership->on_end_membership_id ) ) {
						$member = MS_Model_Member::load( $this->user_id );
						$member->add_membership( $membership->on_end_membership_id );
					}
				}
				break;
					
				/** Deactivated status won't appear here, but it can be changed in get_membership_relationships $args.*/
			case self::STATUS_DEACTIVATED:
			default:
				do_action( 'ms_model_plugin_check_membership_status_' . $this->status, $this );
				break;
		}
		foreach( $comms as $comm ) {
			$comm->save();
		}
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