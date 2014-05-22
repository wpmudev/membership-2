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

class MS_Model_Transaction extends MS_Model_Custom_Post_Type {
	
	public static $POST_TYPE = 'ms_transaction';
	
	protected static $CLASS_NAME = __CLASS__;
	
	const STATUS_BILLED = 'billed';
	
	const STATUS_PAID = 'paid';
	
	const STATUS_CANCELED = 'canceled';
	
	const STATUS_FAILED = 'failed';

	protected $gateway_id;
	
	protected $membership_id;
	
	protected $amount;
	
	protected $currency;
	
	protected $status;
	
	protected $due_date;
	
	protected $notes;
	
	protected $expire_date;
	
	protected $invoice;
	
	protected $taxable;
	
	protected $tax_rate;
	
	protected $tax_description;
	
	protected $total;

	public static function get_status() {
		return apply_filters( 'ms_model_transaction_get_status', array(
				self::STATUS_BILLED => __( 'Billed', MS_TEXT_DOMAIN ),
				self::STATUS_PAID => __( 'Paid', MS_TEXT_DOMAIN ),
				self::STATUS_CANCELED => __( 'Canceled', MS_TEXT_DOMAIN ),
				self::STATUS_FAILED => __( 'Failed', MS_TEXT_DOMAIN ),
			) 
		);
	}
	
	public function get_transaction_count( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'post_status' => 'any',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query($args);
		return $query->found_posts;
		
	}
	
	public static function get_transactions( $args = null ) {
		$defaults = array(
				'post_type' => self::$POST_TYPE,
				'posts_per_page' => 10,
				'order' => 'DESC',
		);
		$args = wp_parse_args( $args, $defaults );
		
		$query = new WP_Query($args);
		$items = $query->get_posts();
		
		$transactions = array();
		foreach ( $items as $item ) {
			$transactions[] = self::load( $item->ID );	
		}
		return $transactions;
	}
	
	public function process_transaction( $status, $force = false ) {
		if( $this->status != $status || $force ) {
			$member = MS_Model_Member::load( $this->user_id );
			if( MS_Model_Transaction::STATUS_PAID == $status ) {
				$member->add_membership( $this->membership_id, $this->gateway_id );
				$member->save();
			}
		}
	}
	
	/**
	 * Returns property associated with the render.
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
					return $this->amount + $this->tax_rate * $this->amount; 
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
				case 'status':
					if( array_key_exists( $value, self::get_status() ) ) {
						$this->$property = $value;
					}
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}