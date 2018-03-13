<?php
/**
 * The Net component.
 * Access via function `lib3()->net`.
 *
 * @since  2.0.0
 */
class TheLib_Net extends TheLib {

	/**
	 * Returns the current URL.
	 * This URL is not guaranteed to look exactly same as the user sees it.
	 * E.g. Hashtags are missing ("index.php#section-a")
	 *
	 * @since  1.0.7
	 * @api
	 *
	 * @param  string $protocol Optional. Define URL protocol ('http', 'https')
	 * @return string Full URL to current page.
	 */
	public function current_url( $protocol = null ) {
		static $Url = array();

		if ( null !== $protocol ) {
			// Remove the "://" part, if it was provided
			$protocol = array_shift( explode( ':', $protocol ) );
		}

		if ( ! isset( $Url[$protocol] ) ) {
			if ( null === $protocol ) {
				$cur_url = 'http';

				if ( isset( $_SERVER['HTTPS'] )
					&& 'on' == strtolower( $_SERVER['HTTPS'] )
				) {
					$cur_url .= 's';
				}
			} else {
				$cur_url = $protocol;
			}

			$is_ssl = (false !== strpos( $cur_url, 'https' ));
			$cur_url .= '://';

			if ( isset( $_SERVER['SERVER_NAME'] ) ) {
				$cur_url .= $_SERVER['SERVER_NAME'];
			} elseif ( defined( 'WP_TESTS_DOMAIN' ) ) {
				$cur_url .= WP_TESTS_DOMAIN;
			}

			if ( ! isset( $_SERVER['SERVER_PORT'] ) ) {
				if ( $is_ssl ) { $_SERVER['SERVER_PORT'] = '443'; }
				else { $_SERVER['SERVER_PORT'] = '80'; }
			}

			if ( ( ! $is_ssl && '80' != $_SERVER['SERVER_PORT'] ) ||
				( $is_ssl && !in_array( $_SERVER['SERVER_PORT'], array( '80', '443' ) ) )
			) {
				$cur_url .= ':' . $_SERVER['SERVER_PORT'];
			}

			if ( empty( $_SERVER['REQUEST_URI'] ) ) {
				$cur_url = trailingslashit( $cur_url );
			} else {
				$cur_url .= $_SERVER['REQUEST_URI'];
			}

			$Url[$protocol] = $cur_url;
		}

		return $Url[$protocol];
	}

	/**
	 * Changes a relative URL to an absolute URL.
	 * This function uses WordPress `home_url()` to expand a relative URL.
	 *
	 * @uses home_url()
	 *
	 * @since  2.0.0
	 * @api
	 *
	 * @param  string $url An URL that can be absolute or relative.
	 * @return string The absolute URL.
	 */
	public function expand_url( $url ) {
		if ( false === strpos( $url, '://' ) ) {
			$url = home_url( $url );
		}

		return $url;
	}

	/**
	 * Retrieves the best guess of the client's actual IP address.
	 * Takes into account numerous HTTP proxy headers due to variations
	 * in how different ISPs handle IP addresses in headers between hops.
	 *
	 * @since 1.1.3
	 * @api
	 *
	 * @return object {
	 *     IP Address details
	 *
	 *     @type string $ip The users IP address (might be spoofed, if $proxy is true)
	 *     @type bool $proxy True, if a proxy was detected
	 *     @type string $proxy_id The proxy-server IP address
	 * }
	 */
	public function current_ip() {
		$result = (object) array(
			'ip' => $_SERVER['REMOTE_ADDR'],
			'proxy' => false,
			'proxy_ip' => '',
		);

		/*
		 * This code tries to bypass a proxy and get the actual IP address of
		 * the visitor behind the proxy.
		 * Warning: These values might be spoofed!
		 */
		$ip_fields = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);
		$forwarded = false;
		foreach ( $ip_fields as $key ) {
			if ( true === array_key_exists( $key, $_SERVER ) ) {
				foreach ( explode( ',', $_SERVER[$key] ) as $ip ) {
					$ip = trim( $ip );

					if ( false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
						$forwarded = $ip;
						break 2;
					}
				}
			}
		}

		// If we found a different IP address then REMOTE_ADDR then it's a proxy!
		if ( ! empty( $forwarded ) && $forwarded != $result->ip ) {
			$result->proxy = true;
			$result->proxy_ip = $result->ip;
			$result->ip = $forwarded;
		}

		return $result;
	}

	/**
	 * Starts a file download and terminates the current request.
	 * Note that this does not work inside Ajax requests!
	 *
	 * @since  1.1.0
	 * @api
	 *
	 * @param  string $contents The file contents (text file).
	 * @param  string $filename The file name.
	 */
	public function file_download( $contents, $filename ) {
		// Send the download headers.
		header( 'Pragma: public' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Cache-Control: private', false ); // required for certain browsers
		header( 'Content-type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Content-Length: ' . strlen( $contents ) );

		// Finally send the export-file content.
		echo $contents;

		exit;
	}

	/**
	 * Checks if the specified URL is publicly reachable.
	 *
	 * @since  1.1.0
	 * @api
	 *
	 * @param  string $url The URL to check.
	 * @return bool If URL is online or not.
	 */
	public function is_online( $url ) {
		static $Checked = array();

		if ( ! isset( $Checked[$url] ) ) {
			$check = 'http://www.isup.me/' . $url;
			$res = wp_remote_get( $check, array( 'decompress' => false ) );

			if ( is_wp_error( $res ) ) {
				$state = false;
			} else {
				$state = ( false === stripos( $res['body'], 'not just you' ) );
			}

			$Checked[$url] = $state;
		}

		return $Checked[$url];
	}

}