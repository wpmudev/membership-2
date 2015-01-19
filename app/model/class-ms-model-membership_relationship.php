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
 * Membership Relationship model.
 *
 * Persisted by parent class MS_Model_Custom_Post_Type.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Membership_Relationship extends MS_Model_Custom_Post_Type {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 * @var string $POST_TYPE
	 */
	public static $POST_TYPE = 'ms_relationship';
	public $post_type = 'ms_relationship';

	/**
	 * Membership Relationship Status constants.
	 *
	 * @since 1.0.0
	 * @see $status property.
	 * @var string $status The status
	 */
	const STATUS_PENDING = 'pending';
	const STATUS_ACTIVE = 'active';
	const STATUS_TRIAL = 'trial';
	const STATUS_TRIAL_EXPIRED = 'trial_expired';
	const STATUS_EXPIRED = 'expired';
	const STATUS_DEACTIVATED = 'deactivated';
	const STATUS_CANCELED = 'canceled';

	/**
	 * The Membership ID.
	 *
	 * @since 1.0.0
	 * @var string $membership_id
	 */
	protected $membership_id;

	/**
	 * The Gateway ID.
	 *
	 * The gateway used to make payments.
	 *
	 * @since 1.0.0
	 * @var string $gateway_id
	 */
	protected $gateway_id;

	/**
	 * The start date of the membership relationship.
	 *
	 * @since 1.0.0
	 * @var string $start_date
	 */
	protected $start_date;

	/**
	 * The expire date of the membership relationship.
	 *
	 * @since 1.0.0
	 * @var string $expire_date
	 */
	protected $expire_date;

	/**
	 * The trial expire date of the membership relationship.
	 *
	 * @since 1.0.0
	 * @var string $trial_expire_date
	 */
	protected $trial_expire_date;

	/**
	 * Trial period completed flag.
	 *
	 * Indicates if already used a trial period and can't have another trial period.
	 *
	 * @since 1.0.0
	 * @var string $trial_period_completed
	 */
	protected $trial_period_completed;

	/**
	 * The status of the membership relationship.
	 *
	 * @since 1.0.0
	 * @var string $status
	 */
	protected $status;

	/**
	 * Current invoice number.
	 *
	 * @since 1.0.0
	 * @var $current_invoice_number
	 */
	protected $current_invoice_number = 1;

	/**
	 * The moving/change/downgrade/upgrade from membership ID.
	 *
	 * @since 1.0.0
	 * @var string $move_from_id
	 */
	protected $move_from_id;

	/**
	 * The related membership model object.
	 *
	 * @since 1.0.0
	 * @var MS_Model_Membership $membership
	 */
	private $membership;

	/**
	 * Don't persist this fields.
	 *
	 * @since 1.0.0
	 * @var string[] The fields to ignore when persisting.
	 */
	static public $ignore_fields = array(
		'membership',
		'post_type',
	);

	/**
	 * Return existing status types and names.
	 *
	 * @since 1.0.0
	 * @return array{
	 *     Return array of ( $type => name );
	 *     @type string $type The status type.
	 *     @type string $name The status name.
	 * }
	 */
	public static function get_status_types() {
		$status_types = array(
			self::STATUS_PENDING => __( 'Pending', MS_TEXT_DOMAIN ),
			self::STATUS_ACTIVE => __( 'Active', MS_TEXT_DOMAIN ),
			self::STATUS_TRIAL => __( 'Trial', MS_TEXT_DOMAIN ),
			self::STATUS_TRIAL_EXPIRED => __( 'Trial Expired', MS_TEXT_DOMAIN ),
			self::STATUS_EXPIRED => __( 'Expired', MS_TEXT_DOMAIN ),
			self::STATUS_DEACTIVATED => __( 'Deactivated', MS_TEXT_DOMAIN ),
			self::STATUS_CANCELED => __( 'Canceled', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_model_membership_relationship_get_status_types',
			$status_types
		);
	}

	/**
	 * Create a new membership relationship.
	 *
	 * Search for existing relationship (unique object), creating if not exists.
	 * Set initial status.
	 *
	 * @since 1.0.0
	 * @return MS_Model_Membership_Relationship The created relationship.
	 */
	public static function create_ms_relationship(
		$membership_id = 0,
		$user_id = 0,
		$gateway_id = 'admin',
		$move_from_id = 0
	) {
		do_action(
			'ms_model_membership_relationship_create_ms_relationship_before',
			$membership_id,
			$user_id,
			$gateway_id,
			$move_from_id
		);

		if ( MS_Model_Membership::is_valid_membership( $membership_id ) ) {
			$ms_relationship = self::_create_ms_relationship(
				$membership_id,
				$user_id,
				$gateway_id,
				$move_from_id
			);
		}
		else {
			$ms_relationship = null;
			MS_Helper_Debug::log(
				'Invalid membership_id: ' .
				"$membership_id, ms_relationship not created for $user_id, $gateway_id, $move_from_id"
			);
			MS_Helper_Debug::debug_trace();
		}

		return apply_filters(
			'ms_model_membership_relationship_create_ms_relationship',
			$ms_relationship,
			$membership_id,
			$user_id,
			$gateway_id,
			$move_from_id
		);
	}

	/**
	 * Helper function called by create_ms_relationship()
	 *
	 * @since  1.0.0
	 *
	 * @return MS_Model_Membership_Relationship The created relationship.
	 */
	private static function _create_ms_relationship( $membership_id, $user_id, $gateway_id, $move_from_id ) {
		// Try to reuse existing db record to keep history.
		$ms_relationship = self::get_membership_relationship( $user_id, $membership_id );

		// Not found, create a new one.
		if ( empty( $ms_relationship ) ) {
			$ms_relationship = MS_Factory::create( 'MS_Model_Membership_Relationship' );
			$ms_relationship->membership_id = $membership_id;
			$ms_relationship->user_id = $user_id;
			$ms_relationship->status = self::STATUS_PENDING;
		}

		// Always update these fields.
		$ms_relationship->move_from_id = $move_from_id;
		$ms_relationship->gateway_id = $gateway_id;

		// Set initial state.
		switch ( $ms_relationship->status ) {
			case self::STATUS_DEACTIVATED:
				$ms_relationship->status = self::STATUS_PENDING;
				break;

			case self::STATUS_TRIAL:
			case self::STATUS_TRIAL_EXPIRED:
			case self::STATUS_ACTIVE:
			case self::STATUS_EXPIRED:
			case self::STATUS_CANCELED:
				/* Once a member or have tried the membership, not
				 * eligible to another trial period, unless the relationship
				 * is permanetly deleted.
				 */
				$ms_relationship->trial_period_completed = true;
				break;

			default:
			case self::STATUS_PENDING:
				// Initial status
				$ms_relationship->name = "user_id: $user_id, membership_id: $membership_id";
				$ms_relationship->description = $ms_relationship->name;
				$ms_relationship->set_start_date();
				$ms_relationship->trial_period_completed = false;
				break;
		}

		if ( 'admin' == $gateway_id ) {
			$ms_relationship->config_period();
			$ms_relationship->status = self::STATUS_ACTIVE;
		} else {
			// Force status calculation.
			$ms_relationship->get_status();
		}
		$ms_relationship->save();

		return $ms_relationship;
	}

	/**
	 * Cancel membership.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $generate_event Optional. Defines if cancel events are generated.
	 */
	public function cancel_membership( $generate_event = true ) {
		do_action(
			'ms_model_membership_relationship_cancel_membership_before',
			$this,
			$generate_event
		);

		try {
			// Canceling in trial period -> change the expired date.
			if ( self::STATUS_TRIAL == $this->status ) {
				$this->expire_date = $this->trial_expire_date;
			}

			$this->status = self::STATUS_CANCELED;
			$this->status = $this->calculate_status();
			$this->save();

			// Cancel subscription in the gateway.
			if ( $gateway = $this->get_gateway() ) {
				$gateway->cancel_membership( $this );
			}

			if ( $generate_event ) {
				MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_CANCELED, $this );
			}
		}
		catch (Exception $e) {
			MS_Helper_Debug::log( '[Error canceling membership]: '. $e->getMessage() );
		}

		do_action(
			'ms_model_membership_relationship_cancel_membership_after',
			$this,
			$generate_event
		);
	}

	/**
	 * Deactivate membership.
	 *
	 * Cancel membership and move to deactivated state.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $generate_event Optional. Defines if cancel events are generated.
	 */
	public function deactivate_membership( $generate_event = true ) {
		do_action(
			'ms_model_membership_relationship_deactivate_membership_before',
			$this,
			$generate_event
		);

		try {
			$this->cancel_membership( false );
			$this->status = self::STATUS_DEACTIVATED;
			$this->save();
			if ( $generate_event ) {
				MS_Model_Event::save_event(
					MS_Model_Event::TYPE_MS_DEACTIVATED,
					$this
				);
			}
		}
		catch( Exception $e ) {
			MS_Helper_Debug::log(
				'[Error deactivating membership]: '. $e->getMessage()
			);
		}

		do_action(
			'ms_model_membership_relationship_deactivate_membership_after',
			$this,
			$generate_event
		);
	}

	/**
	 * Save model.
	 *
	 * Only saves if is not admin user and not a visitor.
	 * Don't save visitor memberships/protected content (auto assigned).
	 *
	 * @since 1.0.0
	 */
	public function save() {
		do_action( 'ms_model_membership_relationship_save_before', $this );

		if ( ! empty( $this->user_id )
			&& ! MS_Model_Member::is_admin_user( $this->user_id )
		) {
			$membership = $this->get_membership();
			if ( ! $membership->is_special() ) {
				parent::save();
			}
		}

		do_action( 'ms_model_membership_relationship_after', $this );
	}

	/**
	 * Retrieve membership relationships.
	 *
	 * By default returns a list of relationships that are not "pending" or
	 * "deactivated". To get a list of all relationships use this:
	 * $args = array( 'status' => 'all' )
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Membership_Relationship[] The array of membership relationships.
	 */
	public static function get_membership_relationships( $args = null ) {
		$args = self::get_query_args( $args );

		$query = new WP_Query( $args );
		$posts = $query->get_posts();
		$ms_relationships = array();

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post_id ) {
				$ms_relationship = MS_Factory::load(
					'MS_Model_Membership_Relationship',
					$post_id
				);

				if ( ! empty( $args['author'] ) ) {
					$ms_relationships[ $ms_relationship->membership_id ] = $ms_relationship;
				} else {
					$ms_relationships[ $post_id ] = $ms_relationship;
				}
			}
		}

		return apply_filters(
			'ms_model_membership_relationship_get_membership_relationships',
			$ms_relationships,
			$args
		);
	}

	/**
	 * Retrieve membership relationship count.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The membership relationship count.
	 */
	public static function get_membership_relationship_count( $args = null ) {
		$args = apply_filters(
			'ms_model_membership_relationship_get_membership_relationship_count_args',
			self::get_query_args( $args )
		);
		$query = new WP_Query( $args );
		$count = $query->found_posts;

		return apply_filters(
			'ms_model_membership_relationship_get_membership_relationship_count',
			$count,
			$args
		);
	}

	/**
	 * Retrieve membership relationship.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id The user id
	 * @return int $membership_id The membership id.
	 */
	public static function get_membership_relationship( $user_id, $membership_id ) {
		$args = apply_filters(
			'ms_model_membership_relationship_get_membership_relationship_args',
			self::get_query_args(
				array(
					'user_id' => $user_id,
					'membership_id' => $membership_id,
					'status' => 'all',
				)
			)
		);

		$query = new WP_Query( $args );
		$post = $query->get_posts();

		$ms_relationship = null;

		if ( ! empty( $post[0] ) ) {
			$ms_relationship = MS_Factory::load(
				'MS_Model_Membership_Relationship',
				$post[0]
			);
		}

		return apply_filters(
			'ms_model_membership_relationship_get_membership_relationship',
			$ms_relationship,
			$args
		);
	}

	/**
	 * Create default args to search posts.
	 *
	 * Merge received args to default ones.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The args.
	 */
	public static function get_query_args( $args = null ) {
		$defaults = apply_filters(
			'ms_model_membership_relationship_get_query_args_defaults',
			array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
				'fields' => 'ids',
				'nopaging' => true,
			)
		);

		$args = wp_parse_args( $args, $defaults );

		// Set filter arguments
		if ( ! empty( $args['user_id'] ) ) {
			$args['author'] = $args['user_id'];
			unset( $args['user_id'] );
		}

		if ( ! empty( $args['membership_id'] ) ) {
			$args['meta_query']['membership_id'] = array(
				'key' => 'membership_id',
				'value' => $args['membership_id'],
			);
			unset( $args['membership_id'] );
		}

		if ( ! empty( $args['gateway_id'] ) ) {
			$args['meta_query']['gateway_id'] = array(
				'key' => 'gateway_id',
				'value' => $args['gateway_id'],
			);
			unset( $args['gateway_id'] );
		}

		if ( ! empty( $args['status'] ) ) {
			// Allowed status filters:
			// 'valid' .. all status values except Deactivated
			// <any other value except 'all'>
			if ( 'valid' === $args['status'] ) {
				$args['meta_query']['status'] = array(
					'key' => 'status',
					'value' => self::STATUS_DEACTIVATED,
					'compare' => 'NOT LIKE',
				);
			} elseif ( 'all' !== $args['status'] ) {
				$args['meta_query']['status'] = array(
					'key' => 'status',
					'value' => $args['status'],
					'compare' => 'LIKE',
				);
			}

			// This is only reached when status === 'all'
			unset( $args['status'] );
		} else {
			$args['meta_query']['status'] = array(
				'key' => 'status',
				'value' => array( self::STATUS_DEACTIVATED, self::STATUS_PENDING ),
				'compare' => 'NOT IN',
			);
		}

		return apply_filters(
			'ms_model_membership_relationship_get_query_args',
			$args,
			$defaults
		);
	}

	/**
	 * Verify if the member can use the trial period.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if trial eligible.
	 */
	public function is_trial_eligible() {
		$membership = $this->get_membership();
		$trial_eligible_status = apply_filters(
			'ms_model_membership_relationship_trial_eligible_status',
			array(
				MS_Model_Membership_Relationship::STATUS_PENDING,
				MS_Model_Membership_Relationship::STATUS_DEACTIVATED,
			)
		);

		$eligible = false;
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL )
			&& in_array( $this->status, $trial_eligible_status )
			&& ! $this->trial_period_completed
			&& $membership->trial_period_enabled
		) {

			$eligible = true;
		}

		return apply_filters(
			'ms_model_membership_relationship_is_trial_eligible',
			$eligible,
			$this
		);
	}

	/**
	 * Set Membership Relationship start date.
	 *
	 * Also updates trial and expire date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $start_date Optional. The start date to set. Default will be calculated.
	 */
	public function set_start_date( $start_date = null ) {
		$membership = $this->get_membership();
		$this->trial_expire_date = null;

		if ( ! empty( $start_date ) ) {
			$this->start_date = $start_date;
		}
		elseif ( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
			$this->start_date = $membership->period_date_start;
		}
		else {
			$this->start_date = MS_Helper_Period::current_date();
		}

		$this->start_date = apply_filters(
			'ms_model_membership_relationship_set_start_date',
			$this->start_date,
			$start_date,
			$this
		);
	}

	/**
	 * Set trial expire date.
	 *
	 * Validate to a date greater than start date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $trial_expire_date Optional. The trial expire date to set. Default will be calculated.
	 */
	public function set_trial_expire_date( $trial_expire_date = null ) {
		if ( ! empty( $trial_expire_date )
			&& strtotime( $trial_expire_date ) >= strtotime( $this->start_date )
		) {
			$this->trial_expire_date = $trial_expire_date;
		}
		else {
			$this->trial_expire_date = $this->calc_trial_expire_date( $this->start_date );
		}

		$this->trial_expire_date = apply_filters(
			'ms_model_membership_relationship_set_trial_start_date',
			$this->trial_expire_date,
			$trial_expire_date,
			$this
		);
	}

	/**
	 * Set trial expire date.
	 *
	 * Validate to a date greater than start date and trial expire date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $expire_date Optional. The expire date to set. Default will be calculated.
	 */
	public function set_expire_date( $expire_date = null ) {
		if ( ! empty( $expire_date )
			&& strtotime( $expire_date ) >= strtotime( $this->start_date )
			&& ( ! empty( $this->trial_expire_date)
				&& strtotime( $expire_date ) >= strtotime( $this->trial_expire_date )
			)
		) {
			$this->expire_date = $expire_date;
		}
		else {
			$this->expire_date = $this->calc_expire_date( $this->start_date );
		}

		$this->expire_date = apply_filters(
			'ms_model_membership_relationship_set_expire_date',
			$this->expire_date,
			$expire_date,
			$this
		);
	}

	/**
	 * Calculate trial expire date.
	 *
	 * Based in the membership definition.
	 *
	 * @since 1.0.0
	 *
	 * @param string $start_date Optional. The start date to calculate date from.
	 * @return string The calculated trial expire date.
	 */
	public function calc_trial_expire_date( $start_date = null ) {
		$membership = $this->get_membership();
		$trial_expire_date = null;

		if ( empty( $start_date ) ) {
			$start_date = $this->start_date;
		}

		if ( $this->is_trial_eligible() ) {
			if ( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
				$period_unit = MS_Helper_Period::get_period_value(
					$membership->trial_period,
					'period_unit'
				);
				$period_type = MS_Helper_Period::get_period_value(
					$membership->trial_period,
					'period_type'
				);
				$trial_expire_date = MS_Helper_Period::add_interval(
					$period_unit,
					$period_type,
					$membership->period_date_start
				);
			}
			else {
				$period_unit = MS_Helper_Period::get_period_value(
					$membership->trial_period,
					'period_unit'
				);
				$period_type = MS_Helper_Period::get_period_value(
					$membership->trial_period,
					'period_type'
				);
				$trial_expire_date = MS_Helper_Period::add_interval(
					$period_unit,
					$period_type,
					$start_date
				);
			}
		}
		else {
			$trial_expire_date = $start_date;
		}

		return apply_filters(
			'ms_model_membership_relationship_calc_trial_expire_date',
			$trial_expire_date,
			$start_date,
			$this
		);
	}

	/**
	 * Calculate expire date.
	 *
	 * Based in the membership definition
	 *
	 * @since 1.0.0
	 *
	 * @param string $start_date Optional. The start date to calculate date from.
	 * @return string The calculated expire date.
	 */
	public function calc_expire_date( $start_date = null ) {
		$membership = $this->get_membership();
		$gateway = $this->get_gateway();

		$trial_expire_date = $this->calc_trial_expire_date( $start_date );
		$expire_date = null;

		/* When in trial period and gateway does not send automatic recurring
		 * payment, the expire date is equal to trial expire date.
		 */
		if ( $this->is_trial_eligible() && ! empty( $gateway->manual_payment ) ) {
			$expire_date = $trial_expire_date;
		} else {
			switch ( $membership->payment_type ){
				case MS_Model_Membership::PAYMENT_TYPE_PERMANENT:
					$expire_date = false;
					break;

				case MS_Model_Membership::PAYMENT_TYPE_FINITE:
					$period_unit = MS_Helper_Period::get_period_value(
						$membership->period,
						'period_unit'
					);
					$period_type = MS_Helper_Period::get_period_value(
						$membership->period,
						'period_type'
					);
					$expire_date = MS_Helper_Period::add_interval(
						$period_unit,
						$period_type,
						$trial_expire_date
					);
					break;

				case MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE:
					$expire_date = $membership->period_date_end;
					break;

				case MS_Model_Membership::PAYMENT_TYPE_RECURRING:
					$period_unit = MS_Helper_Period::get_period_value(
						$membership->pay_cycle_period,
						'period_unit'
					);
					$period_type = MS_Helper_Period::get_period_value(
						$membership->pay_cycle_period,
						'period_type'
					);
					$expire_date = MS_Helper_Period::add_interval(
						$period_unit,
						$period_type,
						$trial_expire_date
					);
					break;
			}
		}

		return apply_filters(
			'ms_model_membership_relationship_calc_expire_date',
			$expire_date,
			$this
		);
	}

	/**
	 * Configure the membership period dates.
	 *
	 * Set initial membership period or renew periods.
	 *
	 * @since 1.0.0
	 */
	public function config_period() {
		do_action(
			'ms_model_membership_relationship_config_period_before',
			$this
		);

		switch ( $this->status ) {
			case self::STATUS_DEACTIVATED:
			case self::STATUS_PENDING:
				// Set initial start, trial and expire date.
				$this->set_start_date();
				$this->set_trial_expire_date();
				$this->set_expire_date();
				break;

			case self::STATUS_EXPIRED:
			case self::STATUS_CANCELED:
			case self::STATUS_ACTIVE:
				$this->trial_period_completed = true;
				// Renew period
				$this->expire_date = $this->calc_expire_date( $this->expire_date );
				break;

			case self::STATUS_TRIAL:
			case self::STATUS_TRIAL_EXPIRED:
				$this->trial_period_completed = true;
				// Confirm expire date.
				$this->expire_date = $this->calc_expire_date( $this->start_date );
				break;

			default:
				do_action(
					'ms_model_membership_relationship_config_period_for_status_' . $this->status,
					$this
				);
				break;
		}

		do_action(
			'ms_model_membership_relationship_config_period_after',
			$this
		);
	}

	/**
	 * Get how many days in this membership.
	 *
	 * @since 1.0.0
	 *
	 * @return string The period desc.
	 */
	public function get_current_period() {
		$period_desc = MS_Helper_Period::subtract_dates(
			MS_Helper_Period::current_date(),
			$this->start_date
		);

		return apply_filters(
			'ms_model_membership_relationship_get_current_period',
			$period_desc,
			$this
		);
	}

	/**
	 * Get how many days until this membership trial expires.
	 *
	 * @since 1.0.0
	 *
	 * @return string The period desc.
	 */
	public function get_remaining_trial_period() {
		$period_desc = MS_Helper_Period::subtract_dates(
			$this->trial_expire_date,
			MS_Helper_Period::current_date()
		);

		return apply_filters(
			'ms_model_membership_relationship_get_remaining_trial_period',
			$period_desc,
			$this
		);
	}

	/**
	 * Get how many days until this membership expires.
	 *
	 * @since 1.0.0
	 *
	 * @return string The period desc.
	 */
	public function get_remaining_period() {
		$period_desc = MS_Helper_Period::subtract_dates(
			$this->expire_date,
			MS_Helper_Period::current_date()
		);

		return apply_filters(
			'ms_model_membership_relationship_get_remaining_period',
			$period_desc,
			$this
		);
	}

	/**
	 * Get related Member model.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Member The member object.
	 */
	public function get_member() {
		$member = null;

		if ( ! empty( $this->user_id ) ) {
			$member = MS_Factory::load( 'MS_Model_Member', $this->user_id );
		}

		return apply_filters(
			'ms_model_membership_relationship_get_member',
			$member
		);
	}

	/**
	 * Get a list of all invoices linked to this relationship
	 *
	 * @since  1.1.0
	 * @return MS_Model_Invoice[]
	 */
	public function get_invoices() {
		$invoices = MS_Model_Invoice::get_invoices(
			array(
				'nopaging' => true,
				'meta_query' => array(
					array(
						'key'     => 'ms_relationship_id',
						'value'   => $this->id,
					),
				),
			)
		);

		return apply_filters(
			'ms_model_membership_relationship_get_invoices',
			$invoices
		);
	}

	/**
	 * Get related Membership model.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Membership The membership model.
	 */
	public function get_membership() {
		if ( empty( $this->membership->id ) ) {
			$this->membership = MS_Factory::load(
				'MS_Model_Membership',
				$this->membership_id
			);
		}

		return apply_filters(
			'ms_model_membership_relationship_get_membership',
			$this->membership
		);
	}

	/**
	 * Returns true if the related membership is the base-membership.
	 *
	 * @since  1.0.4.5
	 * @deprecated since 1.1.0, replaced by is_special().
	 * @return bool
	 */
	public function is_visitor_membership() {
		return $this->get_membership()->is_visitor_membership();
	}

	/**
	 * Returns true if the related membership is the base-membership.
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function is_special( $type = null ) {
		return $this->get_membership()->is_special( $type );
	}

	/**
	 * Get related gateway model.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Gateway
	 */
	public function get_gateway() {
		$gateway = MS_Model_Gateway::factory( $this->gateway_id );

		return apply_filters(
			'ms_model_membership_relationship_get_gateway',
			$gateway
		);
	}

	/**
	 * Get payment information description.
	 *
	 * @since 1.0.0
	 * @param  MS_Model_Invoice $invoice Optional. Specific invoice that defines the price.
	 * @param  MS_Model_Invoice $trial_invoice Optional. Invoice for the trial price.
	 * @return string The description.
	 */
	public function get_payment_description( $invoice = null, $trial_invoice = null ) {
		$currency = MS_Plugin::instance()->settings->currency;
		$membership = $this->get_membership();
		$desc = '';

		if ( null !== $invoice ) {
			$total_price = $invoice->total;
		} else {
			$total_price = $membership->price;
		}

		if ( null !== $trial_invoice ) {
			$trial_price = $trial_invoice->total;
		} else {
			$trial_price = $membership->trial_price;
		}
		$total_price = number_format( $total_price, 2 );
		$trial_price = number_format( $trial_price, 2 );

		switch ( $membership->payment_type ){
			case MS_Model_Membership::PAYMENT_TYPE_PERMANENT:
				if ( 0 == $total_price ) {
					$desc = __( 'You will pay nothing for permanent access.', MS_TEXT_DOMAIN );
				} else {
					$desc = sprintf(
						__( 'You will pay <span class="price">%1$s %2$s</span>, for permanent access.', MS_TEXT_DOMAIN ),
						$currency,
						$total_price
					);
				}
				break;

			case MS_Model_Membership::PAYMENT_TYPE_FINITE:
				if ( 0 == $total_price ) {
					$desc = sprintf(
						__( 'You will pay nothing for access until %1$s.', MS_TEXT_DOMAIN ),
						$this->calc_expire_date( $this->expire_date )
					);
				} else {
					$desc .= sprintf(
						__( 'You will pay <span class="price">%1$s %2$s</span> for access until %3$s.', MS_TEXT_DOMAIN ),
						$currency,
						$total_price,
						$this->calc_expire_date( $this->expire_date )
					);
				}
				break;

			case MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE:
				if ( 0 == $total_price ) {
					$desc = sprintf(
						__( 'You will pay nothing for access from %1$s until %2$s.', MS_TEXT_DOMAIN ),
						$membership->period_date_start,
						$membership->period_date_end
					);
				} else {
					$desc .= sprintf(
						__( 'You will pay <span class="price">%1$s %2$s</span> to access from %3$s to %4$s.', MS_TEXT_DOMAIN ),
						$currency,
						$total_price,
						$membership->period_date_start,
						$membership->period_date_end
					);
				}
				break;

			case MS_Model_Membership::PAYMENT_TYPE_RECURRING:
				$period_unit = MS_Helper_Period::get_period_value(
					$membership->pay_cycle_period,
					'period_unit'
				);
				$period_type = MS_Helper_Period::get_period_value(
					$membership->pay_cycle_period,
					'period_type'
				);

				$desc .= sprintf(
					__( 'You will pay <span class="price">%1$s %2$s</span> each %3$s %4$s.', MS_TEXT_DOMAIN ),
					$currency,
					$total_price,
					$period_unit,
					$period_type
				);
				break;
		}

		if ( $this->is_trial_eligible() ) {
			$period_unit = MS_Helper_Period::get_period_value(
				$membership->trial_period,
				'period_unit'
			);
			$period_type = MS_Helper_Period::get_period_value(
				$membership->trial_period,
				'period_type'
			);

			$desc .= sprintf(
				__( ' <br />In the trial period of %1$s %2$s, you will pay <span class="price">%3$s %4$s</span>.', MS_TEXT_DOMAIN ),
				$period_unit,
				$period_type,
				$currency,
				$trial_price
			);
		}

		return apply_filters(
			'ms_model_membership_relationship_get_payment_description',
			$desc,
			$membership
		);
	}

	/**
	 * Set membership relationship status.
	 *
	 * Validates every time.
	 * Check for status that need membership verification for trial, active and expired.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status The status to set.
	 */
	public function set_status( $status ) {
		// These status are not validated, and promptly assigned
		$allowed_status = apply_filters(
			'ms_model_membership_relationship_set_status_allowed_status',
			array(
				self::STATUS_DEACTIVATED,
				self::STATUS_PENDING,
				self::STATUS_CANCELED,
				self::STATUS_TRIAL_EXPIRED,
			)
		);

		// Validate status and handle status change
		if ( ! in_array( $status, $allowed_status ) ) {
			$status = $this->calculate_status( $status );
			if ( MS_Model_Member::is_admin_user( $this->user_id ) ) {
				$this->status = $status;
			}
			else {
				$this->handle_status_change( $status );
			}
		}
		else {
			$this->status = $status;
		}

		$this->status = apply_filters(
			'ms_model_membership_relationship_set_status',
			$this->status,
			$this
		);
	}

	/**
	 * Get membership relationship status.
	 *
	 * Validates every time.
	 *
	 * Verifies start and end date of a membership and updates status if expired.
	 *
	 * @since 1.0.0
	 * @return string The current status.
	 */
	public function get_status() {
		// No further validations for these status
		$ignored_status = apply_filters(
			'ms_model_membership_relationship_get_status_ignored_status',
			array(
				self::STATUS_DEACTIVATED,
				self::STATUS_PENDING,
				self::STATUS_TRIAL_EXPIRED,
			)
		);

		// Validate current status and handle status change
		if ( ! in_array( $this->status, $ignored_status ) ) {
			$status = $this->calculate_status();
			if ( MS_Model_Member::is_admin_user( $this->user_id ) ) {
				$this->status = $status;
			} else {
				$this->handle_status_change( $status );
			}
		}

		return apply_filters(
			'membership_model_membership_relationship_get_status',
			$this->status,
			$this
		);
	}

	/**
	 * Calculate the membership status.
	 *
	 * Calculate status for the membership verifying the start date, trial exire date and expire date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $set_status The set status to compare.
	 * @return string The calculated status.
	 */
	public function calculate_status( $set_status = null ) {
		$membership = $this->get_membership();
		$status = null;

		if ( $this->trial_expire_date > $this->start_date
			&& strtotime( $this->trial_expire_date ) >= strtotime( MS_Helper_Period::current_date() )
		) {
			$status = self::STATUS_TRIAL;
		}
		elseif ( ! empty( $this->trial_expire_date )
			&& $this->trial_expire_date == $this->expire_date
			&& strtotime( $this->trial_expire_date ) > strtotime( MS_Helper_Period::current_date() )
		) {
			$status = self::STATUS_TRIAL_EXPIRED;
		}
		elseif ( MS_Model_Membership::PAYMENT_TYPE_PERMANENT == $membership->payment_type ) {
			$status = self::STATUS_ACTIVE;
		}
		elseif ( ! empty( $this->expire_date )
			&& strtotime( $this->expire_date ) >= strtotime( MS_Helper_Period::current_date() )
		) {
			$status = self::STATUS_ACTIVE;
		} else {
			$status = self::STATUS_EXPIRED;
		}

		// If user canceled the membership before expire date, still have access until expires.
		if ( self::STATUS_CANCELED == $this->status
			&& self::STATUS_ACTIVE != $set_status
		) {
			// For expired memberships or PAYMENT_TYPE_PERMANENT deactivate it immediately.
			if ( self::STATUS_EXPIRED == $status
				|| MS_Model_Membership::PAYMENT_TYPE_PERMANENT == $membership->payment_type
			) {
				$status = self::STATUS_DEACTIVATED;
			} else {
				$status = self::STATUS_CANCELED;
			}
		}

		return apply_filters(
			'membership_model_membership_relationship_calculate_status',
			$status,
			$this
		);
	}

	/**
	 * Handle status change.
	 *
	 * Save news when status change.
	 *
	 * @since 1.0.0
	 *
	 * @param string $status The status to change to.
	 */
	public function handle_status_change( $status ) {
		do_action(
			'ms_model_membership_relationship_handle_status_change_before',
			$status,
			$this
		);

		if ( ! empty( $this->status )
			&& $status != $this->status
			&& array_key_exists( $status, self::get_status_types() )
		) {

			// deactivated manually or automatically after a limited expired
			// period (or trial expired period).
			if ( self::STATUS_DEACTIVATED == $status ) {
				MS_Model_Event::save_event(
					MS_Model_Event::TYPE_MS_DEACTIVATED,
					$this
				);
			}
			else {
				// Current status to change from.
				switch ( $this->status ) {
					case self::STATUS_PENDING:
						// signup
						if ( 'admin' != $this->gateway_id
							&& in_array( $status, array( self::STATUS_TRIAL, self::STATUS_ACTIVE ) )
						) {
							MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_SIGNED_UP, $this );
						}
						break;

					case self::STATUS_TRIAL:
						// trial finished
						if ( self::STATUS_TRIAL_EXPIRED == $status ) {
							MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_TRIAL_FINISHED, $this );
						}
						elseif ( self::STATUS_ACTIVE == $status ) {
							MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RENEWED, $this );
						}
						elseif ( self::STATUS_CANCELED == $status ) {
							MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_CANCELED, $this );
						}
						break;

					case self::STATUS_TRIAL_EXPIRED:
						if ( self::STATUS_ACTIVE == $status ) {
							MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RENEWED, $this );
						}
						break;

					case self::STATUS_ACTIVE:
						if ( self::STATUS_CANCELED == $status ) {
							MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_CANCELED, $this );
						}
						if ( self::STATUS_EXPIRED == $status ) {
							MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_EXPIRED, $this );
						}
						break;

					case self::STATUS_EXPIRED:
					case self::STATUS_CANCELED:
						if ( self::STATUS_ACTIVE == $status ) {
							MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RENEWED, $this );
						}
						break;

					case self::STATUS_DEACTIVATED:
						break;
				}
			}

			$this->status = apply_filters(
				'ms_model_membership_relationship_set_status',
				$status
			);
			$this->save();
		}

		do_action(
			'ms_model_membership_relationship_handle_status_change_after',
			$status,
			$this
		);
	}

	/**
	 * Get status description.
	 *
	 * @since 1.0.0
	 *
	 * @return string The status description.
	 */
	public function get_status_description() {
		$desc = '';

		switch ( $this->status ) {
			case self::STATUS_PENDING:
				$desc = __( 'Pending payment.', MS_TEXT_DOMAIN );
				break;

			case self::STATUS_TRIAL:
				$desc = __( 'Membership level trial expires on: ', MS_TEXT_DOMAIN ) .
					$this->trial_expire_date;
				break;

			case self::STATUS_ACTIVE:
				if ( ! empty( $this->expire_date ) ) {
					$desc = __( 'Membership level expires on: ', MS_TEXT_DOMAIN ) .
						$this->expire_date;
				}
				else {
					$desc = __( 'Permanent access.', MS_TEXT_DOMAIN );
				}
				break;

			case self::STATUS_TRIAL_EXPIRED:
			case self::STATUS_EXPIRED:
				$desc = __( 'Membership expired on: ', MS_TEXT_DOMAIN ) .
					$this->expire_date;
				break;

			case self::STATUS_CANCELED:
				$desc = __( 'Membership canceled, valid until it expires on: ', MS_TEXT_DOMAIN ) .
					$this->expire_date;
				break;

			case self::STATUS_DEACTIVATED:
				$desc = __( 'Membership deactivated.', MS_TEXT_DOMAIN );
				break;
		}

		return apply_filters(
			'ms_model_membership_relationship_get_status_description',
			$desc
		);
	}

	/**
	 * Check membership status.
	 *
	 * Execute actions when time/period condition are met.
	 * E.g. change membership status, add communication to queue, create invoices.
	 *
	 * @since 1.0.0
	 */
	public function check_membership_status() {
		do_action(
			'ms_model_membership_relationship_check_membership_status_before',
			$this
		);

		$comms = MS_Model_Communication::load_communications();
		$invoice_before_days = 5;//@todo create a setting to configure this period.
		$deactivate_expired_after_days = 30; //@todo create a setting to configure this period.
		$deactivate_pending_after_days = 30; //@todo create a setting to configure this period.
		$deactivate_trial_expired_after_days = 5; //@todo create a setting to configure this period.

		$remaining_days = $this->get_remaining_period();
		$remaining_trial_days = $this->get_remaining_trial_period();

		do_action(
			'ms_model_plugin_check_membership_status_' . $this->status,
			$this,
			$remaining_days,
			$remaining_trial_days
		);

		switch ( $this->get_status() ) {
			case self::STATUS_TRIAL:
				if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL )
					&& MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_AUTO_MSGS_PLUS )
				) {

					// Send trial end communication.
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_BEFORE_TRIAL_FINISHES ];
					if ( $comm->enabled ) {
						$days = MS_Helper_Period::get_period_in_days( $comm->period );
						if ( $days == $remaining_trial_days ) {
							$comm->add_to_queue( $this->id );
							MS_Model_Event::save_event(
								MS_Model_Event::TYPE_MS_BEFORE_TRIAL_FINISHES,
								$this
							);
						}
					}
				}

				// Check for card expiration
				$gateway = $this->get_gateway();
				$gateway->check_card_expiration( $this );
				break;

			case self::STATUS_TRIAL_EXPIRED:
				if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) {
					$invoice = MS_Model_Invoice::get_current_invoice( $this );

					// Request payment to the gateway (for gateways that allows it).
					$gateway = $this->get_gateway();
					$gateway->request_payment( $this );

					// Check for card expiration
					$gateway->check_card_expiration( $this );

					// Deactivate expired memberships after a period of time.
					if ( $deactivate_trial_expired_after_days < - $remaining_trial_days ) {
						$this->deactivate_membership();

						// Move membership to configured membership.
						$membership = $this->get_membership();
						if ( MS_Model_Membership::is_valid_membership( $membership->on_end_membership_id ) ) {
							$member = MS_Factory::load( 'MS_Model_Member', $this->user_id );
							$member->add_membership( $membership->on_end_membership_id );
						}
					}
				}
				break;

			case self::STATUS_ACTIVE:
			case self::STATUS_EXPIRED:
			case self::STATUS_CANCELED:
				/*
				 * Send period end communication.
				 * Deactivate expired memberships after $deactivate_expired_after_days.
				 * Create invoice.
				 */

				do_action(
					'ms_model_plugin_check_membership_status_' . $this->status,
					$this
				);

				// Create next invoice before expire date.
				if ( $remaining_days < $invoice_before_days ) {
					$invoice = MS_Model_Invoice::get_next_invoice( $this );
				}
				else {
					$invoice = MS_Model_Invoice::get_current_invoice( $this );
				}

				// Configure communication messages.
				if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_AUTO_MSGS_PLUS ) ) {

					// Before finishes communication.
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_BEFORE_FINISHES ];
					$days = MS_Helper_Period::get_period_in_days( $comm->period );
					if ( $days == $remaining_days ) {
						$comm->add_to_queue( $this->id );
						MS_Model_Event::save_event(
							MS_Model_Event::TYPE_MS_BEFORE_FINISHES,
							$this
						);
					}

					// After finishes communication.
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_AFTER_FINISHES ];
					$days = MS_Helper_Period::get_period_in_days( $comm->period );

					if ( $remaining_days < 0 && $days == abs( $remaining_days ) ) {
						$comm->add_to_queue( $this->id );
						MS_Model_Event::save_event(
							MS_Model_Event::TYPE_MS_AFTER_FINISHES,
							$this
						);
					}

					// Before payment due.
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_BEFORE_PAYMENT_DUE ];
					$days = MS_Helper_Period::get_period_in_days( $comm->period );
					$invoice_days = MS_Helper_Period::subtract_dates(
						$invoice->due_date,
						MS_Helper_Period::current_date()
					);

					if ( MS_Model_Invoice::STATUS_BILLED == $invoice->status
						&& $days == $invoice_days
					) {
						$comm->add_to_queue( $this->id );
						MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_BEFORE_DUE, $this );
					}

					// After payment due event
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_AFTER_PAYMENT_DUE ];
					$days = MS_Helper_Period::get_period_in_days( $comm->period );
					$invoice_days = MS_Helper_Period::subtract_dates(
						$invoice->due_date,
						MS_Helper_Period::current_date()
					);

					if ( MS_Model_Invoice::STATUS_BILLED == $invoice->status
						&& $days == $invoice_days
					) {
						$comm->add_to_queue( $this->id );
						MS_Model_Event::save_event( MS_Model_Event::TYPE_PAYMENT_AFTER_DUE, $this );
					}
				}

				// Check for card expiration
				$gateway = $this->get_gateway();
				$gateway->check_card_expiration( $this );

				// Request payment to the gateway (for gateways that allows it)
				// when time comes (expired).
				if ( $remaining_days <= 0 ) {
					$gateway->request_payment( $this );
					// Refresh status after payment
					$remaining_days = $this->get_remaining_period();
				}

				// Deactivate expired memberships after a period of time.
				if ( $deactivate_expired_after_days < - $remaining_days ) {
					$this->deactivate_membership();

					// Move membership to configured membership.
					$membership = $this->get_membership();

					if ( MS_Model_Membership::is_valid_membership( $membership->on_end_membership_id ) ) {
						$member = MS_Factory::load( 'MS_Model_Member', $this->user_id );
						$member->add_membership( $membership->on_end_membership_id );
						MS_Model_Event::save_event(
							MS_Model_Event::TYPE_MS_MOVED,
							$member->ms_relationships[ $membership->on_end_membership_id ]
						);
					}
				}
				break;

			case self::STATUS_PENDING:
			case self::STATUS_DEACTIVATED:
			default:
				break;
		}

		foreach ( $comms as $comm ) {
			$comm->save();
		}

		do_action(
			'ms_model_membership_relationship_check_membership_status_after',
			$this
		);
	}

	/**
	 * Returns property.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;

		switch ( $property ) {
			case 'status':
				$value = $this->get_status();
				break;

			default:
				if ( ! property_exists( $this, $property ) ) {
					MS_Helper_Debug::log( 'Property doesnot exist: ' . $property );
				}
				$value = $this->$property;
				break;
		}

		return apply_filters(
			'ms_model_membership_relationship__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Set specific property.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
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

		do_action( 'ms_model_membership_relationship__set_after', $property, $value, $this );
	}

}