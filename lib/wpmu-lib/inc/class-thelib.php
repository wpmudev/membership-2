<?php
/**
 * Implement uniform data storage and sharing among all child classes.
 *
 * @since  1.1.0
 */
abstract class TheLib {

	/**
	 * Internal data collection used to pass arguments to callback functions.
	 * Only used for 5.2 version as alternative to closures.
	 *
	 * @var array
	 * @internal
	 */
	static protected $data = array();

	/**
	 * Back-reference to the main component: TheLib_Core
	 *
	 * @var TheLib_Core
	 * @internal
	 */
	static protected $core = null;


	/**
	 * Checks if a key exists in the request-cache.
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $key The key to check
	 * @return bool
	 */
	protected function _have( $key ) {
		return isset( self::$data[ $key ] );
	}

	/**
	 * Request cache
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $key
	 * @param  mixed $value
	 */
	protected function _add( $key, $value ) {
		if ( ! isset( self::$data[ $key ] )
			|| ! is_array( self::$data[ $key ] )
		) {
			self::$data[ $key ] = array();
		}

		self::$data[ $key ][] = $value;
	}

	/**
	 * Request cache
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $key
	 * @return mixed
	 */
	protected function _get( $key ) {
		if ( ! isset( self::$data[ $key ] )
			|| ! is_array( self::$data[ $key ] )
		) {
			self::$data[ $key ] = array();
		}

		return self::$data[ $key ];
	}

	/**
	 * Request cache
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $key
	 */
	protected function _clear( $key ) {
		self::$data[ $key ] = array();
	}


	// --- Start of Session access

	/**
	 * Flag if we can use the $_SESSION variable
	 *
	 * @var bool
	 * @internal
	 */
	static protected $_have_session = null;

	/**
	 * Session storage
	 *
	 * @since  1.1.0
	 * @internal
	 */
	static private function _sess_init() {
		if ( null !== self::$_have_session ) { return; }

		self::$_have_session = false;

		if ( defined( 'WDEV_USE_SESSION' ) ) {
			$use_session = WDEV_USE_SESSION;
		} else {
			$use_session = true;
		}
		$use_session = apply_filters( 'wdev_lib-use_session', $use_session );

		if ( ! session_id() ) {
			if ( ! $use_session ) { return false; }

			if ( ! headers_sent() ) {
				/**
				 * Fix for IE: This is a privacy policy which states, that we do
				 * not collect personal contact information without consent.
				 * Without this declaraion IE might not save our session!
				 *
				 * Note that other plugins that output this header later will
				 * overwrite it! So this is a default value if no other file
				 * sends the P3P header.
				 *
				 * @since  3.0.0
				 */
				if ( WDEV_SEND_P3P ) {
					$p3p_done = false;
					foreach ( headers_list() as $header ) {
						if ( false !== stripos( $header, 'P3P:' ) ) {
							$p3p_done = true;
							break;
						}
					}
					if ( ! $p3p_done ) { header( 'P3P:' . WDEV_SEND_P3P ); }
				}

				session_start();
				self::$_have_session = true;
			}
		} else {
			self::$_have_session = true;
		}

		return true;
	}

	/**
	 * Session storage
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $key
	 * @return bool
	 */
	static protected function _sess_have( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return false; }

		return isset( $_SESSION[ '_lib_persist_' . $key ] );
	}

	/**
	 * Session storage
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $key
	 * @param  mixed $value
	 */
	static protected function _sess_add( $key, $value ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return; }

		if ( ! isset( $_SESSION[ '_lib_persist_' . $key ] )
			|| ! is_array( $_SESSION[ '_lib_persist_' . $key ] )
		) {
			$_SESSION[ '_lib_persist_' . $key ] = array();
		}

		$_SESSION[ '_lib_persist_' . $key ][] = $value;
	}

	/**
	 * Session storage
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $key
	 * @return array
	 */
	static protected function _sess_get( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return array(); }

		if ( ! isset( $_SESSION[ '_lib_persist_' . $key ] )
			|| ! is_array( $_SESSION[ '_lib_persist_' . $key ] )
		) {
			$_SESSION[ '_lib_persist_' . $key ] = array();
		}

		return $_SESSION[ '_lib_persist_' . $key ];
	}

	/**
	 * Session storage
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $key
	 */
	static protected function _sess_clear( $key ) {
		if ( null === self::$_have_session ) { self::_sess_init(); }
		if ( ! self::$_have_session ) { return; }

		unset( $_SESSION[ '_lib_persist_' . $key ] );
	}

	// --- End of Session access

	/**
	 * Base constructor. Initialize the session if not already done.
	 *
	 * @since 1.1.0
	 * @internal
	 */
	public function __construct() {
		self::_init_const();
		self::_sess_init();
	}

	/**
	 * Initializes missing module-flags with default values.
	 *
	 * @since  3.0.0
	 * @internal
	 */
	static protected function _init_const() {
		if ( ! defined( 'WDEV_UNMINIFIED' ) ) {
			define( 'WDEV_UNMINIFIED', false );
		}
		if ( ! defined( 'WDEV_DEBUG' ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				define( 'WDEV_DEBUG', true );
			} else {
				define( 'WDEV_DEBUG', false );
			}
		}
		if ( ! defined( 'WDEV_AJAX_DEBUG' ) ) {
			define( 'WDEV_AJAX_DEBUG', WDEV_DEBUG );
		}
		if ( ! defined( 'WDEV_SEND_P3P' ) ) {
			define( 'WDEV_SEND_P3P', 'CP="NOI"' );
		}
	}

	/**
	 * Returns the full URL to an internal CSS file of the code library.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  string $file The filename, relative to this plugins folder.
	 * @return string
	 */
	protected function _css_url( $file ) {
		static $Url = null;

		if ( WDEV_UNMINIFIED ) {
			$file = str_replace( '.min.css', '.css', $file );
		}
		if ( null === $Url ) {
			$Url = plugins_url( 'css/', dirname( __FILE__ ) );
		}

		return $Url . $file;
	}

	/**
	 * Returns the full URL to an internal JS file of the code library.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  string $file The filename, relative to this plugins folder.
	 * @return string
	 */
	protected function _js_url( $file ) {
		static $Url = null;

		if ( WDEV_UNMINIFIED ) {
			$file = str_replace( '.min.js', '.js', $file );
		}
		if ( null === $Url ) {
			$Url = plugins_url( 'js/', dirname( __FILE__ ) );
		}

		return $Url . $file;
	}

	/**
	 * Returns the full path to an internal php partial of the code library.
	 *
	 * @since  1.0.0
	 * @internal
	 *
	 * @param  string $file The filename, relative to this plugins folder.
	 * @return string
	 */
	protected function _view_path( $file ) {
		static $Path = null;

		if ( null === $Path ) {
			$basedir = dirname( dirname( __FILE__ ) ) . '/';
			$Path = $basedir . 'view/';
		}

		return $Path . $file;
	}

	/**
	 * Adds or executes an action.
	 *
	 * @since 1.1.3
	 * @api
	 *
	 * @param string $tag The action-name.
	 * @param string $function Function name (must be a class function)
	 * @param int $priority Execution priority. Lower is earlier.
	 */
	protected function add_action( $tag, $function, $priority = 10 ) {
		$hooked = $this->_have( '_hooked_action-' . $tag );

		if ( did_action( $tag ) ) {
			$this->$function();
		} else {
			$this->_add( '_hooked_action-' . $tag, true );
			add_action( $tag, array( $this, $function ), $priority );
		}
	}
}
