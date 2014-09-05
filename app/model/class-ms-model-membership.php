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
	
	public static $POST_TYPE = 'ms_membership';
	
	public $post_type = 'ms_membership';
	
	const TYPE_SIMPLE = 'simple';
	const TYPE_CONTENT_TYPE = 'content_type';
	const TYPE_TIER = 'tier';
	const TYPE_DRIPPED = 'dripped';
	
	const MEMBERSHIP_TYPE_PERMANENT = 'permanent';
	
	const MEMBERSHIP_TYPE_FINITE = 'finite';
	
	const MEMBERSHIP_TYPE_DATE_RANGE = 'date-range';
	
	const MEMBERSHIP_TYPE_RECURRING = 'recurring';
	
	protected static $CLASS_NAME = __CLASS__;

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
	
	/**
	 * @deprecated
	 * @var unknown
	 */
	protected $gateway_id;
	
	protected $type;
	
	protected $membership_type;
	
	protected $linked_membership_ids;
	
	protected $active = false;
	
	protected $private = true;
	
	protected $visitor_membership = false;
	
	protected $price;
	
	protected $period;
		
	protected $pay_cycle_period;
		
	protected $period_date_start;
	
	protected $period_date_end;
		
	protected $trial_period_enabled;
	
	protected $trial_price;
	
	protected $trial_period;
		
	/**
	 * @deprecated
	 * @var unknown
	 */
	protected $on_end_membership_id;
	
	protected $rules = array();
	
	public static function get_types() {
		return apply_filters( 'ms_model_membership_get_types', array(
				self::TYPE_SIMPLE => __( 'Simple', MS_TEXT_DOMAIN ),
				self::TYPE_CONTENT_TYPE => __( 'Multiple content types', MS_TEXT_DOMAIN ),
				self::TYPE_TIER => __( 'Tier Based', MS_TEXT_DOMAIN ),
				self::TYPE_DRIPPED => __( 'Recurring payment', MS_TEXT_DOMAIN ),
		) );
	}

	public static function is_valid_type( $type ) {
		return apply_filters( 'ms_model_membership_is_valid_type', array_key_exists( $type, self::get_types() ) );
	}
	
	public function get_type_description() {
		$description = array();
		
		if( self::is_valid_type( $this->type ) ) {
			$types = self::get_types();
			$description[] = $types[ $this->type ];
			if( $this->is_private_eligible() ) {
				if( $this->is_private() ) {
					$description[] = __( 'Private', MS_TEXT_DOMAIN );
				}
				else {
					$description[] = __( 'Public', MS_TEXT_DOMAIN );
				}
			}
		}
		$description = join( ', ', $description );
		
		return apply_filters( 'ms_model_membership_get_type_description', $description );
	}
	
	public static function get_membership_types() {
		return apply_filters( 'ms_model_membership_get_payment_types', array(
				self::MEMBERSHIP_TYPE_PERMANENT => __( 'Single payment for permanent access', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_TYPE_FINITE => __( 'Single payment for finite access', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_TYPE_DATE_RANGE => __( 'Single payment for date range access', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_TYPE_RECURRING => __( 'Recurring payment', MS_TEXT_DOMAIN ),
		) );
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
		
		/** Private only can be enabled in these types */
		$private_eligible_types = apply_filters( 'ms_model_membership_private_eligible_types', array(
				self::TYPE_SIMPLE,
				self::TYPE_CONTENT_TYPE,
		) );
		if( in_array( $this->type, $private_eligible_types ) ) {
			$is_private_eligible = true;
		}
		return apply_filters( 'ms_model_membership_is_private_eligible', $is_private_eligible );
	}
	public function after_load() {
		/** validate rules using protected content rules */
		if( ! $this->visitor_membership ) {
			$this->merge_protected_content_rules();
		}
	}
	
	public function get_rule( $rule_type ) {
		if( isset( $this->rules[ $rule_type ] ) ) {
			if( $this->visitor_membership ) {
				$this->rules[ $rule_type ]->rule_value_invert = true;
			}
			return $this->rules[ $rule_type ];
		}
		elseif( 'attachment' == $rule_type && isset( $this->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ] ) ) {
			return $this->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ];
		}
		else {
			$this->rules[ $rule_type ] = MS_Model_Rule::rule_factory( $rule_type );
			if( $this->visitor_membership ) {
				$this->rules[ $rule_type ]->rule_value_invert = true;
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
	
	public static function get_query_args( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'order' => 'DESC',
				'post_status' => 'any',
		);
		$args = wp_parse_args( $args, $defaults );

		if( empty( $arg['visitor'] ) ){
			$args['meta_query']['active'] = array(
				'key'     => 'visitor_membership',
				'value'   => '',
			); 
		}
		return $args;
		
	}
	
	public static function get_membership_names( $args = null, $hide_default_memberships = false ) {
		$args = self::get_query_args( $args );
		
		$args['order'] = 'ASC';
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[ $item->ID ] = $item->name;
		}
		if( $hide_default_memberships ) {
			unset( $memberships[ self::get_visitor_membership()->id ] );
		}
		return $memberships;
		
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
			$visitor_membership = new self();
			$visitor_membership->name = __( 'Visitor', MS_TEXT_DOMAIN );
			$visitor_membership->membership_type = self::MEMBERSHIP_TYPE_PERMANENT;
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
			$rule = $this->get_rule( $rule_type );
			$rule->merge_rule_values( $protect_rule );
			$this->set_rule( $rule_type, $rule );
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
		if( $this->active ) {
			/** If 'has access' is found in the hierarchy, it does have access. */
			$rules = $this->get_rules_hierarchy();
			foreach( $rules as $rule ) {
				$has_access = ( $has_access || $rule->has_access( $post_id ) );
				if( $has_access ) {
					break;
				}
			}
			
			/**
			 * Search for the following dripped rules.
			 */
			$dripped = apply_filters( 'ms_model_membership_has_access_to_current_page_dripped_rules', array(
					MS_Model_Rule::RULE_TYPE_PAGE,
					MS_Model_Rule::RULE_TYPE_POST
			) );
			
			/**
			 * Verify membership dripped rules hierachyly.
			 * Dripped has the final decision.
			 */
			foreach( $dripped as $rule_type ) {
				$rule = $this->get_rule( $rule_type );
				if( $rule->has_dripped_rules( $post_id ) ) {
					$has_access = $rule->has_dripped_access( $ms_relationship->start_date, $post_id );
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
			case 'type_description':
				$value = $this->get_type_description();
				break;
			case 'private':
				$value=  $this->is_private();
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
		if ( property_exists( $this, $property ) ) {
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
				case 'membership_type':
					if( array_key_exists( $value, self::get_membership_types() ) ) {
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