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
 * Membership Rule Parent class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Rule extends MS_Model {

	/**
	 * Membership ID.
	 *
	 * @since 1.0.0
	 * @var int $membership_id
	 */
	protected $membership_id = 0;

	/**
	 * Does this rule belong to the base membership?
	 * If yes, then we need to invert all access: "has access" in base rule
	 * means that the item is protected.
	 *
	 * @since 1.1.0
	 * @var bool
	 */
	protected $is_base_rule = false;

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 * @var string $rule_type
	 */
	protected $rule_type;

	/**
	 * Rule value data.
	 *
	 * Each child rule may use it's own data structure, but
	 * need to override core methods that use parent data structure.
	 *
	 * @since 1.0.0
	 * @var array $rule_value {
	 *     @type int $item_id The protected item ID.
	 *     @type int $value The rule value. 0: no access; 1: has access.
	 * }
	 */
	protected $rule_value = array();

	/**
	 * Dripped Rule data.
	 *
	 * Each child rule may use it's own data structure, but
	 * need to override core methods that use parent data structure.
	 *
	 * @since 1.0.0
	 * @var array {
	 *     A hash that defines the drip options of each protected item.
	 *
	 *     @type int The protected item ID.
	 *     @type array {
	 *         @type string $type Type of dripped protection
	 *         @type string $date Only used for type 'specific_date'
	 *         @type string $delay_unit Only used for type 'from_registration'
	 *         @type string $delay_type Only used for type 'from_registration'
	 *     }
	 * }
	 *
	 */
	protected $dripped = array();


	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 * @var int $membership_id The membership that owns this rule object.
	 */
	public function __construct( $membership_id ) {
		parent::__construct();

		$this->membership_id = apply_filters(
			'ms_rule_constructor_membership_id',
			$membership_id,
			$this
		);

		$membership = MS_Factory::load( 'MS_Model_membership', $membership_id );
		$this->is_base_rule = $membership->is_base();

		$this->initialize();
	}

	/**
	 * Called by the constructor.
	 *
	 * This function offers a save way for each rule to initialize itself if
	 * required.
	 *
	 * This function is executed in Admin and Front-End, so it should only
	 * initialize stuff that is really needed!
	 *
	 * @since  1.1
	 */
	protected function initialize() {
		// Can be overwritten by child classes.
	}

	/**
	 * Returns the active flag for a specific rule.
	 * Default state is "active" (return value TRUE)
	 *
	 * Rules that need to be activated via an add-on should overwrite this
	 * method to return the current rule-state
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static public function is_active() {
		return true;
	}

	/**
	 * Validate dripped type.
	 *
	 * @since 1.0.0
	 * @param string $type The rule type to validate.
	 * @return bool True if is a valid dripped type.
	 */
	public static function is_valid_dripped_type( $type ) {
		$valid = array_key_exists( $type, MS_Model_Rule::get_dripped_types() );

		return apply_filters( 'ms_rule_is_valid_dripped_type', $valid );
	}

	/**
	 * Create a rule model.
	 *
	 * @since 1.0.0
	 * @param string $rule_type The rule type to create.
	 * @param int $membership_id The Membership model this rule belongs to.
	 * @return MS_Rule The rule model.
	 * @throws Exception when rule type is not valid.
	 */
	public static function rule_factory( $rule_type, $membership_id ) {
		$rule_types = MS_Model_Rule::get_rule_type_classes();
		if ( isset( $rule_types[ $rule_type ] ) ) {
			$class = $rule_types[ $rule_type ];

			$rule = MS_Factory::load( $class, $membership_id, $rule_type );
		} else {
			MS_Helper_Debug::log( 'Rule type not registered: ' . $rule_type );
			$rule = MS_Factory::create( 'MS_Rule', $membership_id );
		}

		return apply_filters(
			'ms_rule_rule_factory',
			$rule,
			$rule_type,
			$membership_id
		);
	}

	/**
	 * Set initial protection for front-end.
	 *
	 * To be overridden by children classes.
	 *
	 * @since 1.0.0
	 * @param MS_Model_Relationship The membership relationship to protect content from.
	 */
	public function protect_content( $ms_relationship = false ) {
		do_action(
			'ms_rule_protect_content',
			$ms_relationship,
			$this
		);
	}

	/**
	 * Set initial protection for admin side.
	 *
	 * To be overridden by children classes.
	 *
	 * @since 1.1
	 * @param MS_Model_Relationship The membership relationship to protect content from.
	 */
	public function protect_admin_content( $ms_relationship = false ) {
		do_action(
			'ms_rule_protect_admin_content',
			$ms_relationship,
			$this
		);
	}

	/**
	 * Verify if this model has rules set.
	 *
	 * @since 1.0.0
	 * @return boolean True if it has rules, false otherwise.
	 */
	public function has_rules() {
		$has_rules = false;
		foreach ( $this->rule_value as $val ) {
			if ( $val ) {
				$has_rules = true; break;
			}
		}

		return apply_filters(
			'ms_rule_has_rules',
			$has_rules,
			$this
		);
	}

   /**
	* Count protection rules quantity.
	*
	* @since 1.0.0
	* @param bool $has_access_only Optional. Count rules for has_access status only.
	* @return int $count The rule count result.
	*/
	public function count_rules( $has_access_only = true ) {
		$count = 0;

		if ( $has_access_only ) {
			foreach ( $this->rule_value as $val ) {
				if ( $val ) { $count++; }
			}
		} else {
			$count = count( $this->rule_value );
		}

		return apply_filters(
			'ms_rule_count_rules',
			$count,
			$has_access_only,
			$this
		);
	}

	/**
	 * Get rule value for a specific content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to get rule value for.
	 * @return boolean The rule value for the requested content. Default $rule_value_default.
	 */
	public function get_rule_value( $id ) {
		if ( is_scalar( $id ) && isset( $this->rule_value[ $id ] ) ) {
			// A rule is defined. It is either TRUE or FALSE
			$value = (bool) $this->rule_value[ $id ];
		} else {
			// Default response is NULL: "Not-Denied"
			$value = MS_Model_Rule::RULE_VALUE_UNDEFINED;
		}

		return apply_filters(
			'ms_get_rule_value',
			$value,
			$id,
			$this->rule_type,
			$this
		);
	}

	/**
	 * Serializes this rule in a single array.
	 * We don't use the PHP `serialize()` function to serialize the whole object
	 * because a lot of unrequired and duplicate data will be serialized
	 *
	 * Can be overwritten by child classes to implement a distinct
	 * serialization logic.
	 *
	 * @since  1.1.0
	 * @return array The serialized values of the Rule.
	 */
	public function serialize() {
		$access = array();
		foreach ( $this->rule_value as $id => $state ) {
			if ( $state ) {
				if ( isset( $this->dripped[$id] ) ) {
					$access[] = array(
						'id' => $id,
						'dripped' => array(
							$this->dripped[$id]['type'],
							$this->dripped[$id]['date'],
							$this->dripped[$id]['delay_unit'],
							$this->dripped[$id]['delay_type'],
						),
					);
				} else {
					$access[] = $id;
				}
			}
		}

		return $access;
	}

	/**
	 * Populates the rule_value array with the specified value list.
	 * This function is used when de-serializing a membership to re-create the
	 * rules associated with the membership.
	 *
	 * Can be overwritten by child classes to implement a distinct
	 * deserialization logic.
	 *
	 * @since  1.1.0
	 * @param  array $values A list of allowed IDs.
	 */
	public function populate( $values ) {
		foreach ( $values as $data ) {
			if ( is_scalar( $data ) ) {
				$this->give_access( $data );
			} else {

				if ( isset( $data['id'] )
					&& ! empty( $data['dripped'] )
					&& is_array( $data['dripped'] )
					&& count( $data['dripped'] ) > 3
				) {
					$this->give_access( $data['id'] );
					$this->dripped[ $data['id'] ] = array(
						'type' => $data['dripped'][0],
						'date' => $data['dripped'][1],
						'delay_unit' => $data['dripped'][2],
						'delay_type' => $data['dripped'][3],
					);
				}
			}
		}
	}

	/**
	 * Returns an array of membership that protect the specified rule item.
	 *
	 * @since  1.1.0
	 *
	 * @param string $id The content id to check.
	 * @return array List of memberships (ID => name)
	 */
	public function get_memberships( $id ) {
		static $All_Memberships = null;
		$res = array();

		if ( null === $All_Memberships ) {
			$All_Memberships = MS_Model_Membership::get_memberships();
		}

		foreach ( $All_Memberships as $membership ) {
			$rule = $membership->get_rule( $this->rule_type );
			if ( isset( $rule->rule_value[ $id ] ) && $rule->rule_value[ $id ] ) {
				$res[$membership->id] = $membership->name;
			}
		}

		return $res;
	}

	/**
	 * Defines, which memberships protect the specified rule item.
	 *
	 * Note: This method should only be called for the BASE membership!
	 *
	 * @since  1.1.0
	 *
	 * @param string $id The content id to check.
	 * @return array List of memberships (ID => name)
	 */
	public function set_memberships( $id, $memberships ) {
		static $All_Memberships = null;

		if ( ! $this->is_base_rule ) {
			throw new Exception( 'set_memberships() must be called on the base-rule!', 1 );
			return;
		}

		if ( null === $All_Memberships ) {
			$All_Memberships = MS_Model_Membership::get_memberships();
		}

		$base = MS_Model_Membership::get_base();
		$base_rule = $base->get_rule( $this->rule_type );
		$current_protection = $base_rule->get_rule_value( $id );
		$should_protect = ! empty( $memberships );

		if ( ! $should_protect ) {
			$base_rule->remove_access( $id );
		} elseif ( ! $current_protection ) {
			// Only `give_access()` when the item is not protected yet.
			$base_rule->give_access( $id );
		}
		$base->set_rule( $this->rule_type, $base_rule );
		$base->save();

		foreach ( $All_Memberships as $membership ) {
			$rule = $membership->get_rule( $this->rule_type );
			if ( in_array( $membership->id, $memberships ) ) {
				$rule->give_access( $id );
			} else {
				$rule->remove_access( $id );
			}
			$membership->set_rule( $this->rule_type, $rule );
			$membership->save();
		}
	}

	/**
	 * Verify access to the current content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return boolean TRUE if has access, FALSE otherwise.
	 */
	public function has_access( $id ) {
		/*
		 * $access will be one of these:
		 *   - TRUE .. Access explicitly granted
		 *   - FALSE .. Access explicitly denied
		 *   - NULL .. Access implicitly allowed (i.e. "not-denied")
		 */
		$access = $this->get_rule_value( $id );

		if ( $this->is_base_rule ) {
			/*
			 * Base rule ..
			 *   - The meaning of TRUE/FALSE is inverted
			 *   - NULL is always "allowed"
			 */
			$access = ! $access;
		} else {
			// Apply dripped-content rules if neccessary.
			if ( $access && $this->has_dripped_rules( $id ) ) {
				$avail_date = $this->get_dripped_avail_date( $id );
				$now = MS_Helper_Period::current_date();

				$access = strtotime( $now ) >= strtotime( $avail_date );
			}

			if ( MS_Model_Rule::RULE_VALUE_UNDEFINED === $access ) {
				// NULL .. "not-denied" is translated to "allowed"
				$access = true;
			}
		}

		// At this point $access can either be TRUE or FALSE, not NULL!
		$access = (bool) $access;

		return apply_filters(
			'ms_rule_has_access',
			$access,
			$id,
			$this->ruly_type,
			$this
		);
	}

	/**
	 * Verify if has dripped rules.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify.
	 * @return boolean True if has dripped rules.
	 */
	public function has_dripped_rules( $id = null ) {
		if ( ! is_array( $this->dripped ) ) { $this->dripped = array(); }

		if ( empty( $id ) ) {
			$has_dripped = ! empty( $this->dripped );
		} else {
			$has_dripped = ! empty( $this->dripped[$id] );
		}

		return apply_filters(
			'ms_rule_has_dripped_rules',
			$has_dripped,
			$id,
			$this
		);
	}

	/**
	 * Set dripped value.
	 *
	 * Handler for setting dripped data content.
	 *
	 * @since 1.0.0
	 * @param int $item_id Drip-Settings of this item are changed.
	 * @param string $drip_type Any of MS_Model_Rule::DRIPPED_TYPE_* values.
	 * @param string $date Only used for type 'specific_date'
	 * @param string $delay_unit Only used for type 'from_registration'
	 * @param string $delay_type Only used for type 'from_registration'
	 */
	public function set_dripped_value( $item_id, $drip_type, $date = '', $delay_unit = '', $delay_type = '' ) {
		$this->give_access( $item_id );
		$this->dripped[ $item_id ] = apply_filters(
			'ms_rule_set_dripped_value',
			array(
				'type' => $drip_type,
				'date' => $date,
				'delay_unit' => $delay_unit,
				'delay_type' => $delay_type,
			),
			$this
		);

		do_action(
			'ms_rule_set_dripped_value_after',
			$drip_type,
			$item_id,
			$this
		);
	}

	/**
	 * Returns the effective date on which the specified item becomes available.
	 *
	 * @since 1.0.0
	 *
	 * @param string $item_id The content id to verify dripped access.
	 * @param string $start_date The start date of the member membership.
	 * @return string Date on which the item is revealed (e.g. '2015-02-16')
	 */
	public function get_dripped_avail_date( $item_id, $start_date = null ) {
		$avail_date = MS_Helper_Period::current_date();
		$drip_data = false;

		if ( ! is_array( $this->dripped ) ) { $this->dripped = array(); }
		if ( isset( $this->dripped[ $item_id ] ) ) {
			$drip_data = $this->dripped[ $item_id ];
		}

		if ( is_array( $drip_data ) ) {
			WDev()->array->equip( $drip_data, 'type', 'date', 'delay_unit', 'delay_type' );

			switch ( $drip_data['type'] ) {
				case MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE:
					$avail_date = $drip_data['date'];
					break;

				case MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION:
					if ( empty( $start_date ) ) {
						$start_date = MS_Helper_Period::current_date( null, false );
					}

					$period_unit = $drip_data['delay_unit'];
					$period_type = $drip_data['delay_type'];
					$avail_date = MS_Helper_Period::add_interval(
						$period_unit,
						$period_type,
						$start_date
					);
					break;

				case MS_Model_Rule::DRIPPED_TYPE_INSTANTLY:
				default:
					$avail_date = MS_Helper_Period::current_date();
					break;
			}
		}

		return apply_filters(
			'ms_rule_get_dripped_avail_date',
			$avail_date,
			$item_id,
			$start_date,
			$this
		);
	}

	/**
	 * Returns a string that describes the dripped rule.
	 *
	 * @since 1.1.0
	 *
	 * @param string $item_id The content id to verify dripped access.
	 * @return string Text like "Instantly" or "After 7 days"
	 */
	public function get_dripped_description( $item_id ) {
		static $Format = null;

		$desc = '';
		$drip_data = false;

		if ( null === $Format ) { $Format = get_option( 'date_format' ); }
		if ( ! is_array( $this->dripped ) ) { $this->dripped = array(); }
		if ( isset( $this->dripped[ $item_id ] ) ) {
			$drip_data = $this->dripped[ $item_id ];
		}

		if ( is_array( $drip_data ) ) {
			WDev()->array->equip( $drip_data, 'type', 'date', 'delay_unit', 'delay_type' );

			switch ( $drip_data['type'] ) {
				case MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE:
					$desc = sprintf(
						__( 'On <b>%1$s</b>', MS_TEXT_DOMAIN ),
						date_i18n( $Format, strtotime( $drip_data['date'] ) )
					);
					break;

				case MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION:
					$periods = MS_Helper_Period::get_period_types();
					$period_key = $drip_data['delay_type'];

					if ( 0 == $drip_data['delay_unit'] ) {
						$desc = __( '<b>Instantly</b>', MS_TEXT_DOMAIN );
					} elseif ( 1 == $drip_data['delay_unit'] ) {
						$desc = sprintf(
							__( 'After <b>%1$s %2$s</b>', MS_TEXT_DOMAIN ),
							$periods['1' . $period_key],
							''
						);
					} else {
						$desc = sprintf(
							__( 'After <b>%1$s %2$s</b>', MS_TEXT_DOMAIN ),
							$drip_data['delay_unit'],
							$periods[$period_key]
						);
					}
					break;

				case MS_Model_Rule::DRIPPED_TYPE_INSTANTLY:
				default:
					$desc = __( '<b>Instantly</b>', MS_TEXT_DOMAIN );
					break;
			}
		}

		return apply_filters(
			'ms_rule_get_dripped_description',
			$desc,
			$item_id,
			$this
		);
	}

	/**
	 * Count item protected content summary.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array {
	 *     @type int $total The total content count.
	 *     @type int $accessible The has access content count.
	 *     @type int $restricted The protected content count.
	 * }
	 */
	public function count_item_access( $args = null ) {
		if ( $this->is_base_rule ) {
			$args['default'] = 1;
		}

		$args['posts_per_page'] = 0;
		$args['offset'] = false;
		$total = $this->get_content_count( $args );
		$contents = $this->get_contents( $args );
		$count_accessible = 0;
		$count_restricted = 0;

		if ( ! is_array( $this->rule_value ) ) {
			$this->rule_value = array();
		}

		foreach ( $contents as $id => $content ) {
			if ( $content->access ) {
				$count_accessible++;
			} else {
				$count_restricted++;
			}
		}

		if ( $this->is_base_rule ) {
			$count_restricted = $total - $count_accessible;
		} else {
			$count_accessible = $total - $count_restricted;
		}

		$count = array(
			'total' => $total,
			'accessible' => $count_accessible,
			'restricted' => $count_restricted,
		);

		return apply_filters( 'ms_rule_count_item_access', $count );
	}

	/**
	 * Get content to protect.
	 *
	 * To be overridden in children classes.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The contents array.
	 */
	public function get_contents( $args = null ) {
		return array();
	}

	/**
	 * Get content count.
	 *
	 * To be overridden in children classes.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The content count.
	 */
	public function get_content_count( $args = null ) {
		return 0;
	}

   /**
	* Reset the rule value data.
	*
	* @since 1.0.0
	* @param $args The query post args
	*     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	* @return int The content count.
	*/
	public function reset_rule_values() {
		$this->rule_value = apply_filters(
			'ms_rule_reset_values',
			array(),
			$this
		);
	}

   /**
	* Denies access to all items that are defined in the base-rule but
	* not in the current rule.
	*
	* @since 1.0.0
	* @param MS_Rule $base_rule The source rule model to merge rules to.
	*/
	public function protect_undefined_items( $base_rule ) {
		if ( $base_rule->rule_type != $this->rule_type ) { return; }

		if ( ! is_array( $this->rule_value ) ) {
			$this->rule_value = array();
		}
		if ( ! is_array( $base_rule->rule_value ) ) {
			$base_rule->rule_value = array();
		}

		$base_rule_value = $base_rule->rule_value;

		/*
		 * Remove protection of items that are protected by the current rule
		 * but NOT protected by the base-rule.
		 * I.e. remove invalid protection information.
		 */
		foreach ( $this->rule_value as $id => $access ) {
			if ( ! isset( $base_rule->rule_value[$id] ) ) {
				unset( $this->rule_value[ $id ] );
			}
		}

		/*
		 * Get the items that are protected by base but not allowed by
		 * the membership. Deny access to these items.
		 */
		$base_rule_value = array_diff_key(
			$base_rule_value,
			$this->rule_value
		);

		foreach ( $base_rule_value as $id => $access ) {
			if ( $access ) {
				$this->rule_value[ $id ] = MS_Model_Rule::RULE_VALUE_NO_ACCESS;
			}
		}

		do_action( 'ms_merge_rule_values', $this, $base_rule );
	}

	/**
	 * Set access status to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to set access to.
	 * @param bool $access The access status to set.
	 */
	public function set_access( $id, $access ) {
		if ( $access ) {
			$this->rule_value[ $id ] = MS_Model_Rule::RULE_VALUE_HAS_ACCESS;
		} else {
			unset( $this->rule_value[ $id ] );
			unset( $this->dripped[ $id ] );
		}

		do_action( 'ms_rule_set_access', $id, $access, $this );
	}

	/**
	 * Give access to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to give access.
	 */
	public function give_access( $id ) {
		$this->set_access(
			$id,
			MS_Model_Rule::RULE_VALUE_HAS_ACCESS
		);

		do_action( 'ms_rule_give_access', $id, $this );
	}

	/**
	 * Remove access to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to remove access.
	 */
	public function remove_access( $id ) {
		$this->set_access(
			$id,
			MS_Model_Rule::RULE_VALUE_NO_ACCESS
		);

		do_action( 'ms_rule_remove_access', $id, $this );
	}

	/**
	 * Toggle access to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to toggle access.
	 */
	public function toggle_access( $id ) {
		$current_value = $this->get_rule_value( $id );
		$has_access = MS_Model_Rule::RULE_VALUE_HAS_ACCESS !== $current_value;

		$this->set_access(
			$id,
			$has_access
		);

		do_action( 'ms_rule_toggle_access', $id, $this );
	}

	/**
	 * Get WP_Query object arguments.
	 *
	 * Return default search arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public function prepare_query_args( $args = null, $args_type = 'wp_query' ) {
		$filter = $this->get_exclude_include( $args );

		/**
		 * By default the $args collection is supposed to be passed to a
		 * WP_Query constructor. However, we can also prepare the filter
		 * arguments to be used for another type of query, like get_pages()
		 */
		$args_type = strtolower( $args_type );

		switch ( $args_type ) {
			case 'get_pages':
				$defaults = array(
					'number' => false,
					'hierarchical' => 1,
					'sort_column' => 'post_title',
					'sort_order' => 'ASC',
					'post_type' => 'page',
				);
				$args['exclude'] = $filter->exclude;
				$args['include'] = $filter->include;
				break;

			case 'get_categories':
				$defaults = array(
					'get' => 'all', // interpreted by get_terms()
				);

				if ( isset( $args['s'] ) ) {
					$args['search'] = $args['s'];
				}

				$args['exclude'] = $filter->exclude;
				$args['include'] = $filter->include;
				break;

			case 'get_posts':
			case 'wp_query':
			default:
				$defaults = array(
					'posts_per_page' => -1,
					'ignore_sticky_posts' => true,
					'offset' => 0,
					'orderby' => 'ID',
					'order' => 'DESC',
					'post_status' => 'publish',
				);
				$args['post__not_in'] = $filter->exclude;
				$args['post__in'] = $filter->include;
				break;
		}

		$args = wp_parse_args( $args, $defaults );
		$args = $this->validate_query_args( $args, $args_type );

		return apply_filters(
			'ms_rule_' . $this->id . '_get_query_args',
			$args,
			$args_type,
			$this
		);
	}

	/**
	 * Returns a list of post_ids to exclude or include to fullfil the specified
	 * Membership/Status filter.
	 *
	 * @since  1.1.0
	 * @param  array $args
	 * @return array {
	 *     List of post_ids to exclude or include
	 *
	 *     array $include
	 *     array $exclude
	 * }
	 */
	public function get_exclude_include( $args ) {
		// Filter for Membership and Protection status via 'exclude'/'include'
		$include = array();
		$exclude = array();
		$base_rule = $this;
		$child_rule = $this;

		if ( ! $this->is_base_rule ) {
			$base_rule = MS_Model_Membership::get_base()->get_rule( $this->rule_type );
		}
		if ( ! empty( $args['membership_id'] ) ) {
			$child_rule = MS_Factory::load( 'MS_Model_Membership', $args['membership_id'] )->get_rule( $this->rule_type );
		}

		$base_items = array_keys( $base_rule->rule_value, true );
		$child_items = array_keys( $child_rule->rule_value, true );

		$status = ! empty( $args['rule_status'] ) ? $args['rule_status'] : null;

		switch ( $status ) {
			case MS_Model_Rule::FILTER_PROTECTED;
				if ( ! empty( $args['membership_id'] ) ) {
					$include = array_intersect( $child_items, $base_items );
				} else {
					$include = $child_items;
				}
				if ( empty( $include ) ) {
					$include = array( -1 );
				}
				break;

			case MS_Model_Rule::FILTER_NOT_PROTECTED;
				if ( ! empty( $args['membership_id'] ) ) {
					$include = array_diff( $base_items, $child_items );
					if ( empty( $include ) && empty( $exclude ) ) {
						$include = array( -1 );
					}
				} else {
					$exclude = $child_items;
					if ( empty( $include ) && empty( $exclude ) ) {
						$exclude = array( -1 );
					}
				}
				break;

			default:
				// If not visitor membership, just show all protected content
				if ( ! $child_rule->is_base_rule ) {
					$include = $base_items;
				}
				break;
		}

		$res = (object) array(
			'include' => null,
			'exclude' => null,
		);

		if ( ! empty( $include ) ) {
			$res->include = $include;
		} elseif ( ! empty( $exclude ) ) {
			$res->exclude = $exclude;
		} elseif ( ! empty( $args['membership_id'] ) ) {
			$res->include = array( -1 );
		}

		return $res;
	}

	/**
	 * Validate wp query args.
	 *
	 * Avoid post__in and post__not_in conflicts.
	 *
	 * @since 1.0.0
	 * @param mixed $args The query post args
	 *     @see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return mixed $args The validated args.
	 */
	public function validate_query_args( $args, $args_type = 'wp_query' ) {
		switch ( $args_type ) {
			case 'get_pages':
			case 'get_categories':
				$arg_excl = 'exclude';
				$arg_incl = 'include';
				break;

			case 'get_posts':
			case 'wp_query':
			default:
				$arg_excl = 'post__not_in';
				$arg_incl = 'post__in';
				break;
		}

		// Remove undefined exclude/include arguments.
		if ( isset( $args[$arg_incl] ) && null === $args[$arg_incl] ) {
			unset( $args[$arg_incl] );
		}
		if ( isset( $args[$arg_excl] ) && null === $args[$arg_excl] ) {
			unset( $args[$arg_excl] );
		}

		// Cannot use exclude and include at the same time.
		if ( ! empty( $args[$arg_incl] ) && ! empty( $args[$arg_excl] ) ) {
			$include = $args[$arg_incl];
			$exclude = $args[$arg_excl];

			foreach ( $exclude as $id ) {
				$key = array_search( $id, $include );
				unset( $include[ $key ] );
			}
			unset( $args[$arg_excl] );
		}

		if ( isset( $args[$arg_incl] ) && count( $args[$arg_incl] ) == 0 ) {
			$args[$arg_incl] = array( -1 );
		}

		switch ( $args_type ) {
			case 'get_pages':
				// No validation required.
				break;

			case 'get_categories':
				if ( ! empty( $args['number'] ) ) {
					/*
					 * 'hierarchical' and 'child_of' must be empty in order for
					 * offset/number to work correctly.
					 */
					$args['hierarchical'] = false;
					$args['child_of'] = false;
				}
				break;

			case 'wp_query':
			case 'get_posts':
			default:
				if ( ! empty( $args['show_all'] )
					|| ! empty( $args['category__in'] )
				) {
					unset( $args['post__in'] );
					unset( $args['post__not_in'] );
					unset( $args['show_all'] );
				}
				break;
		}

		return apply_filters(
			'ms_rule_' . $this->id . '_validate_query_args',
			$args,
			$args_type,
			$this
		);
	}

	/**
	 * Filter content.
	 *
	 * @since 1.0.0
	 * @param string $status The status to filter.
	 * @param mixed[] $contents The content object array.
	 * @return mixed[] The filtered contents.
	 */
	public function filter_content( $status, $contents ) {
		foreach ( $contents as $key => $content ) {
			if ( ! empty( $content->ignore ) ) {
				continue;
			}

			switch ( $status ) {
				case MS_Model_Rule::FILTER_PROTECTED:
					if ( ! $content->access ) {
						unset( $contents[ $key ] );
					}
					break;

				case MS_Model_Rule::FILTER_NOT_PROTECTED:
					if ( $content->access ) {
						unset( $contents[ $key ] );
					}
					break;

				case MS_Model_Rule::FILTER_DRIPPED:
					if ( empty( $content->delayed_period ) ) {
						unset( $contents[ $key ] );
					}
					break;
			}
		}

		return apply_filters(
			'ms_rule_filter_content',
			$contents,
			$status,
			$this
		);
	}

	/**
	 * Returns Membership object.
	 *
	 * @since 1.0.0
	 * @return MS_Model_Membership The membership object.
	 */
	public function get_membership() {
		$membership = MS_Factory::load(
			'MS_Model_Membership',
			$this->membership_id
		);

		return apply_filters( 'ms_rule_get_membership', $membership );
	}

	/**
	 * Returns property associated with the render.
	 *
	 * @since 1.0.0
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;
		switch ( $property ) {
			case 'rule_value':
			case 'dripped':
				$this->$property = WDev()->array->get( $this->$property );
				$value = $this->$property;
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters(
			'ms_rule__get',
			$value,
			$property,
			$this
		);
	}

	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch ( $property ) {
				case 'rule_type':
					if ( in_array( $value, MS_Model_Rule::get_rule_types() ) ) {
						$this->$property = $value;
					}
					break;

				case 'dripped':
					if ( is_array( $value ) ) {
						$this->$property = $value;
					}
					break;

				default:
					$this->$property = $value;
					break;
			}
		}

		do_action(
			'ms_rule__set_after',
			$property,
			$value,
			$this
		);
	}
}