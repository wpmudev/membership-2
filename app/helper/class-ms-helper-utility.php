<?php
/**
 * This Helper creates additional utility functions.
 *
 * @since  1.0.0
 * @package Membership2
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @return string The URL.
	 */
	public static function get_current_url( $force_ssl = false ) {
		static $Url = null;

		if ( null === $Url ) {
			$Url = 'http://';

			if ( $force_ssl ) {
				$Url = 'https://';
			} elseif ( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] ) {
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @param  string $key Some name/ID value
	 * @return string HTML color code (#123456)
	 */
	public static function color_index( $key ) {
		static $Colors = array();
		$key = strtolower( $key );

		if ( ! isset( $Colors[$key] ) ) {
			$base_hash = md5( $key );

			$h = hexdec( substr( $base_hash, 0, 2 ) ) +
				hexdec( substr( $base_hash, 10, 2 ) ) +
				hexdec( substr( $base_hash, 20, 2 ) );

			$s = 35 + ( hexdec( substr( $base_hash, 1, 2 ) ) % 15 );

			$l = 45 + ( hexdec( substr( $base_hash, 2, 2 ) ) % 15 );

			$Colors[$key] = self::hsl2web( $h, $s / 100, $l / 100 );
		}

		return apply_filters(
                            'ms_helper_color_index',
                            $Colors[$key],
                            $key
                        );
	}

	/**
	 * Takes Hue/Saturation/Lightness color definition and returns a hex color code
	 *
	 * @since  1.0.0
	 * @param  float $h
	 * @param  float $s
	 * @param  float $l
	 * @return string
	 */
	static public function hsl2web( $h, $s, $l ){
		$r = 0; $g = 0; $b = 0;

		$h %= 360;
		$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
		$x = $c * ( 1 - abs( fmod( ( $h / 60 ), 2 ) - 1 ) );
		$m = $l - ( $c / 2 );

		if ( $h < 60 ) {
			$r = $c; $g = $x; $b = 0;
		} else if ( $h < 120 ) {
			$r = $x; $g = $c; $b = 0;
		} else if ( $h < 180 ) {
			$r = 0; $g = $c; $b = $x;
		} else if ( $h < 240 ) {
			$r = 0; $g = $x; $b = $c;
		} else if ( $h < 300 ) {
			$r = $x; $g = 0; $b = $c;
		} else {
			$r = $c; $g = 0; $b = $x;
		}

		$r = str_pad( dechex( ( $r + $m ) * 255 ), 2, '0', STR_PAD_LEFT );
		$g = str_pad( dechex( ( $g + $m ) * 255 ), 2, '0', STR_PAD_LEFT );
		$b = str_pad( dechex( ( $b + $m  ) * 255 ), 2, '0', STR_PAD_LEFT );

		return '#' . $r . $g . $b;
	}

	/**
	 * Determine if the user currently is on the specified URL
	 *
	 * @since  1.0.0
	 *
	 * @param  string $url The URL to check
	 * @return bool
	 */
	static public function is_current_url( $url, $ignore = array( 'paged' ) ) {
		static $Cur_url = null;

		if ( null === $Cur_url ) {
			$query_string = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_QUERY );
			parse_str( $query_string, $Cur_url );
		}
		foreach ( $ignore as $param ) { unset( $Cur_url[$param] ); }

		$query_string = parse_url( $url, PHP_URL_QUERY );
		parse_str( $query_string, $params );
		foreach ( $ignore as $param ) { unset( $params[$param] ); }

		return ( $params == $Cur_url );
	}

	/**
	 * Returns the *correct* home-url for front-end pages.
	 *
	 * By default the home_url() function ignores the is_ssl() flag when it's
	 * called from the admin-dashboard. So when redirecting from dashboard to
	 * the front-page it will usually always redirect to http:// even when the
	 * front-end is on https:// - this function fixes this.
	 *
	 * @since  1.0.1.2
	 * @param  string $path Argument passed to the home_url() function.
	 * @return string The correct URL for a front-end page.
	 */
	static public function home_url( $path = '' ) {
		return self::get_home_url( null, $path );
	}

	/**
	 * Returns the *correct* home-url for front-end pages of a given site.
	 *
	 * {@see description of home_url above for details}
	 *
	 * @since  1.0.1.2
	 * @param  int $blog_id Blog-ID; by default the current blog is used.
	 * @param  string $path Argument passed to the home_url() function.
	 * @return string The correct URL for a front-end page.
	 */
	static public function get_home_url( $blog_id = null, $path = '' ) {
		$schema = is_ssl() ? 'https' : 'http';
		$url = get_home_url( $blog_id, $path, $schema );

		return apply_filters(
			'ms_helper_home_url',
			$url,
			$blog_id,
			$path,
			$schema
		);
	}

	/**
	 * Build URL From parts
	 *
	 * @since 1.0.3.7
	 *
	 * @return String
	 */
	static public function build_url( $parts = array() ) {
		if ( empty( $parts ) ){
			return "";
		}

		return ( isset($parts['scheme'] ) ? "{$parts['scheme']}:" : '' ) . 
				( (isset($parts['user'] ) || isset( $parts['host'] ) ) ? '//' : '' ) . 
				( isset($parts['user'] ) ? "{$parts['user']}" : '' ) . 
				( isset($parts['pass'] ) ? ":{$parts['pass']}" : '' ) . 
				( isset($parts['user'] ) ? '@' : '' ) . 
				( isset($parts['host'] ) ? "{$parts['host']}" : '' ) . 
				( isset($parts['port'] ) ? ":{$parts['port']}" : '' ) . 
				( isset($parts['path'] ) ? "{$parts['path']}" : '' ) . 
				( isset($parts['query'] ) ? "?{$parts['query']}" : '' ) . 
				( isset($parts['fragment'] ) ? "#{$parts['fragment']}" : '' );
	}
}

if ( ! function_exists( 'array_unshift_assoc' ) ) {
	/**
	 * Appends an item to the beginning of an associative array while preserving
	 * the array keys.
	 *
	 * @since  1.0.0
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

if ( ! function_exists( 'ms_home_url' ) ) {
	/**
	 * Returns an URL for a front-end page with the correct URL schema.
	 *
	 * @since  1.0.1.2
	 * @param  string $path Argument passed to the home_url() function.
	 * @return string The correct URL for a front-end page.
	 */
	function ms_home_url( $path ) {
		return MS_Helper_Utility::home_url( $path );
	}
}

if ( ! function_exists( 'ms_get_home_url' ) ) {
	/**
	 * Returns an URL for a front-end page with the correct URL schema.
	 *
	 * @since  1.0.1.2
	 * @param  int $blog_id Blog-ID; by default the current blog is used.
	 * @param  string $path Argument passed to the home_url() function.
	 * @return string The correct URL for a front-end page.
	 */
	function ms_get_home_url( $blog_id, $path ) {
		return MS_Helper_Utility::get_home_url( $blog_id, $path );
	}
}
