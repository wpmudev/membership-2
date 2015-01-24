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
class MS_Model_Rule extends MS_Model {

	/**
	 * Rule type constants.
	 *
	 * @since 1.0.0
	 * @var string $rule_type The rule type.
	 */
	const RULE_TYPE_CATEGORY = 'category';
	const RULE_TYPE_CONTENT = 'content';
	const RULE_TYPE_MEDIA = 'media';
	const RULE_TYPE_MENU = 'menu';
	const RULE_TYPE_PAGE = 'page';
	const RULE_TYPE_POST = 'post';
	const RULE_TYPE_CUSTOM_POST_TYPE = 'cpt';
	const RULE_TYPE_CUSTOM_POST_TYPE_GROUP = 'cpt_group';
	const RULE_TYPE_SHORTCODE = 'shortcode';
	const RULE_TYPE_URL_GROUP = 'url_group';
	const RULE_TYPE_SPECIAL = 'special';
	const RULE_TYPE_REPLACE_MENUS = 'replace_menu';
	const RULE_TYPE_REPLACE_MENULOCATIONS = 'replace_menulocation';
	// New since 1.1
	const RULE_TYPE_ADMINSIDE = 'adminside';
	const RULE_TYPE_MEMBERCAPS = 'membercaps';
	const RULE_TYPE_MEMBERROLES = 'memberroles';

	/**
	 * Rule value constants.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	const RULE_VALUE_NO_ACCESS = 0;
	const RULE_VALUE_HAS_ACCESS = 1;

	/**
	 * Filter type constants.
	 *
	 * @since 1.0.0
	 */
	const FILTER_HAS_ACCESS = 'has_access';
	const FILTER_NO_ACCESS = 'no_access';
	const FILTER_PROTECTED = 'protected';
	const FILTER_NOT_PROTECTED = 'not_protected';
	const FILTER_DRIPPED = 'dripped';

