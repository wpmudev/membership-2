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

class MS_Model_Coupon extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_coupon';
	
	protected static $CLASS_NAME = __CLASS__;
	
	const TYPE_VALUE = 'value';
	
	const TYPE_PERCENT = 'percent';
	
	protected $code;

	protected $discount;
	
	protected $discount_type;
	
	protected $start_date;
	
	protected $expire_date;
	
	protected $membership_id;
	
	protected $max_uses;
	
	protected $used;
	
	public static function get_discount_types() {
		return apply_filters( 'ms_model_coupon_get_discount_types', array(
				self::TYPE_VALUE => __( '$', MS_TEXT_DOMAIN ),
				self::TYPE_PERCENT => __( '%', MS_TEXT_DOMAIN ),
			) 
		);
	}
	
	public function get_coupon_count( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
		);
		$args = wp_parse_args( $args, $defaults );
	
		$query = new WP_Query($args);
		return $query->found_posts;
	
	}
	
	public static function get_coupons( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );
	
		$query = new WP_Query($args);
		$items = $query->get_posts();
	
		$coupons = array();
		foreach ( $items as $item ) {
			$coupons[] = self::load( $item->ID );
		}
		return $coupons;
	}
	
	/**
	 * Returns property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'total':
					return $this->amount + $this->tax_rate/100 * $this->amount; 
					break;
				case 'invoice':
					return $this->id;
					break;
				default:
					return $this->$property;
					break;
			}
		}
	}
	/**
	 * Set specific property.
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
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}