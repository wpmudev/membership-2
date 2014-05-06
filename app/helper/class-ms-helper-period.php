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
	
	
	public static function add_interval( $period_unit, $period_type, $start_date = null ) {
		if( empty ( $start_date ) ) {
			$start_date = date( self::PERIOD_FORMAT );
		}
		
		$end_dt = strtotime( '+' . $period_unit . $period_type , strtotime( $start_date ) ); 
		if ( $end_dt === false) {
			throw new Exception( 'error add_interval' );
		} 
		return date( self::PERIOD_FORMAT, $end_dt ); 
	}
	
	/**
	 * Subtract dates.
	 * 
	 * Return (end_date - start_date) in period_type format
	 *  
	 * @param Date $end_date The end date to subtract from in the format yyyy-mm-dd
	 * @param Date $start_date The start date to subtractin the format yyyy-mm-dd
	 * @return string The resulting of the date subtraction.
	 */
	public static function subtract_dates( $end_date, $start_date ) {
		$end_date = new DateTime( $end_date );
		$start_date = new DateTime( $start_date );
		$interval = $start_date->diff( $end_date );
		
		return $interval;
	}
	
	public static function current_date() {
		return date( self::PERIOD_FORMAT );
	}
	
	public static function get_periods() {
		return array (
				self::PERIOD_TYPE_DAYS => __( self::PERIOD_TYPE_DAYS, MS_TEXT_DOMAIN ),
				self::PERIOD_TYPE_WEEKS =>__( self::PERIOD_TYPE_WEEKS, MS_TEXT_DOMAIN ),
				self::PERIOD_TYPE_MONTHS => __( self::PERIOD_TYPE_MONTHS, MS_TEXT_DOMAIN ),
				self::PERIOD_TYPE_YEARS => __( self::PERIOD_TYPE_YEARS, MS_TEXT_DOMAIN ),
		);
	}
	
	public static function get_period_units() {
		$period_units = array();
		for( $i = 1; $i <= 365; $i++ ){
			$period_units[ $i ] = $i;
		}
		return $period_units;
	}
}