<?php
/**
 * Utilities class
 *
 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @param int $period_unit The period unit to add.
	 * @param string $period_type The period type to add.
	 * @param string|int $start_date The start date to add to.
	 * @return string The added date.
	 */
	public static function add_interval( $period_unit, $period_type, $start_date = null ) {
		if ( empty ( $start_date ) ) {
			$start_date = self::current_date();
		}
		if ( ! is_numeric( $start_date ) ) {
			$start_date = strtotime( $start_date );
		}
		$result = $start_date;

		if ( is_numeric( $period_unit ) && $period_unit > 0 ) {
			$days = self::get_period_in_days( $period_unit, $period_type );
			$result = strtotime( '+' . $days . 'days', $start_date );

			if ( false === $result ) {
				$result = $start_date;
			}
		}

		return apply_filters(
			'ms_helper_period_add_interval',
			gmdate( self::PERIOD_FORMAT, $result )
		);
	}

	/**
	 * Subtract a period interval to a date.
	 *
	 * @since  1.0.0
	 *
	 * @param int $period_unit The period unit to subtract.
	 * @param string $period_type The period type to subtract.
	 * @param string|int $start_date The start date to subtract to.
	 * @return string The subtracted date.
	 */
	public static function subtract_interval( $period_unit, $period_type, $start_date = null ) {
		if ( empty ( $start_date ) ) {
			$start_date = self::current_date();
		}
		if ( ! is_numeric( $start_date ) ) {
			$start_date = strtotime( $start_date );
		}
		$result = $start_date;

		if ( is_numeric( $period_unit ) && $period_unit > 0 ) {
			$days = self::get_period_in_days( $period_unit, $period_type );
			$result = strtotime( '-' . $days . 'days', $start_date );

			if ( false === $result ) {
				$result = $start_date;
			}
		}

		return apply_filters(
			'ms_helper_period_subtract_interval',
			gmdate( self::PERIOD_FORMAT, $result )
		);
	}

	/**
	 * Subtract dates.
	 *
	 * Return (end_date - start_date) in period_type format
	 *
	 * @since  1.0.0
	 *
	 * @param  Date $end_date The end date to subtract from in the format yyyy-mm-dd
	 * @param  Date $start_date The start date to subtraction the format yyyy-mm-dd
	 * @param  int $precission Time constant HOURS_IN_SECONDS will return the
	 *         difference in hours. Default is DAY_IN_SECONDS (return = days).
	 * @param  bool $real_diff If set to true then the result is negative if
	 *         enddate is before startdate. Default is false, which will return
	 *         the absolute difference which is always positive.
	 * @return int The resulting difference of the date subtraction.
	 */
	public static function subtract_dates( $end_date, $start_date, $precission = null, $real_diff = false ) {
		if ( empty( $end_date ) ) {
			// Empty end date is assumed to mean "never"
			$end_date = '2999-12-31';
		}

		$end_date = new DateTime( $end_date );
		$start_date = new DateTime( $start_date );

		if ( ! is_numeric( $precission ) || $precission <= 0 ) {
			$precission = DAY_IN_SECONDS;
		}

		$result = intval(
			( $end_date->format( 'U' ) - $start_date->format( 'U' ) ) /
			$precission
		);

		if ( ! $real_diff ) {
			$result = abs( $result );
		}

		return apply_filters(
			'ms_helper_period_subtract_dates',
			$result
		);
	}

	/**
	 * Checks two things: First if the_date is a valid date and second if
	 * the_date occurs AFTER any other date in the arguments list.
	 *
	 * This function can be used to compare multiple dates, like
	 * $valid = is_after( $today, $date1, $date2, $date3 );
	 *
	 * @since  1.0.0
	 *
	 * @param  string|Date $the_date Date value that is compared with other dates.
	 * @param  string|Date $before_1 Comparison Date 1
	 * @param  ...
	 * @return bool True means that the_date is valid and after all other dates.
	 */
	public static function is_after( $the_date, $before_1 ) {
		$result = true;

		if ( ! is_numeric( $the_date ) ) {
			$the_date = strtotime( $the_date );
		}

		if ( empty( $the_date ) ) {
			// No valid date specified. Fail.
			$result = false;
		} else {
			// Valid date specified, compare with other params.
			$dates = func_get_args();

			// Remove the_date from the param list
			array_shift( $dates );

			foreach ( $dates as $comp_date ) {
				if ( ! is_numeric( $comp_date ) ) {
					$comp_date = strtotime( $comp_date );
				}

				// The date param is invalid, skip comparison.
				if ( empty( $comp_date ) ) { continue; }

				if ( $comp_date > $the_date ) {
					// Comparison date is bigger (=after) the_date. Fail.
					$result = false;
					break;
				}
			}
		}

		return $result;
	}

	/**
	 * Return current date.
	 *
	 * @since  1.0.0
	 * @param  string $format A valid php date format. Default value is 'Y-m-d'.
	 * @return string The current date.
	 */
	public static function current_date( $format = null, $ignore_filters = false ) {
		static $Date = array();
		$key = (string) $format . (int) $ignore_filters;

		if ( ! isset( $Date[$key] ) ) {
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
			$Date[$key] = $date;
		}

		return $Date[$key];
	}

	/**
	 * Return current timestamp.
	 *
	 * @since  1.0.0
	 * @param  string $format [ mysql | timestamp | date-format like 'Y-m-d' ].
	 * @return string The current timestamp.
	 */
	public static function current_time( $format = 'mysql', $ignore_filters = false ) {
		static $Time = array();
		$key = (string) $format . (int) $ignore_filters;

		if ( ! isset( $Time[$key] ) ) {
			$time = current_time( $format, 1 );

			if ( ! $ignore_filters ) {
				$time = apply_filters(
					'ms_helper_period_current_time',
					$time
				);
			}

			$Time[$key] = $time;
		}

		return $Time[$key];
	}

	/**
	 * Return the existing period types.
	 *
	 * @since  1.0.0
	 *
	 * @param string $type [all|singular|plural]
	 * @return array The period types and descriptions.
	 */
	public static function get_period_types( $type = 'all' ) {
		$singular = array(
			'1' . self::PERIOD_TYPE_DAYS => __( 'one day', 'membership2' ),
			'1' . self::PERIOD_TYPE_WEEKS => __( 'one week', 'membership2' ),
			'1' . self::PERIOD_TYPE_MONTHS => __( 'one month', 'membership2' ),
			'1' . self::PERIOD_TYPE_YEARS => __( 'one year', 'membership2' ),
			'1-' . self::PERIOD_TYPE_DAYS => __( 'day', 'membership2' ),
			'1-' . self::PERIOD_TYPE_WEEKS => __( 'week', 'membership2' ),
			'1-' . self::PERIOD_TYPE_MONTHS => __( 'month', 'membership2' ),
			'1-' . self::PERIOD_TYPE_YEARS => __( 'year', 'membership2' ),
		);
		$plural = array(
			self::PERIOD_TYPE_DAYS => __( 'days', 'membership2' ),
			self::PERIOD_TYPE_WEEKS => __( 'weeks', 'membership2' ),
			self::PERIOD_TYPE_MONTHS => __( 'months', 'membership2' ),
			self::PERIOD_TYPE_YEARS => __( 'years', 'membership2' ),
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
	 * @since  1.0.0
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
			$type
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

		if ( 1 == $period_unit ) {
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
	 * @since  1.0.0
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

		$result = self::get_date_time_value( $format, strtotime( $date ), true, true );

		return apply_filters(
			'ms_format_date',
			$result,
			$date,
			$format
		);
	}

	public static function get_date_time_value( $format = null, $timestamp = false, $date = true, $time = false, $gmt = true, $zone = false ){
		$res = '';

		if ( empty( $format ) ) {
			$format = get_option( 'date_format' );
		}

		if ( $timestamp == false ) {
		    $str = current_time( 'timestamp' );
		}

		if ( $date ) {
		    $res .= date_i18n( $format, $timestamp );
		}

		if ( $time ) {
			$zone_setting = floatval( get_option( 'gmt_offset' ) );

			if ( $gmt ) {
				$gm_offset = $zone_setting * 3600;
			} else {
				$gm_offset = 0;
			}
			$res .= ' ' . date_i18n( get_option( 'time_format' ), $timestamp + $gm_offset );

			if ( $zone ) {
				if ( $zone_setting ) {
					$res .= ' UTC';
				} else {
			    	$res .= ' UTC ' . ( $zone_setting > 0 ? '+ ' : '- ' ) . $zone_setting;
				}
			}
		}

		return $res;
	}
}