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
 * @since 1.0.0
 *
 * @package Membership2
 */
class MS_Model extends MS_Hooker {

	/**
	 * ID of the model object.
	 *
	 * @since 1.0.0
	 *
	 * @var int|string
	 */
	protected $id;

	/**
	 * Model name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Stores the caching state of the object.
	 * This value is ONLY modified by MS_Factory::set_singleton(), so if it is
	 * true it means that this object can be accessed via MS_Factory::load()
	 *
	 * @since 1.1.1.4
	 *
	 * @var bool
	 */
	public $_in_cache = false;

	/**
	 * MS_Model Contstuctor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		/**
		 * Actions to execute when constructing the parent Model.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Model object.
		 */
		do_action( 'ms_model_construct', $this );
	}

	/**
	 * Set field value, bypassing the __set validation.
	 *
	 * Used for loading from db.
	 *
	 * @since 1.0.0
	 *
	 * @param string $field
	 * @param mixed $value
	 */
	public function set_field( $field, $value ) {
		// Don't deserialize values of "private" fields.
		if ( '_' === $field[0] ) {
			return;
		}

		if ( property_exists( $this, $field ) ) {
			$this->$field = $value;
		}
	}

	/**
	 * Called before saving model.
	 *
	 * @since 1.0.0
	 */
	public function before_save() {
		do_action( 'ms_model_before_save', $this );
	}

	/**
	 * Abstract method to save model data.
	 *
	 * @since 1.0.0
	 */
	public function save() {
		throw new Exception( 'Method to be implemented in child class' );
	}

	/**
	 * Set the singleton instance if it is not yet defined.
	 *
	 * @since 1.1.1.4
	 */
	public function store_singleton() {
		if ( $this->_in_cache ) { return; }
		MS_Factory::set_singleton( $this );
	}

	/**
	 * Called after saving model data.
	 *
	 * @since 1.0.0
	 */
	public function after_save() {
		do_action( 'ms_model_after_save', $this );
	}

	/**
	 * Get object properties.
	 *
	 * @since 1.0.0
	 *
	 * @return array of fields.
	 */
	public function get_object_vars() {
		return get_object_vars( $this );
	}

	/**
	 * Validate dates used within models.
	 *
	 * @since 1.0.0
	 *
	 * @param string $date Date as a PHP date string
	 * @param string $format Date format.
	 */
	public function validate_date( $date, $format = 'Y-m-d' ) {
		$valid = null;

		$d = new DateTime( $date );
		if ( $d && $d->format( $format ) == $date ) {
			$valid = $date;
		}

		return apply_filters(
			'ms_model_validate_date',
			$valid,
			$date,
			$format,
			$this
		);
	}

	/**
	 * Validate time periods array structure.
	 *
	 * @since 1.0.0
	 *
	 * @param string $period Membership period to validate
	 * @param int $default_period_unit Number of periods (e.g. number of days)
	 * @param string $default_period_type (e.g. days, weeks, years)
	 */
	public function validate_period( $period, $default_period_unit = 0, $default_period_type = MS_Helper_Period::PERIOD_TYPE_DAYS ) {

		$default = array(
			'period_unit' => $default_period_unit,
			'period_type' => $default_period_type,
		);

		if ( ! empty( $period['period_unit'] )
			&& ! empty( $period['period_type'] )
		) {
			$period['period_unit'] = $this->validate_period_unit( $period['period_unit'] );
			$period['period_type'] = $this->validate_period_type( $period['period_type'] );
		} else {
			$period = $default;
		}

		return apply_filters(
			'ms_model_validate_period',
			$period,
			$this
		);
	}

	/**
	 * Validate period unit.
	 *
	 * @since 1.0.0
	 *
	 * @param string $period_unit The period quantity to validate.
	 * @param int $default The default value when not validated. Default to 1.
	 */
	public function validate_period_unit( $period_unit, $default = 1 ) {
		$period_unit = intval( $period_unit );

		if ( $period_unit <= 0 ) {
			$period_unit = $default;
		}

		return apply_filters(
			'ms_model_validate_period_unit',
			$period_unit,
			$this
		);
	}

	/**
	 * Validate period type.
	 *
	 * @since 1.0.0
	 *
	 * @param string $period_type The period type to validate.
	 * @param int $default The default value when not validated. Default to days.
	 */
	public function validate_period_type( $period_type, $default = MS_Helper_Period::PERIOD_TYPE_DAYS ) {
		if ( ! in_array( $period_type, MS_Helper_Period::get_period_types() ) ) {
			$period_type = $default;
		}

		return apply_filters(
			'ms_model_validate_period_type',
			$period_type,
			$this
		);
	}
}