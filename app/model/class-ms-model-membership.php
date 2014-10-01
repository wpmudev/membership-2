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
 * Membership model class.
 * 
 */
class MS_Model_Membership extends MS_Model_Custom_Post_Type {

	protected static $CLASS_NAME = __CLASS__;
	
	public static $POST_TYPE = 'ms_membership';
	
	public $post_type = 'ms_membership';
	
	const TYPE_SIMPLE = 'simple';
	const TYPE_CONTENT_TYPE = 'content_type';
	const TYPE_TIER = 'tier';
	const TYPE_DRIPPED = 'dripped';
	
	const PAYMENT_TYPE_PERMANENT = 'permanent';
	const PAYMENT_TYPE_FINITE = 'finite';
	const PAYMENT_TYPE_DATE_RANGE = 'date-range';
	const PAYMENT_TYPE_RECURRING = 'recurring';
	
	/**
	 * ID of the model object.
	 *
	 * @since 4.0.0
	 */
	protected $id;
	
	/**
	 * Model name.
	 *
	 * @since 4.0.0
	 */
	protected $name;
		
	protected $type;
	
	protected $payment_type;
	
	protected $parent_id = 0;
	
	/**
	 * @deprecated  
	 * @var unknown
	 */
	protected $linked_membership_ids;
	
	protected $active = false;
	
	protected $private = true;
	
	protected $visitor_membership = false;
	
	protected $is_free = false;
	
	protected $price = 0;
	
	protected $period;
		
	protected $pay_cycle_period;
		
	protected $period_date_start;
	
	protected $period_date_end;
		
	protected $trial_period_enabled;
	
	protected $trial_price = 0;
	
	protected $trial_period;

	protected $dripped_type;
	
	protected $on_end_membership_id;
	
	protected $is_setup_completed;
	
	protected $rules = array();
	
	public function before_save() {
		foreach( $this->rules as $rule ) {
			$rule->membership_id = $this->id;
		}
	}
	
	public function after_load() {
		/** validate rules using protected content rules */
		if( ! $this->visitor_membership ) {
			$this->merge_protected_content_rules();
		}
	}
	
	public static function get_types() {
		return apply_filters( 'ms_model_membership_get_types', array(
				self::TYPE_SIMPLE => __( 'Simple', MS_TEXT_DOMAIN ),
				self::TYPE_CONTENT_TYPE => __( 'Multiple Content Types', MS_TEXT_DOMAIN ),
				self::TYPE_TIER => __( 'Tier Based', MS_TEXT_DOMAIN ),
				self::TYPE_DRIPPED => __( 'Dripped Content', MS_TEXT_DOMAIN ),
		) );
	}

	public static function is_valid_type( $type ) {
		return apply_filters( 'ms_model_membership_is_valid_type', array_key_exists( $type, self::get_types() ) );
	}
	
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
		
