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
 * Abstract class for all Models.
 *
 * All models will extend or inherit from the MS_Model class.
 * Methods of this class will prepare objects for the database and
 * manipulate data to be used in a MS_Controller.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_Model extends MS_Hooker {
	
	protected $id;
	
	protected $name;
			
	protected static $ignore_fields = array( 'actions', 'filters' );
		
	public function __construct() {
	}
	
	public function before_save() {
		
	}
	
	public function save() {
		throw new Exception ("Method to be implemented in child class");
	}

	public function after_save() {
	
	}
	
	public function before_load() {
	
	}
	
	public static function load( $model_id ) {
		throw new Exception ("Method to be implemented in child class");
	}

	public function after_load() {
	
	}
	
	public function validate_options( $value, $options ) {
		if( in_array( $value, $options ) ) {
			return $value;
		}
		else {
			return reset( $options );
		}
	}
	
	public function validate_date( $date, $format = 'Y-m-d') {
		$d = DateTime::createFromFormat( $format, $date );
		if ( $d && $d->format( $format ) == $date ) {
			return $date;
		}
		else {
			return null;
		}
	}
	
	public function validate_bool( $value ) {
		if( function_exists( 'boolval' ) ) {
			return boolval( $value );
		}
		else {
			return (bool) $value;
		}
	}
	
	public function validate_min( $value, $min ) {
		return intval( ( $value > $min ) ? $value : $min ); 
	}
	
	public function validate_period( $period, $default_period_unit = 0, $default_period_type = MS_Helper_Period::PERIOD_TYPE_DAYS ) {
		$default = array( 'period_unit' => $default_period_unit, 'period_type' => $default_period_type );
		if( ! empty( $period['period_unit'] ) && ! empty( $period['period_type'] ) ) {
			$period['period_unit'] = intval( $period['period_unit'] ); 
			if( $period['period_unit'] < 0 ) {
				$period['period_unit'] = $default['period_unit'];
			}
			if( ! in_array( $period['period_type'], MS_Helper_Period::get_periods() ) ){
				$period['period_type'] = $default['period_type'];
			}
		}
		else {
			$period = $default;
		}
		return $period;
	}
}