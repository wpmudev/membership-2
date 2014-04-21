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
	
	protected static $CLASS_NAME = __CLASS__;
		
	protected $membership_type;
	
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

	protected $next_membership_id;
	
	protected $linked_membership_ids;
	
	protected $linked_weight;
	
	protected $active;
	
	protected $public;
	
	protected $rules = array();

	public function set_rule( $rule_type, $rule ) {
		$this->rules[ $rule_type ] = $rule;
	}
	
	public function get_trial_expiry_date( $start_date = null ) {
		if( $this->trial_period_unit && $this->trial_period_type ) {
			$expiry_date = MS_Helper_Period::add_interval ( $this->trial_period_unit, $this->trial_period_type , $start_date );
		}
		else {
			$expiry_date = MS_Helper_Period::current_date();
		}
		return $expiry_date;
	}
	
	public function get_expiry_date( $start_date = null ) {
		$start_date = $this->get_trial_expiry_date( $start_date );
		return MS_Helper_Period::add_interval ( $this->period_unit, $this->period_type , $start_date );		
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
	public static function load( $model_id ) {
		$model = parent::load( $model_id );
		foreach( MS_Model_Rule::$RULE_TYPE_CLASSES as $type => $class ) {
			if( empty( $model->rules[ $type ] ) ) {
				$model->rules[ $type ] = MS_Model_Rule::rule_factory( $type );
			}
		}
		return $model;
	}
}