	/**
	 * Dripped type constants.
	 *
	 * @since 1.0.0
	 * @var string $dripped['dripped_type'] The dripped type.
	 */
	const DRIPPED_TYPE_SPEC_DATE = 'specific_date';
	const DRIPPED_TYPE_FROM_TODAY = 'from_today';
	const DRIPPED_TYPE_FROM_REGISTRATION = 'from_registration';

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
	 *     @type int $item_id The protecting item ID.
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
	 *     @type string $dripped_type The selected dripped type.
	 *     @type array $rule_value {
	 *         @type int $item_id The protecting item ID.
	 *         @type int $dripped_data The dripped data like period or release date.
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
			'ms_model_rule_constructor_membership_id',
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
	 * Option to initialize objects.
	 *
	 * @since  1.1
	 */
	public function prepare_obj() {
		if ( $this->_prepared ) { return; }
		$this->_prepared = true;

		// Can be overwritten by child classes.
	}

	/**
	 * Rule types.
	 *
	 * This array is ordered in the hierarchy way.
	 * First one has more priority than the last one.
	 * This hierarchy is used to determine access to protected content.
	 *
	 * @since 1.0.0
	 * @return array $rule_types {
	 *     @type in $priority The rule type priority in the execution sequence.
	 *     @type string $rule_type The rule type.
	 * }
	 */
	public static function get_rule_types() {
		static $Types = null;

		if ( null === $Types ) {
			$settings = MS_Factory::load( 'MS_Model_Settings' );

			// Always start by first creating the full list including all add-ons

			$rule_types = array(
				-10 => self::RULE_TYPE_URL_GROUP,
				-9 => self::RULE_TYPE_MEMBERCAPS,
				-8 => self::RULE_TYPE_MEMBERROLES,
				-1 => self::RULE_TYPE_ADMINSIDE,
				0 => self::RULE_TYPE_SPECIAL,
				1 => self::RULE_TYPE_POST,
				10 => self::RULE_TYPE_CATEGORY,
				20 => self::RULE_TYPE_CUSTOM_POST_TYPE,
				30 => self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
				40 => self::RULE_TYPE_PAGE,
				50 => self::RULE_TYPE_MENU,
				60 => self::RULE_TYPE_SHORTCODE,
				70 => self::RULE_TYPE_CONTENT,
				80 => self::RULE_TYPE_MEDIA,
			);

			// Then we remove/replace items that are not activated

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS ) ) {
				unset( $rule_types[-9] );
			}

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_ADMINSIDE ) ) {
				unset( $rule_types[-1] );
			}

			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
				unset( $rule_types[10] );
			} else {
				unset( $rule_types[1] );
			}

			if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
				unset( $rule_types[30] );
			} else {
				unset( $rule_types[20] );
			}

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
				unset( $rule_types[90] );
			}

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
				unset( $rule_types[-10] );
			}

			switch ( $settings->menu_protection ) {
				case 'item':
					$rule_types[60] = self::RULE_TYPE_MENU;
					break;

				case 'menu':
					$rule_types[60] = self::RULE_TYPE_REPLACE_MENUS;
					break;

				case 'location':
					$rule_types[60] = self::RULE_TYPE_REPLACE_MENULOCATIONS;
					break;

			}

			$rule_types = apply_filters( 'ms_model_rule_get_rule_types', $rule_types );
			$rule_type = ksort( $rule_types );

			$Types = $rule_types;
		}

		return $Types;
	}

	/**
	 * Rule types and respective classes.
	 *
	 * @since 1.0.0
	 * @return array {
	 *     @type string $rule_type The rule type constant.
	 *     @type string $class_name The rule type class.
	 * }
	 */
	public static function get_rule_type_classes() {
		$classes = array(
			self::RULE_TYPE_POST => 'MS_Rule_Post_Model',
			self::RULE_TYPE_SPECIAL => 'MS_Rule_Special_Model',
			self::RULE_TYPE_CATEGORY => 'MS_Rule_Category_Model',
			self::RULE_TYPE_CUSTOM_POST_TYPE => 'MS_Rule_CptItem_Model',
			self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP => 'MS_Rule_CptGroup_Model',
			self::RULE_TYPE_PAGE => 'MS_Rule_Page_Model',
			self::RULE_TYPE_MENU => 'MS_Rule_MenuItem_Model',
			self::RULE_TYPE_REPLACE_MENUS => 'MS_Rule_ReplaceMenu_Model',
			self::RULE_TYPE_REPLACE_MENULOCATIONS => 'MS_Rule_ReplaceLocation_Model',
			self::RULE_TYPE_SHORTCODE => 'MS_Rule_Shortcode_Model',
			self::RULE_TYPE_CONTENT => 'MS_Rule_Content_Model',
			self::RULE_TYPE_MEDIA => 'MS_Rule_Media_Model',
			self::RULE_TYPE_URL_GROUP => 'MS_Rule_Url_Model',
			// New since 1.1
			self::RULE_TYPE_ADMINSIDE => 'MS_Rule_Adminside_Model',
			self::RULE_TYPE_MEMBERCAPS => 'MS_Rule_MemberCaps_Model',
			self::RULE_TYPE_MEMBERROLES => 'MS_Rule_MemberRoles_Model',
		);

		return apply_filters( 'ms_model_rule_get_rule_type_classes', $classes );
	}

	/**
	 * Rule types and respective titles.
	 *
	 * @since 1.0.0
	 * @return array {
	 *     @type string $rule_type The rule type constant.
	 *     @type string $rule_title The rule title.
	 * }
	 */
	public static function get_rule_type_titles() {
		$titles = array(
			self::RULE_TYPE_CATEGORY => __( 'Category', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_SPECIAL => __( 'Special Pages', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_CONTENT => __( 'Comments & More Tag', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_MEDIA => __( 'Media', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_MENU => __( 'Menu', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_REPLACE_MENUS => __( 'Menu', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_REPLACE_MENULOCATIONS => __( 'Menu', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_PAGE => __( 'Page', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_POST => __( 'Post', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_SHORTCODE => __( 'Shortcode', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_URL_GROUP => __( 'URLs', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_CUSTOM_POST_TYPE => __( 'Custom Post Type', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP => __( 'Custom Post Type', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_ADMINSIDE => __( 'Admin Side', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_MEMBERCAPS => __( 'Capabilities', MS_TEXT_DOMAIN ),
			self::RULE_TYPE_MEMBERROLES => __( 'User Role', MS_TEXT_DOMAIN ),
		);

		return apply_filters( 'ms_model_rule_get_rule_type_titles', $titles );
	}

	/**
	 * Dripped Rule types.
	 *
	 * Return only rule types with dripped rules.
	 *
	 * @since 1.0.0
	 * @return string[] $rule_type The rule type constant.
	 */
	public static function get_dripped_rule_types() {
		$dripped = array(
			MS_Model_Rule::RULE_TYPE_PAGE,
			MS_Model_Rule::RULE_TYPE_POST,
		);

		return apply_filters(
			'ms_model_rule_get_dripped_rule_types',
			$dripped
		);
	}

	/**
	 * Get dripped types.
	 *
	 * @todo Remove or develop DRIPPED_TYPE_FROM_TODAY
	 *
	 * @since 1.0.0
	 * @return array {
	 *     @type string $dripped_type The dripped type constant.
	 *     @type string $dripped_type_desc The dripped type description.
	 * }
	 */
	public static function get_dripped_types() {
		$dripped_types = array(
			self::DRIPPED_TYPE_SPEC_DATE => __( 'Reveal Dripped Content on specific dates', MS_TEXT_DOMAIN ),
//			self::DRIPPED_TYPE_FROM_TODAY => __( "Reveal Dripped Content 'X' days from today", MS_TEXT_DOMAIN ),
			self::DRIPPED_TYPE_FROM_REGISTRATION => __( "Reveal Dripped Content 'X' days from user registration", MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'ms_model_rule_get_dripped_types',
			$dripped_types
		);
	}

	/**
	 * Validate dripped type.
	 *
	 * @since 1.0.0
	 * @param string $type The rule type to validate.
	 * @return bool True if is a valid dripped type.
	 */
	public static function is_valid_dripped_type( $type ) {
		$valid = array_key_exists( $type, self::get_dripped_types() );

		return apply_filters( 'ms_model_rule_is_valid_dripped_type', $valid );
	}

	/**
	 * Create a rule model.
	 *
	 * @since 1.0.0
	 * @param string $rule_type The rule type to create.
	 * @param int $membership_id The Membership model this rule belongs to.
	 * @return MS_Model_Rule The rule model.
	 * @throws Exception when rule type is not valid.
	 */
	public static function rule_factory( $rule_type, $membership_id ) {
		if ( self::is_valid_rule_type( $rule_type ) ) {
			$rule_types = self::get_rule_type_classes();
			$class = $rule_types[ $rule_type ];

			$rule = MS_Factory::load( $class, $membership_id, $rule_type );

			return apply_filters(
				'ms_model_rule_rule_factory',
				$rule,
				$rule_type,
				$membership_id
			);
		} else {
			throw new Exception(
				'Rule factory - rule type not found: ' . $rule_type
			);
		}
	}

	/**
	 * Validate rule type.
	 *
	 * @since 1.0.0
	 * @param string $rule_type The rule type to validate.
	 * @return bool True if is a valid type.
	 */
	public static function is_valid_rule_type( $rule_type ) {
		$valid = array_key_exists( $rule_type, self::get_rule_type_classes() );

		return apply_filters( 'ms_model_rule_is_valid_rule_type', $valid );
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
			'ms_model_rule_protect_content',
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
			'ms_model_rule_protect_admin_content',
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
		$has_rules = ! empty( $this->rule_value );

		return apply_filters( 'ms_model_rule_has_rules', $has_rules, $this );
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
			'ms_model_rule_count_rules',
			$count,
			$has_access_only,
			$this
		);
	}

	/**
	 * Verify if this model has rule for a content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify rules for.
	 * @return boolean True if it has rules, false otherwise.
	 */
	public function has_rule( $id ) {
		$has_rule = isset( $this->rule_value[ $id ] ) ;

		return apply_filters(
			'ms_model_rule_has_rule',
			$has_rule,
			$id,
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
			$value = $this->rule_value[ $id ];
		} else {
			// If no rule is defined for the item then assume "No Access".
			$value = self::RULE_VALUE_NO_ACCESS;
		}

		return apply_filters(
			'ms_model_rule_get_rule_value',
			$value,
			$id,
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
			if ( $state ) { $access[] = $id; }
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
		foreach ( $values as $id ) {
			$this->give_access( $id );
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
	 * @since  1.1.0
	 *
	 * @param string $id The content id to check.
	 * @return array List of memberships (ID => name)
	 */
	public function set_memberships( $id, $memberships ) {
		static $All_Memberships = null;

		if ( null === $All_Memberships ) {
			$All_Memberships = MS_Model_Membership::get_memberships();
		}

		$base = MS_Model_Membership::get_base();
		if ( empty( $memberships ) ) {
			$base->get_rule( $this->rule_type )->remove_access( $id );
		} else {
			$base->get_rule( $this->rule_type )->give_access( $id );
		}
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
	 * @return boolean True if has access, false otherwise.
	 */
	public function has_access( $id = null ) {
		$has_access = false;

		if ( ! empty( $id ) ) {
			$has_access = $this->get_rule_value( $id );
		}

		if ( $this->is_base_rule ) {
			$has_access = ! $has_access;
		}

		return apply_filters(
			'ms_model_rule_has_access',
			$has_access,
			$id,
			$this
		);
	}

	/**
	 * Get current dripped type.
	 *
	 * @since 1.0.0
	 *
	 * @return string The dripped type.
	 */
	public function get_dripped_type() {
		$dripped_type = self::DRIPPED_TYPE_SPEC_DATE;

		if ( ! empty( $this->dripped['dripped_type'] ) ) {
			$dripped_type = $this->dripped['dripped_type'];
		}

		return apply_filters(
			'ms_model_rule_get_dripped_type',
			$dripped_type,
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
		$has_dripped = false;
		$dripped_type = $this->get_dripped_type();

		if ( ! empty( $id )
			&& ! empty( $this->dripped[ $dripped_type ][ $id ] )
		) {
			$has_dripped = true;
		}

		return apply_filters(
			'ms_model_rule_has_dripped_rules',
			$has_dripped,
			$this
		);
	}

	/**
	 * Verify access to dripped content.
	 *
	 * The MS_Helper_Period::current_date may be simulating a date.
	 *
	 * @since 1.0.0
	 * @param string $start_date The start date of the member membership.
	 * @param string $id The content id to verify dripped acccess.
	 *
	 * @return bool $has_dripped_access
	 */
	public function has_dripped_access( $start_date, $id ) {
		$has_dripped_access = false;

		$avail_date = $this->get_dripped_avail_date( $id, $start_date );
		$now = MS_Helper_Period::current_date();
		if ( strtotime( $now ) >= strtotime( $avail_date ) ) {
			$has_dripped_access = true;
		}

		$has_access = $this->has_access( $id );
		// Result is a logic AND between dripped and has access.
		$has_dripped_access = $has_dripped_access && $has_access;

		return apply_filters(
			'ms_model_rule_has_dripped_access',
			$has_dripped_access,
			$this
		);
	}

	/**
	 * Get dripped value.
	 *
	 * Handler for dripped data content.
	 * Set default values if not present.
	 *
	 * @since 1.0.0
	 * @param string $dripped_type The dripped type.
	 * @param $id The content id to verify dripped access.
	 * @param $field The field to get from dripped type data.
	 *
	 * @return bool $value
	 */
	public function get_dripped_value( $dripped_type, $id, $field ) {
		$value = null;

		if ( self::is_valid_dripped_type( $dripped_type ) ) {
			if ( isset( $this->dripped[ $dripped_type ][ $id ][ $field ] ) ) {
				$value = $this->dripped[ $dripped_type ][ $id ][ $field ];
			} else {
				switch ( $field ) {
					case 'period_unit':
						$value = $this->validate_period_unit( $value, 0 );
						break;

					case 'period_type':
						$value = $this->validate_period_type( $value );
						break;

					case 'spec_date':
						$value = MS_Helper_Period::current_date();
						break;
				}
			}
		}

		return apply_filters(
			'ms_model_rule_get_dripped_value',
			$value,
			$this
		);
	}

	/**
	 * Set dripped value.
	 *
	 * Handler for setting dripped data content.
	 *
	 * @since 1.0.0
	 * @param string $dripped_type The dripped type.
	 * @param $id The content id to set dripped access.
	 * @param $field The field to set in dripped type data.
	 * @param $value The value to set for $field.
	 */
	public function set_dripped_value( $dripped_type, $id, $field = 'spec_date', $value ) {
		if ( self::is_valid_dripped_type( $dripped_type ) ) {
			$this->dripped[ $dripped_type ][ $id ][ $field ] = apply_filters(
				'ms_model_rule_set_dripped_value',
				$value,
				$dripped_type,
				$id,
				$field
			);

			$this->dripped['dripped_type'] = $dripped_type;
			$this->dripped['modified'] = MS_Helper_Period::current_date( null, false );

			if ( self::DRIPPED_TYPE_FROM_TODAY == $dripped_type ) {
				$this->dripped[ $dripped_type ][ $id ]['avail_date'] = $this->get_dripped_avail_date( $id );
			}
		}

		do_action(
			'ms_model_rule_set_dripped_value_after',
			$dripped_type,
			$id,
			$field,
			$value,
			$this
		);
	}

	/**
	 * Get dripped content available date.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to verify dripped access.
	 * @param string $start_date The start date of the member membership.
	 */
	public function get_dripped_avail_date( $id, $start_date = null ) {
		$avail_date = MS_Helper_Period::current_date();

		$dripped_type = $this->get_dripped_type();

		switch ( $dripped_type ) {
			case self::DRIPPED_TYPE_SPEC_DATE:
				$avail_date = $this->get_dripped_value(
					$dripped_type,
					$id,
					'spec_date'
				);
				break;

			case self::DRIPPED_TYPE_FROM_TODAY:
				if ( ! empty( $this->dripped['modified'] ) ) {
					$modified = $this->dripped['modified'];
				} else {
					$modified = MS_Helper_Period::current_date( null, false );
				}

				$period_unit = $this->get_dripped_value( $dripped_type, $id, 'period_unit' );
				$period_type = $this->get_dripped_value( $dripped_type, $id, 'period_type' );
				$avail_date = MS_Helper_Period::add_interval(
					$period_unit,
					$period_type,
					$modified
				);
				break;

			case self::DRIPPED_TYPE_FROM_REGISTRATION:
				if ( empty( $start_date ) ) {
					$start_date = MS_Helper_Period::current_date( null, false );
				}

				$period_unit = $this->get_dripped_value( $dripped_type, $id, 'period_unit' );
				$period_type = $this->get_dripped_value( $dripped_type, $id, 'period_type' );
				$avail_date = MS_Helper_Period::add_interval( $period_unit, $period_type, $start_date );
				break;

		}

		return apply_filters(
			'ms_model_rule_get_dripped_avail_date',
			$avail_date,
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

		return apply_filters( 'ms_model_rule_count_item_access', $count );
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
		$contents = array();

		return apply_filters(
			'ms_model_rule_get_contents',
			$contents,
			$args,
			$this
		);
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
		$count = 0;

		return apply_filters(
			'ms_model_rule_get_contents',
			$count,
			$args,
			$this
		);
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
			'ms_model_rule_reset_rule_values',
			array(),
			$this
		);
	}

   /**
	* Merge rule values.
	*
	* @since 1.0.0
	* @param MS_Model_Rule $src_rule The source rule model to merge rules to.
	*/
	public function merge_rule_values( $src_rule, $src_is_base ) {
		if ( $src_rule->rule_type != $this->rule_type ) { return; }

		$rule_value = $this->rule_value;
		if ( ! is_array( $this->rule_value ) ) {
			$rule_value = array();
		}

		$src_rule_value = $src_rule->rule_value;
		if ( ! is_array( $src_rule_value ) ) {
			$src_rule_value = array();
		}

		if ( $src_is_base ) {
			/*
			 * Intersect to preserve only protected rules overrides;
			 * Merge preserving keys;
			 */
			$this->rule_value = array_intersect_key(
				$rule_value,
				$src_rule_value
			);
		} else {
			/*
			 * Add the rule values to the current rule-set;
			 */
			foreach ( $src_rule_value as $id => $access ) {
				if ( ! $access ) {
					unset( $src_rule_value[ $id ] );
				}
			}
			$this->rule_value += $src_rule_value;
		}

		do_action( 'ms_model_rule_merge_rule_values', $src_rule, $this );
	}

	/**
	 * Set access status to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to set access to.
	 * @param int $access The access status to set.
	 */
	public function set_access( $id, $access ) {
		if ( is_bool( $access ) ) {
			if ( $access ) {
				$access = self::RULE_VALUE_HAS_ACCESS;
			} else {
				$access = self::RULE_VALUE_NO_ACCESS;
			}
		}

		if ( $access == self::RULE_VALUE_NO_ACCESS ) {
			unset( $this->rule_value[ $id ] );
		} else {
			$this->rule_value[ $id ] = $access;
		}

		do_action( 'ms_model_rule_set_access', $id, $access, $this );
	}

	/**
	 * Give access to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to give access.
	 */
	public function give_access( $id ) {
		$this->set_access( $id, self::RULE_VALUE_HAS_ACCESS );

		do_action( 'ms_model_rule_give_access', $id, $this );
	}

	/**
	 * Remove access to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to remove access.
	 */
	public function remove_access( $id ) {
		$this->set_access( $id, self::RULE_VALUE_NO_ACCESS );

		do_action( 'ms_model_rule_remove_access', $id, $this );
	}

	/**
	 * Toggle access to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to toggle access.
	 */
	public function toggle_access( $id ) {
		$has_access = ! $this->get_rule_value( $id );
		$this->set_access( $id, $has_access );

		do_action( 'ms_model_rule_toggle_access', $id, $this );
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
			'ms_model_rule_' . $this->id . '_get_query_args',
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
			'ms_model_rule_' . $this->id . '_validate_query_args',
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
				case self::FILTER_HAS_ACCESS:
					if ( ! $content->access ) {
						unset( $contents[ $key ] );
					}
					break;

				case MS_Model_Rule::FILTER_NOT_PROTECTED:
				case self::FILTER_NO_ACCESS:
					if ( $content->access ) {
						unset( $contents[ $key ] );
					}
					break;

				case self::FILTER_DRIPPED:
					if ( empty( $content->delayed_period ) ) {
						unset( $contents[ $key ] );
					}
					break;
			}
		}

		return apply_filters(
			'ms_model_rule_filter_content',
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

		return apply_filters( 'ms_model_rule_get_membership', $membership );
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
				$this->rule_value = WDev()->get_array( $this->rule_value );
				$value = $this->rule_value;
				break;

			default:
				if ( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}

		return apply_filters(
			'ms_model_rule__get',
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
					if ( in_array( $value, self::get_rule_types() ) ) {
						$this->$property = $value;
					}
					break;

				case 'dripped':
					if ( is_array( $value ) ) {
						foreach ( $value as $key => $period ) {
							$value[ $key ] = $this->validate_period( $period );
						}
						$this->$property = $value;
					}
					break;

				default:
					$this->$property = $value;
					break;
			}
		}

		do_action( 'ms_model_rule__set_after', $property, $value, $this );
	}
}