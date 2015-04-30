<?php
/**
 * This file defines the MS_Helper_Debug class.
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

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

if ( ! defined( 'WDEV_DEBUG' ) ) {
	define( 'WDEV_DEBUG', false );
}

/**
 * This Helper creates utility functions for debugging.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Helper_Debug extends MS_Helper {
	/**
	 * Logs errors to WordPress debug log.
	 *
	 * The following constants ned to be set in wp-config.php
	 * or elsewhere where turning on and off debugging makes sense.
	 *
	 *     // Essential
	 *     define('WP_DEBUG', true);
	 *     // Enables logging to /wp-content/debug.log
	 *     define('WP_DEBUG_LOG', true);
	 *     // Force debug messages in WordPress to be turned off (using logs instead)
	 *     define('WP_DEBUG_DISPLAY', false);
	 *
	 * @since 1.0.0
	 * @param  mixed $message Array, object or text to output to log.
	 */
	public static function log( $message, $echo_file = false ) {
		if ( ! WP_DEBUG && ! WDEV_DEBUG ) { return; }

		if ( defined( 'DEBUG_BACKTRACE_IGNORE_ARGS' ) ) {
			$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		} else {
			$trace = debug_backtrace();
		}

		$exception = new Exception();
		$debug = array_shift( $trace );
		$caller = array_shift( $trace );
		$exception = $exception->getTrace();
		$callee = array_shift( $exception );

		$msg = isset( $caller['class'] ) ? $caller['class'] . '[' . $callee['line'] . ']: ' : '';

		if ( is_array( $message ) || is_object( $message ) ) {
			$msg .= print_r( $message, true );
		} else {
			$msg .= $message;
		}

		if ( $echo_file ) {
			$msg .= "\nIn " . $callee['file'] . ' on line ' . $callee['line'];
		}

		error_log( $msg );
	}

	public static function debug_trace( $return = false ) {
		if ( ! WP_DEBUG && ! WDEV_DEBUG ) { return; }

		$traces = debug_backtrace();
		$fields = array(
			'file',
			'line',
			'function',
			'class',
		);
		$log = array( '---------------------------- Trace start ----------------------------' );

		foreach ( $traces as $i => $trace ) {
			$line = array();
			foreach ( $fields as $field ) {
				if ( ! empty( $trace[ $field ] ) ) {
					$line[] = "$field: {$trace[ $field ]}";
				}
			}
			$log[] = "  [$i]". implode( '; ', $line );
		}

		if ( $return ) {
			return implode( "\n", $log );
		} else {
			error_log( implode( "\n", $log ) );
		}
	}

}

MS_Helper_Debug::log( '**************************** REQUEST START ****************************' );
MS_Helper_Debug::log( '***** URL: ' . lib2()->net->current_url() );
