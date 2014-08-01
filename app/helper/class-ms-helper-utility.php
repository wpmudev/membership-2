<?php
/**
 * This file defines the MS_Helper_Utility class.
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
 * This Helper creates additional utility functions.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Helper
 */
class MS_Helper_Utility extends MS_Helper {
	
	/**
	 * Implements a multi-dimensional array_intersect_assoc.
	 *
	 * The standard array_intersect_assoc does the intersection based on string values of the keys.
	 * We need to find a way to recursively check multi-dimensional arrays.
	 *
	 * Note that we are not passing values here but references.
	 *
	 * @since 4.0.0
	 * @param  mixed $arr1 First array to intersect.
	 * @param  mixed $arr2 Second array to intersect.	
	 */
	public static function array_intersect_assoc_deep(&$arr1, &$arr2) {
		
		// If not arrays, at least associate the strings this gives the recursive answer
		// If 1 argument is an array and the other not throw error.
		if ( ! is_array( $arr1 ) && ! is_array( $arr2 ) ) {
	        return (string) $arr1 == (string) $arr2 ? (string) $arr1 : false;
		} elseif ( ! is_array( $arr1 ) && is_array( $arr2 ) ) {
			MS_Helper_Debug::log( __( "WARNING: MS_Helper_Utility::array_intersect_assoc_deep() Expected parameter 1 to be an array.", MS_TEXT_DOMAIN ), true );
			return false;
		} elseif ( ! is_array( $arr2 ) && is_array( $arr1 ) ) {
			MS_Helper_Debug::log( __( "WARNING: MS_Helper_Utility::array_intersect_assoc_deep() Expected parameter 2 to be an array.", MS_TEXT_DOMAIN ), true );
			return false;			
		}
		
	    $intersections = array_intersect( array_keys( $arr1 ), array_keys( $arr2 ) );
	    
		$assoc_array = array();
		
		// Time to recursively run through the arrays
	    foreach ( $intersections as $key ) {
			$result = MS_Helper_Utility::array_intersect_assoc_deep( $arr1[ $key ], $arr2[ $key ] );
			if ( $result ) {
				$assoc_array[ $key ] = $result;
			}
	    }
		
	    return apply_filters( 'ms_helper_utility_array_intersect_assoc_deep', $assoc_array );
	}
	
	/**
	 * Get the current page url.
	 * 
	 * @return string The url.
	 */
	public static function get_current_page_url() {
		$current_page_url = @$_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
		if ( $_SERVER['SERVER_PORT'] != '80' ) {
			$current_page_url .= $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['REQUEST_URI'];
		}
		else {
			$current_page_url .= $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		}
		return apply_filters( 'ms_helper_utility_get_current_page_url', $current_page_url );
	}

	/**
	 * Returns user IP address.
	 *
	 * @since 4.0.0
	 *
	 * @static
	 * @access protected
	 * @return string Remote IP address on success, otherwise FALSE.
	 */
	protected static function get_remote_ip() {
		$flag = ! WP_DEBUG ? FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE : null;
		$keys = array(
				'HTTP_CLIENT_IP',
				'HTTP_X_FORWARDED_FOR',
				'HTTP_X_FORWARDED',
				'HTTP_X_CLUSTER_CLIENT_IP',
				'HTTP_FORWARDED_FOR',
				'HTTP_FORWARDED',
				'REMOTE_ADDR',
		);
	
		$remote_ip = false;
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) === true ) {
				foreach ( array_filter( array_map( 'trim', explode( ',', $_SERVER[$key] ) ) ) as $ip ) {
					if ( filter_var( $ip, FILTER_VALIDATE_IP, $flag ) !== false ) {
						$remote_ip = $ip;
						break;
					}
				}
			}
		}
	
		return $remote_ip;
	}
}
