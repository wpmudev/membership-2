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
	
	public function save(){
		throw new Exception ("Method to be implemented in child class");
	}
	
	public static function load( $model_id ) {
		throw new Exception ("Method to be implemented in child class");
	}

	public function get_validation_rules() {
		return array();
	}
	
	public function validate() {
		$validation = $this->get_validation_rules();

		if( ! empty( $validation ) ) {
			foreach( $validation as $field => $function ) {
				if( is_array( $function ) ) {
					$args = ! empty( $function['args'] ) ? $function['args'] : null; 
					$this->$field = call_user_func_array( $function['function'],  array( $this->$field , $args ) );	
				}
				else{
					$this->$field  = call_user_func( $function, $this->$field );
				}
			}
		}
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
	
	public function validate_period( $periods ) {
		$default = array( 'period_unit' => 1, 'period_type' => MS_Helper_Period::PERIOD_TYPE_DAYS );
		foreach( $periods as $key => $period ) {
			if( ! empty( $period['period_unit'] ) && ! empty( $period['period_type'] ) ) {
				$periods[ $key ]['period_unit'] = intval( $periods[ $key ]['period_unit'] ); 
				if( $periods[ $key ]['period_unit'] < 0 ) {
					$periods[ $key ]['period_unit'] = $default['period_unit'];
				}
				if( ! in_array( $period['period_type'], MS_Helper_Period::get_periods() ) ){
					$periods[ $key ]['period_type'] = $default['period_type'];
				}
			}
			else {
				$period = $default;
			}
		}
		return $periods;
	}
}