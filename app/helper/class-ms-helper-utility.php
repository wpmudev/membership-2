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
	 * The standard array_intersect_assoc does the intersection based on string
	 * values of the keys.
	 * We need to find a way to recursively check multi-dimensional arrays.
	 *
	 * Note that we are not passing values here but references.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $arr1 First array to intersect.
	 * @param mixed $arr2 Second array to intersect.
	 * @return array Combined array.
	 */
	public static function array_intersect_assoc_deep( &$arr1, &$arr2 ) {
		// If not arrays, at least associate the strings.
		// If 1 argument is an array and the other not throw error.
		if ( ! is_array( $arr1 ) && ! is_array( $arr2 ) ) {
			$arr1 = (string) $arr1;
			$arr2 = (string) $arr2;
			return $arr1 == $arr2 ? $arr1 : false;
		} elseif ( is_array( $arr1 ) !== is_array( $arr2 ) ) {
			MS_Helper_Debug::log(
				'WARNING: MS_Helper_Utility::array_intersect_assoc_deep() - ' .
				'Both params need to be of same type (array or string).',
				true
			);
			return false;
		}

		$intersections = array_intersect(
			array_keys( $arr1 ),
			array_keys( $arr2 )
		);

		$assoc_array = array();

		// Time to recursively run through the arrays
		foreach ( $intersections as $key ) {
			$result = MS_Helper_Utility::array_intersect_assoc_deep(
				$arr1[ $key ],
				$arr2[ $key ]
			);

			if ( $result ) {
				$assoc_array[ $key ] = $result;
			}
		}

		return apply_filters(
			'ms_helper_utility_array_intersect_assoc_deep',
			$assoc_array,
			$arr1,
			$arr2
		);
	}

	/**
	 * Get the current page URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string The URL.
	 */
	public static function get_current_url( $force_ssl = false ) {
		static $Url = null;

		if ( null === $Url ) {
			$Url = 'http://';

			if ( $force_ssl || 'on' == @$_SERVER['HTTPS'] ) {
				$Url = 'https://';
			}

			$Url .= $_SERVER['SERVER_NAME'];
			if ( $_SERVER['SERVER_PORT'] != '80' ) {
				$Url .= ':' . $_SERVER['SERVER_PORT'];
			}
			$Url .= $_SERVER['REQUEST_URI'];

			$Url = apply_filters(
				'ms_helper_utility_get_current_url',
				$Url
			);
		}

		return $Url;
	}

	/**
	 * Replace http protocol to https
	 *
	 * @since 1.0.0
	 *
	 * @param string $url the original url
	 * @return string The changed url.
	 */
	public static function get_ssl_url( $url ) {
		//TODO: There is a Wordpress function that does the same thing...
		//      Remove this function and replace with WP core function.
		return apply_filters(
			'ms_helper_utility_get_ssl_url',
			preg_replace( '|^http://|', 'https://', $url ),
			$url
		);
	}

	/**
	 * Returns user IP address.
	 *
	 * @since 1.0.0
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

	/**
	 * Register a post type.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $post_type Post type ID.
	 * @param  array $args Post type details.
	 */
	public static function register_post_type( $post_type, $args = null ) {
		$defaults = array(
			'public' => false,
			'has_archive' => false,
			'publicly_queryable' => false,
			'supports' => false,
			'hierarchical' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		register_post_type( $post_type, $args );
	}

	/**
	 * Transforms the $key value into a color index. The same key will always
	 * return the same color
	 *
	 * @since  1.1.0
	 * @param  string $key Some name/ID value
	 * @return string HTML color code (#123456)
	 */
	public static function color_index( $key ) {
		static $Colors = array();
		$key = strtolower( $key );

		if ( ! isset( $Colors[$key] ) ) {
			$col_min_avg = 64;
			$col_max_avg = 192;
			$col_step = 16;

			// (192 - 64) / 16 = 8
			// 8 ^ 3 = 512 colors

			$range = $col_max_avg - $col_min_avg;
			$factor = $range / 256;
			$offset = $col_min_avg;

			$base_hash = md5( $key );
			$b_R = hexdec( substr( $base_hash, 0, 2 ) );
			$b_G = hexdec( substr( $base_hash, 2, 2 ) );
			$b_B = hexdec( substr( $base_hash, 4, 2 ) );

			$f_R = floor( ( floor( $b_R * $factor ) + $offset ) / $col_step ) * $col_step;
			$f_G = floor( ( floor( $b_G * $factor ) + $offset ) / $col_step ) * $col_step;
			$f_B = floor( ( floor( $b_B * $factor ) + $offset ) / $col_step ) * $col_step;

			$Colors[$key] = sprintf( '#%02x%02x%02x', $f_R, $f_G, $f_B );
		}

		return $Colors[$key];
	}

}

if ( ! function_exists( 'array_unshift_assoc' ) ) {
	/**
	 * Appends an item to the beginning of an associative array while preserving
	 * the array keys.
	 *
	 * @since  1.0.3
	 * @param  array $arr
	 * @param  scalar $key
	 * @param  mixed $val
	 * @return array
	 */
	function array_unshift_assoc( &$arr, $key, $val ) {
		$arr = array_reverse( $arr, true );
		$arr[$key] = $val;
		return array_reverse( $arr, true );
	}
}
