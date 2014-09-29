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
 * @since 4.0.0
 *
 */
class MS_Helper_Period extends MS_Helper {
	
	const PERIOD_TYPE_DAYS = 'days';
	
	const PERIOD_TYPE_WEEKS = 'weeks';

	const PERIOD_TYPE_MONTHS = 'months';
	
	const PERIOD_TYPE_YEARS = 'years';
	
	const PERIOD_FORMAT = 'Y-m-d';
	
	const DATE_TIME_FORMAT = 'Y-m-d H:i';
	
	const DATE_FORMAT_SHORT = 'y-m-d';
	
	/**
	 * Add a period interval to a date.
	 *
	 * @since 4.0
	 * 
	 * @param int $period_unit The period unit to add.
	 * @param string $period_type The period type to add.
	 * @param string $start_date The start date to add to.
	 * @throws Exception
	 * @return string The added date.
	 */
	public static function add_interval( $period_unit, $period_type, $start_date = null ) {
		if( empty ( $start_date ) ) {
			$start_date = gmdate( self::PERIOD_FORMAT );
		}
		if( self::PERIOD_TYPE_YEARS == $period_type ) {
			$period_unit *= 365;
			$period_type = self::PERIOD_TYPE_DAYS;
		}
		
		$end_dt = strtotime( '+' . $period_unit . $period_type , strtotime( $start_date ) ); 
		if ( $end_dt === false) {
			throw new Exception( 'error add_interval' );
		} 
		return apply_filters( 'ms_helper_period_add_interval', gmdate( self::PERIOD_FORMAT, $end_dt ) ); 
	}
	
	/**
	 * Subtract a period interval to a date.
	 *
	 * @since 4.0
	 * 
	 * @param int $period_unit The period unit to subtract.
	 * @param string $period_type The period type to subtract.
	 * @param string $start_date The start date to subtract to.
	 * @throws Exception
	 * @return string The subtracted date.
	 */
	public static function subtract_interval( $period_unit, $period_type, $start_date = null ) {
		if( empty ( $start_date ) ) {
			$start_date = gmdate( self::PERIOD_FORMAT );
		}
	
		$end_dt = strtotime( '-' . $period_unit . $period_type , strtotime( $start_date ) );
		if ( $end_dt === false) {
			throw new Exception( 'error subtract_interval' );
		}
		return apply_filters( 'ms_helper_period_subtract_interval', gmdate( self::PERIOD_FORMAT, $end_dt ) );
	}
	
	/**
	 * Subtract dates.
	 * 
	 * Return (end_date - start_date) in period_type format
	 *  
	 * @since 4.0
	 *  
	 * @param Date $end_date The end date to subtract from in the format yyyy-mm-dd
	 * @param Date $start_date The start date to subtractin the format yyyy-mm-dd
	 * @return string The resulting of the date subtraction.
	 */
	public static function subtract_dates( $end_date, $start_date ) {
		$end_date = new DateTime( $end_date );
		$start_date = new DateTime( $start_date );

		$days = round( ( $end_date->format( 'U' ) - $start_date->format( 'U' ) ) / ( 60 * 60 * 24 ) );
		
		return apply_filters( 'ms_helper_period_get_periods', $days );
	}
	
	/**
	 * Return current date.
	 * 
	 * @since 4.0
	 *  
	 * @return string The current date.
	 */
	public static function current_date( $format = null, $ignore_filters = false ) {
		if( empty( $format ) ) {
			$format = self::PERIOD_FORMAT;
		}
		
		$format = apply_filters( 'ms_helper_period_current_date_format', $format );
		$date = gmdate( $format );
		
		if( ! $ignore_filters ) {
			$date = apply_filters( 'ms_helper_period_current_date', $date );	
		}
// 		$format = date_i18n( get_option('date_format'), $date );
		return $date;
	}
	
	/**
	 * Return current timestamp.
	 * 
	 * @since 4.0
	 *  
	 * @return string The current date.
	 */
	public static function current_time() {
		return apply_filters( 'ms_helper_period_current_time', current_time( 'mysql', true ) );
	}
	
	/**
	 * Return the existing period types.
	 *
	 * @todo change method name to get_period_types
	 * 
	 * @since 4.0
	 *
	 * @return array The period types and descriptions.
	 */
	public static function get_periods() {
		return apply_filters( 'ms_helper_period_get_periods', array (
				self::PERIOD_TYPE_DAYS => __( self::PERIOD_TYPE_DAYS, MS_TEXT_DOMAIN ),
				self::PERIOD_TYPE_WEEKS =>__( self::PERIOD_TYPE_WEEKS, MS_TEXT_DOMAIN ),
				self::PERIOD_TYPE_MONTHS => __( self::PERIOD_TYPE_MONTHS, MS_TEXT_DOMAIN ),
				self::PERIOD_TYPE_YEARS => __( self::PERIOD_TYPE_YEARS, MS_TEXT_DOMAIN ),
		) );
	}
	
	/**
	 * Get period in days.
	 * 
	 * Convert period in week, month, years to days.
	 * 
	 * @since 4.0
	 *  
	 * @param $period The period to convert.
	 *  
	 * @return int The calculated days.
	 */
	public static function get_period_in_days( $period ) {
		$days = 0;
		switch( $period['period_type'] ) {
			case self::PERIOD_TYPE_DAYS:
				$days = $period['period_unit'];
				break;
			case self::PERIOD_TYPE_WEEKS:
				$days = $period['period_unit'] * 7;
				break;
			case self::PERIOD_TYPE_MONTHS:
				$days = $period['period_unit'] * 30;
				break;
			case self::PERIOD_TYPE_YEARS:
				$days = $period['period_unit'] * 365;
				break;
		}
		return apply_filters( 'ms_helper_period_get_period_in_days', $days, $period );
	}
	
	public static function get_period_value( $period, $field ) {
		$value = null;
		if( isset( $period[ $field ] ) ) {
			$value = $period[ $field ];
		}
		elseif( 'period_unit' == $field ) {
			$value = 1;
		}
		elseif( 'period_type' == $field ) {
			$value = self::PERIOD_TYPE_DAYS;
		}
		return apply_filters( 'ms_helper_period_get_period_value', $value );
	}
	
	public static function get_period_desc( $period ) {
		$period_unit = MS_Helper_Period::get_period_value( $period, 'period_unit' );
		$period_type = MS_Helper_Period::get_period_value( $period, 'period_type' );
		if( abs( $period_unit < 2 ) ) {
			$period_type = preg_replace( '/s$/', '', $period_type );
		}  
		$desc = sprintf( '%s %s', $period_unit, $period_type );
		
		return apply_filters( 'ms_helper_period_get_period_desc', $desc );
	}
}