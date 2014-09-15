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


class MS_Model_Simulate extends MS_Model_Transient {
	
	protected static $CLASS_NAME = __CLASS__;
	
	public static $instance;
	
	protected $id =  'ms_model_simulate';
	
	protected $name = 'Simulate Membership';
		
	protected $membership_id;
	
	protected $period;
	
	protected $date;
	
	/**
	 * Check simulating status
	 * @return int The simulating membership_id
	 */
	public function is_simulating() {
		return $this->membership_id;
	}
	
	public function is_simulating_period() {
		return $this->period;
	}

	public function is_simulating_date() {
		return $this->date;
	}
	
	public function start_simulation() {
		if( $this->is_simulating_period() ) {
			$this->simulate_period();
		}
		elseif( $this->is_simulating_date() ) {
			$this->simulate_date();
		}
	}
	
	public function simulate_period() {
		$this->add_filter( 'ms_helper_period_current_date', 'simulate_period_filter' );
	}
	
	public function simulate_period_filter( $current_date ) {
		if( ! empty( $this->period ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $this->membership_id );
			if( in_array( $this->period['period_type'], MS_Helper_Period::get_periods() ) ) {
				$current_date = MS_Helper_Period::add_interval( $this->period['period_unit'], $this->period['period_type'] );
			}
		}

		return $current_date;
	}
	
	public function simulate_date() {
		$this->add_filter( 'ms_helper_period_current_date', 'simulate_date_filter' );
	}
	
	public function simulate_date_filter( $current_date ) {
		if( ! empty( $this->date ) ) {
			$current_date = $this->date;
		}
		return $current_date;
	}
	
	public function reset_simulation() {
		$this->membership_id = 0;
		$this->date = null;
		$this->period = null;
		$this->remove_filter( 'ms_helper_period_current_date', 'simulate_date_filter' );
		$this->save();
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
				case 'membership_id':
					$this->$property = 0;
					$id = absint( $value );
					if( 0 < $id ) {
						$membership = MS_Factory::load( 'MS_Model_Membership', $id );
						if ( 0 < $membership->id ) {
							$this->$property = $id;
							if( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
								$this->date = $membership->period_date_start;
							}
							else {
								$this->period = $this->validate_period( array(), 0 );
							}
						}
					}
					break;
				case 'period':
					$this->$property = $this->validate_period( $value, 0 );
					$this->date = null;
					break;
				case 'date':
					if( $date = $this->validate_date( $value ) ){
						$this->date = $value;
						$this->period = null;
					}
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}