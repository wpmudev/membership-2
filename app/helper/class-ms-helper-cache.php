<?php

/**
 * This Helper creates additional utility functions.
 *
 * @since      1.1.3
 * @package    Membership2
 * @subpackage Helper
 */
class MS_Helper_Cache extends MS_Helper {

	/**
	 * Cache group name.
	 */
	const CACHE_GROUP = 'ms_helper_cache';

	/**
	 * Cache key for cache version.
	 */
	const CACHE_VERSION_KEY = 'ms_helper_cache_version';

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
		if ( ! $default_enable ) {
			$simulate = MS_Factory::load( 'MS_Model_Simulate' );
			if ( ! $simulate->is_simulating() ) {
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
	 * @param string     $name - cache name
	 * @param array|null $args - query args
	 *
	 * @return string $name
	 */
	public static function generate_cache_key( $name, $args = null ) {
		if ( ! empty( $args ) ) {
			// Create unique string from args.
			$name = $name . '_' . md5( json_encode( $args ) );
		}

		return $name;
	}

	/**
	 * Query cache
	 * Only cache for query results
	 *
	 * @since 1.1.3
	 *
	 * @param object $results        - the query results
	 * @param string $key            - query key
	 * @param bool   $default_enable - Optional enable b default
	 */
	public static function query_cache( $results, $key, $default_enable = false ) {
		if ( self::is_query_cache_enabled( $default_enable ) ) {
			$duration = 12 * HOUR_IN_SECONDS;
			if ( defined( 'MS_QUERY_CACHE_DURATION' ) && is_int( MS_QUERY_CACHE_DURATION ) ) {
				$duration = MS_QUERY_CACHE_DURATION;
			}

			self::set_cache( $key, $results, self::CACHE_GROUP, $duration );
		}
	}

	/**
	 * Wrapper to get an cache value (regards network-wide protection mode)
	 *
	 * @since  1.1.3
	 *
	 * @param  string $key            cache Key
	 * @param bool    $default_enable - Optional enable b default
	 *
	 * @return mixed cache value
	 */
	public static function get_transient( $key, $default_enable = false ) {
		$results = MS_Helper_Cache::get_cache( $key, self::CACHE_GROUP );

		if ( self::is_query_cache_enabled( $default_enable ) && ! empty( $results ) ) {
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
	 *
	 * @param  string $key cache Key
	 */
	public static function delete_transient( $key ) {
		wp_cache_delete( $key, self::CACHE_GROUP );
	}

	/**
	 * Cache flushing wrapper.
	 *
	 * This is here because object cache flushes can be prevented.
	 * If in case wp_cache_flush function is disabled we will try
	 * to flush it directly.
	 *
	 * @since 1.1.6
	 */
	public static function flush_cache() {
		global $wp_object_cache;

		// In some cases
		if ( is_object( $wp_object_cache ) && is_callable( array( $wp_object_cache, 'flush' ) ) ) {
			$wp_object_cache->flush();
		} elseif ( is_callable( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}
	}

	/**
	 * Wrapper for wp_cache_set in M2.
	 *
	 * Set cache using this method so that
	 * we can delete them without flushing
	 * the object cache as whole. This cache can be
	 * deleted using normal wp_cache_delete.
	 *
	 * @param int|string $key    The cache key to use for retrieval later.
	 * @param mixed      $data   The contents to store in the cache.
	 * @param string     $group  Optional. Where to group the cache contents. Enables the same key
	 *                           to be used across groups. Default empty.
	 * @param int        $expire Optional. When to expire the cache contents, in seconds.
	 *                           Default 0 (no expiration).
	 *
	 * @since 1.1.6
	 *
	 * @return bool False on failure, true on success.
	 */
	public static function set_cache( $key, $data, $group = '', $expire = 0 ) {
		// Get the current version.
		$version = wp_cache_get( self::CACHE_VERSION_KEY );

		// In case version is not set, set now.
		if ( empty( $version ) ) {
			// In case version is not set, use default 1.
			$version = 1;

			// Set cache version.
			wp_cache_set( self::CACHE_VERSION_KEY, $version );
		}

		// Add to cache array with version.
		$data = array(
			'data'    => $data,
			'version' => $version,
		);

		// Set to WP cache.
		return wp_cache_set( $key, $data, $group, $expire );
	}

	/**
	 * Wrapper for wp_cache_get function in M2.
	 *
	 * Use this to get the cache values set using set_cache method.
	 *
	 * @param int|string $key     The key under which the cache contents are stored.
	 * @param string     $group   Optional. Where the cache contents are grouped. Default empty.
	 * @param bool       $force   Optional. Whether to force an update of the local cache from the persistent
	 *                            cache. Default false.
	 * @param bool       $found   Optional. Whether the key was found in the cache (passed by reference).
	 *                            Disambiguates a return of false, a storable value. Default null.
	 *
	 * @since 1.1.6
	 *
	 * @return bool|mixed False on failure to retrieve contents or the cache
	 *                      contents on success
	 */
	public static function get_cache( $key, $group = '', $force = false, &$found = null ) {
		// Get the current version.
		$version = wp_cache_get( self::CACHE_VERSION_KEY );
		// Do not continue if version is not set.
		if ( empty( $version ) ) {
			return false;
		}

		// Get the cache value.
		$data = wp_cache_get( $key, $group, $force, $found );

		// Return only data.
		if ( isset( $data['version'] ) && $version === $data['version'] && ! empty( $data['data'] ) ) {
			return $data['data'];
		}

		return false;
	}

	/**
	 * Refresh the whole M2 cache.
	 *
	 * We can not delete the cache by group. So use
	 * this method to refresh the cache using version.
	 *
	 * @since 1.1.6
	 *
	 * @return bool
	 */
	public static function refresh_cache() {
		// Increment the version.
		$inc = wp_cache_incr( self::CACHE_VERSION_KEY );

		return $inc ? true : false;
	}
}

?>