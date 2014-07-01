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
	
	const MEMBERSHIP_TYPE_PERMANENT = 'permanent';
	
	const MEMBERSHIP_TYPE_FINITE = 'finite';
	
	const MEMBERSHIP_TYPE_DATE_RANGE = 'date-range';
	
	const MEMBERSHIP_TYPE_RECURRING = 'recurring';
	
	protected static $CLASS_NAME = __CLASS__;

	protected $gateway_id;
	
	protected $membership_type;
	
	protected $visitor_membership = false;
	
	protected $default_membership = false;
	
	protected $price;
	
	protected $period;
		
	protected $pay_cycle_period;
		
	protected $period_date_start;
	
	protected $period_date_end;
		
	protected $trial_period_enabled;
	
	protected $trial_price;
	
	protected $trial_period;
		
	protected $on_end_membership_id;

	protected $next_membership_id;
	
	protected $linked_membership_ids;
	
	protected $linked_weight;
	
	protected $active = true;
	
	protected $public = true;
	
	protected $rules = array();
	
	public static function get_membership_types() {
		return array(
				self::MEMBERSHIP_TYPE_PERMANENT => __( 'Single payment for permanent access', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_TYPE_FINITE => __( 'Single payment for finite access', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_TYPE_DATE_RANGE => __( 'Single payment for date range access', MS_TEXT_DOMAIN ),
				self::MEMBERSHIP_TYPE_RECURRING => __( 'Recurring payment', MS_TEXT_DOMAIN ),
		);
	}
	public function get_rule( $rule_type ) {
		if( isset( $this->rules[ $rule_type ] ) ) {
			return $this->rules[ $rule_type ];
		}
		elseif( 'attachment' == $rule_type && isset( $this->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ] ) ) {
			return $this->rules[ MS_Model_Rule::RULE_TYPE_MEDIA ];
		}
		else {
			$this->rules[ $rule_type ] = MS_Model_Rule::rule_factory( $rule_type );
			return $this->rules[ $rule_type ];
		}
	}
		
	public function set_rule( $rule_type, $rule ) {
		if( MS_Model_Rule::is_valid_rule_type( $rule_type) ) {
			$this->rules[ $rule_type ] = $rule;
		}
	}
	
	public function get_trial_expire_date( $start_date = null ) {
		if( empty( $start_date) ) {
			$start_date = MS_Helper_Period::current_date();
		}
		if( $this->trial_period_enabled && $this->trial_period['period_unit'] && $this->trial_period['period_type'] ) {
			if( self::MEMBERSHIP_TYPE_DATE_RANGE == $this->membership_type ) {
				$expiry_date = MS_Helper_Period::add_interval( $this->trial_period['period_unit'], $this->trial_period['period_type'] , $this->period_date_start );
			}
			else {
				$expiry_date = MS_Helper_Period::add_interval( $this->trial_period['period_unit'], $this->trial_period['period_type'] , $start_date );
			}
		}
		else {
			$expiry_date = MS_Helper_Period::current_date();
		}
		return $expiry_date;
	}
	
	public function get_expire_date( $start_date = null ) {
		$start_date = $this->get_trial_expire_date( $start_date );
		$end_date = null;
		switch( $this->membership_type ){
			case self::MEMBERSHIP_TYPE_PERMANENT:
				$end_date = null;
				break;
			case self::MEMBERSHIP_TYPE_FINITE:
				$end_date = MS_Helper_Period::add_interval( $this->period['period_unit'], $this->period['period_type'], $start_date );
				break;
			case self::MEMBERSHIP_TYPE_DATE_RANGE:
				$end_date = $this->period_end_date;
				break;
			case self::MEMBERSHIP_TYPE_RECURRING:
				$end_date = MS_Helper_Period::add_interval( $this->pay_cycle_period['period_unit'], $this->pay_cycle_period['period_type'], $start_date );
				break;
		}
		return $end_date;		
	}
	
	public static function get_membership_count( $args = null ) {
		$args = self::get_query_args( $args );
		
		$query = new WP_Query($args);
		return $query->found_posts;
		
	}
	
	public static function get_memberships( $args = null ) {
		$args = self::get_query_args( $args );
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[] = self::load( $item->ID );	
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
		if( ! MS_Plugin::instance()->settings->default_membership_enabled ) {
			$args['meta_query']['default_membership']  = array(
					'key' => 'default_membership',
					'value' => '1',
					'compare' => '!='
			);
		}

		return $args;
		
	}
	
	public static function get_membership_names( $args = null ) {
		$args = self::get_query_args( $args );
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[ $item->ID ] = $item->name;
		}
		return $memberships;
		
	}
	
	public static function load( $model_id = false ) {
		$model = parent::load( $model_id );
		
		if( empty( $model->rules ) ) {
			$model->rules = MS_Model_Rule::rule_set_factory( $model->rules );
		}

		return $model;
	}
	
	public static function is_valid_membership( $membership_id ) {
		return ( static::load( $membership_id )->id > 0 );
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
			$visitor_membership = self::load( $item[0]->ID );
		}
		else {
			$description = __( 'Default visitor membership', MS_TEXT_DOMAIN );
			$visitor_membership = new self();
			$visitor_membership->name = __( 'Visitor', MS_TEXT_DOMAIN );
			$visitor_membership->membership_type = self::MEMBERSHIP_TYPE_PERMANENT;
			$visitor_membership->title = $description;
			$visitor_membership->description = $description;
			$visitor_membership->visitor_membership = true;
			$visitor_membership->default_membership = false;
			$visitor_membership->active = true;
			$visitor_membership->public = true;
			$visitor_membership->save();
			$visitor_membership = self::load( $visitor_membership->id );
		}
		return $visitor_membership;
	}
	
	public static function get_default_membership() {
		$settings = MS_Plugin::instance()->settings;
		
		if( $settings->default_membership_enabled ) {
			$args = array(
					'post_type' => self::$POST_TYPE,
					'post_status' => 'any',
					'meta_query' => array(
							array(
									'key' => 'default_membership',
									'value' => '1',
									'compare' => '='
							)
					)
			);
			$query = new WP_Query( $args );
			$item = $query->get_posts();

			$default_membership = null;
			if( ! empty( $item[0] ) ) {
				$default_membership = self::load( $item[0]->ID );
			}
			else {
				$description = __( 'Default membership for non members', MS_TEXT_DOMAIN );
				$default_membership = new self();
				$default_membership->name = __( 'Default', MS_TEXT_DOMAIN );
				$default_membership->membership_type = self::MEMBERSHIP_TYPE_PERMANENT;
				$default_membership->title = $description;
				$default_membership->description = $description;
				$default_membership->visitor_membership = false;
				$default_membership->default_membership = true;
				$default_membership->active = true;
				$default_membership->public = true;
				$default_membership->save();
				$default_membership = self::load( $default_membership->id );
			}
		}
		else {
			$default_membership = self::get_visitor_membership();
		}
		return $default_membership;
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
			if ( 0 < count( $this->rules[ $type ]->dripped ) ) {
				return true;
			}
		}
		return false;	
	}

	/**
	 * Get payment information description.
	 *
	 * A more .
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @return boolean
	 */
	public function get_payment_description() {
		
		$currency = MS_Plugin::instance()->settings->currency;
		$desc = sprintf( __( 'You will pay %s %s ', MS_TEXT_DOMAIN ), $currency, number_format( $this->price, 2 ) );
		
		switch( $this->membership_type ){
			case self::MEMBERSHIP_TYPE_PERMANENT:
				$desc .= __( 'for permanent access.', MS_TEXT_DOMAIN );
				break;
			case self::MEMBERSHIP_TYPE_FINITE:
				$desc .= sprintf( __( 'for access until %s.', MS_TEXT_DOMAIN ), $this->get_expire_date() );
				break;
			case self::MEMBERSHIP_TYPE_DATE_RANGE:
				$desc .= sprintf( __( 'to access from %s to %s.', MS_TEXT_DOMAIN ), $this->period_date_start, $this->period_date_end );
				break;
			case self::MEMBERSHIP_TYPE_RECURRING:
				$desc .= sprintf( __( 'each %s %s.', MS_TEXT_DOMAIN ), $this->pay_cycle_period['period_unit'], $this->pay_cycle_period['period_type'] );
				break;
		}
		
		if( $this->trial_period_enabled ) {
			$desc .= sprintf( __( ' <br />In the trial period of %s %s, you will pay %s %s', MS_TEXT_DOMAIN ), 
					$this->trial_period['period_unit'], 
					$this->trial_period['period_type'], 
					$currency, 
					number_format( $this->trial_price, 2 ) 
			);
		}
		
		return $desc;
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
				case 'membership_type':
					if( array_key_exists( $value, self::get_membership_types() ) ) {
						if( empty( $this->id ) || 0 == MS_Model_Membership_Relationship::get_membership_relationship_count( array( 'membership_id' => $this->id ) ) ) {
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
					if( 0 < self::load( $value )->id ) {
						$this->$property = $value;
					}
				default:
					$this->$property = $value;
					break;
			}
		}
	}

}