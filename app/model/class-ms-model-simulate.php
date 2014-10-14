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
 * Membership Simulation model.
 *
 * Persisted by parent class MS_Model_Transient.
 *
 * @since 1.0.0
 * 
 * @package Membership
 * @subpackage Model
 */
class MS_Model_Simulate extends MS_Model_Transient {

	/**
	 * Simulation type constants.
	 *
	 * @since 1.0.0
	 * 
	 * @see $type property.
	 * @var string
	 */
	const TYPE_DATE = 'type_date';
	const TYPE_PERIOD = 'type_period';

	/**
	 * Singleton instance.
	 *
	 * @since 1.0.0
	 *
	 * @staticvar MS_Model_Settings
	 */
	public static $instance;

	/**
	 * The membership ID to simulate.
	 *
	 * @since 1.0.0
	 * 
	 * @var int
	 */
	protected $membership_id;

	/**
	 * Simulation type.
	 *
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	protected $type = self::TYPE_PERIOD;
	
	/**
	 * The period to simulate.
	 *
	 * @since 1.0.0
	 *
	 * @var array {
	 *		@type int $period_unit The period of time quantity.
	 *		@type string $period_type The period type (days, weeks, months, years).
	 * }
	 */
	protected $period;

	/**
	 * The date to simulate.
	 *
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	protected $date;

	/**
	 * Get simulation types.
	 *
	 * @since 1.0.0
	 * 
	 * @return string[] The simulation types.
	 */
	public static function get_simulation_types() {
		static $types;
		
		if( empty( $types ) ) {
			$types = array(
					self::TYPE_DATE,
					self::TYPE_PERIOD,
			);
		}
		
		return apply_filters( 'ms_model_simulate_get_simulation_types', $types );
	}
	
	/**
	 * Verify simulate type validation.
	 *
	 * @since 1.0.0
	 * 
	 * @param string $type The type to verify validation.
	 * @return bool True if valid.
	 */
	public static function is_valid_simulation_type( $type ) {
		$valid = false;
		
		if( in_array( $type, self::get_simulation_types() ) ) {
			$valid = true;
		}
		
		return apply_filters( 'ms_model_simulate_get_simulation_types', $valid );
	}
	
	/**
	 * Check simulating status
	 * 
	 * @since 1.0.0
	 * 
	 * @return int The simulating membership_id
	 */
	public function is_simulating() {
		
		return $this->membership_id;
	}

	/**
	 * Start simulation period/date.
	 * 
	 * @since 1.0.0
	 */
	public function start_simulation() {

		if ( self::TYPE_PERIOD == $this->type ) {
			$this->add_filter( 'ms_helper_period_current_date', 'simulate_period_filter' );
		}
		elseif ( self::TYPE_DATE == $this->type ) {
			$this->add_filter( 'ms_helper_period_current_date', 'simulate_date_filter' );
		}
	}

	/**
	 * Simulate period.
	 * 
	 * ** Hooks filter/actions: **
	 * * ms_helper_period_current_date
	 * 
	 * @since 1.0.0
	 * 
	 * @param string $current_date The date to filter.
	 * @return string The filtered date.
	 */
	public function simulate_period_filter( $current_date ) {
		if ( ! empty( $this->period ) ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $this->membership_id );

			if ( in_array( $this->period['period_type'], MS_Helper_Period::get_periods() ) ) {
				$current_date = MS_Helper_Period::add_interval(
					$this->period['period_unit'],
					$this->period['period_type']
				);
			}
		}

		return $current_date;
	}

	/**
	 * Simulate date.
	 *
	 * ** Hooks filter/actions: **
	 * * ms_helper_period_current_date
	 *
	 * @since 1.0.0
	 *
	 * @param string $current_date The date to filter.
	 * @return string The filtered date.
	 */
	public function simulate_date_filter( $current_date ) {
		if ( ! empty( $this->date ) ) {
			$current_date = $this->date;
		}

		return $current_date;
	}
	
	/**
	 * Reset simulation.
	 * 
	 * @since 1.0.0
	 */
	public function reset_simulation() {
		$this->membership_id = 0;
		$this->date = null;
		$this->period = null;
		$this->remove_filter( 'ms_helper_period_current_date', 'simulate_date_filter' );
		$this->remove_filter( 'ms_helper_period_current_date', 'simulate_period_filter' );
		$this->save();
	}

	/**
	 * Get simulation type.
	 * Validate simulation type accordinly to membership.
	 * 
	 * @since 1.0.0
	 * 
	 * @return string The simulation type.
	 */
	public function get_simulation_type() {
		
		$membership = MS_Factory::load( 'MS_Model_Membership', $this->membership_id );
		
		if ( $membership->is_valid() ) {
		
			if ( MS_Model_Membership::PAYMENT_TYPE_DATE_RANGE == $membership->payment_type ) {
				$this->type = MS_Model_Simulate::TYPE_DATE;
			}
			else {
				switch( $membership->type ) {
					case MS_Model_Membership::TYPE_DRIPPED:
						if( MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE == $membership->dripped_type ) {
							$this->type = MS_Model_Simulate::TYPE_DATE;
						}
						else {
							$this->type = MS_Model_Simulate::TYPE_PERIOD;
						}
						break;
					default:
						$this->type = MS_Model_Simulate::TYPE_PERIOD;
						break;
				}
			}
		}
		
		return apply_filters( 'ms_model_simulate_get_simulation_type', $this->type, $this );
	}
	
	/**
	 * Returns property associated with the render.
	 *
	 * @since 1.0.0
	 *
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
	
		$value = null;
	
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'type':
					$value = $this->get_simulation_type();
					break;
				case 'date':
					if( empty( $this->date ) ) {
						$this->date = MS_Helper_Period::current_date();
					}
					$value = $this->date;
					break;
				case 'period':
					if( empty( $this->period ) ) {
						$this->period = $this->validate_period( array(), 0 );
					}
					$value = $this->period;
					break;
				default:
					$value = $this->$property;
					break;
			}
		}
			
		return apply_filters( 'ms_model_simulate__get', $value, $property, $this );
	}
	
	/**
	 * Validate specific property before set.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) { 

			switch ( $property ) {
				case 'membership_id':
					$this->$property = 0;
					$id = absint( $value );
					if ( 0 < $id ) {
						if ( MS_Model_Membership::is_valid_membership( $id ) ) {
							$this->$property = $id;
							$this->get_simulation_type();
						}
					}
					break;
	
				case 'type': 
					if( self::is_valid_simulation_type( $value ) ) {
						$this->type = $value;
					}
					break;
					
				case 'period':
					$this->$property = $this->validate_period( $value, 0 );
					break;
	
				case 'date':
					if ( $date = $this->validate_date( $value ) ) {
						$this->date = $value;
					}
					break;
	
				default:
					$this->$property = $value;
					break;
			}
		}
		
		do_action( 'ms_model_simulate__set_after', $property, $value, $this );
	}
}