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
 * Utilities class
 *
 * @since 1.0.0
 *
 */
class MS_Helper_Period extends MS_Helper {

	/**
	 * Period types
	 */
	const PERIOD_TYPE_DAYS = 'days';
	const PERIOD_TYPE_WEEKS = 'weeks';
	const PERIOD_TYPE_MONTHS = 'months';
	const PERIOD_TYPE_YEARS = 'years';

	/**
	 * Date formats
	 */
	const PERIOD_FORMAT = 'Y-m-d';
	const DATE_TIME_FORMAT = 'Y-m-d H:i';
	const DATE_FORMAT_SHORT = 'y-m-d';

	/**
	 * Add a period interval to a date.
	 *
	 * @since 1.0.0
	 *
	 * @param int $period_unit The period unit to add.
	 * @param string $period_type The period type to add.
	 * @param string $start_date The start date to add to.
	 * @throws Exception
	 * @return string The added date.
	 */
	public static function add_interval( $period_unit, $period_type, $start_date = null ) {
		if ( empty ( $start_date ) ) {
			$start_date = gmdate( self::PERIOD_FORMAT );
		}

		if ( self::PERIOD_TYPE_YEARS == $period_type ) {
			$period_unit *= 365;
			$period_type = self::PERIOD_TYPE_DAYS;
		}

		$end_dt = strtotime( '+' . $period_unit . $period_type , strtotime( $start_date ) );
		if ( $end_dt === false ) {
			throw new Exception( 'error add_interval' );
		}

		return apply_filters(
			'ms_helper_period_add_interval',
			gmdate( self::PERIOD_FORMAT, $end_dt )
		);
	}

	/**
	 * Subtract a period interval to a date.
	 *
	 * @since 1.0.0
	 *
	 * @param int $period_unit The period unit to subtract.
	 * @param string $period_type The period type to subtract.
	 * @param string $start_date The start date to subtract to.
	 * @throws Exception
	 * @return string The subtracted date.
	 */
	public static function subtract_interval( $period_unit, $period_type, $start_date = null ) {
		if ( empty ( $start_date ) ) {
			$start_date = gmdate( self::PERIOD_FORMAT );
		}

		$end_dt = strtotime( '-' . $period_unit . $period_type , strtotime( $start_date ) );
		if ( $end_dt === false ) {
			throw new Exception( 'error subtract_interval' );
		}

		return apply_filters(
			'ms_helper_period_subtract_interval',
			gmdate( self::PERIOD_FORMAT, $end_dt )
		);
	}

	/**
	 * Subtract dates.
	 *
	 * Return (end_date - start_date) in period_type format
	 *
	 * @since 1.0.0
	 *
	 * @param Date $end_date The end date to subtract from in the format yyyy-mm-dd
	 * @param Date $start_date The start date to subtraction the format yyyy-mm-dd
	 * @return string The resulting of the date subtraction.
	 */
	public static function subtract_dates( $end_date, $start_date ) {
		$end_date = new DateTime( $end_date );
		$start_date = new DateTime( $start_date );

		$days = round(
			( $end_date->format( 'U' ) - $start_date->format( 'U' ) ) /
			86400 // = 60 * 60 * 24
		);

		return apply_filters(
			'ms_helper_period_subtract_dates',
			$days
		);
	}

	/**
	 * Return current date.
	 *
	 * @since 1.0.0
	 *
	 * @return string The current date.
	 */
	public static function current_date( $format = null, $ignore_filters = false ) {
		if ( empty( $format ) ) {
			$format = self::PERIOD_FORMAT;
		}

		$format = apply_filters(
			'ms_helper_period_current_date_format',
			$format
		);

		$date = gmdate( $format );

		if ( ! $ignore_filters ) {
			$date = apply_filters(
				'ms_helper_period_current_date',
				$date
			);
		}

		return $date;
	}

	/**
	 * Return current timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @return string The current date.
	 */
	public static function current_time( $type = 'mysql' ) {
		return apply_filters(
			'ms_helper_period_current_time',
			current_time( $type, true )
		);
	}

