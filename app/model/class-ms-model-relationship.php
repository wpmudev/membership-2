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
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Relationship extends MS_Model_CustomPostType {

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
	const STATUS_WAITING = 'waiting'; // Start-Date not reached yet
	const STATUS_EXPIRED = 'expired'; // End-Date reached
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
	 * Where the data came from. Can only be changed by data import tool
	 *
	 * @since 1.1.0
	 * @var string
	 */
	protected $source = '';

	/**
	 * The number of successful payments that were made for this subscription.
	 *
	 * We use this value to determine the end of a recurring payment plan.
	 * Also this information is displayed in the member-info popup (only for
	 * admins; see MS_View_Member_Dialog)
	 *
	 * @since 1.1.0
	 * @var array {
	 *      A list of all payments that were made [since 1.1.0]
	 *
	 *      string $date    Payment date/time.
	 *      number $amount  Payment-Amount.
	 *      string $gateway Gateway that confirmed payment.
	 * }
	 */
	protected $payments = array();

	/**
	 * Flag that keeps track, if this subscription is a simulated or a real one.
	 *
	 * @since 1.1.0
	 * @var bool
	 */
	protected $is_simulated = false;

	/**
	 * The related membership model object.
	 *
	 * @since 1.0.0
	 * @var MS_Model_Membership $membership
	 */
	private $membership;

	//
	//
	//
	// -------------------------------------------------------------- COLLECTION

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since 1.0.0
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Protected Content Subscriptions', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_customposttype_register_args',
			$args,
			self::$POST_TYPE
		);
	}

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
			self::STATUS_WAITING => __( 'Not yet active', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_model_relationship_get_status_types',
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
	 * @return MS_Model_Relationship The created relationship.
	 */
	public static function create_ms_relationship(
		$membership_id = 0,
		$user_id = 0,
		$gateway_id = 'admin',
		$move_from_id = 0
	) {
		do_action(
			'ms_model_relationship_create_ms_relationship_before',
			$membership_id,
			$user_id,
			$gateway_id,
			$move_from_id
		);

		if ( MS_Model_Membership::is_valid_membership( $membership_id ) ) {
			$subscription = self::_create_ms_relationship(
				$membership_id,
				$user_id,
				$gateway_id,
				$move_from_id
			);
		} else {
			$subscription = null;
			MS_Helper_Debug::log(
				'Invalid membership_id: ' .
				"$membership_id, ms_relationship not created for $user_id, $gateway_id, $move_from_id"
			);
			MS_Helper_Debug::debug_trace();
		}

		return apply_filters(
			'ms_model_relationship_create_ms_relationship',
			$subscription,
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
	 * @return MS_Model_Relationship The created relationship.
	 */
	private static function _create_ms_relationship( $membership_id, $user_id, $gateway_id, $move_from_id ) {
		$is_simulated = false;

		// Try to reuse existing db record to keep history.
		$subscription = self::get_subscription( $user_id, $membership_id );

		if ( 'simulation' == $gateway_id ) {
			$is_simulated = true;
			$gateway_id = 'admin';
			$subscription = false;
		}

		// Not found, create a new one.
		if ( empty( $subscription ) ) {
			$subscription = MS_Factory::create( 'MS_Model_Relationship' );
			$subscription->membership_id = $membership_id;
			$subscription->user_id = $user_id;
			$subscription->status = self::STATUS_PENDING;
			$subscription->is_simulated = $is_simulated;

			if ( $is_simulated ) {
				$subscription->id = -1;
			}
		}

		// Always update these fields.
		$subscription->move_from_id = $move_from_id;
		$subscription->gateway_id = $gateway_id;

		// Set initial state.
		switch ( $subscription->status ) {
			case self::STATUS_DEACTIVATED:
				$subscription->status = self::STATUS_PENDING;
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
				$subscription->trial_period_completed = true;
				break;

			case self::STATUS_PENDING:
			default:
				// Initial status
				$subscription->name = "user_id: $user_id, membership_id: $membership_id";
				$subscription->description = $subscription->name;
				$subscription->trial_period_completed = false;
				break;
		}

		$subscription->config_period(); // Needed to initialize start/expire.

		$membership = $subscription->get_membership();
		if ( 'admin' == $gateway_id || $membership->is_free() ) {
			$subscription->status = self::STATUS_ACTIVE;

			if ( ! $subscription->is_system() && ! $is_simulated ) {
				$subscription->save();

				// Create event.
				MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_SIGNED_UP, $subscription );
			}
		} else {
			// Force status calculation.
			$subscription->get_status();
			$subscription->save();
		}

		return $subscription;
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
	 * @param array $args The query post args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @param bool $include_system Whether to include the base/guest memberships.
	 * @return MS_Model_Relationship[] The array of membership relationships.
	 */
	public static function get_subscriptions( $args = null, $include_system = false ) {
		$args = self::get_query_args( $args );

		$query = new WP_Query( $args );
		$posts = $query->get_posts();
		$subscriptions = array();

		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post_id ) {
				$ms_relationship = MS_Factory::load(
					'MS_Model_Relationship',
					$post_id
				);

				// Remove System-Memberships
				if ( $ms_relationship->is_system() && ! $include_system ) {
					continue;
				}

				if ( ! empty( $args['author'] ) ) {
					$subscriptions[ $ms_relationship->membership_id ] = $ms_relationship;
				} else {
					$subscriptions[ $post_id ] = $ms_relationship;
				}
			}
		}

		$subscriptions = apply_filters(
			'ms_model_relationship_get_subscriptions',
			$subscriptions,
			$args
		);

		return $subscriptions;
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
	public static function get_subscription_count( $args = null ) {
		$args = apply_filters(
			'ms_model_relationship_get_subscription_count_args',
			self::get_query_args( $args )
		);
		$query = new WP_Query( $args );
		$count = $query->found_posts;

		return apply_filters(
			'ms_model_relationship_get_subscription_count',
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
	public static function get_subscription( $user_id, $membership_id ) {
		$args = apply_filters(
			'ms_model_relationship_get_subscription_args',
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
				'MS_Model_Relationship',
				$post[0]
			);
		}

		return apply_filters(
			'ms_model_relationship_get_subscription',
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
			'ms_model_relationship_get_query_args_defaults',
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
			'ms_model_relationship_get_query_args',
			$args,
			$defaults
		);
	}


	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM

	/**
	 * Cancel membership.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $generate_event Optional. Defines if cancel events are generated.
	 */
	public function cancel_membership( $generate_event = true ) {
		do_action(
			'ms_model_relationship_cancel_membership_before',
			$this,
			$generate_event
		);

		if ( self::STATUS_CANCELED == $this->status ) { return; }
		if ( self::STATUS_DEACTIVATED == $this->status ) { return; }

		try {
			// Canceling in trial period -> change the expired date.
			if ( self::STATUS_TRIAL == $this->status ) {
				$this->expire_date = $this->trial_expire_date;
			}

			$this->status = $this->calculate_status( self::STATUS_CANCELED );
			$this->save();

			// Cancel subscription in the gateway.
			if ( $gateway = $this->get_gateway() ) {
				$gateway->cancel_membership( $this );
			}

			// Remove any unpaid invoices.
			$this->remove_unpaid_invoices();

			if ( $generate_event ) {
				MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_CANCELED, $this );
			}
		}
		catch ( Exception $e ) {
			MS_Helper_Debug::log( '[Error canceling membership]: '. $e->getMessage() );
		}

		do_action(
			'ms_model_relationship_cancel_membership_after',
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
	 */
	public function deactivate_membership() {
		do_action(
			'ms_model_relationship_deactivate_membership_before',
			$this
		);

		/**
		 * Documented in check_membership_status()
		 *
		 * @since 1.1.0.5
		 */
		if ( MS_Plugin::get_modifier( 'MS_LOCK_SUBSCRIPTIONS' ) ) {
			return false;
		}

		if ( self::STATUS_DEACTIVATED == $this->status ) { return; }

		try {
			$this->cancel_membership( false );
			$this->status = self::STATUS_DEACTIVATED;
			$this->save();

			MS_Model_Event::save_event(
				MS_Model_Event::TYPE_MS_DEACTIVATED,
				$this
			);
		}
		catch( Exception $e ) {
			MS_Helper_Debug::log(
				'[Error deactivating membership]: '. $e->getMessage()
			);
		}

		do_action(
			'ms_model_relationship_deactivate_membership_after',
			$this
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
		do_action( 'ms_model_relationship_save_before', $this );

		if ( ! empty( $this->user_id )
			&& ! MS_Model_Member::is_admin_user( $this->user_id )
		) {
			if ( ! $this->is_system() ) {
				parent::save();
				parent::store_singleton();
			}
		}

		do_action( 'ms_model_relationship_after', $this );
	}

	/**
	 * Removes any unpaid invoice that belongs to this subscription.
	 *
	 * @since  1.1.1.3
	 */
	public function remove_unpaid_invoices() {
		$invoices = $this->get_invoices();

		foreach ( $invoices as $invoice ) {
			if ( 'paid' != $invoice->status ) {
				$invoice->delete();
			}
		}
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
			'ms_model_relationship_trial_eligible_status',
			array(
				self::STATUS_PENDING,
				self::STATUS_DEACTIVATED,
			)
		);

		$eligible = false;

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) {
			// Trial Membership is globally disabled.
			$eligible = false;
		} elseif ( self::STATUS_TRIAL == $this->status ) {
			// Subscription IS already in trial, so it's save to assume true.
			$eligible = true;
		} elseif ( ! in_array( $this->status, $trial_eligible_status ) ) {
			// Current Subscription is not allowed for a trial membership anymore.
			$eligible = false;
		} elseif ( $this->trial_period_completed ) {
			// Trial membership already consumed.
			$eligible = false;
		} elseif ( ! $membership->trial_period_enabled ) {
			// Trial mode for this membership is disabled.
			$eligible = false;
		} else {
			// All other cases: User can sign up for trial!
			$eligible = true;
		}

		return apply_filters(
			'ms_model_relationship_is_trial_eligible',
			$eligible,
			$this
		);
	}

	/**
	 * Checks if the current subscription consumes a trial period.
	 *
	 * When the subscription either is currently in trial or was in trial before
	 * then this function returns true.
	 * If the subscription never was in trial status it returns false.
	 *
	 * @since  1.1.1.4
	 * @return bool
	 */
	public function has_trial() {
		$result = false;

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) {
			$result = false;
		} elseif ( ! $this->trial_expire_date ) {
			$result = false;
		} elseif ( $this->trial_expire_date == $this->start_date ) {
			$result = false;
		} else {
			$result = true;
		}

		return $result;
	}

	/**
	 * Set Membership Relationship start date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $start_date Optional. The start date to set.
	 *               Default will be calculated.
	 */
	public function set_start_date( $start_date = null ) {
		$membership = $this->get_membership();

		if ( empty( $start_date ) ) {
			if ( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
				$start_date = $membership->period_date_start;
			} else {
				/*
				 * Note that we pass TRUE as second param to current_date
				 * This is needed so that we 100% use the current date, which
				 * is required to successfully do simulation.
				 */
				$start_date = MS_Helper_Period::current_date( null, true );
			}
		}

		$this->start_date = apply_filters(
			'ms_model_relationship_set_start_date',
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
	 * @param string $trial_expire_date Optional. The trial expire date to set.
	 *               Default will be calculated.
	 */
	public function set_trial_expire_date( $trial_expire_date = null ) {
		if ( $this->is_trial_eligible() ) {
			$valid_date = MS_Helper_Period::is_after(
				$trial_expire_date,
				$this->start_date
			);

			if ( ! $valid_date ) {
				$trial_expire_date = $this->calc_trial_expire_date( $this->start_date );
			}
		} else {
			// Do NOT set any trial-expire-date when trial period is not available!
			$trial_expire_date = '';
		}

		$this->trial_expire_date = apply_filters(
			'ms_model_relationship_set_trial_start_date',
			$trial_expire_date,
			$this
		);

		// Subscriptions with this status have no valid expire-date.
		$no_expire_date = array(
			self::STATUS_DEACTIVATED,
			self::STATUS_PENDING,
			self::STATUS_TRIAL,
			self::STATUS_TRIAL_EXPIRED,
		);

		if ( $this->trial_expire_date && in_array( $this->status, $no_expire_date ) ) {
			// Set the expire date to trial-expire date
			$this->expire_date = $this->trial_expire_date;
		}
	}

	/**
	 * Set trial expire date.
	 *
	 * Validate to a date greater than start date and trial expire date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $expire_date Optional. The expire date to set.
	 *               Default will be calculated.
	 */
	public function set_expire_date( $expire_date = null ) {
		$no_expire_date = array(
			self::STATUS_DEACTIVATED,
			self::STATUS_PENDING,
		);

		if ( ! in_array( $this->status, $no_expire_date ) ) {
			$valid_date = MS_Helper_Period::is_after(
				$expire_date,
				$this->start_date,
				$this->trial_expire_date
			);

			if ( ! $valid_date ) {
				$expire_date = $this->calc_expire_date( $this->start_date );
			}
		} else {
			// Do NOT set any expire-date when subscription is not active!
			$expire_date = '';
		}

		$this->expire_date = apply_filters(
			'ms_model_relationship_set_expire_date',
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
	 * @param  string $start_date Optional. The start date to calculate date from.
	 * @return string The calculated trial expire date.
	 */
	public function calc_trial_expire_date( $start_date = null ) {
		$membership = $this->get_membership();
		$trial_expire_date = null;

		if ( empty( $start_date ) ) {
			$start_date = $this->start_date;
		}
		if ( empty( $start_date ) ) {
			$start_date = MS_Helper_Period::current_date();
		}

		if ( $this->is_trial_eligible() ) {
			// Trial period was not consumed yet, calculate the expiration date.

			if ( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
				$from_date = $membership->period_date_start;
			} else {
				$from_date = $start_date;
			}

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
				$from_date
			);
		} else {
			// Subscription not entitled for trial anymore. Trial expires instantly.
			$trial_expire_date = $start_date;
		}

		return apply_filters(
			'ms_model_relationship_calc_trial_expire_date',
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
	 * @param  bool $paid If the user made a payment to extend the expire date.
	 * @return string The calculated expire date.
	 */
	public function calc_expire_date( $start_date = null, $paid = false ) {
		$membership = $this->get_membership();
		$gateway = $this->get_gateway();

		$start_date = $this->calc_trial_expire_date( $start_date );
		$expire_date = null;

		/*
		 * When in trial period and gateway does not send automatic recurring
		 * payment notifications, the expire date is equal to trial expire date.
		 */
		if ( $this->is_trial_eligible() ) {
			$expire_date = $start_date;
		} else {
			if ( $paid ) {
				/*
				 * Always extend the membership from current date or later, even if
				 * the specified start-date is in the past.
				 *
				 * Example: User does not pay for 3 days (subscription set "pending")
				 *          Then he pays: The 3 days without access are for free;
				 *          his subscriptions is extended from current date!
				 */
				$today = MS_Helper_Period::current_date();
				if ( MS_Helper_Period::is_after( $today, $start_date ) ) {
					$start_date = $today;
				}
			}

			/*
			 * The gatway calls the payment handler URL automatically:
			 * This means that the user does not need to re-authorize each
			 * payment.
			 */
			switch ( $membership->payment_type ) {
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
						$start_date
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
						$start_date
					);
					break;
			}
		}

		return apply_filters(
			'ms_model_relationship_calc_expire_date',
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
	public function config_period() { // Needed because of status change.
		do_action(
			'ms_model_relationship_config_period_before',
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
				// Nothing else done. Expire date is changed by add_payment.
				break;

			case self::STATUS_TRIAL:
			case self::STATUS_TRIAL_EXPIRED:
				$this->set_trial_expire_date();
				break;

			default:
				do_action(
					'ms_model_relationship_config_period_for_status_' . $this->status,
					$this
				);
				break;
		}

		do_action(
			'ms_model_relationship_config_period_after',
			$this
		);
	}

	/**
	 * Returns the remaining subscription period of this membership in days.
	 *
	 * @since 1.0.0
	 *
	 * @return int Remaining days.
	 */
	public function get_current_period() {
		$period_days = MS_Helper_Period::subtract_dates(
			MS_Helper_Period::current_date(),
			$this->start_date
		);

		return apply_filters(
			'ms_model_relationship_get_current_period',
			$period_days,
			$this
		);
	}

	/**
	 * Returns the remaining trial period of this membership in days.
	 *
	 * @since 1.0.0
	 *
	 * @return int Remaining days.
	 */
	public function get_remaining_trial_period() {
		$period_days = MS_Helper_Period::subtract_dates(
			$this->trial_expire_date,
			MS_Helper_Period::current_date()
		);

		return apply_filters(
			'ms_model_relationship_get_remaining_trial_period',
			$period_days,
			$this
		);
	}

	/**
	 * Get how many days until this membership expires.
	 *
	 * @since 1.0.0
	 *
	 * @return int Remaining days.
	 */
	public function get_remaining_period() {
		$period_days = MS_Helper_Period::subtract_dates(
			$this->expire_date,
			MS_Helper_Period::current_date()
		);

		return apply_filters(
			'ms_model_relationship_get_remaining_period',
			$period_days,
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
			'ms_model_relationship_get_member',
			$member
		);
	}

	/**
	 * Convenience function to access current invoice for this subscription.
	 *
	 * @since  1.1.1.4
	 * @return MS_Model_Invoice
	 */
	public function get_current_invoice( $create_missing = true ) {
		return MS_Model_Invoice::get_current_invoice( $this, $create_missing );
	}

	/**
	 * Convenience function to access next invoice for this subscription.
	 *
	 * @since  1.1.1.4
	 * @return MS_Model_Invoice
	 */
	public function get_next_invoice( $create_missing = true ) {
		return MS_Model_Invoice::get_next_invoice( $this, $create_missing );
	}

	/**
	 * Convenience function to access previous invoice for this subscription.
	 *
	 * @since  1.1.1.4
	 * @return MS_Model_Invoice
	 */
	public function get_previous_invoice( $status = null ) {
		return MS_Model_Invoice::get_previous_invoice( $this, $status );
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
			'ms_model_relationship_get_invoices',
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
				$this->membership_id,
				$this->id
			);

			// Set the context of the membership to current subscription.
			$this->membership->subscription_id = $this->id;
		}

		return apply_filters(
			'ms_model_relationship_get_membership',
			$this->membership
		);
	}

	/**
	 * Returns true if the related membership is the base-membership.
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function is_base() {
		return $this->get_membership()->is_base();
	}

	/**
	 * Returns true if the related membership is the guest-membership.
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function is_guest() {
		return $this->get_membership()->is_guest();
	}

	/**
	 * Returns true if the related membership is the user-membership.
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function is_user() {
		return $this->get_membership()->is_user();
	}

	/**
	 * Returns true if the related membership is a system membership.
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function is_system() {
		return $this->get_membership()->is_system();
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
			'ms_model_relationship_get_gateway',
			$gateway
		);
	}

	/**
	 * Get payment information description.
	 *
	 * @since 1.0.0
	 * @param  MS_Model_Invoice $invoice Optional. Specific invoice that defines the price.
	 * @return string The description.
	 */
	public function get_payment_description( $invoice = null, $short = false ) {
		$currency = MS_Plugin::instance()->settings->currency;
		$membership = $this->get_membership();
		$desc = '';

		if ( null !== $invoice ) {
			$total_price = $invoice->total; // Includes Tax
			$trial_price = $invoice->trial_price; // Includes Tax
		} else {
			$total_price = $membership->total_price; // Excludes Tax
			$trial_price = $membership->trial_price; // Excludes Tax
		}

		$total_price = MS_Helper_Billing::format_price( $total_price );
		$trial_price = MS_Helper_Billing::format_price( $trial_price );

		switch ( $membership->payment_type ){
			case MS_Model_Membership::PAYMENT_TYPE_PERMANENT:
				if ( 0 == $total_price ) {
					if ( $short ) {
						$lbl = __( 'Nothing (for ever)', MS_TEXT_DOMAIN );
					} else {
						$lbl = __( 'You will pay nothing for permanent access.', MS_TEXT_DOMAIN );
					}
				} else {
					if ( $short ) {
						$lbl  = __( '<span class="price">%1$s %2$s</span> (for ever)', MS_TEXT_DOMAIN );
					} else {
						$lbl = __( 'You will pay <span class="price">%1$s %2$s</span> for permanent access.', MS_TEXT_DOMAIN );
					}
				}

				$desc = sprintf(
					$lbl,
					$currency,
					$total_price
				);
				break;

			case MS_Model_Membership::PAYMENT_TYPE_FINITE:
				if ( 0 == $total_price ) {
					if ( $short ) {
						$lbl = __( 'Nothing (until %4$s)', MS_TEXT_DOMAIN );
					} else {
						$lbl = __( 'You will pay nothing for access until %3$s.', MS_TEXT_DOMAIN );
					}
				} else {
					if ( $short ) {
						$lbl = __( '<span class="price">%1$s %2$s</span> (until %4$s)', MS_TEXT_DOMAIN );
					} else {
						$lbl = __( 'You will pay <span class="price">%1$s %2$s</span> for access until %3$s.', MS_TEXT_DOMAIN );
					}
				}

				$desc .= sprintf(
					$lbl,
					$currency,
					$total_price,
					MS_Helper_Period::format_date( $this->calc_expire_date( $this->expire_date ) ),
					$this->calc_expire_date( $this->expire_date )
				);
				break;

			case MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE:
				if ( 0 == $total_price ) {
					if ( $short ) {
						$lbl = __( 'Nothing (%5$s - %6$s)', MS_TEXT_DOMAIN );
					} else {
						$lbl = __( 'You will pay nothing for access from %3$s until %4$s.', MS_TEXT_DOMAIN );
					}
				} else {
					if ( $short ) {
						$lbl = __( '<span class="price">%1$s %2$s</span> (%5$s - %6$s)', MS_TEXT_DOMAIN );
					} else {
						$lbl = __( 'You will pay <span class="price">%1$s %2$s</span> to access from %3$s until %4$s.', MS_TEXT_DOMAIN );
					}
				}

				$desc .= sprintf(
					$lbl,
					$currency,
					$total_price,
					MS_Helper_Period::format_date( $membership->period_date_start ),
					MS_Helper_Period::format_date( $membership->period_date_end ),
					$membership->period_date_start,
					$membership->period_date_end
				);
				break;

			case MS_Model_Membership::PAYMENT_TYPE_RECURRING:
				if ( 1 == $membership->pay_cycle_repetitions ) {
					// Exactly 1 payment. Actually same as the "finite" type.
					if ( $short ) {
						$lbl = __( '<span class="price">%1$s %2$s</span> (once)', MS_TEXT_DOMAIN );
					} else {
						$lbl = __( 'You will pay <span class="price">%1$s %2$s</span> once.', MS_TEXT_DOMAIN );
					}
				} else {
					if ( $membership->pay_cycle_repetitions > 1 ) {
						// Fixed number of payments (more than 1)
						if ( $short ) {
							$lbl = __( '%4$s times <span class="price">%1$s %2$s</span> (each %3$s)', MS_TEXT_DOMAIN );
						} else {
							$lbl = __( 'You will make %4$s payments of <span class="price">%1$s %2$s</span>, one each %3$s.', MS_TEXT_DOMAIN );
						}
					} else {
						// Indefinite number of payments
						if ( $short ) {
							$lbl = __( '<span class="price">%1$s %2$s</span> (each %3$s)', MS_TEXT_DOMAIN );
						} else {
							$lbl = __( 'You will pay <span class="price">%1$s %2$s</span> each %3$s.', MS_TEXT_DOMAIN );
						}
					}
				}

				$desc .= sprintf(
					$lbl,
					$currency,
					$total_price,
					MS_Helper_Period::get_period_desc( $membership->pay_cycle_period ),
					$membership->pay_cycle_repetitions
				);
				break;
		}

		if ( $this->is_trial_eligible() && 0 != $total_price ) {
			if ( 0 == absint( $trial_price ) ) {
				if ( $short ) {
					if ( MS_Model_Membership::PAYMENT_TYPE_RECURRING == $membership->payment_type ) {
						$lbl = __( 'after %4$s', MS_TEXT_DOMAIN );
					} else {
						$lbl = __( 'on %4$s', MS_TEXT_DOMAIN );
					}
				} else {
					$trial_price = __( 'nothing', MS_TEXT_DOMAIN );
					$lbl = __( 'The trial period of %1$s is for free.', MS_TEXT_DOMAIN );
				}
			} else {
				$trial_price = MS_Helper_Billing::format_price( $trial_price );
				$lbl = __( 'For the trial period of %1$s you only pay <span class="price">%2$s %3$s</span>.', MS_TEXT_DOMAIN );
			}

			$desc .= sprintf(
				' <br />' . $lbl,
				MS_Helper_Period::get_period_desc( $membership->trial_period, true ),
				$currency,
				$trial_price,
				MS_Helper_Period::format_date( $invoice->due_date, __( 'M j', MS_TEXT_DOMAIN ) )
			);
		}

		return apply_filters(
			'ms_model_relationship_get_payment_description',
			$desc,
			$membership
		);
	}

	/**
	 * Saves information on a payment that was made.
	 *
	 * @since 1.1.0
	 */
	public function add_payment( $amount, $gateway ) {
		if ( ! is_array( $this->payments ) ) {
			$this->payments = array();
		}

		// Update the payment-gateway.
		$this->gateway_id = $gateway;

		if ( $amount > 0 ) {
			$this->payments[] = array(
				'date' => MS_Helper_Period::current_date( MS_Helper_Period::DATE_TIME_FORMAT ),
				'amount' => $amount,
				'gateway' => $gateway,
			);
		}

		// Upon first payment set the start date to current date.
		if ( 1 == count( $this->payments ) && ! $this->trial_expire_date ) {
			$this->set_start_date();
		}

		// Updates the subscription status.
		if ( MS_Gateway_Free::ID == $gateway && $this->is_trial_eligible() ) {
			// Calculate the final trial expire date.

			/*
			 * Important:
			 * FIRST set the TRIAL EXPIRE DATE, otherwise status is set to
			 * active instead of trial!
			 */
			$this->set_trial_expire_date();

			$this->set_status( MS_Model_Relationship::STATUS_TRIAL );
		} else {
			/*
			 * Important:
			 * FIRST set the SUBSCRIPTION STATUS, otherwise the expire date is
			 * based on start_date instead of trial_expire_date!
			 */
			$this->set_status( MS_Model_Relationship::STATUS_ACTIVE );

			/*
			 * Renew period. Every time this function is called, the expire
			 * date is extended for 1 period
			 */
			$this->expire_date = $this->calc_expire_date(
				$this->expire_date, // Extend past the current expire date.
				true                // Grant the user a full payment interval.
			);
		}

		$this->save();
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
		$ignored_status = apply_filters(
			'ms_model_relationship_unvalidated_status',
			array(
				self::STATUS_DEACTIVATED,
				self::STATUS_TRIAL_EXPIRED,
			),
			'set'
		);
		$membership = $this->get_membership();

		if ( self::STATUS_PENDING == $this->status && $membership->is_free() ) {
			// Skip "Pending" for free memberships.
			$status = self::STATUS_ACTIVE;
			$this->handle_status_change( $status );
		} elseif ( in_array( $status, $ignored_status ) ) {
			// No validation for this status.
			$this->status = $status;
		} else {
			// Check if this status is still valid.
			$calc_status = $this->calculate_status( $status );
			$this->handle_status_change( $calc_status );
		}

		$this->status = apply_filters(
			'ms_model_relationship_set_status',
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
		return apply_filters(
			'membership_model_relationship_get_status',
			$this->status,
			$this
		);
	}

	/**
	 * Returns a translated version of the invoice status
	 *
	 * @since  1.1.1.4
	 * @return string
	 */
	public function status_text() {
		static $Status = null;

		if ( null === $Status ) {
			$Status = self::get_status_types();
		}

		$result = $this->status;

		if ( isset( $Status[$this->status] ) ) {
			$result = $Status[$this->status];
		}

		return apply_filters(
			'ms_subscription_status_text',
			$result,
			$this->status
		);
	}

	/**
	 * Calculate the membership status.
	 *
	 * Calculate status for the membership verifying the start date,
	 * trial exire date and expire date.
	 *
	 * @since 1.0.0
	 *
	 * @param string $set_status The set status to compare.
	 * @return string The calculated status.
	 */
	protected function calculate_status( $set_status = null ) {
		/**
		 * Documented in check_membership_status()
		 *
		 * @since 1.1.0.5
		 */
		if ( MS_Plugin::get_modifier( 'MS_LOCK_SUBSCRIPTIONS' ) ) {
			return $set_status;
		}

		$membership = $this->get_membership();
		$calc_status = null;
		$check_trial = $this->is_trial_eligible();

		if ( ! empty( $this->payments ) ) {
			/*
			 * The user already paid for this membership, so don't check for
			 * trial status anymore
			 */
			$check_trial = false;
		}

		// If the start-date is not reached yet, then set membership to Pending.
		if ( empty( $calc_status )
			&& ! empty( $this->start_date )
			&& strtotime( $this->start_date ) > strtotime( MS_Helper_Period::current_date() )
		) {
			$calc_status = self::STATUS_WAITING;
		}

		if ( $check_trial ) {
			if ( empty( $calc_status )
				&& strtotime( $this->trial_expire_date ) >= strtotime( MS_Helper_Period::current_date() )
			) {
				$calc_status = self::STATUS_TRIAL;
			}

			if ( empty( $calc_status )
				&& strtotime( $this->trial_expire_date ) < strtotime( MS_Helper_Period::current_date() )
			) {
				$calc_status = self::STATUS_TRIAL_EXPIRED;
			}
		}

		// Permanent memberships grant instant access, no matter what.
		if ( empty( $calc_status )
			&& MS_Model_Membership::PAYMENT_TYPE_PERMANENT == $membership->payment_type
		) {
			$calc_status = self::STATUS_ACTIVE;
		}

		// If expire date is empty and Active-state is requests then use active.
		if ( empty( $calc_status )
			&& empty( $this->expire_date )
			&& self::STATUS_ACTIVE == $set_status
		) {
			$calc_status = self::STATUS_ACTIVE;
		}

		// If expire date is not reached then membership obviously is active.
		if ( empty( $calc_status )
			&& ! empty( $this->expire_date )
			&& strtotime( $this->expire_date ) >= strtotime( MS_Helper_Period::current_date() )
		) {
			$calc_status = self::STATUS_ACTIVE;
		}

		// If no other condition was true then the expire date was reached.
		if ( empty( $calc_status ) ) {
			$calc_status = self::STATUS_EXPIRED;
		}

		// Did the user cancel the membership?
		$cancel_it = self::STATUS_CANCELED == $set_status
			|| (
				self::STATUS_CANCELED == $this->status
				&& self::STATUS_ACTIVE != $set_status
				&& self::STATUS_TRIAL != $set_status
			);
		if ( $cancel_it ) {
			/*
			 * When a membership is cancelled then it will stay "Cancelled"
			 * until the expiration date is reached. A user has access to the
			 * contents of a cancelled membership until it expired.
			 */

			if ( self::STATUS_EXPIRED == $calc_status ) {
				// Membership has expired. Finally deactivate it!
				// (possibly it was cancelled a few days earlier)
				$calc_status = self::STATUS_DEACTIVATED;
			} elseif ( self::STATUS_TRIAL_EXPIRED == $calc_status ) {
				// Trial period has expired. Finally deactivate it!
				$calc_status = self::STATUS_DEACTIVATED;
			} elseif ( self::STATUS_TRIAL == $calc_status ) {
				// User can keep access until trial period finishes...
				$calc_status = self::STATUS_CANCELED;
			} elseif ( MS_Model_Membership::PAYMENT_TYPE_PERMANENT == $membership->payment_type ) {
				// This membership has no expiration-time. Deactivate it!
				$calc_status = self::STATUS_DEACTIVATED;
			} else {
				// Wait until the expiration date is reached...
				$calc_status = self::STATUS_CANCELED;
			}
		}

		// Fallback to "Expired" status
		if ( empty( $calc_status ) ) {
			$calc_status = self::STATUS_EXPIRED;
		}

		return apply_filters(
			'membership_model_relationship_calculate_status',
			$calc_status,
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
	 * @param string $new_status The status to change to.
	 */
	public function handle_status_change( $new_status ) {
		do_action(
			'ms_model_relationship_handle_status_change_before',
			$new_status,
			$this
		);

		if ( empty( $new_status ) ) { return false; }
		if ( $new_status == $this->status ) { return false; }
		if ( ! array_key_exists( $new_status, self::get_status_types() ) ) { return false; }

		if ( $this->is_simulated ) {
			// Do not trigger any events for simulated relationships.
		} elseif ( self::STATUS_DEACTIVATED == $new_status ) {
			/*
			 * Deactivated manually or automatically after a limited
			 * expiration-period or trial period ended.
			 */
			MS_Model_Event::save_event(
				MS_Model_Event::TYPE_MS_DEACTIVATED,
				$this
			);
		} else {
			// Current status to change from.
			switch ( $this->status ) {
				case self::STATUS_PENDING:
					// signup
					if ( in_array( $new_status, array( self::STATUS_TRIAL, self::STATUS_ACTIVE ) ) ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_SIGNED_UP, $this );

						// When changing from Pending -> Trial set trial_period_completed to true.
						$this->trial_period_completed = true;
					}
					break;

				case self::STATUS_TRIAL:
					// Trial finished
					if ( self::STATUS_TRIAL_EXPIRED == $new_status ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_TRIAL_FINISHED, $this );
					} elseif ( self::STATUS_ACTIVE == $new_status ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RENEWED, $this );
					} elseif ( self::STATUS_CANCELED == $new_status ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_CANCELED, $this );
					}
					break;

				case self::STATUS_TRIAL_EXPIRED:
					if ( self::STATUS_ACTIVE == $new_status ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RENEWED, $this );
					}
					break;

				case self::STATUS_ACTIVE:
					if ( self::STATUS_CANCELED == $new_status ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_CANCELED, $this );
					} elseif ( self::STATUS_EXPIRED == $new_status ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_EXPIRED, $this );
					}
					break;

				case self::STATUS_EXPIRED:
				case self::STATUS_CANCELED:
					if ( self::STATUS_ACTIVE == $new_status ) {
						MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_RENEWED, $this );
					}
					break;

				case self::STATUS_DEACTIVATED:
					break;

				case self::STATUS_WAITING:
					// Start date is not reached yet, so don't do anything.
					break;
			}
		}

		$this->status = apply_filters(
			'ms_model_relationship_set_status',
			$new_status
		);
		$this->save();

		do_action(
			'ms_model_relationship_handle_status_change_after',
			$new_status,
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
				$desc = sprintf(
					'%s <span class="ms-date">%s</span>',
					__( 'Membership Trial expires on ', MS_TEXT_DOMAIN ),
					MS_Helper_Period::format_date( $this->trial_expire_date )
				);
				break;

			case self::STATUS_ACTIVE:
				if ( ! empty( $this->expire_date ) ) {
					$desc = sprintf(
						'%s <span class="ms-date">%s</span>',
						__( 'Membership expires on ', MS_TEXT_DOMAIN ),
						MS_Helper_Period::format_date( $this->expire_date )
					);
				}
				else {
					$desc = __( 'Permanent access.', MS_TEXT_DOMAIN );
				}
				break;

			case self::STATUS_TRIAL_EXPIRED:
			case self::STATUS_EXPIRED:
				$desc = sprintf(
					'%s <span class="ms-date">%s</span>',
					__( 'Membership expired since ', MS_TEXT_DOMAIN ),
					MS_Helper_Period::format_date( $this->expire_date )
				);
				break;

			case self::STATUS_CANCELED:
				$desc = sprintf(
					'%s <span class="ms-date">%s</span>',
					__( 'Membership canceled, valid until it expires on ', MS_TEXT_DOMAIN ),
					MS_Helper_Period::format_date( $this->expire_date )
				);
				break;

			case self::STATUS_DEACTIVATED:
				$desc = __( 'Membership deactivated.', MS_TEXT_DOMAIN );
				break;
		}

		return apply_filters(
			'ms_model_relationship_get_status_description',
			$desc
		);
	}

	/**
	 * Check membership status.
	 *
	 * Execute actions when time/period condition are met.
	 * E.g. change membership status, add communication to queue, create invoices.
	 *
	 * This check is called via a cron job.
	 *
	 * @since 1.0.0
	 * @see MS_Model_Plugin::check_membership_status()
	 */
	public function check_membership_status() {
		do_action(
			'ms_model_relationship_check_membership_status_before',
			$this
		);

		/**
		 * Use `define( 'MS_LOCK_SUBSCRIPTIONS', true );` in wp-config.php to prevent
		 * Protected Content from sending *any* emails to users.
		 * Also any currently enqueued message is removed from the queue
		 *
		 * @since 1.1.0.5
		 */
		if ( MS_Plugin::get_modifier( 'MS_LOCK_SUBSCRIPTIONS' ) ) {
			return false;
		}

		$comms = MS_Model_Communication::load_communications();
		$invoice_before_days = 5;//@todo create a setting to configure this period.
		$deactivate_expired_after_days = 30; //@todo create a setting to configure this period.
		$deactivate_pending_after_days = 30; //@todo create a setting to configure this period.
		$deactivate_trial_expired_after_days = 5; //@todo create a setting to configure this period.

		$membership = $this->get_membership();
		$remaining_days = $this->get_remaining_period();
		$remaining_trial_days = $this->get_remaining_trial_period();

		//@todo: Add a flag to subscriptions with sent communications. Then improve the conditions below to prevent multiple emails.

		do_action(
			'ms_check_membership_status-' . $this->status,
			$this,
			$remaining_days,
			$remaining_trial_days
		);

		// Update the Subscription status.
		$cur_status = $this->calculate_status();

		switch ( $cur_status ) {
			case self::STATUS_TRIAL:
				if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL )
					&& MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_AUTO_MSGS_PLUS )
				) {

					// Send trial end communication.
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_BEFORE_TRIAL_FINISHES ];

					if ( $comm->enabled ) {
						$days = MS_Helper_Period::get_period_in_days(
							$comm->period['period_unit'],
							$comm->period['period_type']
						);
						//@todo: This will send out the reminder multiple times on the reminder-day (4 times or more often)
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
					// Mark the trial period as completed. $this->save() is below.
					$this->trial_period_completed = true;

					// Request payment to the gateway (for gateways that allows it).
					$gateway = $this->get_gateway();

					/*
					 * The subscription will be either automatically activated
					 * or set to pending.
					 *
					 * Important: Set trial_period_completed=true before calling
					 * request_payment()!
					 */
					if ( $gateway->request_payment( $this ) ) {
						$cur_status = self::STATUS_ACTIVE;
						$this->status = $cur_status;
						$this->config_period(); // Needed because of status change.
					}

					// Check for card expiration
					$gateway->check_card_expiration( $this );

					// Deactivate expired memberships after a period of time.
					if ( $deactivate_trial_expired_after_days < - $remaining_trial_days ) {
						$this->deactivate_membership();
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
					'ms_check_membership_status-' . $this->status,
					$this
				);

				/*
				 * Only "Recurring" memberships will ever try to automatically
				 * renew the subscription. All other types will expire when the
				 * end date is reached.
				 */
				$auto_renew = $membership->payment_type == MS_Model_Membership::PAYMENT_TYPE_RECURRING;
				$deactivate = false;
				$invoice = null;

				if ( $auto_renew && $membership->pay_cycle_repetitions > 0 ) {
					/*
					 * The membership has a payment-repetition limit.
					 * When this limit is reached then we do not auto-renew the
					 * subscription but expire it.
					 */
					$payments = lib2()->array->get( $this->payments );
					if ( count( $payments ) >= $membership->pay_cycle_repetitions ) {
						$auto_renew = false;
					}
				}

				if ( $auto_renew ) {
					if ( $remaining_days < $invoice_before_days ) {
						// Create a new invoice.
						$invoice = $this->get_next_invoice();
					} else {
						$invoice = $this->get_current_invoice();
					}
				} else {
					$invoice = $this->get_current_invoice();
				}

				// Advanced communications Add-on.
				if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_AUTO_MSGS_PLUS ) ) {
					// Before finishes communication.
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_BEFORE_FINISHES ];
					$days = MS_Helper_Period::get_period_in_days(
						$comm->period['period_unit'],
						$comm->period['period_type']
					);
					if ( $days == $remaining_days ) {
						$comm->add_to_queue( $this->id );
						MS_Model_Event::save_event(
							MS_Model_Event::TYPE_MS_BEFORE_FINISHES,
							$this
						);
					}

					// After finishes communication.
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_AFTER_FINISHES ];
					$days = MS_Helper_Period::get_period_in_days(
						$comm->period['period_unit'],
						$comm->period['period_type']
					);

					if ( $remaining_days < 0 && $days == abs( $remaining_days ) ) {
						$comm->add_to_queue( $this->id );
						MS_Model_Event::save_event(
							MS_Model_Event::TYPE_MS_AFTER_FINISHES,
							$this
						);
					}

					// Before payment due.
					$comm = $comms[ MS_Model_Communication::COMM_TYPE_BEFORE_PAYMENT_DUE ];
					$days = MS_Helper_Period::get_period_in_days(
						$comm->period['period_unit'],
						$comm->period['period_type']
					);
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
					$days = MS_Helper_Period::get_period_in_days(
						$comm->period['period_unit'],
						$comm->period['period_type']
					);
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
				} // -- End of advanced communications Add-on

				// Subscription ended. See if we can renew it.
				if ( $remaining_days <= 0 ) {
					if ( $auto_renew ) {
						/*
						 * The membership can be renewed. Try to renew it
						 * automatically by requesting the next payment from the
						 * payment gateway (only works if gateway supports this)
						 */
						$gateway = $this->get_gateway();
						$gateway->check_card_expiration( $this );
						$gateway->request_payment( $this );

						// Check if the payment was successful.
						$remaining_days = $this->get_remaining_period();

						/*
						 * User did not renew the membership. Give him some time
						 * to react before restricting his access.
						 */
						if ( $deactivate_expired_after_days < - $remaining_days ) {
							$deactivate = true;
						}
					} else {
						$deactivate = true;
					}
				}

				if ( $deactivate ) {
					$this->deactivate_membership();

					// Move membership to configured membership.
					$membership = $this->get_membership();

					$new_membership = MS_Factory::load(
						'MS_Model_Membership',
						$membership->on_end_membership_id
					);

					if ( $new_membership->is_valid() ) {
						$member = MS_Factory::load( 'MS_Model_Member', $this->user_id );
						$new_subscription = $member->add_membership(
							$membership->on_end_membership_id,
							$this->gateway_id
						);

						MS_Model_Event::save_event(
							MS_Model_Event::TYPE_MS_MOVED,
							$new_subscription
						);

						/*
						 * If the new membership is paid we want that the user
						 * confirms the payment in his account. So we set it
						 * to "Pending" first.
						 */
						if ( $new_membership->is_free() ) {
							$new_subscription->status = self::STATUS_PENDING;
						}
					}
				}
				break;

			case self::STATUS_PENDING:
			case self::STATUS_DEACTIVATED:
			default:
				break;
		}

		// Save the new status.
		$this->status = $cur_status;
		$this->save();

		// Save the changed email queue.
		foreach ( $comms as $comm ) {
			$comm->save();
		}

		do_action(
			'ms_model_relationship_check_membership_status_after',
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
					MS_Helper_Debug::log( 'Property does not exist: ' . $property );
				} else {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters(
			'ms_model_relationship__get',
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
				if ( property_exists( $this, $property ) ) {
					$this->$property = $value;
				}
				break;
		}

		do_action(
			'ms_model_relationship__set_after',
			$property,
			$value,
			$this
		);
	}

}