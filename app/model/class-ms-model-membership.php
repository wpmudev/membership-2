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

class MS_Model_Membership extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_membership';
	
	const MEMBERSHIP_TYPE_PERMANENT = 'permanent';
	
	const MEMBERSHIP_TYPE_FINITE = 'finite';
	
	const MEMBERSHIP_TYPE_DATE_RANGE = 'date-range';
	
	const MEMBERSHIP_TYPE_RECURRING = 'recurring';
	
	protected static $CLASS_NAME = __CLASS__;
		
	protected $membership_type;
	
	protected $visitor_membership = false;
	
	protected $price;
	
	protected $period_unit;
	
	protected $period_type;
	
	protected $pay_cicle_period_unit;
	
	protected $pay_cicle_period_type;
	
	protected $period_date_start;
	
	protected $period_date_end;
	
	protected $trial_price;
	
	protected $trial_period_enabled;
	
	protected $trial_period_unit;
	
	protected $trial_period_type;
	
	protected $on_end_membership_id;

	protected $next_membership_id;
	
	protected $linked_membership_ids;
	
	protected $linked_weight;
	
	protected $active;
	
	protected $public;
	
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
		$start_date = MS_Helper_Period::current_date();
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
	
	public static function get_memberships( $limit = 10 ) {
		$args = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => $limit,
				'order' => 'DESC',
		);
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
			$visitor_membership->save();
			$visitor_membership = self::load( $visitor_membership->id );
		}
		return $visitor_membership;
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
}