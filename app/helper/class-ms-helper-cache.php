<?php
/**
 * This Helper creates additional utility functions.
 *
 * @since  1.1.3
 * @package Membership2
 * @subpackage Helper
 */
class MS_Helper_Cache extends MS_Helper {

	/**
	 * Check if query cache is enabled
	 * 
	 * @since 1.1.3
	 * 
	 * @return bool
	 */
	public static function is_query_cache_enabled() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		return $settings->enable_query_cache;
	}

	/**
	 * Generate cache key
	 * Used especially if there are pages
	 * 
	 * @since 1.1.3
	 * 
	 * @param string $name - cache name
	 * @param array|null $args - query args
	 * 
	 * @return string $name
	 */
	public static function generate_cache_key( $name, $args = null ) {
		if ( !is_null( $args ) && is_array( $args ) ) {
			if ( isset( $args['page'] ) ) {
				$name = $name . '_' . $args['page'];
			}
		}
		return $name;
	}

	/**
	 * Query cache
	 * Only cache for query results
	 * 
	 * @since 1.1.3
	 * 
	 * @param object $results - the query results
	 * @param string $key - query key
	 */
	public static function query_cache( $results, $key ) {
		if ( self::is_query_cache_enabled() ) {
			$duration = 12 * HOUR_IN_SECONDS;
			if ( defined( 'MS_QUERY_CACHE_DURATION' ) && is_int( MS_QUERY_CACHE_DURATION ) ) {
				$duration = MS_QUERY_CACHE_DURATION;
			}
			MS_Factory::set_transient( $key, $results, $duration );
		}
	}

	/**
	 * Wrapper to get an transient value (regards network-wide protection mode)
	 *
	 * @since  1.1.3
	 * 
	 * @param  string $key Transient Key
	 * 
	 * @return mixed Transient value
	 */
	public static function get_transient( $key ) {
		$results = MS_Factory::get_transient( $key );
		if ( self::is_query_cache_enabled() && !empty( $results ) ) {
			return $results;
		} else {
			self::delete_transient( $key );
		}
		return false;
	}

	/**
	 * Delete transient
	 *
	 * @since  1.1.3
	 * @param  string $key Transient Key
	 */
	public static function delete_transient( $key ) {
		MS_Factory::delete_transient( $key );
	}
}
?>