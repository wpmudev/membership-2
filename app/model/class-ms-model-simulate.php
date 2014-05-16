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
	
// 	public function set_simulation( $membership_id ) {
// 		$this->membership_id = absint( $membership_id );
// 	}
	
// 	public function set_simulation_period( $period_unit, $period_type ) {
// 		$period['period_unit'] = $period_unit;
// 		$period['period_type'] = $period_type;
// 		$this->period = $this->validate_period( $period );
// 		if( ! empty ( $this->period ) ) {
// 			$this->date = null;
// 		}
// 	}
	
// 	public function set_simulation_date( $date ) {
// 		if( $date = $this->validate_date( $date ) ){
// 			$this->date = $date;
// 			$this->period = null;
// 		}
// 	}
	public function simulate_date() {
		$this->add_filter( 'membership_helper_period_current_date', 'simulate_date_filter' );
	}
	public function simulate_date_filter( $current_date ) {
		return $this->date;
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
						$membership = MS_Model_Membership::load( $id );
						if ( 0 < $membership->id ) {
							$this->$property = $id;
							if( MS_Model_Membership::MEMBERSHIP_TYPE_DATE_RANGE == $membership->membership_type ) {
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