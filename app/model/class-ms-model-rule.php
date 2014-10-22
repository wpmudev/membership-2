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
	const RULE_TYPE_COMMENT = 'comment';
	const RULE_TYPE_MEDIA = 'media';
	const RULE_TYPE_MENU = 'menu';
	const RULE_TYPE_PAGE = 'page';
	const RULE_TYPE_POST = 'post';
	const RULE_TYPE_MORE_TAG = 'more_tag';
	const RULE_TYPE_CUSTOM_POST_TYPE = 'cpt';
	const RULE_TYPE_CUSTOM_POST_TYPE_GROUP = 'cpt_group';
	const RULE_TYPE_SHORTCODE = 'shortcode';
	const RULE_TYPE_URL_GROUP = 'url_group';
	
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
	 * ID of the model object.
	 *
	 * Saved as WP post ID.
	 *
	 * @since 1.0.0
	 * @var int $id
	 */
	protected $id;
	
	/**
	 * Membership ID.
	 *
	 * @since 1.0.0
	 * @var int $membership_id
	 */
	protected $membership_id = 0;
	
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
	 * 		@type int $item_id The protecting item ID.
	 * 		@type int $value The rule value. 0: no access; 1: has access.
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
	 * @var array{
	 * 		@type string $dripped_type The selected dripped type.
	 * 		@type array $rule_value {
	 *		 	@type int $item_id The protecting item ID.
	 * 			@type int $dripped_data The dripped data like period or release date.
	 * 		}
	 * }
	 * 
	 */
	protected $dripped = array();
	
	/**
	 * Default rule value if no rules are set.
	 *
	 * @since 1.0.0
	 * @var int $rule_value_default The default value. Default 1 (has access).
	 */
	protected $rule_value_default = 1;
	
	/**
	 * Rule value invert.
	 * 
	 * Invert the access rules. Eg. if has access => no access.
	 *
	 * @since 1.0.0
	 * @var bool $rule_value_invert True if the rule values are inverted.
	 */
	protected $rule_value_invert = false;

	/**
	 * Class constructor.
	 * 
	 * @since 1.0.0
	 * @var int $membership_id The membership that owns this rule object.
	 */
	public function __construct( $membership_id ) {

		parent::__construct();
		
		 $this->membership_id = apply_filters( 'ms_model_rule_contructor_membership_id', $membership_id, $this );
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
	 * 		@type in $priority The rule type priority in the execution sequence.
	 * 		@type string $rule_type The rule type.
	 * }
	 */
	public static function get_rule_types() {
		$rule_types =  array(
				-10 => self::RULE_TYPE_URL_GROUP,
				0 => self::RULE_TYPE_POST,
				10 => self::RULE_TYPE_CATEGORY,
				20 => self::RULE_TYPE_CUSTOM_POST_TYPE,
				30 => self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
				40 => self::RULE_TYPE_PAGE,
				50 => self::RULE_TYPE_MORE_TAG,
				60 => self::RULE_TYPE_MENU,
				70 => self::RULE_TYPE_SHORTCODE,
				80 => self::RULE_TYPE_COMMENT,
				90 => self::RULE_TYPE_MEDIA,
		);
	
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			unset( $rule_types[10] );
		}
		else {
			unset( $rule_types[0] );
		}
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			unset( $rule_types[30] );
		}
		else {
			unset( $rule_types[20] );
		}
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
			unset( $rule_types[90] );
		}
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SHORTCODE ) ) {
			unset( $rule_types[70] );
		}
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			unset( $rule_types[-10] );
		}
		
		$rule_types = apply_filters( 'ms_model_rule_get_rule_types', $rule_types );
		$rule_type = ksort( $rule_types );

		return apply_filters( 'ms_model_rule_get_rule_types', $rule_types );
	}
	
	/**
	 * Rule types and respective classes.
	 *
	 * @since 1.0.0
	 * @return array {
	 * 		@type string $rule_type The rule type constant.
	 * 		@type string $class_name The rule type class.
	 * }
	 */
	public static function get_rule_type_classes() {
		
		$classes = array(
				self::RULE_TYPE_POST => 'MS_Model_Rule_Post',
				self::RULE_TYPE_CATEGORY => 'MS_Model_Rule_Category',
				self::RULE_TYPE_CUSTOM_POST_TYPE => 'MS_Model_Rule_Custom_Post_Type',
				self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP => 'MS_Model_Rule_Custom_Post_Type_Group',
				self::RULE_TYPE_PAGE => 'MS_Model_Rule_Page',
				self::RULE_TYPE_MORE_TAG => 'MS_Model_Rule_More',
				self::RULE_TYPE_MENU => 'MS_Model_Rule_Menu',
				self::RULE_TYPE_SHORTCODE => 'MS_Model_Rule_Shortcode',
				self::RULE_TYPE_COMMENT => 'MS_Model_Rule_Comment',
				self::RULE_TYPE_MEDIA => 'MS_Model_Rule_Media',
				self::RULE_TYPE_URL_GROUP => 'MS_Model_Rule_Url_Group',
		);
		
		return apply_filters( 'ms_model_rule_get_rule_type_classes', $classes );
	}
	
	/**
	 * Rule types and respective titles.
	 *
	 * @since 1.0.0
	 * @return array {
	 * 		@type string $rule_type The rule type constant.
	 * 		@type string $rule_title The rule title.
	 * }
	 */
	public static function get_rule_type_titles() {
		
		$titles = array(
				self::RULE_TYPE_CATEGORY => __( 'Category' , MS_TEXT_DOMAIN ),
				self::RULE_TYPE_COMMENT => __( 'Comment', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_MEDIA => __( 'Media', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_MENU => __( 'Menu', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_PAGE => __( 'Page', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_MORE_TAG => __( 'More Tag', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_POST => __( 'Post', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_SHORTCODE => __( 'Shortcode', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_URL_GROUP => __( 'Url Group', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_CUSTOM_POST_TYPE => __( 'Custom Post Type', MS_TEXT_DOMAIN ),
				self::RULE_TYPE_CUSTOM_POST_TYPE_GROUP => __( 'CPT Group', MS_TEXT_DOMAIN ),
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
				MS_Model_Rule::RULE_TYPE_POST
		);
		
		return apply_filters( 'ms_model_rule_get_dripped_rule_types', $dripped );
	}
	
	/**
	 * Get dripped types. 
	 *
	 * @todo Remove or develop DRIPPED_TYPE_FROM_TODAY
	 * 
	 * @since 1.0.0
	 * @return array {
	 * 		@type string $dripped_type The dripped type constant.
	 * 		@type string $dripped_type_desc The dripped type description.
	 * }
	 */
	public static function get_dripped_types() {
		
		$dripped_types = array(
				self::DRIPPED_TYPE_SPEC_DATE => __( "Reveal Dripped Content on specific dates", MS_TEXT_DOMAIN ),
// 				self::DRIPPED_TYPE_FROM_TODAY => __( "Reveal Dripped Content 'X' days from today", MS_TEXT_DOMAIN ),
				self::DRIPPED_TYPE_FROM_REGISTRATION => __( "Reveal Dripped Content 'X' days from user registration", MS_TEXT_DOMAIN ),
		);
		
		return apply_filters( 'ms_model_rule_get_dripped_types', $dripped_types );
	}
	
	/**
	 * Validate dripped type.
	 *
	 * @since 1.0.0
	 * @param string $rule_type The rule type to validate.
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
		
		if( self::is_valid_rule_type( $rule_type ) ) {
			
			$rule_types = self::get_rule_type_classes();
			$class = $rule_types[ $rule_type ];
			$rule = new $class( $membership_id );
			
			return apply_filters( 'ms_model_rule_rule_factory', $rule, $rule_type, $membership_id );
		}
		else {
			throw new Exception( "Rule factory - rule type not found: $rule_type"  );
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
	 * Set initial protection.
	 * 
	 * To be overridden by children classes.
	 * 
	 * @since 1.0.0
	 * @param MS_Model_Membership_Relationship The membership relationship to protect content from.
	 */
	public function protect_content( $ms_relationship = false ) {
		
		do_action( 'ms_model_rule_protect_content', $ms_relationship, $this );
		
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
		
		if( $has_access_only ) {
			foreach( $this->rule_value as $val ) {
				if( $val ) {
					$count++;
				}
			}
		}
		else {
			$count = count( $this->rule_value );
		}
		
		return apply_filters( 'ms_model_rule_count_rules', $count, $has_access_only, $this );
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
		
		return apply_filters( 'ms_model_rule_has_rule', $has_rule, $id, $this );	
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
		
		$value = isset( $this->rule_value[ $id ] ) ? $this->rule_value[ $id ] : $this->rule_value_default;
		
		return apply_filters( 'ms_model_rule_get_rule_value', $value, $id, $this );
		
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
		
		if( ! empty( $id ) ) {
 			$has_access = $this->get_rule_value( $id );
		}
		
		if( $this->rule_value_invert ) {
			$has_access = ! $has_access;
		}
		
		return apply_filters( 'ms_model_rule_has_access', $has_access, $id, $this );
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
		
		if( ! empty( $this->dripped['dripped_type'] ) ) {
			$dripped_type = $this->dripped['dripped_type'];
		}
		
		return apply_filters( 'ms_model_rule_get_dripped_type', $dripped_type, $this );
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
		
		if( ! empty( $id ) && ! empty( $this->dripped[ $dripped_type ][ $id ] ) ) {
			$has_dripped = true;
		}

		return apply_filters( 'ms_model_rule_has_dripped_rules', $has_dripped, $this );
	}
		
	/**
	 * Verify access to dripped content.
	 * 
	 * The MS_Helper_Period::current_date may be simulating a date.
	 * 
	 * @since 1.0.0
	 * @param string $start_date The start date of the member membership.
	 * @param string $id The content id to verify dripped acccess. 
	 */
	public function has_dripped_access( $start_date, $id ) {
		
		$has_dripped_access = false;
		
		$avail_date = $this->get_dripped_avail_date( $id, $start_date );
		$now = MS_Helper_Period::current_date();
		if( strtotime( $now ) >= strtotime( $avail_date ) ) {
			$has_dripped_access = true;
		}
		
		$has_access = $this->has_access( $id );
		/* Result is a logic AND between dripped and has access */
		$has_dripped_access = $has_dripped_access && $has_access;
		
		return apply_filters( 'ms_model_rule_has_dripped_access', $has_dripped_access, $this );
	}
	
	/**
	 * Get dripped value.
	 *
	 * Handler for dripped data content.
	 * Set default values if not present. 
	 *
	 * @since 1.0.0
	 * @param string $dripped_type The dripped type.
	 * @param $id The content id to verify dripped acccess.
	 * @param $field The field to get from dripped type data.
	 */
	public function get_dripped_value( $dripped_type, $id, $field ) {
		$value = null;
		
		if( self::is_valid_dripped_type( $dripped_type ) ) {
			if( isset( $this->dripped[ $dripped_type ][ $id ][ $field ] ) ) {
				$value = $this->dripped[ $dripped_type ][ $id ][ $field ];
			}
			else {
				switch( $field ) {
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
		
		return apply_filters( 'ms_model_rule_get_dripped_value', $value, $this );
	}
	
	/**
	 * Set dripped value.
	 *
	 * Handler for setting dripped data content.
	 *
	 * @since 1.0.0
	 * @param string $dripped_type The dripped type.
	 * @param $id The content id to set dripped acccess.
	 * @param $field The field to set in dripped type data.
	 * @param $value The value to set for $field.
	 */
	public function set_dripped_value( $dripped_type, $id, $field = 'spec_date', $value ) {
		
		if( self::is_valid_dripped_type( $dripped_type ) ) {
			$this->dripped[ $dripped_type ][ $id ][ $field ] = apply_filters( 'ms_model_rule_set_dripped_value', $value, $dripped_type, $id, $field );
			$this->dripped['dripped_type'] = $dripped_type;
			$this->dripped['modified'] = MS_Helper_Period::current_date( null, false );

			if( self::DRIPPED_TYPE_FROM_TODAY == $dripped_type ) {
				$this->dripped[ $dripped_type ][ $id ]['avail_date'] = $this->get_dripped_avail_date( $id );
			}
		}
		
		do_action( 'ms_model_rule_set_dripped_value_after', $dripped_type, $id, $field, $value, $this );
	}
	
	/**
	 * Get dripped content available date.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to verify dripped acccess.
	 * @param string $start_date The start date of the member membership.
	 */
	public function get_dripped_avail_date( $id, $start_date = null ) {
		
		$avail_date = MS_Helper_Period::current_date();
		
		$dripped_type = $this->get_dripped_type();

		switch( $dripped_type ) {
			case self::DRIPPED_TYPE_SPEC_DATE:
				$avail_date = $this->get_dripped_value( $dripped_type, $id, 'spec_date' );
				break;
			case self::DRIPPED_TYPE_FROM_TODAY:
				$modified = ! empty( $this->dripped['modified'] ) ? $this->dripped['modified'] : MS_Helper_Period::current_date( null, false );
				$period_unit = $this->get_dripped_value( $dripped_type, $id, 'period_unit' );
				$period_type = $this->get_dripped_value( $dripped_type, $id, 'period_type' );
				$avail_date = MS_Helper_Period::add_interval( $period_unit, $period_type, $modified );
				break;
			case self::DRIPPED_TYPE_FROM_REGISTRATION:
				if( empty( $start_date ) ) {
					$start_date = MS_Helper_Period::current_date( null, false ); 
				}
				$period_unit = $this->get_dripped_value( $dripped_type, $id, 'period_unit' );
				$period_type = $this->get_dripped_value( $dripped_type, $id, 'period_type' );
				$avail_date = MS_Helper_Period::add_interval( $period_unit, $period_type, $start_date );
				break;
						
		}	
		
		return apply_filters( 'ms_model_rule_get_dripped_avail_date', $avail_date, $this );
	}
	
	/**
	 * Count item protected content summary.
	 *
	 * @since 1.0.0
	 * @param $args The query post args
	 * 				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array {
	 * 		@type int $total The total content count.
	 * 		@type int $accessible The has access content count.
	 * 		@type int $restricted The protected content count.
	 * } 
	 */
	public function count_item_access( $args = null ) {
		
		if( $this->rule_value_invert ) {
			$args['default'] = 1;
		}
		
		$total = $this->get_content_count( $args );
		$contents = $this->get_contents( $args );
		$count_accessible = 0;
		$count_restricted = 0;
		if( ! is_array( $this->rule_value ) ) {
			$this->rule_value = array();
		}
		foreach( $contents as $id => $content ) {
			if( $content->access ) {
				$count_accessible++;
			}
			else {
				$count_restricted++;
			}
		}
		
		if( $this->rule_value_default ) {
			$count_accessible = $total - $count_restricted;
		}
		else {
			$count_restricted = $total - $count_accessible;
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
	 * 				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array The contents array. 
	 */
	public function get_contents( $args = null ) {
		
		$contents = array();
		
		return apply_filters( 'ms_model_rule_get_contents', $contents, $args, $this );
	}
	
	/**
	 * Get content count.
	 * 
	 * To be overridden in children classes.
	 * 
	 * @since 1.0.0
	 * @param $args The query post args
	 * 				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return int The content count. 
	 */
	public function get_content_count( $args = null ) {
		
		$count = 0;
		
		return apply_filters( 'ms_model_rule_get_contents', $count, $args, $this );
	}
	
   /**
	* Reset the rule value data.
	*
	* @since 1.0.0
	* @param $args The query post args
	* 				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	* @return int The content count.
	*/
	public function reset_rule_values() {
		
		$this->rule_value = apply_filters( 'ms_model_rule_reset_rule_values', array(), $this );
	}
	
   /**
	* Merge rule values.
	*
	* @since 1.0.0
	* @param MS_Model_Rule $src_rule The source rule model to merge rules to. 
	*/
	public function merge_rule_values( $src_rule ) {
		
		if( $src_rule->rule_type == $this->rule_type ) {
			$rule_value = $this->rule_value;
			if( ! is_array( $this->rule_value ) ) {
				$rule_value = array();
			}
			$src_rule_value = $src_rule->rule_value;
			if( ! is_array( $src_rule->rule_value ) ) {
				$src_rule_value = array();
			}
			foreach( $src_rule_value as $id => $value ) {
				if( ! $value ) {
					unset( $src_rule_value[ $id ] );
				}
			}
			/*
			 * Intersect to preserve only protected rules overrides;
			 * Merge preserving keys;
			 */
			$this->rule_value = array_intersect_key( $rule_value,  $src_rule_value) + $src_rule_value;
		}
		
		do_action( 'ms_model_rule_merge_rule_values', $src_rule, $this );
	}
	
	/**
	 * Set access status to content.
	 *
	 * @since 1.0.0
	 * @param string $id The content id to set access to.
	 * @param int $has_access The access status to set. 
	 */
	public function set_access( $id, $access ) {
		
		if( is_bool( $access ) ) {
			$access = $access ? self::RULE_VALUE_HAS_ACCESS : self::RULE_VALUE_NO_ACCESS;
		}
		
		$this->rule_value[ $id ] = $access;
		
		if( $this->rule_value_invert && $access == self::RULE_VALUE_NO_ACCESS ) {
			unset( $this->rule_value[ $id ] );
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
	 * 				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return array $args The parsed args.
	 */
	public function get_query_args( $args = null ) {
	
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'ID',
				'order'       => 'DESC',
				'post_status' => 'publish',
		);
		$args = wp_parse_args( $args, $defaults );
	
		/* If not visitor membership, just show protected content */
		if( ! $this->rule_value_invert ) {
			$args['post__in'] = array_keys( $this->rule_value );
		}
			
		$args = $this->validate_query_args( $args );
		
		return apply_filters( "ms_model_rule_{$this->id}_get_query_args", $args );
	}
	
	/**
	 * Validate wp query args.
	 *
	 * Avoid post__in and post__not_in conflicts.
	 *
	 * @since 1.0.0
	 * @param mixed $args The query post args
	 * 				@see @link http://codex.wordpress.org/Class_Reference/WP_Query
	 * @return mixed $args The validated args.
	 */
	public function validate_query_args( $args ) {
		
		/** Cannot use post__in and post_not_in at the same time.*/
		if( ! empty( $args['post__in'] ) && ! empty( $args['post__not_in'] ) ) {
			$include = $args['post__in'];
			$exclude = $args['post__not_in'];
			foreach( $exclude as $id ) {
				$key = array_search( $id, $include );
				unset( $include[ $key ] );
			}
			unset( $args['post__not_in'] );
		}
		if( ! empty( $args['show_all'] ) || ! empty( $args['category__in'] ) ) {
			unset( $args['post__in'] );
			unset( $args['post__not_in'] );
			unset( $args['show_all'] );
		}
		
		if( isset( $args['post__in'] ) && count( $args['post__in'] ) == 0 ) {
			$args['post__in'] = array( -1 );
		}
		
		return apply_filters( "ms_model_rule_{$this->id}_validate_query_args", $args );
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
		
		foreach( $contents as $key => $content ) {
			if( !empty( $content->ignore ) ) {
				continue;
			}
			switch( $status ) {
				case MS_Model_Rule::FILTER_PROTECTED:
				case self::FILTER_HAS_ACCESS:
					if( ! $content->access ) {
						unset( $contents[ $key ] );
					}
					break;
				case MS_Model_Rule::FILTER_NOT_PROTECTED:
				case self::FILTER_NO_ACCESS:
					if( $content->access ) {
						unset( $contents[ $key ] );
					}
					break;
				case self::FILTER_DRIPPED:
					if( empty( $content->delayed_period ) ) {
						unset( $contents[ $key ] );
					}
					break;
			}
		}
		
		return apply_filters( 'ms_model_rule_filter_content', $contents, $status, $this );
	}
	
	/**
	 * Returns Membership object.
	 *
	 * @since 1.0.0
	 * @return MS_Model_Membership The membership object.
	 */
	public function get_membership() {
		
		$membership = MS_Factory::load( 'MS_Model_Membership', $this->membership_id );
		
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
		switch( $property ) {
			case 'rule_value':
				if( ! is_array( $this->rule_value ) ) {
					$this->rule_value = array();
				}
				$value = $this->rule_value;
				break;
			default:
				if( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}
	
		return apply_filters( 'ms_model_rule__get', $value, $property, $this );
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
			switch( $property ) {
				case 'rule_type':
					if( in_array( $value, self::get_rule_types() ) ) {
						$this->$property = $value;
					}
					break;
				case 'dripped':
					if( is_array( $value ) ) {
						foreach( $value as $key => $period ) {
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