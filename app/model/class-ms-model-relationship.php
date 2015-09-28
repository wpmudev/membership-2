<?php
/**
 * Subscription model (former "Membership Relationship").
 *
 * The Subscription defines which user has access to which membership and for
 * how long. Do not confuse this with an invoice - a single subscription can
 * have multiple invoices!
 *
 * Note that all properties are declared protected but they can be access
 * directly (e.g. `$membership->type` to get the type value).
 * There are magic methods \_\_get() and \_\_set() that do some validation before
 * accessing the properties.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Relationship extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * @since  1.0.0
	 * @internal  Use self::get_post_type() instead!
	 * @var string $POST_TYPE
	 */
	protected static $POST_TYPE = 'ms_relationship';

	/**
	 * Membership Relationship Status constants.
	 * Pending is the first status that means the member did not confirm his
	 * intention to complete his payment/registration.
	 *
	 * NO ACCESS.
	 *
	 * @since  1.0.0
	 * @see $status $status property.
	 */
	const STATUS_PENDING = 'pending';

	/**
	 * Membership Relationship Status constants.
	 * This status has a much higher value than PENDING, because it means that
	 * the member already made a payment, but the subscription is not yet
	 * activated because the start date was not reached.
	 *
	 * NO ACCESS.
	 *
	 * @since  1.0.0
	 * @see $status $status property.
	 */
	const STATUS_WAITING = 'waiting';

	/**
	 * Membership Relationship Status constants.
	 *
	 * FULL ACCESS TO MEMBERSHIP CONTENTS.
	 *
	 * @since  1.0.0
	 * @see $status $status property.
	 */
	const STATUS_ACTIVE = 'active';

	/**
	 * Membership Relationship Status constants.
	 *
	 * FULL ACCESS TO MEMBERSHIP CONTENTS.
	 *
	 * @since  1.0.0
	 * @see $status $status property.
	 */
	const STATUS_TRIAL = 'trial';

	/**
	 * Membership Relationship Status constants.
	 * User cancelled his subscription but the end date of the current payment
	 * period is not reached yet. The user has full access to the membership
	 * contents until the end date is reached.
	 *
	 * FULL ACCESS TO MEMBERSHIP CONTENTS.
	 *
	 * @since  1.0.0
	 * @see $status $status property.
	 */
	const STATUS_CANCELED = 'canceled';

	/**
	 * Membership Relationship Status constants.
	 *
	 * NO ACCESS.
	 *
	 * @since  1.0.0
	 * @see $status $status property.
	 */
	const STATUS_TRIAL_EXPIRED = 'trial_expired';

	/**
	 * Membership Relationship Status constants.
	 * End-Date reached. The subscription is available for renewal for a few
	 * more days.
	 *
	 * NO ACCESS.
	 *
	 * @since  1.0.0
	 * @see $status $status property.
	 */
	const STATUS_EXPIRED = 'expired';

	/**
	 * Membership Relationship Status constants.
	 * Deactivated means, that we're completely done with this subscription.
	 * It's not displayed for renewal and the member can be set to inactive now.
	 *
	 * NO ACCESS.
	 *
	 * @since  1.0.0
	 * @see $status $status property.
	 */
	const STATUS_DEACTIVATED = 'deactivated';

	/**
	 * The Membership ID.
	 *
	 * @since  1.0.0
	 * @var string $membership_id
	 */
	protected $membership_id;

	/**
	 * The Payment Gateway ID.
	 *
	 * @since  1.0.0
	 * @var string $gateway_id
	 */
	protected $gateway_id;

	/**
	 * The start date of the membership relationship.
	 *
	 * @since  1.0.0
	 * @var string $start_date
	 */
	protected $start_date;

	/**
	 * The expire date of the membership relationship.
	 *
	 * @since  1.0.0
	 * @var string $expire_date
	 */
	protected $expire_date;

	/**
	 * The trial expire date of the membership relationship.
	 *
	 * @since  1.0.0
	 * @var string $trial_expire_date
	 */
	protected $trial_expire_date;

	/**
	 * Trial period completed flag.
	 *
	 * Indicates if already used a trial period and can't have another trial period.
	 *
	 * @since  1.0.0
	 * @var string $trial_period_completed
	 */
	protected $trial_period_completed;

	/**
	 * The status of the membership relationship.
	 *
	 * @since  1.0.0
	 * @var string $status
	 */
	protected $status;

	/**
	 * Current invoice count.
	 * This is NOT the public invoice-number but an counter to determine how
	 * many invoices were generated for this subscription already.
	 *
	 * @since  1.0.0
	 * @var $current_invoice_number
	 */
	protected $current_invoice_number = 1;

	/**
	 * The moving/change/downgrade/upgrade from membership ID.
	 *
	 * This can be a single ID or a comma separated list of IDs.
	 *
	 * The value is set by function self::create_ms_relationship()
	 * It is used in MS_Model_Invoice:
	 * 1. When creating the invoice the move_from_id define the Pro Rating.
	 * 2. When an invoice is paid the move_from_id memberships are cancelled.
	 *
	 * @since  1.0.0
	 * @internal
	 * @var string $move_from_id
	 */
	protected $move_from_id = '';

	/**
	 * After the memberships specified by $move_from_id were cancelled their
	 * IDs are stored in this property for logging purposes.
	 *
	 * @since  1.0.1.0
	 * @internal
	 * @var string $cancelled_memberships
	 */
	protected $cancelled_memberships = '';

	/**
	 * Where the data came from. Can only be changed by data import tool
	 *
	 * @since  1.0.0
	 * @internal
	 * @var string
	 */
	protected $source = '';

	/**
	 * Relevant for imported items. This is the ID that was used by the import
	 * source.
	 *
	 * @since  1.0.1.0
	 * @internal
	 * @var string
	 */
	protected $source_id = '';

	/**
	 * The number of successful payments that were made for this subscription.
	 *
	 * We use this value to determine the end of a recurring payment plan.
	 * Also this information is displayed in the member-info popup (only for
	 * admins; see MS_View_Member_Dialog)
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @internal
	 * @var bool
	 */
	protected $is_simulated = false;

	/**
	 * The payment type that this subscription was created with.
	 * Since the user can change the payment_type of the membership any time
	 * we might end up with a subscription with an invalid expire date.
	 *
	 * This flag allows us to detect changes in the parent membership payment
	 * options so we can update this membership accordingly.
	 *
	 * @since  1.0.0
	 * @var string
	 */
	protected $payment_type = '';

	/**
	 * The related membership model object.
	 *
	 * @since  1.0.0
	 * @var MS_Model_Membership $membership
	 */
	private $membership;

	//
	//
	//
	// -------------------------------------------------------------- COLLECTION

	/**
	 * Returns the post-type of the current object.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return string The post-type name.
	 */
	public static function get_post_type() {
		return parent::_post_type( self::$POST_TYPE );
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Membership2 Subscriptions', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_customposttype_register_args',
			$args,
			self::get_post_type()
		);
	}

	/**
	 * Don't persist this fields.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @var string[] The fields to ignore when persisting.
	 */
	static public $ignore_fields = array(
		'membership',
		'post_type',
	);

	/**
	 * Return existing status types and names.
	 *
	 * @since  1.0.0
	 * @internal
	 *
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
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  int $membership_id The Membership to subscribe to.
	 * @param  int $user_id The user who subscribes to the membership.
	 * @param  string $gateway_id The gateway which is used for payment.
	 * @param  int|string $move_from_id A list of membership IDs to cancel on
	 *         payment. This property is handled by the MS_Model_Invoice class.
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
	 * @internal
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
			$subscription->status = self::STATUS_PENDING;
			$subscription->is_simulated = $is_simulated;

			if ( $is_simulated ) {
				$subscription->id = -1;
			}
		}

		// Always update these fields.
		$subscription->membership_id = $membership_id;
		$subscription->user_id = $user_id;
		$subscription->move_from_id = $move_from_id;
		$subscription->gateway_id = $gateway_id;
		$subscription->expire_date = '';

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

		$subscription->config_period();
		$membership = $subscription->get_membership();
		$subscription->payment_type = $membership->payment_type;

		if ( 'admin' == $gateway_id ) {
			$subscription->trial_period_completed = true;
			$subscription->status = self::STATUS_ACTIVE;

			// Set the start/expire dates. Do this *after* the set_status() call!
			$subscription->config_period();

			if ( ! $subscription->is_system() && ! $is_simulated ) {
				// Create event.
				MS_Model_Event::save_event( MS_Model_Event::TYPE_MS_SIGNED_UP, $subscription );
			}
		}

		$subscription->save();

		return $subscription;
	}

	/**
	 * Returns a list of subscription IDs that match the specified attributes.
	 *
	 * @since  1.0.1.0
	 * @internal
	 *
	 * @param  $args The query post args.
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array A list of subscription IDs.
	 */
	public static function get_subscription_ids( $args = null ) {
		static $Subscription_IDs = array();
		$args = self::get_query_args( $args );
		$key = md5( json_encode( $args ) );

		if ( ! isset( $Subscription_IDs[$key] ) ) {
			$Subscription_IDs[$key] = array();

			MS_Factory::select_blog();
			$query = new WP_Query( $args );
			$items = $query->posts;
			MS_Factory::revert_blog();
			$subscriptions = array();

			/**
			 * We only cache the IDs to avoid re-querying the database.
			 * The positive side effect is, that the memory used by the
			 * membership list will be freed again after the calling function
			 * is done with it.
			 *
			 * If we cache the whole list here, it would not occupy memory for
			 * the whole request duration which can cause memory_limit errors.
			 *
			 * @see MS_Model_Membership::get_memberships()
			 */
			foreach ( $items as $post_id ) {
				$Subscription_IDs[$key][] = $post_id;
			}
		}

		return $Subscription_IDs[$key];
	}

	/**
	 * Retrieve membership relationships.
	 *
	 * By default returns a list of relationships that are not "pending" or
	 * "deactivated". To get a list of all relationships use this:
	 * $args = array( 'status' => 'all' )
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  array $args The query post args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @param  bool $include_system Whether to include the base/guest memberships.
	 * @return MS_Model_Relationship[] The array of membership relationships.
	 */
	public static function get_subscriptions( $args = null, $include_system = false, $ordered = false ) {
		$ids = self::get_subscription_ids( $args );
		$subscriptions = array();

		foreach ( $ids as $id ) {
			$subscription = MS_Factory::load(
				'MS_Model_Relationship',
				$id
			);

			// Remove System-Memberships
			if ( $subscription->is_system() && ! $include_system ) {
				continue;
			}

			if ( ! empty( $args['author'] ) ) {
				$subscriptions[ $subscription->membership_id ] = $subscription;
			} else {
				$subscriptions[ $id ] = $subscription;
			}
		}

		$subscriptions = apply_filters(
			'ms_model_relationship_get_subscriptions',
			$subscriptions,
			$args
		);

		// Sort the subscription list.
		usort(
			$subscriptions,
			array( __CLASS__, 'sort_by_priority' )
		);

		return $subscriptions;
	}

	/**
	 * Sort function used as second param by `uasort()` to sort a subscription
	 * list by membership priority.
	 * Memberships with equal priority are sorted alphabeically.
	 *
	 * @since  1.0.1.0
	 * @param  MS_Model_Relationship $a
	 * @param  MS_Model_Relationship $b
	 * @return int -1: a < b | 0: a = b | +1: a > b
	 */
	static public function sort_by_priority( $a, $b ) {
		$m1 = $a->get_membership();
		$m2 = $b->get_membership();

		if ( $m1->priority == $m2->priority ) {
			return $m1->name < $m2->name ? -1 : 1;
		} else {
			return $m1->priority - $m2->priority;
		}
	}

	/**
	 * Retrieve membership relationship count.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  $args The query post args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The membership relationship count.
	 */
	public static function get_subscription_count( $args = null ) {
		$ids = self::get_subscription_ids( $args );
		$count = count( $ids );

		return apply_filters(
			'ms_model_relationship_get_subscription_count',
			$count,
			$args
		);
	}

	/**
	 * Retrieve membership relationship.
	 *
	 * @since  1.0.0
	 * @internal
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

		MS_Factory::select_blog();
		$query = new WP_Query( $args );
		$post = $query->posts;
		MS_Factory::revert_blog();

		$subscription = null;

		if ( ! empty( $post[0] ) ) {
			$subscription = MS_Factory::load(
				'MS_Model_Relationship',
				$post[0]
			);
		}

		return apply_filters(
			'ms_model_relationship_get_subscription',
			$subscription,
			$args
		);
	}

	/**
	 * Create default args to search posts.
	 *
	 * Merge received args to default ones.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param $args The query post args
	 *         @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The args.
	 */
	public static function get_query_args( $args = null ) {
		$defaults = apply_filters(
			'ms_model_relationship_get_query_args_defaults',
			array(
				'post_type' => self::get_post_type(),
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
			switch ( $args['status'] ) {
				case 'valid':
					$args['meta_query']['status'] = array(
						'key' => 'status',
						'value' => self::STATUS_DEACTIVATED,
						'compare' => 'NOT LIKE',
					);
					break;

				case 'exp':
					$args['meta_query']['status'] = array(
						'key' => 'status',
						'value' => array( self::STATUS_TRIAL_EXPIRED, self::STATUS_EXPIRED ),
						'compare' => 'IN',
					);
					break;

				case 'all':
					// No params for this. We want all items!
					break;

				default:
					$args['meta_query']['status'] = array(
						'key' => 'status',
						'value' => $args['status'],
						'compare' => '=',
					);
					break;
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
	 * @since  1.0.0
	 * @api
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
	 * @since  1.0.0
	 * @api
	 */
	public function deactivate_membership() {
		do_action(
			'ms_model_relationship_deactivate_membership_before',
			$this
		);

		/**
		 * Documented in check_membership_status()
		 *
		 * @since  1.0.0
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
	 * Don't save automatically assigned visitor/system memberships.
	 *
	 * @since  1.0.0
	 * @api
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
	 * @since  1.0.0
	 * @internal
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
	 * It returns FALSE if
	 *  .. Trial Add-on is disabled
	 *  .. The membership does not allow a trial period
	 *  .. The current user already consumed trial period or is in trial period
	 *
	 * @since  1.0.0
	 * @api
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

		if ( ! $membership->has_trial() ) {
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
	 * Returns true if the current subscription is expired.
	 *
	 * @since  1.0.1.0
	 * @return bool
	 */
	public function is_expired() {
		$result = false;

		if ( self::STATUS_EXPIRED == $this->status ) {
			$result = true;
		} elseif ( self::STATUS_TRIAL_EXPIRED == $this->status ) {
			$result = true;
		}

		return apply_filters(
			'ms_model_relationship_is_expired',
			$result,
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
	 * @since  1.0.0
	 * @api
	 *
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
	 * @since  1.0.0
	 * @api
	 *
	 * @param string $start_date Optional. The start date to set.
	 *        Default will be calculated/current date.
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
	 * @since  1.0.0
	 * @api
	 *
	 * @param string $trial_expire_date Optional. The trial expire date to set.
	 *        Default will be calculated based on start_date.
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

			/*
			 * When payment-type is DATE-RANGE make sure that the trial period
			 * is not longer than the specified end-date
			 */
			$membership = $this->get_membership();
			if ( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
				if ( $membership->period_date_end < $trial_expire_date ) {
					$trial_expire_date = $membership->period_date_end;
				}
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
	 * @since  1.0.0
	 * @api
	 *
	 * @param string $expire_date Optional. The expire date to set.
	 *        Default will be calculated based on start_date.
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
	 * @since  1.0.0
	 * @internal
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
	 * @since  1.0.0
	 * @internal
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
	 * Configure the membership period dates based on the current subscription
	 * status.
	 *
	 * Set initial membership period or renew periods.
	 *
	 * @since  1.0.0
	 * @internal
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
				/*
				 * If no expire date is set yet then add it now.
				 * This case happens when the subscription was added via the
				 * Members admin page (by an admin and not by the member)
				 *
				 * Usually nothing is done here as the expire date is only
				 * changed by add_payment().
				 */
				if ( empty( $this->expire_date ) ) {
					$membership = $this->get_membership();
					if ( MS_Model_Membership::PAYMENT_TYPE_PERMANENT != $membership->payment_type ) {
						$this->set_expire_date();
					}
				}
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
	 * Returns the number of days since the subscription started.
	 *
	 * Example: If the start_date is 14 days ago it will return the value 14.
	 *
	 * @since  1.0.0
	 * @api
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
	 * Returns the number of days until trial period ends.
	 *
	 * @since  1.0.0
	 * @api
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
	 * Get number of days until this membership expires.
	 *
	 * @since  1.0.0
	 * @api
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
	 * Get Member model of the subscription owner.
	 *
	 * @since  1.0.0
	 * @api
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
	 * @since  1.0.0
	 * @api
	 *
	 * @return MS_Model_Invoice
	 */
	public function get_current_invoice( $create_missing = true ) {
		return MS_Model_Invoice::get_current_invoice( $this, $create_missing );
	}

	/**
	 * Convenience function to access next invoice for this subscription.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return MS_Model_Invoice
	 */
	public function get_next_invoice( $create_missing = true ) {
		return MS_Model_Invoice::get_next_invoice( $this, $create_missing );
	}

	/**
	 * Convenience function to access previous invoice for this subscription.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return MS_Model_Invoice
	 */
	public function get_previous_invoice( $status = null ) {
		return MS_Model_Invoice::get_previous_invoice( $this, $status );
	}

	/**
	 * Get a list of all invoices linked to this relationship
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return MS_Model_Invoice[] List of invoices.
	 */
	public function get_invoices() {
		$invoices = MS_Model_Invoice::get_invoices(
			array(
				'nopaging' => true,
				'meta_query' => array(
					array(
						'key'   => 'ms_relationship_id',
						'value' => $this->id,
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
	 * Finds the first unpaid invoice of the current subscription and returns
	 * the invoice_id.
	 *
	 * If the subscription has no unpaid invoices then a new invoice is created!
	 *
	 * @since  1.0.2.0
	 * @return int The first invoice that is not paid yet.
	 */
	public function first_unpaid_invoice() {
		$invoice_id = 0;

		// Try to find the first unpaid invoice for the subscription.
		$invoices = $this->get_invoices();
		foreach ( $invoices as $invoice ) {
			if ( ! $invoice->is_paid() ) {
				$invoice_id = $invoice->id;
				break;
			}
		}

		// If no unpaid invoice was found: Create one.
		if ( ! $invoice_id ) {
			$invoice = $this->get_next_invoice();
			$invoice_id = $invoice->id;
		}

		return $invoice_id;
	}

	/**
	 * Get related Membership model.
	 *
	 * @since  1.0.0
	 * @api
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
	 * @since  1.0.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_base() {
		return $this->get_membership()->is_base();
	}

	/**
	 * Returns true if the related membership is the guest-membership.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_guest() {
		return $this->get_membership()->is_guest();
	}

	/**
	 * Returns true if the related membership is the user-membership.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_user() {
		return $this->get_membership()->is_user();
	}

	/**
	 * Returns true if the related membership is a system membership.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_system() {
		return $this->get_membership()->is_system();
	}

	/**
	 * Get related gateway model.
	 *
	 * @since  1.0.0
	 * @api
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
	 * Either creates or updates the value of a custom data field.
	 *
	 * Note: Remember to prefix the $key with a unique string to prevent
	 * conflicts with other plugins that also use this function.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 * @param  mixed $value The new value to assign to the field.
	 */
	public function set_custom_data( $key, $value ) {
		// Wrapper function, so this function shows up in API docs.
		parent::set_custom_data( $key, $value );
	}

	/**
	 * Removes a custom data field from this object.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 */
	public function delete_custom_data( $key ) {
		// Wrapper function, so this function shows up in API docs.
		parent::delete_custom_data( $key );
	}

	/**
	 * Returns the value of a custom data field.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key The field-key.
	 * @return mixed The value that was previously assigned to the custom field
	 *         or false if no value was set for the field.
	 */
	public function get_custom_data( $key ) {
		// Wrapper function, so this function shows up in API docs.
		return parent::get_custom_data( $key );
	}

	/**
	 * Get textual payment information description.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  MS_Model_Invoice $invoice Optional. Specific invoice that defines
	 *         the price. Default is the price defined in the membership.
	 * @param  bool $short Optional. Default is false. If set to true then a
	 *          hort sumary is returned
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

		$payment_type = $this->payment_type;
		if ( ! $payment_type ) {
			$payment_type = $membership->payment_type;
		}

		switch ( $payment_type ) {
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
					if ( MS_Model_Membership::PAYMENT_TYPE_RECURRING == $payment_type ) {
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
			$membership,
			$payment_type
		);
	}

	/**
	 * Saves information on a payment that was made.
	 * This function also extends the expire_date for one period if the
	 * membership payment-type is recurring or limited
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  float $amount The payment amount. Set to 0 for free subscriptions.
	 * @param  string $gateway The payment gateway-ID.
	 * @param  string $external_id A string that can identify the payment.
	 * @return bool True if the subscription has ACTIVE status after payment.
	 *         If the amount was 0 and membership uses a trial period the status
	 *         could also be TRIAL, in which case the returnv alue is false.
	 */
	public function add_payment( $amount, $gateway, $external_id = '' ) {
		$this->payments = lib3()->array->get( $this->payments );

		// Update the payment-gateway.
		if ( ! $this->gateway_id ) {
			$this->gateway_id = $gateway;
		} elseif ( MS_Gateway_Free::ID != $gateway ) {
			// Don't change an existing gateway to "Free".
			$this->gateway_id = $gateway;
		}

		if ( $amount > 0 ) {
			$this->payments[] = array(
				'date' => MS_Helper_Period::current_date( MS_Helper_Period::DATE_TIME_FORMAT ),
				'amount' => $amount,
				'gateway' => $gateway,
				'external_id' => $external_id,
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

			$this->set_status( self::STATUS_TRIAL );
		} else {
			/*
			 * Important:
			 * FIRST set the SUBSCRIPTION STATUS, otherwise the expire date is
			 * based on start_date instead of trial_expire_date!
			 */
			$this->set_status( self::STATUS_ACTIVE );

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

		// Thanks for paying or for starting your trial period!
		// You're officially active :)
		$member = $this->get_member();
		$member->is_member = true;

		if ( self::STATUS_ACTIVE == $this->status ) {
			/**
			 * Make sure the new subscription is instantly available in the
			 * member object.
			 *
			 * Before version 1.0.1.2 the new subscription was available in the
			 * member object after the next page refresh.
			 *
			 * @since 1.0.1.2
			 */
			$found = false;
			$subscriptions = $member->subscriptions;
			foreach ( $subscriptions as $sub ) {
				if ( $sub->membership_id == $this->membership_id ) {
					$found = true;
					break;
				}
			}
			if ( ! $found ) {
				$subscriptions[] = $this;
				$member->subscriptions = $subscriptions;
			}
		}

		$member->save();

		// Return true if the subscription is active.
		$paid_status = array(
			self::STATUS_ACTIVE,
			self::STATUS_WAITING,
		);
		$is_active = in_array( $this->status, $paid_status );
		return $is_active;
	}

	/**
	 * Returns a sanitized list of all payments.
	 *
	 * @since  1.0.2.0
	 * @return array
	 */
	public function get_payments() {
		$res = lib3()->array->get( $this->payments );

		foreach ( $res as $key => $info ) {
			if ( ! isset( $info['amount'] ) ) {
				unset( $res[$key] );
				continue;
			}

			if ( ! isset( $info['date'] ) ) { $res[$key]['date'] = ''; }
			if ( ! isset( $info['gateway'] ) ) { $res[$key]['gateway'] = ''; }
			if ( ! isset( $info['external_id'] ) ) { $res[$key]['external_id'] = ''; }
		}

		return $res;
	}

	/**
	 * Set membership relationship status.
	 *
	 * Validates every time.
	 * Check for status that need membership verification for trial, active and expired.
	 *
	 * @since  1.0.0
	 * @internal Use $this->status instead!
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

		if ( in_array( $status, $ignored_status ) ) {
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

		/**
		 * Trigger an action to allow other plugins to oberse a change in an
		 * subscription status.
		 *
		 * @since  1.0.0
		 * @var MS_Model_Relationship The subscription model.
		 * @var MS_Model_Member       The member who is affected.
		 */
		do_action(
			'ms_subscription_status-' . $this->status,
			$this,
			$membership,
			$this->get_member()
		);
	}

	/**
	 * Get membership relationship status.
	 *
	 * Validates every time.
	 *
	 * Verifies start and end date of a membership and updates status if expired.
	 *
	 * @since  1.0.0
	 * @internal Use $this->status instead!
	 *
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
	 * Returns an i18n translated version of the subscription status.
	 *
	 * @since  1.0.0
	 * @api
	 *
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
	 * @since  1.0.0
	 * @internal
	 *
	 * @param string $set_status The set status to compare.
	 * @return string The calculated status.
	 */
	protected function calculate_status( $set_status = null, $debug = false ) {
		/**
		 * Documented in check_membership_status()
		 *
		 * @since  1.0.0
		 */
		if ( MS_Plugin::get_modifier( 'MS_LOCK_SUBSCRIPTIONS' ) ) {
			return $set_status;
		}

		$membership = $this->get_membership();
		$calc_status = null;
		$debug_msg = array();
		$check_trial = $this->is_trial_eligible();

		if ( ! empty( $this->payments ) ) {
			/*
			 * The user already paid for this membership, so don't check for
			 * trial status anymore
			 */
			$check_trial = false;
		}

		// If the start-date is not reached yet, then set membership to Pending.
		if ( ! $calc_status
			&& ! empty( $this->start_date )
			&& strtotime( $this->start_date ) > strtotime( MS_Helper_Period::current_date() )
		) {
			$calc_status = self::STATUS_WAITING;
			$debug_msg[] = '[WAITING: Start-date not reached]';
		} elseif ( ! $calc_status && $debug ) {
			$debug_msg[] = '[Not WAITING: No start-date or start-date reached]';
		}

		if ( $check_trial ) {
			if ( ! $calc_status
				&& strtotime( $this->trial_expire_date ) >= strtotime( MS_Helper_Period::current_date() )
			) {
				$calc_status = self::STATUS_TRIAL;
				$debug_msg[] = '[TRIAL: Trial-Expire date not reached]';
			} elseif ( ! $calc_status && $debug ) {
				$debug_msg[] = '[Not TRIAL: Trial-Expire date reached]';
			}

			if ( ! $calc_status
				&& strtotime( $this->trial_expire_date ) < strtotime( MS_Helper_Period::current_date() )
			) {
				$calc_status = self::STATUS_TRIAL_EXPIRED;
				$debug_msg[] = '[TRIAL-EXPIRED: Trial-Expire date reached]';
			} elseif ( ! $calc_status && $debug ) {
				$debug_msg[] = '[Not TRIAL-EXPIRED: Trial-Expire date not reached]';
			}
		} elseif ( ! $calc_status && $debug ) {
			$debug_msg[] = '[Skipped TRIAL status]';
		}

		// Status an only become active when added by admin or invoice is paid.
		$can_activate = false;
		if ( 'admin' == $this->gateway_id ) {
			$can_activate = true;
			$debug_msg[] = '[Can activate: Admin gateway]';
		} elseif ( $membership->is_free() ) {
			$can_activate = true;
			$debug_msg[] = '[Can activate: Free membership]';
		} else {
			$valid_payment = false;
			// Check if there is *any* payment, no matter what height.
			foreach ( $this->get_payments() as $payment ) {
				if ( $payment['amount'] > 0 ) {
					$valid_payment = true;
					$debug_msg[] = '[Can activate: Payment found]';
					break;
				}
			}
			if ( ! $valid_payment ) {
				// Check if any invoice was paid already.
				for ( $ind = $this->current_invoice_number; $ind > 0; $ind -= 1 ) {
					$invoice = MS_Model_Invoice::get_invoice( $this->id, $ind );
					if ( ! $invoice ) { continue; }
					if ( $invoice->uses_trial ) { continue; }
					if ( $invoice->is_paid() ) {
						$valid_payment = true;
						$debug_msg[] = '[Can activate: Paid invoice found]';
						break;
					}
				}
			}
			if ( ! $valid_payment ) {
				// Check if the current invoice is free.
				$invoice = $this->get_current_invoice();
				if ( 0 == $invoice->total ) {
					$valid_payment = true;
				}
			}

			if ( $valid_payment ) {
				$can_activate = true;
			}

			if ( ! $can_activate && $debug ) {
				$debug_msg[] = sprintf(
					'[Can not activate: Gateway: %s; Price: %s; Invoice: %s]',
					$this->gateway_id,
					$membership->price,
					$invoice->total
				);
			}
		}

		if ( $can_activate ) {
			// Permanent memberships grant instant access, no matter what.
			if ( ! $calc_status
				&& MS_Model_Membership::PAYMENT_TYPE_PERMANENT == $membership->payment_type
			) {
				$calc_status = self::STATUS_ACTIVE;
				$debug_msg[] = '[ACTIVE(1): Payment-type is permanent]';
			} elseif ( ! $calc_status && $debug ) {
				$debug_msg[] = '[Not ACTIVE(1): Payment-type is not permanent]';
			}

			// If expire date is empty and Active-state is requests then use active.
			if ( ! $calc_status
				&& empty( $this->expire_date )
				&& self::STATUS_ACTIVE == $set_status
			) {
				$calc_status = self::STATUS_ACTIVE;
				$debug_msg[] = '[ACTIVE(2): Expire date empty and active requested]';
			} elseif ( ! $calc_status && $debug ) {
				$debug_msg[] = '[Not ACTIVE(2): Expire date set or wrong status-request]';
			}

			// If expire date is not reached then membership obviously is active.
			if ( ! $calc_status
				&& ! empty( $this->expire_date )
				&& strtotime( $this->expire_date ) >= strtotime( MS_Helper_Period::current_date() )
			) {
				$calc_status = self::STATUS_ACTIVE;
				$debug_msg[] = '[ACTIVE(3): Expire date set and not reached]';
			} elseif ( ! $calc_status && $debug ) {
				$debug_msg[] = '[Not ACTIVE(3): Expire date set and reached]';
			}
		} elseif ( ! $calc_status && self::STATUS_PENDING == $this->status ) {
			// Invoice is not paid yet.
			$calc_status = self::STATUS_PENDING;
			$debug_msg[] = '[PENDING: Cannot activate pending subscription]';
		} elseif ( ! $calc_status && $debug ) {
			$debug_msg[] = '[Not ACTIVE/PENDING: Cannot activate subscription]';
		}

		// If no other condition was true then the expire date was reached.
		if ( ! $calc_status ) {
			$calc_status = self::STATUS_EXPIRED;
			$debug_msg[] = '[EXPIRED: Default status]';
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
			} elseif ( self::STATUS_WAITING == $calc_status ) {
				// The membership did not yet start. Deactivate it!
				$calc_status = self::STATUS_DEACTIVATED;
			} elseif ( ! $this->expire_date ) {
				// Membership without expire date cannot be cancelled. Deactivate it!
				$calc_status = self::STATUS_DEACTIVATED;
			} else {
				// Wait until the expiration date is reached...
				$calc_status = self::STATUS_CANCELED;
			}
		}

		if ( $debug ) {
			// Intended debug output, leave it here.
			lib3()->debug->dump( $debug_msg );
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
	 * @since  1.0.0
	 * @internal
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
	 * Get a detailled status description.
	 *
	 * @since  1.0.0
	 * @api
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
	 * @since  1.0.0
	 * @internal  Used by Cron
	 * @see MS_Model_Plugin::check_membership_status()
	 */
	public function check_membership_status() {
		do_action(
			'ms_model_relationship_check_membership_status_before',
			$this
		);

		/**
		 * Use `define( 'MS_LOCK_SUBSCRIPTIONS', true );` in wp-config.php to prevent
		 * Membership2 from sending *any* emails to users.
		 * Also any currently enqueued message is removed from the queue
		 *
		 * @since  1.0.0
		 */
		if ( MS_Plugin::get_modifier( 'MS_LOCK_SUBSCRIPTIONS' ) ) {
			return false;
		}

		$membership = $this->get_membership();
		$remaining_days = $this->get_remaining_period();
		$remaining_trial_days = $this->get_remaining_trial_period();

		$comms = MS_Model_Communication::get_communications( $membership );
		$invoice_before_days = 5;//@todo create a setting to configure this period.
		$deactivate_expired_after_days = 30; //@todo create a setting to configure this period.
		$deactivate_pending_after_days = 30; //@todo create a setting to configure this period.
		$deactivate_trial_expired_after_days = 5; //@todo create a setting to configure this period.

		//@todo: Add a flag to subscriptions with sent communications. Then improve the conditions below to prevent multiple emails.

		do_action(
			'ms_check_membership_status-' . $this->status,
			$this,
			$remaining_days,
			$remaining_trial_days
		);

		// Update the Subscription status.
		$next_status = $this->calculate_status( null );

		switch ( $next_status ) {
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
						$next_status = self::STATUS_ACTIVE;
						$this->status = $next_status;
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
				 * Make sure the expire date has a correct value, in case the user
				 * changed the payment_type of the parent membership after this
				 * subscription was created.
				 */
				if ( $this->payment_type != $membership->payment_type ) {
					$this->payment_type = $membership->payment_type;

					switch ( $this->payment_type ) {
						case MS_Model_Membership::PAYMENT_TYPE_PERMANENT:
							$this->expire_date = false;
							break;

						default:
							// Either keep the current expire date (if valid) or
							// calculate a new expire date, based on current date.
							if ( ! $this->expire_date ) {
								$this->expire_date = $this->calc_expire_date(
									MS_Helper_Period::current_date()
								);
							}

							break;
					}

					// Recalculate the days until the subscription expires.
					$remaining_days = $this->get_remaining_period();

					// Recalculate the new Subscription status.
					$next_status = $this->calculate_status();
				}

				/*
				 * Only "Recurring" memberships will ever try to automatically
				 * renew the subscription. All other types will expire when the
				 * end date is reached.
				 */
				$auto_renew = ($membership->payment_type == MS_Model_Membership::PAYMENT_TYPE_RECURRING);
				$deactivate = false;
				$invoice = null;

				if ( $auto_renew && $membership->pay_cycle_repetitions > 0 ) {
					/*
					 * The membership has a payment-repetition limit.
					 * When this limit is reached then we do not auto-renew the
					 * subscription but expire it.
					 */
					$payments = $this->get_payments();
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
						if ( ! $new_membership->is_free() ) {
							$new_subscription->status = self::STATUS_PENDING;
						}
					}
				}
				break;

			case self::STATUS_DEACTIVATED:
				/*
				 * A subscription was finally deactivated.
				 * Lets check if the member has any other active subscriptions,
				 * or (if not) his account should be deactivated.
				 *
				 * First get a list of all subscriptions that do not have status
				 * Pending / Deactivated.
				 */
				$subscriptions = self::get_subscriptions(
					array( 'user_id' => $this->user_id )
				);

				// Check if there is a subscription that keeps the user active.
				$deactivate = true;
				foreach ( $subscriptions as $item ) {
					if ( $item->id == $this->id ) { continue; }
					$deactivate = false;
				}

				if ( $deactivate ) {
					$member = $this->get_member();
					$member->is_member = false;
					$member->save();
				}
				break;

			case self::STATUS_PENDING:
			default:
				// Do nothing.
				break;
		}

		// Save the new status.
		$this->status = $next_status;
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
	 * @since  1.0.0
	 * @internal
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
	 * @since  1.0.0
	 * @internal
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