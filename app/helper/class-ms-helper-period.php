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
	
	
	public static function add_interval( $period_unit, $period_type, $start_date ) {
		if( empty ( $start_date ) ) {
			$start_date = date( self::PERIOD_FORMAT );
		}
		
		$end_dt = strtotime( '+' . $period_unit . $period_type , strtotime( $start_date ) ); 
		if ( $end_dt === false) {
			throw new Exception( 'error add_interval' );
		} 
		return date( self::PERIOD_FORMAT, $end_dt ); 
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
}