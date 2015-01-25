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
 * Membership model.
 *
 * Persisted by parent class MS_Model_CustomPostType.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Membership extends MS_Model_CustomPostType {

	/**
	 * Model custom post type.
	 *
	 * Both static and class property are used to handle php 5.2 limitations.
	 *
	 * @since 1.0.0
	 * @var string $POST_TYPE
	 */
	public static $POST_TYPE = 'ms_membership';
	public $post_type = 'ms_membership';

	/**
	 * Membership type constants.
	 *
	 * @since 1.0.0
	 * @see $type property.
	 * @var string $type The membership type.
	 */
	const TYPE_STANDARD = 'simple';
	const TYPE_DRIPPED = 'dripped';
	const TYPE_BASE = 'base'; // System membership, hidden, created automatically
	const TYPE_GUEST = 'guest'; // Guest membership, only one membership possible

	/**
	 * Membership payment type constants.
	 *
	 * @since 1.0.0
	 * @see $payment_type property.
	 * @var string $payment_type The payment type.
	 */
	const PAYMENT_TYPE_PERMANENT = 'permanent';
	const PAYMENT_TYPE_FINITE = 'finite';
	const PAYMENT_TYPE_DATE_RANGE = 'date-range';
	const PAYMENT_TYPE_RECURRING = 'recurring';

	/**
	 * Membership type.
	 *
	 * @since 1.0.0
	 * @var string $type
	 */
	protected $type = self::TYPE_STANDARD;

	/**
	 * Membership payment type.
	 *
	 * @since 1.0.0
	 * @var string $payment_type
	 */
	protected $payment_type = self::PAYMENT_TYPE_PERMANENT;

	/**
	 * Membership active status.
	 *
	 * By default a new membership is active.
	 *
	 * @since 1.0.0
	 * @var bool $active
	 */
	protected $active = true;

	/**
	 * Membership private status.
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
	 *
	 * @since 1.0.0
	 * @var array $period {
	 *		@type int $period_unit The period of time quantity.
	 *		@type string $period_type The period type (days, weeks, months, years).
	 * }
	 */
	protected $period = array( 'period_unit' => 1, 'period_type' => 'days' );

	/**
	 * Membership payment recurring period cycle.
	 *
	 * @since 1.0.0
	 * @var array $pay_cycle_period @see $period.
	 */
	protected $pay_cycle_period = array( 'period_unit' => 1, 'period_type' => 'days' );

	/**
	 * Membership start date for date range payment type.
	 *
	 * @since 1.0.0
	 * @var string The membership start date.
	 */
	protected $period_date_start = '';

	/**
	 * Membership end date for date range payment type.
	 *
	 * @since 1.0.0
	 * @var string The membership end date.
	 */
	protected $period_date_end = '';

	/**
	 * Membership trial period enabled indicator.
	 *
	 * @since 1.0.0
	 * @var bool $trial_period_enabled.
	 */
	protected $trial_period_enabled = false;

	/**
	 * Membership trial price value.
	 *
	 * @since 1.0.0
	 * @var float $trial_price.
	 */
	protected $trial_price = 0;

	/**
	 * Membership trial period.
	 *
	 * @since 1.0.0
	 * @var array $trial_period @see $period.
	 */
	protected $trial_period = array( 'period_unit' => 1, 'period_type' => 'days' );

	/**
	 * Membership dripped type.
	 *
	 * @since 1.0.0
	 * @var string $dripped_type The dripped type used in this membership.
	 *     @see MS_Model_Rule::get_dripped_rule_types()
	 */
	protected $dripped_type = MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE;

	/**
	 * Move to Membership when the current one expires.
	 *
	 * After current membership expire move to the indicated membership_id.
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
	 * @var bool $is_setup_completed.
	 */
	protected $is_setup_completed = false;

	/**
	 * Membership composite Rules.
	 *
	 * These are protection rules for this membership only.
	 *
	 * @since 1.0.0
	 * @var array MS_Model_Rule[].
	 */
	protected $rules = array();

	/**
	 * Only used for serialization of the membership.
	 * @see __sleep()
	 *
	 * @since 1.1.0
	 * @var array
	 */
	protected $rule_values = array();

	/**
	 * Used in simulation mode explaining why a page is allowed or denied.
	 *
	 * @since 1.0.1
	 * @var array
	 */
	public $_access_reason = array();

	/**
	 * Returns a list of variables that should be included in serialization,
	 * i.e. these values are the only ones that are stored in DB
	 *
	 * @since  1.1.0
	 * @return array
	 */
	public function __sleep() {
		// Rule values are pre-processd before saving...
		$rules = array();
		foreach ( $this->rules as $key => $rule ) {
			$rule = $this->get_rule( $key );
			$rules[$key] = $rule->serialize();

			if ( empty( $rules[$key] ) ) {
				unset( $rules[$key] );
			}
		}

		$this->rule_values = $rules;

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
			'period_date_start',
			'period_date_end',
			'trial_period_enabled',
			'trial_price',
			'trial_period',
			'dripped_type',
			'on_end_membership_id',
			'is_setup_completed',
		);
	}

	/**
	 * Set rules membership_id before saving.
	 *
	 * @since 1.0.0
	 */
	public function before_save() {
		parent::before_save();

		foreach ( $this->rules as $rule ) {
			$rule->membership_id = $this->id;
		}
	}

	/**
	 * Merge current rules to protected content.
	 *
	 * Assure the membership rules get updated whenever protected content is changed.
	 *
	 * @since 1.0.0
	 */
	public function prepare_obj() {
		parent::prepare_obj();

		foreach ( $this->rule_values as $key => $values ) {
			if ( empty( $values ) ) { continue; }
			$rule = $this->get_rule( $key );
			$rule->populate( $values );
		}
		$this->rule_values = array();

		// validate rules using protected content rules
		if ( ! $this->is_base() && $this->is_valid() ) {
			$this->merge_protected_content_rules();
		}
	}

	/**
	 * Get membership types.
	 *
	 * @since 1.0.0
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
			self::TYPE_BASE => __( 'System Membership', MS_TEXT_DOMAIN ),
		);

		return apply_filters( 'ms_model_membership_get_types', $types );
	}

	/**
	 * Get current membership type description.
	 *
	 * @since 1.0.0
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
	 * Get membership payment types.
	 *
	 * @since 1.0.0
	 * @return array {
	 *		Returns array of $type => $title.
	 *
	 *		@type string $type The membership payment type
	 *		@type string $title The membership payment type title
	 * }
	 */
	public static function get_payment_types() {
		$payment_types = array(
			self::PAYMENT_TYPE_PERMANENT => __( 'Single payment for permanent access', MS_TEXT_DOMAIN ),
			self::PAYMENT_TYPE_FINITE => __( 'Single payment for finite access', MS_TEXT_DOMAIN ),
			self::PAYMENT_TYPE_DATE_RANGE => __( 'Single payment for date range access', MS_TEXT_DOMAIN ),
			self::PAYMENT_TYPE_RECURRING => __( 'Recurring payment', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_model_membership_get_payment_types',
			$payment_types
		);
	}

	/**
	 * Get current payment type description.
	 *
	 * Description to show in the admin list table.
	 *
	 * @since 1.0.0
	 * @return string The current payment type description.
	 */
	public function get_payment_type_desc() {
		$desc = __( 'N/A', MS_TEXT_DOMAIN );
		$has_payment = ! $this->is_free();

		if ( $has_payment ) {
			switch ( $this->payment_type ) {
				case self::PAYMENT_TYPE_FINITE:
					$desc = sprintf(
						__( 'For %s', MS_TEXT_DOMAIN ),
						MS_Helper_Period::get_period_desc( $this->period )
					);
					break;

				case self::PAYMENT_TYPE_DATE_RANGE:
					$desc = sprintf(
						__( 'From %s to %s', MS_TEXT_DOMAIN ),
						$this->period_date_start,
						$this->period_date_end
					);
					break;

				case self::PAYMENT_TYPE_RECURRING:
					$desc = sprintf(
						__( 'Each %s', MS_TEXT_DOMAIN ),
						MS_Helper_Period::get_period_desc( $this->pay_cycle_period )
					);
					break;

				case self::PAYMENT_TYPE_PERMANENT:
				default:
					$desc = __( 'Single payment', MS_TEXT_DOMAIN );
					break;
			}
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
	 * @since 1.0.0
	 *
	 * @param string The rule model type @see MS_Model_Rule
	 * @return MS_Model_Rule The requested rule model.
	 */
	public function get_rule( $rule_type ) {
		if ( 'attachment' === $rule_type ) {
			$rule_type = MS_Model_Rule::RULE_TYPE_MEDIA;
		}

		if ( ! isset( $this->rules[ $rule_type ] ) ) {
			// Create a new rule model object.
			$rule = MS_Model_Rule::rule_factory( $rule_type, $this->id );

			$rule = apply_filters(
				'ms_model_membership_get_rule',
				$rule,
				$rule_type,
				$this
			);

			$this->rules[ $rule_type ] = $rule;
		}

		return $this->rules[ $rule_type ];
	}

	/**
	 * Returns the unique HEX color for this membership.
	 * The color is calculated from the membership-ID and therefore will never
	 * change.
	 *
	 * @since  1.1.0
	 * @return string Hex color, e.g. '#FFFFFF'
	 */
	public function get_color() {
		return MS_Helper_Utility::color_index( $this->id );
	}

	/**
	 * Set protection Rule Model.
	 *
	 * @since 1.0.0
	 *
	 * @param string The rule model type @see MS_Model_Rule
	 * @param MS_Model_Rule $rule The protection rule to set.
	 */
	public function set_rule( $rule_type, $rule ) {
		if ( MS_Model_Rule::is_valid_rule_type( $rule_type ) ) {
			$this->rules[ $rule_type ] = apply_filters(
				'ms_model_membership_set_rule',
				$rule,
				$rule_type,
				$this
			);
		}
	}

	/**
	 * Get available Memberships count.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The membership count.
	 */
	public static function get_membership_count( $args = null ) {
		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );

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
				self::$POST_TYPE // WHERE condition
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
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public static function get_query_args( $args = null ) {
		$defaults = apply_filters(
			'ms_model_membership_get_query_args_defaults',
			array(
				'post_type' => self::$POST_TYPE,
				'order' => 'DESC',
				'orderby' => 'ID',
				'post_status' => 'any',
				'post_per_page' => -1,
				'nopaging' => true,
				'include_base' => false,
				'include_guest' => true,
			)
		);

		$args = wp_parse_args( $args, $defaults );

		if ( ! WDev()->is_true( $args['include_base'] ) ) {
			$args['meta_query']['base'] = array(
				'key'     => 'type',
				'value'   => self::TYPE_BASE,
				'compare' => '!=',
			);
		}

		if ( ! WDev()->is_true( $args['include_guest'] ) ) {
			$args['meta_query']['guest'] = array(
				'key'     => 'type',
				'value'   => self::TYPE_GUEST,
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
	 * Get after membership expired options.
	 *
	 * Memberships can be downgraded to the guest level protection.
	 *
	 * @since 1.0.0
	 * @return array {
	 *		Returns array of $membership_id => $description.
	 *		@type int $membership_id The membership Id.
	 *		@type string $description The expired option description.
	 * }
	 */
	public function get_after_ms_ends_options() {
		$options = array(
			self::get_base()->id => __( 'Restrict access to Visitor-Level', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_model_membership_get_membership_names',
			$options,
			$this
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
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Membership[] The selected memberships.
	 */
	static public function get_memberships( $args = null ) {
		$args = self::get_query_args( $args );
		$args['order'] = 'ASC';
		$query = new WP_Query( $args );
		$items = $query->get_posts();

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

	/**
	 * Get membership names.
	 *
	 * @since 1.0.0
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
	 *
	 * @param int $membership_id The membership id to verify.
	 * @return bool True if is valid.
	 */
	public static function is_valid_membership( $membership_id ) {
		$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
		$valid = ( $membership->id > 0 );

		return apply_filters(
			'ms_model_membership_is_valid_membership',
			$valid,
			$membership_id
		);
	}

	/**
	 * Returns true if the membership the base membership.
	 *
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public function is_base() {
		return $this->type == self::TYPE_BASE;
	}

	/**
	 * Returns true if the membership the guest membership.
	 *
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public function is_guest() {
		return $this->type == self::TYPE_GUEST;
	}

	/**
	 * Returns true if the membership the base or guest membership.
	 *
	 * @since  1.1.0
	 *
	 * @return bool
	 */
	public function is_system() {
		$res = $this->is_base() || $this->is_guest();
	}

	/**
	 * Get protected content membership.
	 *
	 * Create a new membership if membership does not exist.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $type The membership to load [protected_content|role]
	 * @param  book $create_missing If set to false then missing special
	 *           memberships are not created.
	 * @return MS_Model_Membership The protected content.
	 */
	private static function _get_system_membership( $type, $create_missing = true ) {
		static $Special_Membership = array();
		$comp_key = $type;
		$membership = false;

		if ( ! isset( $Special_Membership[$comp_key] ) ) {
			$membership = false;
			global $wpdb;

			/*
			 * We are using a normal SQL query instead of using the WP_Query object
			 * here, because the WP_Query object does some strange things sometimes:
			 * In some cases new Protected Content memberships were created when a
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
				self::$POST_TYPE,
				'type',
				$type,
			);

			$sql = $wpdb->prepare( $sql, $values );
			$item = $wpdb->get_results( $sql );
			$base = array_shift( $item ); // Remove the base membership from the results

			if ( ! empty( $base ) ) {
				$membership = MS_Factory::load( 'MS_Model_Membership', $base->ID );

				// ---------- DB-correction part.
				// If more than one base memberships were found we fix the issue here!
				// This could happen in versions 1.0.0 - 1.0.4
				if ( 'protected_content' == $type && count( $item ) ) {
					// Change the excess base-memberships into normal memberships
					foreach ( $item as $membership ) {
						update_post_meta( $membership->ID, 'type', self::TYPE_STANDARD );
						wp_update_post(
							array(
								'ID' => $membership->ID,
								'post_title' => __( '(Invalid membership found)', MS_TEXT_DOMAIN ),
							)
						);
					}

					if ( MS_Model_Member::is_admin_user() ) {
						// Display a notification about the DB changes to Admin users only.
						WDev()->message(
							sprintf(
								__(
								'<b>Please check your Protected Content settings</b><br />' .
								'We found and fixed invalid content in your Database. ' .
								'However, plugin settings might have changed due to this change.<br />' .
								'You can review and delete the invalid content in the ' .
								'<a href="admin.php?page=%s">Memberships section</a>.',
								MS_TEXT_DOMAIN
								),
								MS_Controller_Plugin::MENU_SLUG
							)
						);
					}
				}
				// ---------- End of DB-correction part.
			} else if ( $create_missing ) {
				$names = self::get_types();

				$description = __( 'Protected Content core membership', MS_TEXT_DOMAIN );
				$membership = MS_Factory::create( 'MS_Model_Membership' );
				$membership->name = $names[$type];
				$membership->title = $names[$type];
				$membership->description = $description;
				$membership->type = $type;
				$membership->save();

				$membership = MS_Factory::load(
					'MS_Model_Membership',
					$membership->id
				);

				// It is important! The Protected Content membership must be public
				// so that the membership options are available for guest users.
				wp_publish_post( $membership->id );
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
	 * Get protected content membership.
	 *
	 * Create a new membership if membership does not exist.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Membership The protected content.
	 */
	public static function get_base() {
		static $Protected_content = null;

		if ( null === $Protected_content ) {
			$Protected_content = self::_get_system_membership(
				self::TYPE_BASE
			);

			foreach ( $Protected_content->rules as $rule_type => $rule ) {
				$Protected_content->rules[$rule_type]->is_base_rule = true;
			}

			$Protected_content = apply_filters(
				'ms_model_membership_get_base',
				$Protected_content
			);
		}

		return $Protected_content;
	}

	/**
	 * Get special role membership for a certain user role.
	 *
	 * Create a new membership if membership does not exist.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $role A WordPress user-role.
	 * @return MS_Model_Membership The core membership.
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

		return $Guest_Membership;
	}

	/**
	 * Merge protected content rules.
	 *
	 * Merge every rule model with protected content/visitor membership rules.
	 * This ensure rules are consistent with protected content rules.
	 *
	 * @since 1.0.0
	 */
	public function merge_protected_content_rules() {
		if ( $this->is_base() ) {
			// This is the visitor membership, no need to merge anything.
			return;
		}

		$base_rules = self::get_base()->rules;

		foreach ( $base_rules as $rule_type => $denied_items ) {
			try {
				if ( MS_Model_Rule::is_valid_rule_type( $rule_type ) ) {

					if ( ! empty( $this->rules[ $rule_type ] ) ) {
						// The membership has granted access to some rule items.
						$rule = $this->get_rule( $rule_type );
						$rule->merge_rule_values( $denied_items, true );
					} else {
						// This membership does not change the default protection.
						$rule = $this->get_rule( $rule_type );

						foreach ( $denied_items->rule_value as $id => $val ) {
							$rule->set_access( $id, MS_Model_Rule::RULE_VALUE_NO_ACCESS );
						}
					}
					$this->set_rule( $rule_type, $rule );
				}
			}
			catch( Exception $e ) {
				MS_Helper_Debug::log( $e );
			}
		}

		$this->rules = apply_filters(
			'ms_model_membership_merge_protected_content_rules',
			$this->rules,
			$this
		);
	}

	/**
	 * Get members count of this membership.
	 *
	 * @since 1.0.0
	 * @return int The members count.
	 */
	public function get_members_count() {
		$count = MS_Model_Relationship::get_membership_relationship_count(
			array( 'membership_id' => $this->id )
		);

		return apply_filters(
			'ms_model_membership_get_members_count',
			$count
		);
	}

	/**
	 * Delete membership.
	 *
	 * @since 1.0.0
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
				$ms_relationships = MS_Model_Relationship::get_membership_relationships(
					array( 'membership_id' => $this->id )
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
	 * Return membership has dripped content.
	 *
	 * Verify post and page rules if there is a dripped content.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	public function has_dripped_content() {
		$has_dripped = false;
		$dripped = array( 'post', 'page' );

		foreach ( $dripped as $type ) {
			// using count() as !empty() never returned true
			if ( 0 < count( $this->get_rule( $type )->dripped ) ) {
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
	 */
	private function get_rules_hierarchy() {
		$rule_types = MS_Model_Rule::get_rule_types();

		if ( self::TYPE_DRIPPED == $this->type
			&& ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST )
		) {
			$rule_types = array( 0 => MS_Model_Rule::RULE_TYPE_POST ) + $rule_types;
		}

		foreach ( $rule_types as $rule_type ) {
			$rules[ $rule_type ] = $this->get_rule( $rule_type );
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
	 * Verify access to current page.
	 *
	 * Verify membership rules hierarchy for content accessed directly.
	 * If 'has access' is found, it does have access.
	 * Only for active memberships.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship The membership relationship.
	 * @param int $post_id
	 * @return bool|null True if has access to current page. Default is false.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access_to_current_page( $ms_relationship, $post_id = null ) {
		$has_access = null;
		$this->_access_reason = array();

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

				// URL groups have final decission.
				if ( MS_Model_Rule::RULE_TYPE_URL_GROUP === $rule->rule_type ) {
					$has_access = $rule_access;
					break;
				}

				// Special pages have final decission after URL groups.
				if ( MS_Model_Rule::RULE_TYPE_SPECIAL === $rule->rule_type ) {
					$has_access = $rule_access;
					$this->_access_reason[] = $rule->matched_type;
					break;
				}

				$has_access = ( $has_access || $rule_access );

				if ( $has_access === true ) {
					break;
				}
			}

			// Search for dripped rules.
			$dripped = MS_Model_Rule::get_dripped_rule_types();

			/*
			 * Verify membership dripped rules hierarchy.
			 * Dripped has the final decision.
			 */
			foreach ( $dripped as $rule_type ) {
				$rule = $this->get_rule( $rule_type );

				if ( $rule->has_dripped_rules( $post_id ) ) {
					$dripped_access = $rule->has_dripped_access(
						$ms_relationship->start_date,
						$post_id,
						$this->dripped_type
					);
					$has_access = $dripped_access;

					$this->_access_reason[] = sprintf(
						__( '%s: Dripped Content', MS_TEXT_DOMAIN ),
						$dripped_access ? __( 'Allow', MS_TEXT_DOMAIN ) : __( 'Deny', MS_TEXT_DOMAIN )
					);
				}
			}
		}

		return apply_filters(
			'ms_model_membership_has_access_to_current_page',
			$has_access,
			$ms_relationship,
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
	 *
	 * @param int $post_id ID of specific post
	 * @return boolean True if has access to current page. Default is false.
	 */
	public function has_access_to_post( $post_id ) {
		$has_access = false;

		if ( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
			if ( 'attachment' === $post->post_type ) {
				$post_id = get_post_field( 'post_parent', $post_id );
			}
		}

		// If 'has access' is found in the hierarchy, it does have access.
		$rules = $this->get_rules_hierarchy();
		foreach ( $rules as $rule ) {
			// url groups have final decision
			if ( MS_Model_Rule::RULE_TYPE_URL_GROUP == $rule->rule_type
				&& $rule->has_rule_for_post( $post_id )
			) {
				$has_access = $rule->has_access( $post_id );
				break;
			} else {
				$has_access = ( $has_access || $rule->has_access( $post_id ) );
			}

			if ( $has_access ) {
				break;
			}
		}

		return apply_filters(
			'ms_model_membership_has_access_to_post',
			$has_access,
			$this
		);
	}

	/**
	 * Set initial protection for front-end.
	 *
	 * Hide restricted content for this membership.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship $ms_relationship The membership relationship.
	 */
	public function protect_content( $ms_relationship ) {
		do_action(
			'ms_model_membership_protect_content_before',
			$ms_relationship,
			$this
		);

		$rules = $this->get_rules_hierarchy();

		// Apply protection settings of all rules (replace/hide contents, ...)
		foreach ( $rules as $rule ) {
			$rule->protect_content( $ms_relationship );
		}

		do_action(
			'ms_model_membership_protect_content_after',
			$ms_relationship,
			$this
		);
	}

	/**
	 * Set initial protection for admin side.
	 *
	 * Hide restricted content for this membership.
	 *
	 * @since 1.1
	 * @param MS_Model_Relationship $ms_relationship The membership relationship.
	 */
	public function protect_admin_content( $ms_relationship ) {
		do_action(
			'ms_model_membership_protect_content_before',
			$ms_relationship,
			$this
		);

		$rules = $this->get_rules_hierarchy();

		foreach ( $rules as $rule ) {
			$rule->protect_admin_content( $ms_relationship );
		}

		do_action(
			'ms_model_membership_protect_content_after',
			$ms_relationship,
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
	 * @return bool
	 */
	public function can_change_payment() {
		// Allow if Membership is new/unsaved.
		if ( empty( $this->id ) ) { return true; }

		// Allow if no payment detail was entered yet (incomplete setup).
		if ( empty( $this->payment_type ) ) { return true; }

		// Allow if no members signed up yet.
		$members = MS_Model_Relationship::get_membership_relationship_count(
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
				if ( ! array_key_exists( $this->$property, $types ) ) {
					$this->$property = self::PAYMENT_TYPE_PERMANENT;
				}
				$value = $this->$property;
				break;

			case 'trial_period_enabled':
			case 'active':
			case 'private':
			case 'is_free':
				$value = WDev()->is_true( $this->$property );
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
	 *
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'name':
				case 'title':
				case 'description':
					$this->$property = sanitize_text_field( $value );
					break;

				case 'type':
					switch ( $value ) {
						case self::TYPE_BASE:
						case self::TYPE_GUEST:
							// Only one instance of these types can exist.
							$existing = $this->_get_system_membership( $value, false );

							if ( $existing ) {
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
							$error = 'Membership type cannot be changed after members have signed up.';
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
					$this->$property = WDev()->is_true( $value );
					break;

				case 'price':
				case 'trial_price':
					$this->$property = floatval( $value );
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
					if ( 0 < MS_Factory::load( 'MS_Model_Membership', $value )->id ) {
						$this->$property = $value;
					}

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

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since 1.0.0
	 */
	public static function get_register_post_type_args() {
		$args = array(
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
			'ms_model_membership_get_register_post_type_args',
			$args
		);
	}
}
