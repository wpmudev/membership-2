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
 * Persisted by parent class MS_Model_Custom_Post_Type.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Membership extends MS_Model_Custom_Post_Type {

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
	const TYPE_SIMPLE = 'simple';
	const TYPE_CONTENT_TYPE = 'content_type';
	const TYPE_TIER = 'tier';
	const TYPE_DRIPPED = 'dripped';

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
	 * ID of the model object.
	 *
	 * Saved as WP post ID.
	 *
	 * @since 1.0.0
	 * @var int $id
	 */
	protected $id;

	/**
	 * Membership name.
	 *
	 * @since 1.0.0
	 * @var string $name
	 */
	protected $name = '';

	/**
	 * Membership description.
	 *
	 * @since 1.0.0
	 * @var string $description
	 */
	protected $description = '';

	/**
	 * Membership type.
	 *
	 * @since 1.0.0
	 * @var string $type
	 */
	protected $type;

	/**
	 * Membership payment type.
	 *
	 * @since 1.0.0
	 * @var string $payment_type
	 */
	protected $payment_type;

	/**
	 * Membership parent.
	 *
	 * Zero value indicate this membership does not have a parent.
	 *
	 * @since 1.0.0
	 * @var int $parent_id
	 */
	protected $parent_id = 0;

	/**
	 * Membership active status.
	 *
	 * @since 1.0.0
	 * @var bool $active
	 */
	protected $active = false;

	/**
	 * Membership private status.
	 *
	 * @since 1.0.0
	 * @var bool $private
	 */
	protected $private = true;

	/**
	 * Protected Content Membership.
	 *
	 * It is the membership assigned to visitors.
	 *
	 * @since 1.0.0
	 * @var bool $protected_content.
	 */
	protected $protected_content = false;

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
	protected $period;

	/**
	 * Membership payment recurring period cycle.
	 *
	 * @since 1.0.0
	 * @var array $pay_cycle_period @see $period.
	 */
	protected $pay_cycle_period;

	/**
	 * Membership start date for date range payment type.
	 *
	 * @since 1.0.0
	 * @var string The membership start date.
	 */
	protected $period_date_start;

	/**
	 * Membership end date for date range payment type.
	 *
	 * @since 1.0.0
	 * @var string The membership end date.
	 */
	protected $period_date_end;

	/**
	 * Membership trial period enabled indicator.
	 *
	 * @since 1.0.0
	 * @var bool $trial_period_enabled.
	 */
	protected $trial_period_enabled;

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
	protected $trial_period;

	/**
	 * Membership dripped type.
	 *
	 * @since 1.0.0
	 * @var string $dripped_type The dripped type used in this membership.
	 *				@see MS_Model_Rule::get_dripped_rule_types()
	 */
	protected $dripped_type;

	/**
	 * Move to Membership when the current one expires.
	 *
	 * After current membership expire move to the indicated membership_id.
	 *
	 * @since 1.0.0
	 * @var int $on_end_membership_id.
	 */
	protected $on_end_membership_id;

	/**
	 * Membership setup completed flag.
	 *
	 * @since 1.0.0
	 * @var bool $is_setup_completed.
	 */
	protected $is_setup_completed;

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
	 * Used in simulation mode explaining why a page is allowed or denied.
	 *
	 * @since 1.0.1
	 * @var array
	 */
	public $access_reason = array();

	/**
	 * Set rules membership_id before saving.
	 *
	 * @since 1.0.0
	 */
	public function before_save() {

		parent::before_save();

		foreach( $this->rules as $rule ) {
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
	public function after_load() {

		parent::after_load();

		/** validate rules using protected content rules */
		if( ! $this->protected_content && $this->is_valid() ) {
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
	public static function get_types() {

		$types = array(
				self::TYPE_SIMPLE => __( 'Simple', MS_TEXT_DOMAIN ),
				self::TYPE_CONTENT_TYPE => __( 'Multiple Content Types', MS_TEXT_DOMAIN ),
				self::TYPE_TIER => __( 'Tier Based', MS_TEXT_DOMAIN ),
				self::TYPE_DRIPPED => __( 'Dripped Content', MS_TEXT_DOMAIN ),
		);

		return apply_filters( 'ms_model_membership_get_types', $types );
	}

	/**
	 * Verify membership type validation.
	 *
	 * @since 1.0.0
	 * @param string $type The type to verify validation.
	 * @return bool True if valid.
	 */
	public static function is_valid_type( $type ) {

		$valid = array_key_exists( $type, self::get_types() );

		return apply_filters( 'ms_model_membership_is_valid_type', $valid );
	}

	/**
	 * Get current membership type description.
	 *
	 * @since 1.0.0
	 * @return string The membership type description.
	 */
	public function get_type_description() {
		$description = array();

		if( self::is_valid_type( $this->type ) && empty( $this->parent_id ) ) {
			$types = self::get_types();
			$desc = $types[ $this->type ];
			if( $this->can_have_children() ) {
				$desc .= sprintf( ' (%s)', $this->get_children_count() );
			}
			$description[] = $desc;
			if( $this->is_private_eligible() ) {
				if( $this->is_private() ) {
					$description[] = __( 'Private', MS_TEXT_DOMAIN );
				}
			}
		}
		$description = join( ', ', $description );

		return apply_filters( 'ms_model_membership_get_type_description', $description, $this );
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

		return apply_filters( 'ms_model_membership_get_payment_types', $payment_types );
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

		switch ( $this->payment_type ) {
			case self::PAYMENT_TYPE_PERMANENT:
				$desc = __( 'Single payment', MS_TEXT_DOMAIN );
				break;

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

		}
		return apply_filters( 'ms_model_membership_get_payment_type_desc', $desc, $this );
	}

	/**
	 * Information if payment information are defined for this membership.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True if payment information are available.
	 */
	public function has_payment() {
		if ( $this->payment_type === self::PAYMENT_TYPE_PERMANENT ) { return true; }
		if ( $this->payment_type === self::PAYMENT_TYPE_FINITE ) { return true; }
		if ( $this->payment_type === self::PAYMENT_TYPE_DATE_RANGE ) { return true; }
		if ( $this->payment_type === self::PAYMENT_TYPE_RECURRING ) { return true; }
		return false;
	}

	/**
	 * Return Parent information.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if has parent.
	 */
	public function has_parent() {

		$has_parent = false;

		if( $this->parent_id > 0 ) {
			$has_parent = true;
		}

		return apply_filters( 'ms_model_membership_has_parent', $has_parent, $this );
	}

	/**
	 * Get Parent Membership.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Membership The parent membership or null if doesn't exist.
	 */
	public function get_parent() {

		$parent = null;

		if( $this->has_parent() ) {
			$parent = MS_Factory::load( 'MS_Model_Membership', $this->parent_id );
		}

		return apply_filters( 'ms_model_membership_get_parent', $parent, $this );
	}

	/**
	 * Return if current Membership can have children.
	 *
	 * No grand child is allowed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if can have children.
	 */
	public function can_have_children() {

		$can_have_children = false;

		if( ! $this->has_parent() && in_array( $this->type, self::get_parent_eligible_types() ) ) {
			$can_have_children = true;
		}

		return apply_filters( 'ms_model_membership_can_have_children', $can_have_children, $this );
	}

	/**
	 * Return Membership types tha can be parent.
	 *
	 * @since 1.0.0
	 *
	 * @return array The membership parent eligible types
	 */
	public static function get_parent_eligible_types() {

		$parent_eligible_types = array(
				self::TYPE_CONTENT_TYPE,
				self::TYPE_TIER
		);

		return apply_filters( 'ms_model_membership_get_parent_eligible_types', $parent_eligible_types );
	}

	/**
	 * Create child membership.
	 *
	 * Create a child cloning the parent membership.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Membership The child membership.
	 */
	public function create_child( $name ) {

		$child = null;
		$src = null;

		if( $this->can_have_children() ) {
			if( self::TYPE_TIER == $this->type ) {
				$src = $this->get_last_descendant();
			}
			else {
				$src = $this;
			}

			$child = clone $src;
			$child->id = 0;
			$child->parent_id = $this->id;
			$child->name = $name;
			$child->description = '';
			$child->save();
		}

		return apply_filters( 'ms_model_membership_create_child', $child, $name, $this );
	}

	/**
	 * Retrieve all children Memberships.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Membership[] The children memberships.
	 */
	public function get_children( $args = null ) {

		$children = array();

		if( ! $this->has_parent() ) {
			$args['meta_query']['children'] = array(
					'key'     => 'parent_id',
					'value'   => $this->id,
			);

			$children = self::get_memberships( $args );
		}

		return apply_filters( 'ms_model_membership_get_children', $children, $this, $args );
	}

	/**
	 * Retrieve the last descendant.
	 *
	 * Used to clone the last tier of tiered membership types.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Membership The last descendant.
	 */
	public function get_last_descendant() {

		$last = null;

		if( $this->can_have_children() ) {
			$child = $this->get_children( array( 'post_per_page' => 1 ) );
			if( isset( $child[0] ) ) {
				$last = $child[0];
			}
			else {
				$last = $this;
			}
		}

		return apply_filters( 'ms_model_membership_get_last_descendant', $last, $this );
	}

	/**
	 * Retrieve the number of children of this membership.
	 *
	 * @since 1.0.0
	 *
	 * @return int The children count.
	 */
	public function get_children_count() {

		$children = $this->get_children();
		$count = count( $children );

		return apply_filters( 'ms_model_membership_get_children_count', $count, $this );
	}

	/**
	 * Retrieve the private status.
	 *
	 * @since 1.0.0
	 *
	 * @return bool The private status.
	 */
	public function is_private() {

		$private = false;

		if( $this->is_private_eligible() && $this->private ) {
			$private = true;
		}

		return apply_filters( 'ms_model_membership_is_private', $private, $this );
	}

	/**
	 * Verify if this membership can be a private one.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if this membership can be private.
	 */
	public function is_private_eligible() {

		$is_private_eligible = false;

		if( in_array( $this->type, self::get_private_eligible_types() ) ) {
			$is_private_eligible = true;
		}

		return apply_filters( 'ms_model_membership_is_private_eligible', $is_private_eligible, $this );
	}

	/**
	 * Get the private eligible membership types.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] The private eligible types.
	 */
	public static function get_private_eligible_types() {

		// Private memberships can only be enabled in these types.
		$private_eligible_types = array(
			self::TYPE_SIMPLE,
			self::TYPE_CONTENT_TYPE,
		);

		return apply_filters( 'ms_model_membership_get_private_eligible_types', $private_eligible_types );
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
		$rule = null;

		if ( isset( $this->rules[ $rule_type ] ) ) {
			$rule = $this->rules[ $rule_type ];
		}
		elseif ( 'attachment' === $rule_type
			&& isset( $this->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ] )
		) {
			$rule = $this->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ];
		}
		// Create a new rule model object.
		else {
			$rule = MS_Model_Rule::rule_factory( $rule_type, $this->id );
			$this->rules[ $rule_type ] = $rule;
		}

		if ( $this->protected_content ) {
			// This is the base membership "Protected Content".
			$rule->rule_value_invert = true;
			$rule->rule_value_default = false;
		}
		else {
			// This is a normal membership created by the Admin.
			$rule->rule_value_invert = false;
			$rule->rule_value_default = true;
		}

		return apply_filters(
			'ms_model_membership_get_rule',
			$rule,
			$rule_type,
			$this
		);
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
		if( MS_Model_Rule::is_valid_rule_type( $rule_type) ) {
			$this->rules[ $rule_type ] = apply_filters( 'ms_model_membership_set_rule', $rule, $rule_type, $this );
		}
	}

	/**
	 * Get available Memberships count.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The membership count.
	 */
	public static function get_membership_count( $args = null ) {

		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );

		$count = $query->found_posts;

		return apply_filters( 'ms_model_membership_get_membership_count', $count, $args );
	}

	/**
	 * Get Memberships models.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Membership[] The selected memberships.
	 */
	public static function get_memberships( $args = null ) {

		$args = self::get_query_args( $args );
		$query = new WP_Query( $args );
		$items = $query->get_posts();

		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[] = MS_Factory::load( 'MS_Model_Membership', $item->ID );
		}

		return apply_filters( 'ms_model_membership_get_memberships', $memberships, $args );
	}

	/**
	 * Get grouped Memberships models.
	 *
	 * Used in admin list table to show parent and children memberships grouped.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return MS_Model_Membership[] The selected memberships.
	 */
	public static function get_grouped_memberships( $args ) {

		/** Get parent memberships */
		$args['post_parent'] = 0;
		$memberships = self::get_memberships( $args );

		/** Get children memberships */
		if( ! empty( $args['post__not_in'] ) ) {
			$args = array( 'post__not_in' => $args['post__not_in'] );
		}
		else {
			$args = array();
		}
		$args['post_parent__not_in'] = array( 0 );
		$args['order'] = 'ASC';
		$children = self::get_memberships( $args );
		foreach( $children as $child ) {
			$new = array();
			foreach( $memberships as $ms ){
				$new[] = $ms;
				if( $ms->id == $child->parent_id ) {
					$new[ $child->id ] = $child;
				}
			}
			$memberships = $new;
		}

		return apply_filters( 'ms_model_membership_get_grouped_memberships', $memberships, $args );
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Default search arguments for this custom post_type.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public static function get_query_args( $args = null ) {

		$defaults = apply_filters( 'ms_model_membership_get_query_args_defaults', array(
				'post_type' => self::$POST_TYPE,
				'order' => 'DESC',
				'orderby' => 'ID',
				'post_status' => 'any',
				'post_per_page' => -1,
				'nopaging' => true,
		) );

		$args = wp_parse_args( $args, $defaults );

		if( empty( $args['include_visitor'] ) ){
			$args['meta_query']['visitor'] = array(
				'key'     => 'protected_content',
				'value'   => '',
			);
		}

		return apply_filters( 'ms_model_membership_get_query_args', $args, $defaults );
	}

	/**
	 * Get after membership expired options.
	 *
	 * Tiered membership types can downgrade to a lower tier.
	 * Other membership types can be restricted to protected content = visitor membership.
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
				self::get_protected_content()->id => __( 'Restrict access to Protected Content', MS_TEXT_DOMAIN ),
		);
		switch( $this->type ) {
			case self::TYPE_TIER:
				$parent = $this;
				if( $this->parent_id > 0 ) {
					$parent = $this->get_parent();
				}
				$children = $parent->get_children();
				foreach ( $children as $child ) {
					if( $child->id >= $this->id ) {
						continue;
					}
					$options[ $child->id ] = sprintf( __( 'Downgrade to %s', MS_TEXT_DOMAIN ), $child->name );
				}

				break;
			default:
				break;
		}

		return apply_filters( 'ms_model_membership_get_membership_names', $options, $this );
	}

	/**
	 * Get membership names.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @param bool $exclude_visitor_membership Exclude visitor membership from the list.
	 * @return array {
	 *		Returns array of $membership_id => $name
	 *		@type int $membership_id The membership Id.
	 *		@type string $name The membership name;
	 * }
	 */
	public static function get_membership_names( $args = null, $exclude_visitor_membership = false ) {
		$args['order'] = 'ASC';
		$args = self::get_query_args( $args );

		$query = new WP_Query( $args );
		$items = $query->get_posts();

		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[ $item->ID ] = $item->name;
		}
		if ( $exclude_visitor_membership ) {
			unset( $memberships[ self::get_visitor_membership()->id ] );
		}

		return apply_filters( 'ms_model_membership_get_membership_names', $memberships, $args, $exclude_visitor_membership );
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
		$not_in[] = MS_Model_Membership::get_visitor_membership()->id;
		$args['post__not_in'] = array_unique( $not_in );

		if ( ! is_admin() ) {
			$include_private = false;
		}
		// List of private memberships (they are grouped in own array).
		$private = array();

		// Retrieve memberships user is not part of, using selected args
		$memberships = MS_Model_Membership::get_grouped_memberships( $args );

		$parent_ids = array();
		foreach ( $memberships as $key => $membership ) {
			if ( $membership->can_have_children() ) {
				if ( ! $membership->active ) {
					$parent_ids[] = $membership->id;
				}
				// Remove parent memberships
				unset( $memberships[ $key ] );
			}
			// Remove all children if parent is disabled
			if ( in_array( $membership->parent_id, $parent_ids ) ) {
				unset( $memberships[ $key ] );
			}
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
	 * @param int $membership_id The membership id to verify.
	 * @return bool True if is valid.
	 */
	public static function is_valid_membership( $membership_id ) {

		$valid = ( MS_Factory::load( 'MS_Model_Membership', $membership_id )->id > 0 );

		return apply_filters( 'ms_model_membership_is_valid_membership', $valid, $membership_id );
	}

	/**
	 * Get protected content membership.
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Membership The protected content.
	 */
	public static function get_protected_content() {
		static $Protected_content = null;

		if ( null === $Protected_content ) {
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
				INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID
				WHERE
					p.post_type = %s
					AND m.meta_key = %s
					AND m.meta_value = %s
			";

			$sql = $wpdb->prepare( $sql, self::$POST_TYPE, 'protected_content', 1 );
			$item = $wpdb->get_results( $sql );
			$base = array_shift( $item ); // Remove the base membership from the results

			if ( ! empty( $base ) ) {
				$Protected_content = MS_Factory::load( 'MS_Model_Membership', $base->ID );

				// If more than one base memberships were found we fix the issue here!
				// This could happen in versions 1.0.0 - 1.0.4
				if ( count( $item ) ) {
					// Change the excess base-memberships into normal memberships
					foreach ( $item as $membership ) {
						update_post_meta( $membership->ID, 'protected_content', '' );
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
				// End of DB-correction part.
			}
			else {
				$description = __( 'Protected content, and also a default membership for visitors', MS_TEXT_DOMAIN );
				$Protected_content = MS_Factory::create( 'MS_Model_Membership' );
				$Protected_content->name = __( 'Protected Content', MS_TEXT_DOMAIN );
				$Protected_content->payment_type = self::PAYMENT_TYPE_PERMANENT;
				$Protected_content->title = $description;
				$Protected_content->description = $description;
				$Protected_content->protected_content = true;
				$Protected_content->active = true;
				$Protected_content->private = true;
				$Protected_content->save();
				$Protected_content = MS_Factory::load( 'MS_Model_Membership', $Protected_content->id );

				// It is important! The Protected Content membership must be public
				// so that the membership options are available for guest users.
				wp_publish_post( $Protected_content->id );
			}

			$Protected_content = apply_filters( 'ms_model_membership_get_protected_content', $Protected_content );
		}

		return $Protected_content;
	}

	/**
	 * Get membership assigned to visitors.
	 *
	 * Create a new membership if visitor membership does not exist.
	 * It is the same as "protected content".
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Membership The visitor membership.
	 */
	public static function get_visitor_membership() {

		$visitor_membership = self::get_protected_content();

		return apply_filters( 'ms_model_membership_get_visitor_membership', $visitor_membership );
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

		$protected_content_rules = self::get_protected_content()->rules;

		foreach( $protected_content_rules as $rule_type => $protect_rule ) {
			try {
				if( MS_Model_Rule::is_valid_rule_type( $rule_type ) ) {

					if( ! empty( $this->rules[ $rule_type ] ) ) {
						$rule = $this->get_rule( $rule_type );
						$rule->merge_rule_values( $protect_rule );
					}
					else {
						$rule = clone $protect_rule;
						$rule->rule_value_invert = false;

						$init_rule_value = MS_Model_Rule::RULE_VALUE_NO_ACCESS;
						if( self::TYPE_SIMPLE == $this->type ) {
							$init_rule_value = MS_Model_Rule::RULE_VALUE_HAS_ACCESS;
						}
						foreach( $rule->rule_value as $id => $val ) {
							$rule->set_access( $id, $init_rule_value );
						}
					}
					$this->set_rule( $rule_type, $rule );
				}
			}
			catch( Exception $e ) {
				MS_Helper_Debug::log( $e );
			}
		}

		$this->rules = apply_filters( 'ms_model_membership_merge_protected_content_rules', $this->rules, $this );
	}

	/**
	 * Get members count of this membership.
	 *
	 * @since 1.0.0
	 * @return int The members count.
	 */
	public function get_members_count() {

		$count = MS_Model_Membership_Relationship::get_membership_relationship_count( array( 'membership_id' => $this->id ) );

		return apply_filters( 'ms_model_membership_get_members_count', $count );
	}

	/**
	 * Delete membership.
	 *
	 * @since 1.0.0
	 *
	 * @param $force To force delete memberships with members, visitor or default memberships.
	 */
	public function delete( $force = false ) {
		do_action( 'ms_model_membership_before_delete', $this );

		if ( ! empty( $this->id ) ) {
			if ( $this->get_members_count() > 0 && ! $force ) {
				throw new Exception( 'Can not delete membership with existing members.' );
			}
			elseif ( $this->protected_content && ! $force ) {
				throw new Exception( 'Protected Content / Visitor membership could not be deleted.' );
			}
			elseif ( $this->get_children_count() > 0 && ! $force ) {
				throw new Exception( 'Can not delete membership level with children. Delete children membership levels first.' );
			}
			wp_delete_post( $this->id );
		}

		do_action( 'ms_model_membership_after_delete', $this );
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

		foreach( $dripped as $type ) {
			//using count() as !empty() never returned true
			if ( 0 < count( $this->get_rule( $type )->dripped ) ) {
				$has_dripped = true;
			}
		}

		return apply_filters( 'ms_model_membership_has_dripped_content', $has_dripped, $this );
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

		if ( self::TYPE_DRIPPED == $this->type && ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$rule_types = array( 0 => MS_Model_Rule::RULE_TYPE_POST ) + $rule_types;
		}

		foreach( $rule_types as $rule_type ) {
			$rules[ $rule_type ] = $this->get_rule( $rule_type );
		}

		return apply_filters( 'ms_model_membership_get_rules_hierarchy', $rules, $this );
	}

	/**
	 * Mark membership setup as completed.
	 *
	 * Used for auto setup purposes.
	 *
	 * @since 1.0.0
	 *
	 * @return bool $marked True in the first time setup is finished.
	 */
	public function mark_setup_completed() {

		$marked = false;

		if( ! $this->is_setup_completed ) {
			$this->is_setup_completed = true;
			$marked = true;
		}

		return apply_filters( 'ms_model_membership_mark_setup_completed', $marked, $this );
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
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @param int $post_id
	 * @return boolean True if has access to current page. Default is false.
	 */
	public function has_access_to_current_page( $ms_relationship, $post_id = null ) {
		$has_access = false;
		$this->access_reason = array();

		// Only verify access if membership is Active.
		if ( $this->active ) {

			// If 'has access' is found in the hierarchy, it does have access.
			$rules = $this->get_rules_hierarchy();
			foreach ( $rules as $rule ) {
				$rule_access = $rule->has_access( $post_id );
				$this->access_reason[] = sprintf(
					__( '%s: Rule "%s"', MS_TEXT_DOMAIN ),
					$rule_access ? __( 'Allow', MS_TEXT_DOMAIN ) : __( 'Deny', MS_TEXT_DOMAIN ),
					$rule->rule_type
				);

				// URL groups have final decission.
				if ( MS_Model_Rule::RULE_TYPE_URL_GROUP === $rule->rule_type
					&& $rule->has_rule_for_current_url()
				) {
					$has_access = $rule_access;
					break;
				}

				// Special pages have final decission after URL groups.
				if ( MS_Model_Rule::RULE_TYPE_SPECIAL === $rule->rule_type
					&& $rule->has_rule_for_current_page()
				) {
					$has_access = $rule_access;
					$this->access_reason[] = $rule->matched_type;
					break;
				}

				$has_access = ( $has_access || $rule_access );

				if ( $has_access ) {
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

					$this->access_reason[] = sprintf(
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

		if( ! empty( $post_id ) ) {
			$post = get_post( $post_id );
			if( 'attachment' == $post->post_type ) {
				$post_id = get_post_field( 'post_parent', $post_id );
			}
		}
		/* If 'has access' is found in the hierarchy, it does have access. */
		$rules = $this->get_rules_hierarchy();
		foreach( $rules as $rule ) {
			/* url groups have final decision */
			if( MS_Model_Rule::RULE_TYPE_URL_GROUP == $rule->rule_type && $rule->has_rule_for_post( $post_id ) ) {
				$has_access = $rule->has_access( $post_id );
				break;
			}
			else {
				$has_access = ( $has_access || $rule->has_access( $post_id ) );
			}

			if( $has_access ) {
				break;
			}
		}

		return apply_filters( 'ms_model_membership_has_access_to_post', $has_access, $this );
	}

	/**
	 * Set initial protection.
	 *
	 * Hide restricted content for this membership.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 */
	public function protect_content( $ms_relationship ) {

		do_action( 'ms_model_membership_protect_content_before', $ms_relationship, $this );

		$rules = $this->get_rules_hierarchy();

		/**
		 * Set initial protection.
		 * Hide content.
		*/
		foreach( $rules as $rule ) {
			$rule->protect_content( $ms_relationship );
		}

		do_action( 'ms_model_membership_protect_content_after', $ms_relationship, $this );
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
			case 'payment_type':
				if ( empty( $this->payment_type ) ) {
					$this->payment_type = self::PAYMENT_TYPE_PERMANENT;
				}
				$value = $this->payment_type;
				break;

			case 'type_description':
				$value = $this->get_type_description();
				break;

			case 'private':
				$value = $this->is_private();
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

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters( 'ms_model_membership__get', $value, $property, $this );
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
					if ( array_key_exists( $value, self::get_types() ) ) {
						$this->$property = $value;
					}
					break;

				case 'payment_type':
					if ( array_key_exists( $value, self::get_payment_types() ) ) {
						if ( empty( $this->$property ) || empty( $this->id ) || 0 == MS_Model_Membership_Relationship::get_membership_relationship_count( array( 'membership_id' => $this->id ) ) ) {
							$this->$property = $value;
						}
						elseif ( $this->$property != $value ) {
							$error = 'Membership type cannot be changed after members have signed up.';
							MS_Helper_Debug::log( $error );
							throw new Exception( $error );
						}
					}
					else {
						throw new Exception( 'Invalid membership type.' );
					}
					break;

				case 'protected_content':
				case 'trial_period_enabled':
				case 'active':
				case 'public':
					$this->$property = $this->validate_bool( $value );
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
		}
		else {
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

		do_action( 'ms_model_membership__set_after', $property, $value, $this );
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

		return apply_filters( 'ms_model_membership_get_register_post_type_args', $args );
	}
}