		return apply_filters( 'ms_model_membership_get_type_description', $description );
	}
	
	public static function get_payment_types() {
		return apply_filters( 'ms_model_membership_get_payment_types', array(
				self::PAYMENT_TYPE_PERMANENT => __( 'Single payment for permanent access', MS_TEXT_DOMAIN ),
				self::PAYMENT_TYPE_FINITE => __( 'Single payment for finite access', MS_TEXT_DOMAIN ),
				self::PAYMENT_TYPE_DATE_RANGE => __( 'Single payment for date range access', MS_TEXT_DOMAIN ),
				self::PAYMENT_TYPE_RECURRING => __( 'Recurring payment', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function get_payment_type_desc() {
		$desc = __( 'N/A', MS_TEXT_DOMAIN );
		switch( $this->payment_type ) {
			case self::PAYMENT_TYPE_PERMANENT:
				$desc = __( 'Single payment', MS_TEXT_DOMAIN );
				break;
			case self::PAYMENT_TYPE_FINITE:
				$desc = sprintf( __( 'For %s', MS_TEXT_DOMAIN ),  MS_Helper_Period::get_period_desc( $this->period ) );
				break;
			case self::PAYMENT_TYPE_DATE_RANGE:
				$desc = sprintf( __( 'From %s to %s', MS_TEXT_DOMAIN ),  $this->period_date_start, $this->period_date_end );
				break;
			case self::PAYMENT_TYPE_RECURRING:
				$desc = sprintf( __( 'Each %s', MS_TEXT_DOMAIN ),  MS_Helper_Period::get_period_desc( $this->pay_cycle_period ) );
				break;
					
		}
		return apply_filters( 'ms_model_membership_get_payment_type_desc', $desc );	
	}
	
	public function has_parent() {
		$has_parent = false;
		
		if( $this->parent_id > 0 ) {
			$has_parent = true;
		}
		
		return apply_filters( 'ms_model_membership_has_parent', $has_parent );
	}
	public function get_parent() {
		$parent = null;
		if( $this->has_parent() ) {
			$parent = MS_Factory::load( 'MS_Model_Membership', $this->parent_id );
		}
		return apply_filters( 'ms_model_membership_get_parent', $parent );
	}
	
	public function can_have_children() {
		$can_have_children = false;
		
		$can_have_children_types = array( self::TYPE_CONTENT_TYPE, self::TYPE_TIER );
		if( ! $this->has_parent() && in_array( $this->type, $can_have_children_types ) ) {
			$can_have_children = true;
		}
		
		return apply_filters( 'ms_model_membership_can_have_children', $can_have_children, $this->type );
	}
	
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
			$child->save();
		}
		
		return apply_filters( 'ms_model_membership_create_child', $child );
	}
	
	public function get_children( $args = null ) {
		$children = array();
		
		if( ! $this->has_parent() ) {
			$args['meta_query']['children'] = array(
					'key'     => 'parent_id',
					'value'   => $this->id,
			);
				
			$children = self::get_memberships( $args );
		}
		
		return apply_filters( 'ms_model_membership_get_children', $children );
	}
	
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
	
		return apply_filters( 'ms_model_membership_get_last_descendant', $last );
	}
	
	public function get_children_count() {
		$children = $this->get_children();
		$count = count( $children );
		
		return apply_filters( 'ms_model_membership_get_children_count', $count, $this );
	}
	
	public function is_private() {
		$private = false;
	
		if( $this->is_private_eligible() && $this->private ) {
			$private = true;
		}
		
		return apply_filters( 'ms_model_membership_is_private', $private );
	}
	
	public function is_private_eligible() {
		$is_private_eligible = false;
		
		if( in_array( $this->type, self::get_private_eligible_types() ) ) {
			$is_private_eligible = true;
		}
		return apply_filters( 'ms_model_membership_is_private_eligible', $is_private_eligible );
	}
	
	public static function get_private_eligible_types() {
		/** Private memberships can only be enabled in these types */
		$private_eligible_types = array(
				self::TYPE_SIMPLE,
				self::TYPE_CONTENT_TYPE,
		);
		
		return apply_filters( 'ms_model_membership_get_private_eligible_types', $private_eligible_types );
	}
		
	public function get_rule( $rule_type ) {
		if( isset( $this->rules[ $rule_type ] ) ) {
			if( $this->visitor_membership ) {
				$this->rules[ $rule_type ]->rule_value_invert = true;
				$this->rules[ $rule_type ]->rule_value_default = false;
			}
			return $this->rules[ $rule_type ];
		}
		elseif( 'attachment' == $rule_type && isset( $this->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ] ) ) {
			return $this->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ];
		}
		else {
			$this->rules[ $rule_type ] = MS_Model_Rule::rule_factory( $rule_type, $this->id );
			if( $this->visitor_membership ) {
				$this->rules[ $rule_type ]->rule_value_invert = true;
				$this->rules[ $rule_type ]->rule_value_default = false;
			}
			return $this->rules[ $rule_type ];
		}
	}
		
	public function set_rule( $rule_type, $rule ) {
		if( MS_Model_Rule::is_valid_rule_type( $rule_type) ) {
			$this->rules[ $rule_type ] = $rule;
		}
	}
	
	public static function get_membership_count( $args = null ) {
		$args = self::get_query_args( $args );
		
		$query = new WP_Query($args);
		return $query->found_posts;
		
	}
	
	public static function get_memberships( $args = null ) {
		$args = self::get_query_args( $args );

		$query = new WP_Query( $args );
		$items = $query->get_posts();
		
		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[] = MS_Factory::load( 'MS_Model_Membership', $item->ID );	
		}
		return $memberships;
	}
	
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
		 
		return apply_filters( 'ms_model_membership_get_grouped_memberships', $memberships );
	}
	
	public static function get_query_args( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'order' => 'DESC',
				'orderby' => 'ID',
				'post_status' => 'any',
				'post_per_page' => -1,
		);
		$args = wp_parse_args( $args, $defaults );

		if( empty( $args['include_visitor'] ) ){
			$args['meta_query']['visitor'] = array(
				'key'     => 'visitor_membership',
				'value'   => '',
			); 
		}
		
		return apply_filters( 'ms_model_membership_get_query_args', $args );
		
	}
	
	public function get_after_ms_ends_options( $args = null ) {
		$options = array(
				self::get_visitor_membership()->id => __( 'Restrict access to Protected Content', MS_TEXT_DOMAIN ),
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

		return apply_filters( 'ms_model_membership_get_membership_names', $options );
	}
	
	public static function get_membership_names( $args = null, $hide_default_memberships = false ) {
		$args['order'] = 'ASC';
		$args = self::get_query_args( $args );
		
		$query = new WP_Query( $args );
		$items = $query->get_posts();
		
		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[ $item->ID ] = $item->name;
		}
		if( $hide_default_memberships ) {
			unset( $memberships[ self::get_visitor_membership()->id ] );
		}
		
		return apply_filters( 'ms_model_membership_get_membership_names', $memberships );
	}
	
	public static function is_valid_membership( $membership_id ) {
		return ( MS_Factory::load( 'MS_Model_Membership', $membership_id )->id > 0 );
	}
	
	public static function get_visitor_membership() {
		$args = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
				'meta_query' => array(
						array(
								'key' => 'visitor_membership',
								'value' => '1',
								'compare' => '='
						)
				)
		);
		$query = new WP_Query($args);
		$item = $query->get_posts();

		$visitor_membership = null;
		if( ! empty( $item[0] ) ) {
			$visitor_membership = MS_Factory::load( 'MS_Model_Membership', $item[0]->ID );
		}
		else {
			$description = __( 'Default visitor membership', MS_TEXT_DOMAIN );
			$visitor_membership = MS_Factory::create( 'MS_Model_Membership' );
			$visitor_membership->name = __( 'Visitor', MS_TEXT_DOMAIN );
			$visitor_membership->payment_type = self::PAYMENT_TYPE_PERMANENT;
			$visitor_membership->title = $description;
			$visitor_membership->description = $description;
			$visitor_membership->visitor_membership = true;
			$visitor_membership->active = true;
			$visitor_membership->private = true;
			$visitor_membership->save();
			$visitor_membership = MS_Factory::load( 'MS_Model_Membership', $visitor_membership->id );
		}
		return $visitor_membership;
	}
	
	public function merge_protected_content_rules() {
		$protected_content_rules = self::get_visitor_membership()->rules;
		
		foreach( $protected_content_rules as $rule_type => $protect_rule ) {
			try {
				if( MS_Model_Rule::is_valid_rule_type( $rule_type ) ) {
					$rule = $this->get_rule( $rule_type );
					$rule->merge_rule_values( $protect_rule );
					$this->set_rule( $rule_type, $rule );
				}
			} 
			catch( Exception $e ) {
				MS_Helper_Debug::log( $e );
			}
		}
		$this->rules = apply_filters( 'ms_model_membership_merge_protected_content_rules', $this->rules );
	}
	
	public function get_members_count() {
		return MS_Model_Membership_Relationship::get_membership_relationship_count( array( 'membership_id' => $this->id ) );
	}
	/**
	 * Delete membership.
	 * 
	 * @param $force To force delete memberships with members, visitor or default memberships.
	 */
	public function delete( $force = false ) {
		if( ! empty( $this->id ) ) {
			if( $this->get_members_count() > 0 && ! $force ) {
				throw new Exception("Could not delete membership with members.");
			}
			elseif( $this->visitor_membership && ! $force ) {
				throw new Exception("Visitor membership could not be deleted.");
			}
			wp_delete_post( $this->id );
		}
	}

	/**
	 * Return membership has dripped content.
	 *
	 * Verify post and page rules if there is a dripped content.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @return boolean
	 */
	public function has_dripped_content() {
		$dripped = array( 'post', 'page' );
		foreach( $dripped as $type ) {
			//using count() as !empty() never returned true
			if ( 0 < count( $this->get_rule( $type )->dripped ) ) {
				return true;
			}
		}
		return false;	
	}
	
	/**
	 * Get protection rules sorted.
	 * First one has priority over the last one.
	 * These rules are used to determine access.
	 * @since 4.0.0
	 */
	private function get_rules_hierarchy() {
		$rule_types = MS_Model_Rule::get_rule_types();
		foreach( $rule_types as $rule_type ) {
			$rules[ $rule_type ] = $this->get_rule( $rule_type );
		}
		return apply_filters( 'ms_model_membership_get_rules_hierarchy', $rules );
	}
	
	/**
	 * Mark membership setup as completed.
	 * 
	 * @since 1.0
	 * 
	 * @return bool $marked True in the first time setup is finished.
	 */
	public function mark_setup_completed() {
		$marked = false;
		if( ! $this->is_setup_completed ) {
			$this->is_setup_completed = true;
			$marked = true;
		}
		return apply_filters( 'ms_model_memberhsip_mark_setup_completed', $marked, $this );
	}
	
	/**
	 * Verify access to current page.
	 * 
	 * Verify membership rules hierachyly for content accessed directly.
	 * If 'has access' is found, it does have access.
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @return boolean 
	 */
	public function has_access_to_current_page( $ms_relationship, $post_id = null ) {
		$has_access = false;
//		MS_Helper_Debug::log("------------------------------");
		if( $this->active ) {
			/** If 'has access' is found in the hierarchy, it does have access. */
			$rules = $this->get_rules_hierarchy();
			foreach( $rules as $rule ) {
				/** url groups have final decision */
				if( MS_Model_Rule::RULE_TYPE_URL_GROUP == $rule->rule_type && $rule->has_rule_for_current_url() ) {
					$has_access = $rule->has_access();
					break;
				}
				else {
					$has_access = ( $has_access || $rule->has_access( $post_id ) );
				}
//				MS_Helper_Debug::log("rule: $rule->rule_type, $has_access");
				if( $has_access ) {
					break;
				}
			}
			
			/**
			 * Search for dripped rules.
			 */
			$dripped = MS_Model_Rule::get_dripped_rule_types();
			
			/**
			 * Verify membership dripped rules hierachyly.
			 * Dripped has the final decision.
			 */
			foreach( $dripped as $rule_type ) {
				$rule = $this->get_rule( $rule_type );
				if( $rule->has_dripped_rules( $post_id ) ) {
					$has_access = $rule->has_dripped_access( $ms_relationship->start_date, $post_id, $this->dripped_type );
				}
			}
		}
		
		return apply_filters( 'ms_model_membership_has_access', $has_access, $this );
	}
	
	/**
	 * Set initial protection.
	 * 
	 * Hide restricted content for this membership.
	 * 
	 * @param MS_Model_Membership_Relationship $ms_relationship The membership relationship.
	 * @since 4.0.0
	 */
	public function protect_content( $ms_relationship ) {
		$rules = $this->get_rules_hierarchy();
		/**
		 * Set initial protection.
		 * Hide content.
		*/
		foreach( $rules as $rule ) {
			$rule->protect_content( $ms_relationship );
		}
	}
	
	/**
	 * Returns property associated with the render.
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		$value = null;
		switch( $property ) {
			case 'payment_type':
				if( empty( $this->payment_type ) ) {
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
				if( property_exists( $this, $property ) ) {
					$value = $this->$property;
				}
				break;
		}
		
		return apply_filters( 'ms_model_membership__get', $value, $property );
	}
	
	/**
	 * Validate specific property before set.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'name':
				case 'title':
				case 'description':
					$this->$property = sanitize_text_field( $value );
					break;
				case 'type':
					if( array_key_exists( $value, self::get_types() ) ) {
						$this->$property = $value;
					}
					break;
				case 'payment_type':
					if( array_key_exists( $value, self::get_payment_types() ) ) {
						if( empty( $this->$property ) || empty( $this->id ) || 0 == MS_Model_Membership_Relationship::get_membership_relationship_count( array( 'membership_id' => $this->id ) ) ) {
							$this->$property = $value;
						}
						elseif( $this->$property != $value ) {
							$error = "Membership type cannot be changed after members have signed up.";
							MS_Helper_Debug::log( $error );
							throw new Exception( $error );
						}
					}
					else {
						throw new Exception( "Invalid membeship type." );
					}
					break;
				case 'visitor_membership':
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
					if( 0 < MS_Factory::load( 'MS_Model_Membership', $value )->id ) {
						$this->$property = $value;
					}
				default:
					$this->$property = $value;
					break;
			}
		}
		else {
			switch( $property ) {
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
	}

	/**
	 * Get custom register post type args for this model.
	 *
	 * @since 4.0.0
	 */
	public static function get_register_post_type_args() {
		$args = apply_filters( 'ms_model_membership_register_post_type_args', array(
			'description' => __( 'Memberships user can join to.', MS_TEXT_DOMAIN ),
			'show_ui' => false,
			'show_in_menu' => false,
			'menu_position' => 70, // below Users
			'menu_icon' => MS_Plugin::instance()->url . "/assets/images/members.png",
			'public' => true,
			'has_archive' => false,
			'publicly_queryable' => false,
			'supports' => false,
// 			'capability_type' => apply_filters( self::$POST_TYPE, '_capability', 'page' ),
			'hierarchical' => false
		) );
		
		return $args;
	}
}