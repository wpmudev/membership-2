<?php
/**
 * This file defines the MS_Model object.
 *
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
 * @package Membership
 */
class MS_Model extends MS_Hooker {
	
	/** 
	 * ID of the model object.
	 *
	 * @since 4.0.0
	 */
	protected $id;
	
	/** 
	 * Model name.
	 *
	 * @since 4.0.0
	 */	
	protected $name;

	/** 
	 * Excludes actions and filters from validation.
	 *
	 * @since 4.0.0
	 */			
	public $ignore_fields = array( 'actions', 'filters', 'ignore_fields' );

	/**
	 * MS_Model Contstuctor
	 *
	 * @since 4.0.0
	 */	
	public function __construct() {
		
		/**
		 * Actions to execute when constructing the parent Model.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Model object.
		 */
		do_action( 'membership_parent_model_construct', $this );		
	}
	
	/**
	 * Set field value, bypassing the __set validation.
	 * 
	 * Used for loading from db.
	 * 
	 * @since 4.0.0
	 * 
	 * @param string $field
	 * @param mixed $value
	 */
	public function set_field( $field, $value ) {
		if( property_exists( $this, $field ) ) {
			$this->$field = $value;
		}
	}
	
	/**
	 * Prepare data before saving model.
	 *
	 * @since 4.0.0
	 */		
	public function before_save() {		
	}

	/**
	 * Abstract save method to save model data.
	 *
	 * @since 4.0.0
	 */		
	public function save() {
		throw new Exception ("Method to be implemented in child class");
	}

	/**
	 * Called after saving model data.
	 *
	 * @since 4.0.0
	 */	
	public function after_save() {
	
	}

	/**
	 * Prepare data bedfore loading the model.
	 *
	 * @since 4.0.0
	 */		
	public function before_load() {
	
	}

	/**
	 * Load the model data.
	 *
	 * @since 4.0.0
	 */		
	public function load( $model_id = false ) {
		throw new Exception ("Method to be implemented in child class");
	}

	/**
	 * Called after loading model data.
	 *
	 * @since 4.0.0
	 */	
	public function after_load() {
	
	}

	/**
	 * Get object properties.
	 * 
	 * @since 4.0.0
	 * 
	 * @return array of fields.
	 */
	public function get_object_vars() {
		return get_object_vars( $this );	
	}
	
	/**
	 * Validate model properties.
	 *
	 * @since 4.0.0
	 * @param mixed $value 
	 * @param object $options 
	 */		
	public function validate_options( $value, $options ) {
		if( in_array( $value, $options ) ) {
			return $value;
		}
		else {
			return reset( $options );
		}
	}

	/**
	 * Validate dates used within models.
	 *
	 * @since 4.0.0
	 * @param string $date Date as a PHP date string
	 * @param string $format Date format.
	 */		
	public function validate_date( $date, $format = 'Y-m-d') {
		$d = new DateTime( $date );
		if ( $d && $d->format( $format ) == $date ) {
			return $date;
		}
		else {
			return null;
		}
	}

	/**
	 * Validate booleans.
	 *
	 * @since 4.0.0
	 * @param bool $value Boolean to validate.
	 */		
	public function validate_bool( $value ) {
		$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
		return $value;
	}

	/**
	 * Validate minimum values.
	 *
	 * @since 4.0.0
	 * @param int $value Value to validate
	 * @param int $min Minimum value
	 */		
	public function validate_min( $value, $min ) {
		return intval( ( $value > $min ) ? $value : $min ); 
	}

	/**
	 * Validate time periods used with models.
	 *
	 * @since 4.0.0
	 * @param string $period Membership period to validate
	 * @param int $default_period_unit Number of periods (e.g. number of days)
	 * @param string $default_period_type (e.g. days, weeks, years)
	 */		
	public function validate_period( $period, $default_period_unit = 0, $default_period_type = MS_Helper_Period::PERIOD_TYPE_DAYS ) {
		$default = array( 'period_unit' => $default_period_unit, 'period_type' => $default_period_type );
		if( ! empty( $period['period_unit'] ) && ! empty( $period['period_type'] ) ) {
			$period['period_unit'] = $this->validate_period_unit( $period['period_unit'] );
			$period['period_type'] = $this->validate_period_type( $period['period_type'] );
		}
		else {
			$period = $default;
		}
		return $period;
	}
	
	public function validate_period_unit( $period_unit, $default = 0 ) {
		$period_unit = intval( $period_unit );
		if( $period_unit < 0 ) {
			$period_unit = 0;
		}
		return apply_filters( 'ms_model_validate_period_unit', $period_unit );
	}
	
	public function validate_period_type( $period_type, $default = MS_Helper_Period::PERIOD_TYPE_DAYS ) {
		if( ! in_array( $period_type, MS_Helper_Period::get_periods() ) ){
			$period_type = $default;
		}
		
		return apply_filters( 'ms_model_validate_period_type', $period_type );
	}
}