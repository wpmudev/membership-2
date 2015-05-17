<?php
/**
 * Membership model.
 *
 * A membership defines payment options, access rules and similar details.
 * It is not related to a specific user!
 *
 * Note that all properties are declared protected but they can be access
 * directly (e.g. `$membership->type` to get the type value).
 * There are magic methods __get() and __set() that do some validation before
 * accessing the properties.
 *
 * @since 1.0.0
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Membership extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * @since 1.0.0
	 * @internal self::get_post_type() to get this value!
	 *
	 * @var string $POST_TYPE
	 */
	protected static $POST_TYPE = 'ms_membership';

	/**
	 * Membership type constant.
	 *
	 * @since 1.0.0
	 * @see $type  $type property.
	 */
	const TYPE_STANDARD = 'simple';

	/**
	 * Membership type constant.
	 *
	 * @since 1.0.0
	 * @see $type $type property.
	 */
	const TYPE_DRIPPED = 'dripped';

	/**
	 * Membership type constant.
	 * System membership, hidden, created automatically.
	 *
	 * @since 1.1.0
	 * @see $type $type property.
	 */
	const TYPE_BASE = 'base';

	/**
	 * Membership type constant.
	 * Guest membership, only one membership possible.
	 *
	 * @since 1.1.0
	 * @see $type $type property.
	 */
	const TYPE_GUEST = 'guest';

	/**
	 * Membership type constant.
	 * User membership, only one membership possible.
	 *
	 * @since 1.1.0
	 * @see $type $type property.
	 */
	const TYPE_USER = 'user';

	/**
	 * Membership payment type constants.
	 *
	 * @since 1.0.0
	 * @see $payment_type $payment_type property.
	 */
	const PAYMENT_TYPE_PERMANENT = 'permanent';

	/**
	 * Membership payment type constants.
	 *
	 * @since 1.0.0
	 * @see $payment_type $payment_type property.
	 */
	const PAYMENT_TYPE_FINITE = 'finite';

	/**
	 * Membership payment type constants.
	 *
	 * @since 1.0.0
	 * @see $payment_type $payment_type property.
	 */
	const PAYMENT_TYPE_DATE_RANGE = 'date-range';

	/**
	 * Membership payment type constants.
	 * The only type that auto-renews without asking the user!
	 *
	 * @since 1.0.0
	 * @see $payment_type $payment_type property.
	 */
	const PAYMENT_TYPE_RECURRING = 'recurring';

	/**
	 * Membership type.
	 * Default is TYPE_STANDARD.
	 *
	 * @since 1.0.0
	 * @var string $type
	 */
	protected $type = self::TYPE_STANDARD;

	/**
	 * Membership payment type.
	 * Default is PAYMENT_TYPE_PERMANENT.
	 *
	 * @since 1.0.0
	 * @var string $payment_type
	 */
	protected $payment_type = self::PAYMENT_TYPE_PERMANENT;

	/**
	 * Membership active status.
	 * By default a new membership is active.
	 *
	 * @since 1.0.0
	 * @var bool $active
	 */
	protected $active = true;

	/**
	 * Membership private status.
	 * Private means that the membership will not be displayed on the default
	 * registration page and membership list.
	 * Users can still stubscribe to the membership via the shortcode
	 * [ms-membership-buy] or by otherwise reaching the subscription URL.
	 *
	 * @since 1.0.0
	 * @var bool $private
	 */
	protected $private = false;

	/**
	 * Membership free status.
	 *
	 * @since 1.0.0
	 * @var bool $free.
	 */
	protected $is_free = false;

	/**
	 * Membership price.
	 *
	 * @since 1.0.0
	 * @var float $price.
	 */
	protected $price = 0;

	/**
	 * Membership period for finite access.
	 * Used for payment_type PAYMENT_TYPE_FINITE.
	 *
	 * @since 1.0.0
	 * @see $payment_type $payment_type property.
	 * @var array $period {
	 *		@type int $period_unit The period of time quantity.
	 *		@type string $period_type The period type (days, weeks, months, years).
	 * }
	 */
	protected $period = array( 'period_unit' => 1, 'period_type' => 'days' );

	/**
	 * Membership payment recurring period cycle.
	 * Used for the payment_type PAYMENT_TYPE_RECURRING.
	 *
	 * @since 1.0.0
	 * @see  $payment_type $payment_type property.
	 * @var array $pay_cycle_period {@see $period $period property}.
	 */
	protected $pay_cycle_period = array( 'period_unit' => 1, 'period_type' => 'days' );

	/**
	 * Defines how many payments are made before the membership ends.
	 * Used for the payment_type PAYMENT_TYPE_RECURRING.
	 *
	 * @since 1.1.0
	 * @see  $payment_type $payment_type property.
	 * @var int
	 */
	protected $pay_cycle_repetitions = 0;

	/**
	 * Membership start date for date range payment type.
	 * Used for the payment_type PAYMENT_TYPE_DATE_RANGE.
	 *
	 * @since 1.0.0
	 * @see  $payment_type $payment_type property.
	 * @var string The membership start date.
	 */
	protected $period_date_start = '';

	/**
	 * Membership end date for date range payment type.
	 * Used for the payment_type PAYMENT_TYPE_DATE_RANGE.
	 *
	 * @since 1.0.0
	 * @see  $payment_type $payment_type property.
	 * @var string The membership end date.
	 */
	protected $period_date_end = '';

	/**
	 * Membership trial period enabled indicator.
	 * Requires the Trial Period Add-on to work.
	 *
	 * @since 1.0.0
	 * @var bool $trial_period_enabled.
	 */
	protected $trial_period_enabled = false;

	/**
	 * Membership trial price value.
	 * Requires the Trial Period Add-on to work.
	 *
	 * @since 1.0.0
	 * @internal This property has no effect yet.
	 * @var float $trial_price.
	 */
	protected $trial_price = 0;

	/**
	 * Membership trial period.
	 *
	 * @since 1.0.0
	 * @var array $trial_period {@see $period $period property}.
	 */
	protected $trial_period = array( 'period_unit' => 1, 'period_type' => 'days' );

	/**
	 * Move to Membership when the current one expires.
	 *
	 * After current membership expire move to the indicated membership_id.
	 * This membership is assigned when the current membership expires.
	 *
	 * @see MS_Model_Relationship::check_membership_status()
	 *
	 * @since 1.0.0
	 * @var int $on_end_membership_id.
	 */
	protected $on_end_membership_id = 0;

	/**
	 * Membership setup completed flag.
	 *
	 * We need this to determine if payment options of the membership are edited
	 * the first time during the setup assistant, or later via the membership
	 * list.
	 *
	 * @since 1.0.0
	 * @internal
	 * @var bool $is_setup_completed.
	 */
	protected $is_setup_completed = false;

	/**
	 * Where the data came from. Can only be changed by data import tool.
	 *
	 * @since 1.1.0
	 * @internal
	 * @var string
	 */
	protected $source = '';

	/**
	 * Membership composite Rules.
	 *
	 * These are protection rules for this membership only.
	 *
	 * Network-wide mode: The rules stored in here are the rules that apply
	 * to the currently selected site in the network!
	 *
	 * Example:
	 *   When the network has 10 sites then $rule_values will have 10 "page" rules
	 *   which are stored as "1:page", "2:page", ...
	 *   However, the $_rules property will only have ONE "page" rule, and that's
	 *   the one for the currently visible site!
	 *
	 * @since 1.0.0
	 * @internal
	 * @var array MS_Rule[].
	 */
	protected $_rules = array();

	/**
	 * Only used for serialization of the membership.
	 * @see __sleep()
	 *
	 * @since 1.1.0
	 * @internal
	 * @var array
	 */
	protected $rule_values = array();

	/**
	 * Used in simulation mode explaining why a page is allowed or denied.
	 *
	 * @since 1.0.1
	 * @internal
	 * @var array
	 */
	public $_access_reason = array();

	/**
	 * Similar to $_access_reason, but only contains the rules that denied page
	 * access.
	 *
	 * @since 1.1.0
	 * @internal
	 * @var array
	 */
	public $_deny_rule = array();

	/**
	 * Similar to $_access_reason, but only contains the rules that allowed page
	 * access.
	 *
	 * @since 1.1.0
	 * @internal
	 * @var array
	 */
	public $_allow_rule = array();

	/**
	 * Stores the subscription-ID of the parent object.
	 * This value will only have a value when the Membership is loaded within
	 * the context of a subscription.
	 *
	 * @since 1.1.0.7
	 * @var   int
	 */
	protected $subscription_id = 0;


	//
	//
	//
	// -------------------------------------------------------------- COLLECTION


	/**
	 * Returns the post-type of the current object.
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @return string The post-type name.
	 */
	public static function get_post_type() {
		return parent::_post_type( __CLASS__ );
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public static function get_register_post_type_args() {
		$args = array(
			'label' => __( 'Membership2 Memberships', MS_TEXT_DOMAIN ),
			'description' => __( 'Memberships user can join to.', MS_TEXT_DOMAIN ),
			'show_ui' => false,
			'show_in_menu' => false,
			'menu_position' => 70, // below Users
			'menu_icon' => 'dashicons-lock',
			'public' => true,
			'has_archive' => false,
			'publicly_queryable' => false,
			'supports' => false,
			'hierarchical' => false,
		);

		return apply_filters(
			'ms_customposttype_register_args',
			$args,
			self::get_post_type()
		);
	}

	/**
	 * Get membership types.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @return array {
	 *		Returns array of $type => $title.
	 *
	 *		@type string $type The membership type
	 *		@type string $title The membership type title
	 * }
	 */
	static public function get_types() {
		$types = array(
			self::TYPE_STANDARD => __( 'Standard Membership', MS_TEXT_DOMAIN ),
			self::TYPE_DRIPPED => __( 'Dripped Content Membership', MS_TEXT_DOMAIN ),
			self::TYPE_GUEST => __( 'Guest Membership', MS_TEXT_DOMAIN ),
			self::TYPE_USER => __( 'Default Membership', MS_TEXT_DOMAIN ),
			self::TYPE_BASE => __( 'System Membership', MS_TEXT_DOMAIN ),
		);

		return apply_filters( 'ms_model_membership_get_types', $types );
	}

	/**
	 * Get membership payment types.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @return array {
	 *		Returns array of $type => $title.
	 *
	 *		@type string $type The membership payment type
	 *		@type string $title The membership payment type title
	 * }
	 */
	public static function get_payment_types( $type = 'paid' ) {
		if ( 'free' == $type ) {
			$payment_types = array(
				self::PAYMENT_TYPE_PERMANENT => __( 'Permanent access', MS_TEXT_DOMAIN ),
				self::PAYMENT_TYPE_FINITE => __( 'Finite access', MS_TEXT_DOMAIN ),
				self::PAYMENT_TYPE_DATE_RANGE => __( 'Date range access', MS_TEXT_DOMAIN ),
			);
		} else {
			$payment_types = array(
				self::PAYMENT_TYPE_PERMANENT => __( 'One payment for permanent access', MS_TEXT_DOMAIN ),
				self::PAYMENT_TYPE_FINITE => __( 'One payment for finite access', MS_TEXT_DOMAIN ),
				self::PAYMENT_TYPE_DATE_RANGE => __( 'One payment for date range access', MS_TEXT_DOMAIN ),
				self::PAYMENT_TYPE_RECURRING => __( 'Recurring payments', MS_TEXT_DOMAIN ),
			);
		}

		return apply_filters(
			'ms_model_membership_get_payment_types',
			$payment_types,
			$type
		);
	}

	/**
	 * Get available Memberships count.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The membership count.
	 */
	public static function get_membership_count( $args = null ) {
		MS_Factory::select_blog();
		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );
		MS_Factory::revert_blog();

		$count = $query->found_posts;

		return apply_filters(
			'ms_model_membership_get_membership_count',
			$count,
			$args
		);
	}

	/**
	 * Find out if the installation has at least one paid membership
	 *
	 * @since 1.1.0
	 * @internal
	 *
	 * @return bool
	 */
	public static function have_paid_membership() {
		static $Have_Paid = null;

		if ( null === $Have_Paid ) {
			global $wpdb;
			// Using a custom WPDB query because building the meta-query is more
			// complex than really required here...
			$sql = "
			SELECT COUNT( 1 )
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} priv ON priv.post_id = p.ID AND priv.meta_key = %s
			INNER JOIN {$wpdb->postmeta} free ON free.post_id = p.ID AND free.meta_key = %s
			INNER JOIN {$wpdb->postmeta} pric ON pric.post_id = p.ID AND pric.meta_key = %s
			WHERE
				p.post_type = %s
				AND priv.meta_value != '1'
				AND NOT (
					free.meta_value = '1'
					OR pric.meta_value = '0'
				)
			";

			$sql = $wpdb->prepare(
				$sql,
				'private',       // INNER JOIN
				'is_free',       // INNER JOIN
				'price',         // INNER JOIN
				self::get_post_type() // WHERE condition
			);

			$res = $wpdb->get_var( $sql );

			$Have_Paid = apply_filters(
				'ms_model_membership_have_paid_membership',
				intval( $res ) > 0
			);
		}

		return $Have_Paid;
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Default search arguments for this custom post_type.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public static function get_query_args( $args = null ) {
		$defaults = apply_filters(
			'ms_model_membership_get_query_args_defaults',
			array(
				'post_type' => self::get_post_type(),
				'order' => 'DESC',
				'orderby' => 'name',
				'post_status' => 'any',
				'post_per_page' => -1,
				'nopaging' => true,
				'include_base' => false,
				'include_guest' => true,
			)
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! lib2()->is_true( $args['include_base'] ) ) {
			$args['meta_query']['base'] = array(
				'key'     => 'type',
				'value'   => self::TYPE_BASE,
				'compare' => '!=',
			);
		}

		if ( ! lib2()->is_true( $args['include_guest'] ) ) {
			$args['meta_query']['guest'] = array(
				'key'     => 'type',
				'value'   => self::TYPE_GUEST,
				'compare' => '!=',
			);
			$args['meta_query']['user'] = array(
				'key'     => 'type',
				'value'   => self::TYPE_USER,
				'compare' => '!=',
			);
		}

		return apply_filters(
			'ms_model_membership_get_query_args',
			$args,
			$defaults
		);
	}

	/**
	 * Get Memberships models.
	 *
	 * When no $args are specified then all memberships except the base
	 * membership will be returned.
	 *
	 * To include the base membership use:
	 * $args = array( 'include_base' => 1 )
	 *
	 * To exclude the guest membership use:
	 * $args = array( 'include_guest' => 0 )
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Membership[] The selected memberships.
	 */
	static public function get_memberships( $args = null ) {
		$args = self::get_query_args( $args );
		$args['order'] = 'ASC';

		MS_Factory::select_blog();
		$query = new WP_Query( $args );
		$items = $query->get_posts();
		MS_Factory::revert_blog();

		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[] = MS_Factory::load(
				'MS_Model_Membership',
				$item->ID
			);
		}

		return apply_filters(
			'ms_model_membership_get_memberships',
			$memberships,
			$args
		);
	}

	/*
	 * Returns a list of the dripped memberships.
	 *
	 * @since 1.1.0
	 * @internal
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Membership[] The selected memberships.
	 */
	static public function get_dripped_memberships( $args = null ) {
		$drip_args = array(
			'meta_query' => array(
				array(
					'key' => 'type',
					'value' => self::TYPE_DRIPPED,
				),
			),
		);

		$drip_args = wp_parse_args( $drip_args, $args );
		$memberships = self::get_memberships( $drip_args );

		return apply_filters(
			'ms_model_membership_get_dripped_memberships',
			$memberships,
			$args
		);
	}

	/**
	 * Get membership names.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @param bool $include_base_membership Include base membership from the list.
	 * @return array {
	 *		Returns array of $membership_id => $name
	 *		@type int $membership_id The membership Id.
	 *		@type string $name The membership name;
	 * }
	 */
	public static function get_membership_names( $args = null ) {
		$items = self::get_memberships( $args );

		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[ $item->id ] = $item->name;
		}

		return apply_filters(
			'ms_model_membership_get_membership_names',
			$memberships,
			$args
		);
	}

	/**
	 * Get membership eligible to signup.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @param int[] $exclude_ids Optional. The membership ids to exclude.
	 * @param bool $only_names Optional. Return only array {
	 *     @type int $membership_id The membership ID.
	 *     @type string $membership_name The membership name.
	 * }
	 * @param bool $include_private If private memberships should be listed
	 *     This param is only recognized in the admin section so admins can
	 *     manually assign a private membership to a user.
	 * @return array {
	 *     Returns array of $membership_id => $membership
	 *     @type int $membership_id The membership Id.
	 *     @type MS_Model_Membership The membership model object.
	 * }
	 */
	public static function get_signup_membership_list(
		$args = null,
		$exclude_ids = null,
		$only_names = false,
		$include_private = false
	) {
		$not_in = array();
		if ( is_array( $exclude_ids ) ) {
			$not_in = $exclude_ids;
		}
		$not_in[] = MS_Model_Membership::get_base()->id;
		$not_in[] = MS_Model_Membership::get_guest()->id;
		$not_in[] = MS_Model_Membership::get_user()->id;
		$args['post__not_in'] = array_unique( $not_in );

		if ( ! is_admin() ) {
			$include_private = false;
		}
		// List of private memberships (they are grouped in own array).
		$private = array();

		// Retrieve memberships user is not part of, using selected args
		$memberships = self::get_memberships( $args );

		foreach ( $memberships as $key => $membership ) {
			// Remove if not active.
			if ( ! $membership->active ) {
				unset( $memberships[ $key ] );
			}

			if ( $membership->private ) {
				if ( $include_private ) {
					// Move the private memberships to a option-group.
					$private[ $key ] = $memberships[ $key ];
				}
				unset( $memberships[ $key ] );
			}
		}

		if ( $only_names ) {
			$ms_names = array();
			foreach ( $memberships as $ms ) {
				$ms_names[ $ms->id ] = $ms->name;
			}
			if ( ! empty( $private ) ) {
				$priv_key = __( 'Private Memberships', MS_TEXT_DOMAIN );
				$ms_names[ $priv_key ] = array();
				foreach ( $private as $ms ) {
					$ms_names[ $priv_key ][ $ms->id ] = $ms->name;
				}
			}
			$memberships = $ms_names;
		} else {
			$memberships = array_merge( $memberships, $private );
		}

		return apply_filters(
			'ms_model_membership_get_signup_membership_list',
			$memberships,
			$exclude_ids,
			$only_names
		);
	}

	/**
	 * Verify if membership is valid.
	 *
	 * Verify if membership was not deleted, trying to load from DB.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param int $membership_id The membership id to verify.
	 * @return bool True if is valid.
	 */
	public static function is_valid_membership( $membership_id ) {
		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id, '_is_valid_' );
		$valid = ( $membership->id > 0 );

		return apply_filters(
			'ms_model_membership_is_valid_membership',
			$valid,
			$membership_id
		);
	}

	/**
	 * Get Membership2 membership.
	 *
	 * Create a new membership if membership does not exist.
	 *
	 * @since 1.1.0
	 * @internal
	 *
	 * @param  string $type The membership to load [protected_content|role]
	 * @param  book $create_missing If set to false then missing special
	 *           memberships are not created.
	 * @return MS_Model_Membership The Membership2.
	 */
	private static function _get_system_membership( $type, $create_missing = true ) {
		static $Special_Membership = array();
		$comp_key = $type;
		$membership = false;

		if ( ! isset( $Special_Membership[$comp_key] ) ) {
			$membership = false;
			global $wpdb;

			MS_Factory::select_blog();
			/*
			 * We are using a normal SQL query instead of using the WP_Query object
			 * here, because the WP_Query object does some strange things sometimes:
			 * In some cases new Membership2 memberships were created when a
			 * guest accessed the page.
			 *
			 * By using a manual query we are very certain that only one
			 * base-membership exists on the database.
			 */
			$sql = "
				SELECT ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} m_type ON m_type.post_id = p.ID
				WHERE
					p.post_type = %s
					AND m_type.meta_key = %s
					AND m_type.meta_value = %s
			";
			$values = array(
				self::get_post_type(),
				'type',
				$type,
			);

			$sql = $wpdb->prepare( $sql, $values );
			$item = $wpdb->get_results( $sql );
			$base = array_shift( $item ); // Remove the base membership from the results
			MS_Factory::revert_blog();

			if ( ! empty( $base ) ) {
				$membership = MS_Factory::load( 'MS_Model_Membership', $base->ID );
			} else if ( $create_missing ) {
				$names = self::get_types();

				$description = __( 'Membership2 Core Membership', MS_TEXT_DOMAIN );
				$membership = MS_Factory::create( 'MS_Model_Membership' );
				$membership->name = $names[$type];
				$membership->title = $names[$type];
				$membership->description = $description;
				$membership->type = $type;
				$membership->save();
			}

			$Special_Membership[$comp_key] = apply_filters(
				'ms_model_membership_get_system_membership',
				$membership,
				$type
			);
		}

		return $Special_Membership[$comp_key];
	}

	/**
	 * Get Membership2 base membership.
	 *
	 * Create a new membership if membership does not exist.
	 * This is an internal membership which is never displayed anywhere.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return MS_Model_Membership The base membership.
	 */
	public static function get_base() {
		static $Base_Membership = null;

		if ( null === $Base_Membership ) {
			$Base_Membership = self::_get_system_membership(
				self::TYPE_BASE
			);

			foreach ( $Base_Membership->_rules as $key => $rule ) {
				$Base_Membership->_rules[$key]->is_base_rule = true;
			}

			$Base_Membership = apply_filters(
				'ms_model_membership_get_base',
				$Base_Membership
			);
		}

		return $Base_Membership;
	}

	/**
	 * Get special membership that is assigned to all guests.
	 *
	 * Create a new membership if membership does not exist.
	 *
	 * @since 1.1.0
	 * @api
	 *
	 * @param  string $role A WordPress user-role.
	 * @return MS_Model_Membership The guest membership.
	 */
	public static function get_guest() {
		static $Guest_Membership = null;

		if ( null === $Guest_Membership ) {
			$Guest_Membership = self::_get_system_membership(
				self::TYPE_GUEST,
				false // Don't create this membership automatically
			);

			$Guest_Membership = apply_filters(
				'ms_model_membership_get_guest',
				$Guest_Membership
			);
		}

		if ( ! $Guest_Membership ) {
			$Guest_Membership = MS_Factory::create( 'MS_Model_Membership' );
		}

		return $Guest_Membership;
	}

	/**
	 * Get default membership for all logged-in users that did not yet subscribe
	 * to any membership.
	 *
	 * Create a new membership if membership does not exist.
	 *
	 * @since 1.1.0
	 * @api
	 *
	 * @param  string $role A WordPress user-role.
	 * @return MS_Model_Membership The guest membership.
	 */
	public static function get_user() {
		static $User_Membership = null;

		if ( null === $User_Membership ) {
			$User_Membership = self::_get_system_membership(
				self::TYPE_USER,
				false // Don't create this membership automatically
			);

			$User_Membership = apply_filters(
				'ms_model_membership_get_user',
				$User_Membership
			);
		}

		if ( ! $User_Membership ) {
			$User_Membership = MS_Factory::create( 'MS_Model_Membership' );
		}

		return $User_Membership;
	}


	//
	//
	//
	// ------------------------------------------------------------- SINGLE ITEM


	/**
	 * Returns a list of variables that should be included in serialization,
	 * i.e. these values are the only ones that are stored in DB
	 *
	 * @since  1.1.0
	 * @internal
	 * @return array
	 */
	public function __sleep() {
		/*
		 * Rule values are pre-processd before saving...
		 * Note: $this->_rules only contains rules for the *current* site, so
		 * all rules that are serialized here get the current-site prefix.
		 * Rules for the other sites are already in the $this->rule_values
		 * array and were not de-serialized on page load.
		 */
		$this->rule_values = lib2()->array->get( $this->rule_values );
		foreach ( $this->_rules as $rule_type => $rule ) {
			$key = MS_Rule::rule_key( $rule_type );

			$this->rule_values[$key] = $rule->serialize();
			if ( empty( $this->rule_values[$key] ) ) {
				unset( $this->rule_values[$key] );
			}
		}

		return array(
			'id',
			'name',
			'title',
			'description',
			'rule_values',
			'type',
			'payment_type',
			'active',
			'private',
			'is_free',
			'price',
			'period',
			'pay_cycle_period',
			'pay_cycle_repetitions',
			'period_date_start',
			'period_date_end',
			'trial_period_enabled',
			'trial_price',
			'trial_period',
			'on_end_membership_id',
			'is_setup_completed',
			'custom_data',
		);
	}

	/**
	 * Set rules membership_id before saving.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function before_save() {
		parent::before_save();

		foreach ( $this->_rules as $rule ) {
			$rule->membership_id = $this->id;
		}
	}

	/**
	 * Save model and move the object to the singleton cache if required.
	 *
	 * @since 2.0.0
	 */
	public function save() {
		parent::save();
		parent::store_singleton();
	}

	/**
	 * After the membership was saved to DB we make sure that it is published.
	 *
	 * Network-wide mode: We are still in the switched blog (main site) so
	 * there is no need to call MS_Factory::select_blog() in this function.
	 *
	 * @since  2.0.0
	 * @internal
	 */
	public function after_save() {
		// It is important! The Membership2 membership must be public
		// so that the membership options are available for guest users.
		wp_publish_post( $this->id );
	}

	/**
	 * Permanently delete the membership.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return bool
	 */
	public function delete() {
		do_action( 'ms_model_membership_before_delete', $this );
		$res = false;

		if ( $this->is_base() ) {
			throw new Exception(
				'Can not delete the system membership.'
			);
		}

		if ( ! empty( $this->id ) ) {
			if ( $this->get_members_count() > 0 ) {
				$ms_relationships = MS_Model_Relationship::get_subscriptions(
					array( 'membership_id' => $this->id ),
					true
				);

				foreach ( $ms_relationships as $ms_relationship ) {
					$ms_relationship->delete();
				}
			}

			$res = ( false !== wp_delete_post( $this->id, true ) );
		}

		do_action( 'ms_model_membership_after_delete', $this, $res );
		return $res;
	}

	/**
	 * Merge current rules to Membership2.
	 *
	 * Assure the membership rules get updated whenever Membership2 is changed.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function prepare_obj() {
		parent::prepare_obj();

		if ( false !== strpos( $this->_factory_id, '_is_valid_' ) ) {
			// This object only checks if the item ID is valid.
			// No need to load any rules yet...
			return;
		}

		foreach ( $this->rule_values as $key => $values ) {
			// Skip rules without any values.
			if ( empty( $values ) ) { continue; }

			// Network-wide: Only instanciate rules for the *current* site!
			if ( ! MS_Rule::is_current_site( $key ) ) { continue; }

			// Key could be "type" of "site:type" format.
			$rule_type = MS_Rule::rule_type( $key );

			// At this point we have an empty rule-instance
			$rule = $this->get_rule( $rule_type );

			// Now we populate that rule-instance with site-specific settings.
			$rule->populate( $values );
		}

		// validate rules using Membership2 rules
		if ( ! $this->is_base() && $this->is_valid() ) {
			$this->merge_protection_rules();
		}
	}

	/**
	 * Get current payment type description.
	 *
	 * Description to show in the admin list table.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return string The current payment type description.
	 */
	public function get_payment_type_desc() {
		$desc = __( 'N/A', MS_TEXT_DOMAIN );
		$has_payment = ! $this->is_free();

		switch ( $this->payment_type ) {
			case self::PAYMENT_TYPE_FINITE:
				$desc = sprintf(
					__( 'For %1$s', MS_TEXT_DOMAIN ),
					MS_Helper_Period::get_period_desc( $this->period )
				);
				break;

			case self::PAYMENT_TYPE_DATE_RANGE:
				$desc = sprintf(
					__( 'From %1$s to %2$s', MS_TEXT_DOMAIN ),
					$this->period_date_start,
					$this->period_date_end
				);
				break;

			case self::PAYMENT_TYPE_RECURRING:
				$desc = __( 'Each %1$s', MS_TEXT_DOMAIN );

				if ( $has_payment ) {
					if ( 1 == $this->pay_cycle_repetitions ) {
						$desc = __( 'Single payment', MS_TEXT_DOMAIN );
					} elseif ( $this->pay_cycle_repetitions > 1 ) {
						$desc .= ', ' . __( '%2$s payments', MS_TEXT_DOMAIN );
					}
				}

				$desc = sprintf(
					$desc,
					MS_Helper_Period::get_period_desc( $this->pay_cycle_period ),
					$this->pay_cycle_repetitions
				);
				break;

			case self::PAYMENT_TYPE_PERMANENT:
			default:
				if ( $has_payment ) {
					$desc = __( 'Single payment', MS_TEXT_DOMAIN );
				} else {
					$desc = __( 'Permanent access', MS_TEXT_DOMAIN );
				}
				break;
		}

		return apply_filters(
			'ms_model_membership_get_payment_type_desc',
			$desc,
			$this
		);
	}

	/**
	 * Returns true if the current membership is free.
	 *
	 * A membership is free when...
	 * ... it is explicitely marked as "free"
	 * ... the price is 0.00
	 * ... it is a parent membership that cannot be signed up for
	 *
	 * @since  1.0.4.7
	 * @api
	 *
	 * @return bool
	 */
	public function is_free() {
		$result = false;

		if ( $this->is_free ) { $result = true; }
		elseif ( empty( $this->price ) ) { $result = true; }

		$result = apply_filters(
			'ms_model_membership_is_free',
			$result,
			$this
		);

		if ( $result && $this->is_free ) {
			$this->is_free = $result;
		}

		return $result;
	}

	/**
	 * Get protection Rule Model.
	 *
	 * Note for network-wide mode:
	 * In DB the rules for each site are stored in different objects.
	 * When loading a membership we will always load 1 instance of each
	 * rule_type, and this is the instance that belongs to the current site!
	 * Instances for other sites are not accessible.
	 * -> This is why we do not use/need a site_id or similar in this function.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param string $rule_type The rule model type @see MS_Rule
	 * @return MS_Rule The requested rule model.
	 */
	public function get_rule( $rule_type ) {
		if ( 'attachment' === $rule_type ) {
			$rule_type = MS_Rule_Media::RULE_ID;
		}

		if ( ! isset( $this->_rules[ $rule_type ] )
			|| ! is_object( $this->_rules[ $rule_type ] ) // During plugin update.
		) {
			// Create a new rule model object.
			$rule = MS_Rule::rule_factory(
				$rule_type,
				$this->id,
				$this->subscription_id
			);

			$rule = apply_filters(
				'ms_model_membership_get_rule',
				$rule,
				$rule_type,
				$this
			);

			$this->_rules[ $rule_type ] = $rule;
			if ( ! is_array( $rule->rule_value ) ) {
				$rule->rule_value = array();
			}
		}

		return $this->_rules[ $rule_type ];
	}

	/**
	 * Set protection Rule Model.
	 *
	 * Note for network-wide mode:
	 * In DB the rules for each site are stored in different objects.
	 * When loading a membership we will always load 1 instance of each
	 * rule_type, and this is the instance that belongs to the current site!
	 * Instances for other sites are not accessible.
	 * -> This is why we do not use/need a site_id or similar in this function.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param string The rule model type @see MS_Rule
	 * @param MS_Rule $rule The protection rule to set.
	 */
	public function set_rule( $rule_type, $rule ) {
		$this->_rules[ $rule_type ] = apply_filters(
			'ms_model_membership_set_rule',
			$rule,
			$rule_type,
			$this
		);
	}

	/**
	 * Returns the unique HEX color for this membership.
	 * The color is calculated from the membership-ID and therefore will never
	 * change.
	 *
	 * @since  1.1.0
	 * @api
	 *
	 * @return string Hex color, e.g. '#FFFFFF'
	 */
	public function get_color() {
		return MS_Helper_Utility::color_index( $this->type . $this->id );
	}

	/**
	 * Returns a HTML tag that shows the membership name with the internal
	 * membership color.
	 *
	 * @since  2.0.0
	 * @api
	 */
	public function get_name_tag() {
		$code = sprintf(
			'<span class="ms-membership" style="background:%2$s">%1$s</span>',
			esc_html( $this->name ),
			$this->get_color()
		);

		return $code;
	}

	/**
	 * Echo a HTML tag that shows the membership name with the internal
	 * membership color.
	 *
	 * @since  2.0.0
	 * @api
	 */
	public function name_tag() {
		echo $this->get_name_tag();
	}

	/**
	 * Get current membership type description.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return string The membership type description.
	 */
	public function get_type_description() {
		$types = self::get_types();
		$desc = $types[ $this->type ];

		return apply_filters(
			'ms_model_membership_get_type_description',
			$desc,
			$this
		);
	}

	/**
	 * Either creates or updates the value of a custom data field.
	 *
	 * Note: Remember to prefix the $key with a unique string to prevent
	 * conflicts with other plugins that also use this function.
	 *
	 * @since  2.0.0
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
	 * @since  2.0.0
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
	 * @since  2.0.0
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
	 * Merge Membership2 rules.
	 *
	 * Merge every rule model with Membership2/visitor membership rules.
	 * This ensure rules are consistent with Membership2 rules.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function merge_protection_rules() {
		if ( $this->is_base() ) {
			// This is the visitor membership, no need to merge anything.
			return;
		}

		$base_rules = self::get_base()->_rules;

		foreach ( $base_rules as $key => $base_rule ) {
			try {
				// Key could be "type" of "site:type" format.
				$rule_type = MS_Rule::rule_type( $key );

				$rule = $this->get_rule( $rule_type );
				$rule->protect_undefined_items( $base_rule, true );
				$this->set_rule( $rule_type, $rule );
			}
			catch( Exception $e ) {
				MS_Helper_Debug::log( $e );
			}
		}

		$this->_rules = apply_filters(
			'ms_model_membership_merge_protection_rules',
			$this->_rules,
			$this
		);
	}

	/**
	 * Get after membership expired options.
	 *
	 * Memberships can be downgraded to the guest level protection.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return array {
	 *		Returns array of $membership_id => $description.
	 *		@type int $membership_id The membership Id.
	 *		@type string $description The expired option description.
	 * }
	 */
	public function get_after_ms_ends_options() {
		$options = array(
			0 => __( 'Restrict access to Visitor-Level', MS_TEXT_DOMAIN ),
		);

		$args = array(
			'include_guest' => false,
		);
		$options += $this->get_membership_names( $args );
		unset( $options[$this->id] );

		$label = __( 'Change to: %s', MS_TEXT_DOMAIN );
		foreach ( $options as $id => $option ) {
			if ( $id > 0 ) {
				$options[$id] = sprintf( $label, $option );
			}
		}

		return apply_filters(
			'ms_model_membership_get_membership_names',
			$options,
			$this
		);
	}

	/**
	 * Get members count of this membership.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return int The members count.
	 */
	public function get_members_count() {
		$count = MS_Model_Relationship::get_subscription_count(
			array( 'membership_id' => $this->id )
		);

		return apply_filters(
			'ms_model_membership_get_members_count',
			$count
		);
	}

	/**
	 * Return membership has dripped content.
	 *
	 * Verify post and page rules if there is a dripped content.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @return boolean
	 */
	public function has_dripped_content() {
		$has_dripped = false;
		$dripped = array( 'post', 'page' );

		foreach ( $dripped as $rule_type ) {
			// using count() as !empty() never returned true
			if ( 0 < count( $this->get_rule( $rule_type )->dripped ) ) {
				$has_dripped = true;
			}
		}

		return apply_filters(
			'ms_model_membership_has_dripped_content',
			$has_dripped,
			$this
		);
	}

	/**
	 * Get protection rules sorted.
	 *
	 * First one has priority over the last one.
	 * These rules are used to determine access.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	private function get_rules_hierarchy() {
		$rule_types = MS_Model_Rule::get_rule_types();
		$rules = array();
		$subscription = MS_Factory::load( 'MS_Model_Relationship', $this->subscription_id );

		foreach ( $rule_types as $rule_type ) {
			$rule = $this->get_rule( $rule_type );

			if ( $rule->rule_type != $rule_type ) {
				// This means that the $rule_type was not found...
				continue;
			}

			// Sometimes the $subscription->id can be 0, which is intentional:
			// This is the case when the membership was auto-assigned to guest
			// or default membership.
			$rule->_subscription_id = $subscription->id;

			$rule->membership_id = $this->id;
			$rules[ $rule_type ] = $rule;
		}

		return apply_filters(
			'ms_model_membership_get_rules_hierarchy',
			$rules,
			$this
		);
	}

	/**
	 * Mark membership setup as completed.
	 *
	 * Only purpose of this flag is to display the correct update message to the
	 * user: If setup_completed() returns true, then "Membership added" is
	 * displayed, otherwise "Membership updated"
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @return bool $marked True in the first time setup is finished.
	 */
	public function setup_completed() {
		$marked = false;

		if ( ! $this->is_setup_completed ) {
			$this->is_setup_completed = true;
			$marked = true;
		}

		return apply_filters(
			'ms_model_membership_setup_completed',
			$marked,
			$this
		);
	}

	/**
	 * Returns true if the membership the base membership.
	 *
	 * @since  1.1.0
	 * @see  description of MS_Model_Membership::get_base()
	 * @api
	 *
	 * @return bool
	 */
	public function is_base() {
		$res = $this->type == self::TYPE_BASE;

		return apply_filters(
			'ms_model_membership_is_base',
			$res,
			$this
		);
	}

	/**
	 * Returns true if the membership the guest membership.
	 *
	 * @since  1.1.0
	 * @see description of MS_Model_Membership::get_guest()
	 * @api
	 *
	 * @return bool
	 */
	public function is_guest() {
		$res = $this->type == self::TYPE_GUEST;

		return apply_filters(
			'ms_model_membership_is_guest',
			$res,
			$this
		);
	}

	/**
	 * Returns true if the membership the user membership.
	 *
	 * @since  1.1.0
	 * @see  description of MS_Model_Membership::get_user()
	 * @api
	 *
	 * @return bool
	 */
	public function is_user() {
		$res = $this->type == self::TYPE_USER;

		return apply_filters(
			'ms_model_membership_is_user',
			$res,
			$this
		);
	}

	/**
	 * Returns true if the membership a dripped membership.
	 *
	 * @since  1.1.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_dripped() {
		$res = $this->type == self::TYPE_DRIPPED;

		return apply_filters(
			'ms_model_membership_is_dripped',
			$res,
			$this
		);
	}

	/**
	 * Returns true if the membership the base or guest/user membership.
	 *
	 * @since  1.1.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_system() {
		$res = $this->is_base() || $this->is_guest() || $this->is_user();

		return apply_filters(
			'ms_model_membership_is_system',
			$res,
			$this
		);
	}

	/**
	 * Can be used to validate if the current membership is actually loaded
	 * from database. If this function returns false, then the specified
	 * membership-ID does not exist in DB.
	 *
	 * @since  1.1.0
	 * @api
	 *
	 * @return bool
	 */
	public function is_valid() {
		$res = ! empty( $this->id );

		return apply_filters(
			'ms_model_membership_is_valid',
			$res,
			$this
		);
	}

	/**
	 * Verify access to current page.
	 *
	 * Verify membership rules hierarchy for content accessed directly.
	 * If 'has access' is found, it does have access.
	 * Only for active memberships.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param int $post_id
	 * @return bool|null True if has access to current page. Default is false.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access_to_current_page( $post_id = null ) {
		$has_access = null;
		$this->_access_reason = array();
		$this->_deny_rule = array();
		$this->_allow_rule = array();

		// Only verify access if membership is Active.
		if ( $this->active ) {

			// If 'has access' is found in the hierarchy, it does have access.
			$rules = $this->get_rules_hierarchy();
			foreach ( $rules as $rule ) {
				$rule_access = $rule->has_access( $post_id );

				if ( null === $rule_access ) {
					$this->_access_reason[] = sprintf(
						__( 'Ignored: Rule "%s"', MS_TEXT_DOMAIN ),
						$rule->rule_type
					);
					continue;
				}

				$this->_access_reason[] = sprintf(
					__( '%s: Rule "%s"', MS_TEXT_DOMAIN ),
					$rule_access ? __( 'Allow', MS_TEXT_DOMAIN ) : __( 'Deny', MS_TEXT_DOMAIN ),
					$rule->rule_type
				);

				if ( ! $rule_access ) {
					$this->_deny_rule[] = $rule->rule_type;
				} else {
					$this->_allow_rule[] = $rule->rule_type;
				}

				// URL groups have final decission.
				if ( MS_Rule_Url::RULE_ID === $rule->rule_type ) {
					$has_access = $rule_access;
					break;
				}

				// Special pages have final decission after URL groups.
				if ( MS_Rule_Special::RULE_ID === $rule->rule_type ) {
					$has_access = $rule_access;
					$this->_access_reason[] = $rule->matched_type;
					break;
				}

				$has_access = ( $has_access || $rule_access );

				if ( true === $has_access ) {
					break;
				}
			}
		}

		return apply_filters(
			'ms_model_membership_has_access_to_current_page',
			$has_access,
			$post_id,
			$this
		);
	}

	/**
	 * Verify access to post.
	 *
	 * Verify membership rules hierarchy for specific post or CPT.
	 *
	 * @since 1.0.0
	 * @api
	 *
	 * @param int $post_id ID of specific post
	 * @return boolean True if has access to current page. Default is false.
	 */
	public function has_access_to_post( $post_id ) {
		$has_access = null;

		if ( MS_Model_Member::is_normal_admin() ) {
			return true;
		}

		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
			if ( 'attachment' === $post->post_type ) {
				$post_id = get_post_field( 'post_parent', $post_id );
			}
		}

		// If 'has access' is found in the hierarchy, it does have access.
		$rules = $this->get_rules_hierarchy();
		foreach ( $rules as $rule ) {
			$rule->prepare_rule( $subscription );

			// url groups have final decision
			if ( MS_Rule_Url::RULE_ID == $rule->rule_type
				&& $rule->has_rule_for_post( $post_id )
			) {
				$has_access = $rule->has_access( $post_id );
				break;
			} else {
				$rule_access = $rule->has_access( $post_id );
				if ( null !== $rule_access ) {
					$has_access = $rule_access;
				}
			}

			if ( $has_access ) {
				break;
			}
		}

		if ( null === $has_access ) {
			// The post is not denied by any rule, so allow access.
			$has_access = true;
		}

		return apply_filters(
			'ms_model_membership_has_access_to_post',
			$has_access,
			$this
		);
	}

	/**
	 * Set up the membership. This is always done, regardless if the user is
	 * a normal user or an Admin user.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param MS_Model_Relationship $subscription The membership relationship.
	 */
	public function initialize( $subscription ) {
		do_action(
			'ms_model_membership_initialize_before',
			$subscription,
			$this
		);

		$this->subscription_id = $subscription->id;
		$rules = $this->get_rules_hierarchy();

		// Apply protection settings of all rules (replace/hide contents, ...)
		foreach ( $rules as $rule ) {
			$rule->prepare_rule( $subscription );
		}

		do_action(
			'ms_model_membership_initialize_after',
			$subscription,
			$this
		);
	}

	/**
	 * Set initial protection for front-end.
	 * This function is only executed when the current user is no Admin user.
	 *
	 * Hide restricted content for this membership.
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function protect_content() {
		do_action(
			'ms_model_membership_protect_content_before',
			$this
		);

		$rules = $this->get_rules_hierarchy();

		// Apply protection settings of all rules (replace/hide contents, ...)
		foreach ( $rules as $rule ) {
			$rule->protect_content();
		}

		do_action(
			'ms_model_membership_protect_content_after',
			$this
		);
	}

	/**
	 * Set initial protection for admin side.
	 *
	 * Hide restricted content for this membership.
	 *
	 * @since 1.1
	 * @internal
	 */
	public function protect_admin_content() {
		do_action(
			'ms_model_membership_protect_content_before',
			$this
		);

		$rules = $this->get_rules_hierarchy();

		foreach ( $rules as $rule ) {
			$rule->protect_admin_content();
		}

		do_action(
			'ms_model_membership_protect_content_after',
			$this
		);
	}

	/*
	 * Checks if the user is allowed to change the payment details for the
	 * current membership.
	 *
	 * Payment details can only be changed when
	 * (A) no payment details were saved yet  - OR -
	 * (B) no members signed up for the memberhips
	 *
	 * @since  1.0.4.5
	 * @api
	 *
	 * @return bool
	 */
	public function can_change_payment() {
		// Allow if Membership is new/unsaved.
		if ( empty( $this->id ) ) { return true; }

		// Allow if no payment detail was entered yet (incomplete setup).
		if ( empty( $this->payment_type ) ) { return true; }

		// Allow if no members signed up yet.
		$members = MS_Model_Relationship::get_subscription_count(
			array( 'membership_id' => $this->id )
		);
		if ( empty( $members ) ) { return true; }

		// Otherwise payment details cannot be changed anymore.
		return false;
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;

		switch ( $property ) {
			case 'type':
				switch ( $this->type ) {
					case self::TYPE_BASE:
					case self::TYPE_GUEST:
					case self::TYPE_USER:
					case self::TYPE_DRIPPED:
						break;

					default:
						$this->type = self::TYPE_STANDARD;
						break;
				}

				$value = $this->type;
				break;

			case 'payment_type':
				$types = self::get_payment_types();
				if ( ! array_key_exists( $this->payment_type, $types ) ) {
					$this->payment_type = self::PAYMENT_TYPE_PERMANENT;
				}
				$value = $this->payment_type;
				break;

			case 'trial_period_enabled':
			case 'active':
			case 'private':
			case 'is_free':
				$value = lib2()->is_true( $this->$property );
				break;

			case 'type_description':
				$value = $this->get_type_description();
				break;

			case 'period_unit':
				$value = MS_Helper_Period::get_period_value( $this->period, 'period_unit' );
				break;

			case 'period_type':
				$value = MS_Helper_Period::get_period_value( $this->period, 'period_type' );
				break;

			case 'pay_cycle_period_unit':
				$value = MS_Helper_Period::get_period_value( $this->pay_cycle_period, 'period_unit' );
				break;

			case 'pay_cycle_period_type':
				$value = MS_Helper_Period::get_period_value( $this->pay_cycle_period, 'period_type' );
				break;

			case 'trial_period_unit':
				$value = MS_Helper_Period::get_period_value( $this->trial_period, 'period_unit' );
				break;

			case 'trial_period_type':
				$value = MS_Helper_Period::get_period_value( $this->trial_period, 'period_type' );
				break;

			case 'price':
				if ( $this->is_free() ) {
					$value = 0;
				} else {
					$value = $this->price;
				}
				break;

			case 'total_price':
				if ( $this->is_free() ) {
					$value = 0;
				} else {
					$value = $this->price;
				}

				$value = apply_filters(
					'ms_apply_taxes',
					$value,
					$this
				);
				break;

			case 'pay_cycle_repetitions':
				$value = absint( $this->pay_cycle_repetitions );
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters(
			'ms_model_membership__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 * @internal
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'name':
				case 'title':
					$this->$property = sanitize_text_field( $value );
					break;

				case 'description':
					$this->$property = wp_kses( $value, 'post' );
					break;

				case 'type':
					switch ( $value ) {
						case self::TYPE_BASE:
						case self::TYPE_GUEST:
						case self::TYPE_USER:
							// Only one instance of these types can exist.
							$existing = $this->_get_system_membership( $value, false );

							if ( $existing && $existing->id != $this->id ) {
								$value = self::TYPE_STANDARD;
							} else {
								$this->active = true;
								$this->private = true;
								$this->is_free = true;
								$this->price = 0;
								$this->post_name = sanitize_html_class( $this->title );
								$this->payment_type = self::PAYMENT_TYPE_PERMANENT;
								$this->post_author = get_current_user_id();
							}
							break;

						case self::TYPE_DRIPPED:
							break;

						default:
							$value = self::TYPE_STANDARD;
							break;
					}

					$this->type = $value;
					break;

				case 'payment_type':
					$types = self::get_payment_types();
					if ( array_key_exists( $value, $types ) ) {
						if ( $this->can_change_payment() ) {
							$this->payment_type = $value;
						} elseif ( $this->payment_type != $value ) {
							$error = 'Payment type cannot be changed after members have signed up.';
							MS_Helper_Debug::log( $error );
							throw new Exception( $error );
						}
					} else {
						throw new Exception( 'Invalid membership type.' );
					}
					break;

				case 'trial_period_enabled':
				case 'active':
				case 'private':
				case 'is_free':
					$this->$property = lib2()->is_true( $value );
					break;

				case 'price':
				case 'trial_price':
					$this->$property = floatval( $value );
					break;

				case 'pay_cycle_repetitions':
					$this->$property = absint( $value );
					break;

				case 'period':
				case 'pay_cycle_period':
				case 'trial_period':
					$this->$property = $this->validate_period( $value );
					break;

				case 'period_date_start':
				case 'period_date_end':
					$this->$property = $this->validate_date( $value );
					break;

				case 'on_end_membership_id':
					if ( 0 == $value ) {
						$this->$property = 0;
					} else if ( 0 < MS_Factory::load( 'MS_Model_Membership', $value )->id ) {
						$this->$property = $value;
					}
					break;

				default:
					$this->$property = $value;
					break;
			}
		} else {
			switch ( $property ) {
				case 'period_unit':
					$this->period['period_unit'] = $this->validate_period_unit( $value );
					break;

				case 'period_type':
					$this->period['period_type'] = $this->validate_period_type( $value );
					break;

				case 'pay_cycle_period_unit':
					$this->pay_cycle_period['period_unit'] = $this->validate_period_unit( $value );
					break;

				case 'pay_cycle_period_type':
					$this->pay_cycle_period['period_type'] = $this->validate_period_type( $value );
					break;

				case 'trial_period_unit':
					$this->trial_period['period_unit'] = $this->validate_period_unit( $value );
					break;

				case 'trial_period_type':
					$this->trial_period['period_type'] = $this->validate_period_type( $value );
					break;
			}
		}

		do_action(
			'ms_model_membership__set_after',
			$property,
			$value,
			$this
		);
	}

}
