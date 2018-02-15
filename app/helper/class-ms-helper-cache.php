<?php
/**
 * This Helper creates additional utility functions.
 *
 * @since  1.1.3
 * @package Membership2
 * @subpackage Helper
 */
class MS_Helper_Cache extends MS_Helper {

	const CACHE_GROUP = 'ms_helper_cache';

	/**
	 * Check if query cache is enabled
	 * 
	 * @param bool $default_enable - Optional enable b default
	 * 
	 * @since 1.1.3
	 * 
	 * @return bool
	 */
	public static function is_query_cache_enabled( $default_enable = false ) {
		if ( !$default_enable ) {
			$simulate = MS_Factory::load( 'MS_Model_Simulate' );
			if ( !$simulate->is_simulating() ) {
				$settings = MS_Factory::load( 'MS_Model_Settings' );
				return $settings->enable_query_cache;
			}
			return false;
		}
		return true;
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
	 * @param bool $default_enable - Optional enable b default
	 */
	public static function query_cache( $results, $key, $default_enable = false ) {
		if ( self::is_query_cache_enabled( $default_enable ) ) {
			$duration = 12 * HOUR_IN_SECONDS;
			if ( defined( 'MS_QUERY_CACHE_DURATION' ) && is_int( MS_QUERY_CACHE_DURATION ) ) {
				$duration = MS_QUERY_CACHE_DURATION;
			}
			wp_cache_set( $key, $results, self::CACHE_GROUP, $duration );
		}
	}

	/**
	 * Wrapper to get an cache value (regards network-wide protection mode)
	 *
	 * @since  1.1.3
	 * 
	 * @param  string $key cache Key
	 * @param bool $default_enable - Optional enable b default
	 * 
	 * @return mixed cache value
	 */
	public static function get_transient( $key, $default_enable = false ) {
		$results = wp_cache_get( $key, self::CACHE_GROUP );
		if ( self::is_query_cache_enabled( $default_enable ) && !empty( $results ) ) {
			return $results;
		} else {
			self::delete_transient( $key );
		}
		return false;
	}

	/**
	 * Delete cache
	 *
	 * @since  1.1.3
	 * @param  string $key cache Key
	 */
	public static function delete_transient( $key ) {
		wp_cache_delete( $key, self::CACHE_GROUP );
	}
}
?>