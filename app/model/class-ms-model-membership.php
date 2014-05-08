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
 * @todo Maybe create MS_Model_Period to handle these many *_period_unit *_period_type
 *
 */
class MS_Model_Membership extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_membership';
	
	const MEMBERSHIP_TYPE_PERMANENT = 'permanent';
	
	const MEMBERSHIP_TYPE_FINITE = 'finite';
	
	const MEMBERSHIP_TYPE_DATE_RANGE = 'date-range';
	
	const MEMBERSHIP_TYPE_RECURRING = 'recurring';
	
	protected static $CLASS_NAME = __CLASS__;
		
	protected $membership_type;
	
	protected $visitor_membership = false;
	
	protected $default_membership = false;
	
	protected $price;
	
	protected $period_unit;
	
	protected $period_type;
	
	protected $pay_cicle_period_unit;
	
	protected $pay_cicle_period_type;
	
	protected $period_date_start;
	
	protected $period_date_end;
		
	protected $trial_period_enabled;
	
	protected $trial_price;
	
	protected $trial_period_unit;
	
	protected $trial_period_type;
	
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
	public function set_rule( $rule_type, $rule ) {
		$this->rules[ $rule_type ] = $rule;
	}
		
	public function get_trial_expire_date( $start_date = null ) {
		if( empty( $start_date) ) {
			$start_date = MS_Helper_Period::current_date();
		}
		if( $this->trial_period_unit && $this->trial_period_type ) {
			$expiry_date = MS_Helper_Period::add_interval( $this->trial_period_unit, $this->trial_period_type , $start_date );
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
				$end_date = MS_Helper_Period::add_interval( $this->period_unit, $this->period_type , $start_date );
				break;
			case self::MEMBERSHIP_TYPE_DATE_RANGE:
				$end_date = $this->period_end_date;
				break;
			case self::MEMBERSHIP_TYPE_RECURRING:
				$end_date = MS_Helper_Period::add_interval( $this->pay_cicle_period_unit, $this->pay_cicle_period_type , $start_date );
				break;
		}
		return $end_date;		
	}
	
	public function get_membership_count( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query($args);
		return $query->found_posts;
		
	}
	
	public static function get_memberships( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[] = self::load( $item->ID );	
		}
		return $memberships;
	}
	
	public static function get_membership_names( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$memberships = array();
		foreach ( $items as $item ) {
			$memberships[ $item->ID ] = $item->name;
		}
		return $memberships;
		
	}
	
	public static function load( $model_id ) {
		$model = parent::load( $model_id );
		foreach( MS_Model_Rule::$RULE_TYPE_CLASSES as $type => $class ) {
			if( empty( $model->rules[ $type ] ) ) {
				$model->rules[ $type ] = MS_Model_Rule::rule_factory( $type );
			}
		}
// 		if( empty( $model->rules['category']->post_rule ) ) {
// 			$category_rule = $model->rules['category'];
// 			$category_rule->set_post_rule( $model->rules['post'] );
// 			$model->set_rule( 'category', $category_rule ); 
// 		}
		return $model;
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
			$visitor_membership->name = 'Visitor';
			$visitor_membership->type = self::MEMBERSHIP_TYPE_PERMANENT;
			$visitor_membership->title = $description;
			$visitor_membership->description = $description;
			$visitor_membership->visitor_membership = true;
			$visitor_membership->defaut_membership = false;
			$visitor_membership->active = true;
			$visitor_membership->public = true;
			$visitor_membership->save();
			$visitor_membership = self::load( $visitor_membership->id );
		}
		return $visitor_membership;
	}
	
	public static function get_default_membership() {
		$settings = MS_Plugin::instance()->settings;
		
		if( $settings->show_default_membership ) {
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
			$query = new WP_Query($args);
			$item = $query->get_posts();
	
			$default_membership = null;
			if( ! empty( $item[0] ) ) {
				$default_membership = self::load( $item[0]->ID );
			}
			else {
				$description = __( 'Default membership for non members', MS_TEXT_DOMAIN );
				$default_membership = new self();
				$default_membership->name = 'Default';
				$default_membership->type = self::MEMBERSHIP_TYPE_PERMANENT;
				$default_membership->title = $description;
				$default_membership->description = $description;
				$default_membership->visitor_membership = false;
				$default_membership->defaut_membership = true;
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
		$args = array(
			'meta_query' => array(
				array(
					'key'     => 'ms_membership_ids',
					'value'   => "i:{$this->id};",
					'compare' => 'LIKE'
				),
			)
		 );
		$query = new WP_User_Query( $args );
		return $query->get_total();
		
	}
	
	public function delete() {
		if( ! empty( $this->id ) ) {
			if( $this->get_members_count() > 0 ) {
				throw new Exception("Could not delete membership with members.");
			}
			elseif( $this->visitor_membership ) {
				throw new Exception("Visitor membership could not be deleted.");
			}
			wp_delete_post( $this->id );
		}
	}

	public function has_dripped_content() {
		$dripped = array( 'post', 'page', 'category' );
		foreach( $dripped as $type ) {
			if( ! empty ( $this->rules[ $type ]->dripped ) ) {
				return true;
			}
		}
		return false;	
	}
	
	public function get_validation_rules() {
		$period_unit = array(
				'function' => array( &$this, 'validate_min' ),
				'args' => 1
		);
		$period_type = array(
				'function' => array( $this, 'validate_options' ),
				'args' => MS_Helper_Period::get_periods(),
		);
		$period_date = array(
				'function' =>	array( &$this, 'validate_date' ),
				'args' => MS_Helper_Period::PERIOD_FORMAT
		);
		$bool = array(
				'function' => array( &$this, 'validate_bool' )
		);
		$membership_type = array(
				'function' =>	array( &$this, 'validate_options' ),
				'args' => array_keys( self::get_membership_types() )
		);
		return apply_filters( 'membeship_model_membership_validation_rules', array(
				'name' => 'sanitize_text_field',
				'title' => 'sanitize_title',
				'description' => 'sanitize_text_field',
				'membership_type' => $membership_type,
				'visitor_membership' => $bool,
				'price' => 'floatval',
				'period_unit' => $period_unit,
				'period_type' => $period_type,
				'pay_cicle_period_unit' => $period_unit,
				'pay_cicle_period_type' => $period_type,
				'period_date_start' => $period_date,
				'period_date_end' => $period_date,
				'trial_period_enabled' => $bool,
				'trial_price' => 'floatval',
				'trial_period_unit' => $period_unit,
				'trial_period_type' => $period_type,
				'on_end_membership_id' => 'intval',
				'active' => $bool,
				'public' => $bool,
		) );
	}
}