	/**
	 * Return the existing period types.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type [all|singular|plural]
	 * @return array The period types and descriptions.
	 */
	public static function get_period_types( $type = 'all' ) {
		$singular = array(
			'1' . self::PERIOD_TYPE_DAYS => __( 'one day', MS_TEXT_DOMAIN ),
			'1' . self::PERIOD_TYPE_WEEKS => __( 'one week', MS_TEXT_DOMAIN ),
			'1' . self::PERIOD_TYPE_MONTHS => __( 'one month', MS_TEXT_DOMAIN ),
			'1' . self::PERIOD_TYPE_YEARS => __( 'one year', MS_TEXT_DOMAIN ),
			'1-' . self::PERIOD_TYPE_DAYS => __( 'day', MS_TEXT_DOMAIN ),
			'1-' . self::PERIOD_TYPE_WEEKS => __( 'week', MS_TEXT_DOMAIN ),
			'1-' . self::PERIOD_TYPE_MONTHS => __( 'month', MS_TEXT_DOMAIN ),
			'1-' . self::PERIOD_TYPE_YEARS => __( 'year', MS_TEXT_DOMAIN ),
		);
		$plural = array(
			self::PERIOD_TYPE_DAYS => __( 'days', MS_TEXT_DOMAIN ),
			self::PERIOD_TYPE_WEEKS => __( 'weeks', MS_TEXT_DOMAIN ),
			self::PERIOD_TYPE_MONTHS => __( 'months', MS_TEXT_DOMAIN ),
			self::PERIOD_TYPE_YEARS => __( 'years', MS_TEXT_DOMAIN ),
		);

		switch ( $type ) {
			case 'singular': $res = $singular; break;
			case 'plural':   $res = $plural; break;
			default:         $res = $singular + $plural; break;
		}

		return apply_filters(
			'ms_helper_period_get_periods',
			$res
		);
	}

	/**
	 * Get period in days.
	 *
	 * Convert period in week, month, years to days.
	 *
	 * @since 1.0.0
	 *
	 * @param $period The period to convert.
	 *
	 * @return int The calculated days.
	 */
	public static function get_period_in_days( $unit, $type ) {
		$days = 0;

		switch ( $type ) {
			case self::PERIOD_TYPE_DAYS:
				$days = intval( $unit );
				break;

			case self::PERIOD_TYPE_WEEKS:
				$days = intval( $unit ) * 7;
				break;

			case self::PERIOD_TYPE_MONTHS:
				$days = intval( $unit ) * 30;
				break;

			case self::PERIOD_TYPE_YEARS:
				$days = intval( $unit ) * 365;
				break;
		}

		return apply_filters(
			'ms_helper_period_get_period_in_days',
			$days,
			$period
		);
	}

	public static function get_period_value( $period, $field ) {
		$value = null;

		if ( isset( $period[ $field ] ) ) {
			$value = $period[ $field ];
		} elseif ( 'period_unit' == $field ) {
			$value = 1;
		} elseif ( 'period_type' == $field ) {
			$value = self::PERIOD_TYPE_DAYS;
		}

		return apply_filters(
			'ms_helper_period_get_period_value',
			$value
		);
	}

	public static function get_period_desc( $period, $include_quanity_one = false ) {
		$period_unit = MS_Helper_Period::get_period_value(
			$period,
			'period_unit'
		);
		$period_type = MS_Helper_Period::get_period_value(
			$period,
			'period_type'
		);

		$types = self::get_period_types();

		if ( $period_unit == 1 ) {
			$desc = '%2$s';

			if ( $include_quanity_one ) {
				$period_type = $types['1' . $period_type];
			} else {
				$period_type = $types['1-' . $period_type];
			}
		} else {
			$desc = '%1$s %2$s';
		}
		$desc = sprintf( $desc, $period_unit, $period_type );

		return apply_filters(
			'ms_helper_period_get_period_desc',
			$desc
		);
	}

	/**
	 * Returns a valid value for the specified range-unit.
	 *
	 * This validation is according to PayPal IPN requiremens:
	 *   Day   -> value will be between 1 - 90
	 *   Week  -> value will be between 1 - 52
	 *   Month -> value will be between 1 - 24
	 *   Year  -> value will be between 1 - 5
	 *
	 * @since  1.0.4.5
	 * @param  int $value The value to validate
	 * @param  string $unit Period unit (D/W/M/Y or long days/weeks/...)
	 * @return int The validated value
	 */
	public static function validate_range( $value, $unit ) {
		if ( $value <= 1 ) {
			$value = 1;
		} else {
			$unit = strtoupper( $unit[0] );
			$max = 1;

			switch ( $unit ) {
				case 'D': $max = 90; break;
				case 'W': $max = 52; break;
				case 'M': $max = 24; break;
				case 'Y': $max = 5; break;
			}

			$value = min( $value, $max );
		}

		return $value;
	}

	/**
	 * Returns a formated date string
	 *
	 * @param  string $date The date value.
	 * @param  string $format Optional the format to apply.
	 */
	public static function format_date( $date, $format = null ) {
		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}

		$result = date_i18n( $format, strtotime( $date ) );

		return apply_filters(
			'ms_format_date',
			$result,
			$date,
			$format
		);
	}
}