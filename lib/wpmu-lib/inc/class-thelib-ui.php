<?php
/**
 * The UI component.
 * Access via function `lib3()->ui`.
 *
 * @since  1.1.4
 */
class TheLib_Ui extends TheLib {

	// Load the main JS module (the wpmUi object) and basic styles.
	const MODULE_CORE = 'core';

	// Load CSS animations.
	const MODULE_ANIMATION = 'animate';

	// Custom select2 integration. JS: wpmUi.upgrade_multiselect()
	const MODULE_SELECT = 'select';

	// Vertical navigation support.
	const MODULE_VNAV = 'vnav';

	// Styles for lib3()->html->element() output.
	const MODULE_HTML = 'html_element';

	// WordPress media gallery support.
	const MODULE_MEDIA = 'media';

	// Fontawesome CSS icons.
	const MODULE_ICONS = 'fontawesome';

	// jQuery datepicker, draggable, jQuery-UI styles.
	const MODULE_JQUERY = 'jquery-ui';

	/**
	 * Class constructor
	 *
	 * @since 1.0.0
	 * @internal
	 */
	public function __construct() {
		parent::__construct();

		// Check for persistent data from last request that needs to be processed.
		$this->add_action(
			'plugins_loaded',
			'_check_admin_notices'
		);
	}

	/**
	 * Enqueue core UI files (CSS/JS).
	 *
	 * Defined modules:
	 *  - core
	 *  - select
	 *  - vnav
	 *  - card-list
	 *  - html-element
	 *  - media
	 *  - fontawesome
	 *  - jquery-ui
	 *
	 * All undefined modules are assumed to be a valid CSS or JS file-name.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $modules The module to load.
	 * @param  string $onpage A page hook; files will only be loaded on this page.
	 */
	public function add( $module = 'core', $onpage = 'all' ) {
		switch ( $module ) {
			case 'core':
				$this->css( $this->_css_url( 'wpmu-ui.3.min.css' ), $onpage );
				$this->js( $this->_js_url( 'wpmu-ui.3.min.js' ), $onpage );
				break;

			case 'animate':
			case 'animation':
				$this->css( $this->_css_url( 'animate.3.min.css' ), $onpage );
				break;

			case 'select':
				$this->css( $this->_css_url( 'select2.3.min.css' ), $onpage );
				$this->js( $this->_js_url( 'select2.3.min.js' ), $onpage );
				break;

			case 'vnav':
				$this->css( $this->_css_url( 'wpmu-vnav.3.min.css' ), $onpage );
				$this->js( $this->_js_url( 'wpmu-vnav.3.min.js' ), $onpage );
				break;

			case 'html-element':
			case 'html_element':
			case 'html':
				$this->css( $this->_css_url( 'wpmu-html.3.min.css' ), $onpage );
				break;

			case 'media':
				$this->js( 'wpmu:media', $onpage );
				break;

			case 'fontawesome':
			case 'icons':
				$this->css( $this->_css_url( 'fontawesome.3.min.css' ), $onpage );
				break;

			case 'jquery-ui':
			case 'jquery':
				$this->js( 'jquery-ui-core', $onpage );
				$this->js( 'jquery-ui-datepicker', $onpage );
				$this->js( 'jquery-ui-draggable', $onpage );
				$this->css( $this->_css_url( 'jquery-ui.wpmui.3.min.css' ), $onpage );
				break;

			default:
				$ext = strrchr( $module, '.' );

				if ( WDEV_UNMINIFIED ) {
					$module = str_replace( '.min' . $ext, $ext, $module );
				}
				if ( '.css' === $ext ) {
					$this->css( $module, $onpage, 20 );
				} else if ( '.js' === $ext ) {
					$this->js( $module, $onpage, 20 );
				}
		}
	}

	/**
	 * Adds a variable to javascript.
	 *
	 * @since 1.0.7
	 * @api
	 *
	 * @param string $name Name of the variable
	 * @param mixed $data Value of the variable
	 */
	public function data( $name, $data ) {
		$this->_add( 'js_data_hook', true );

		// Determine which hook should print the data.
		$hook = ( is_admin() ? 'admin_footer' : 'wp_footer' );

		// Enqueue the data for output with javascript sources.
		$this->_add( 'js_data', array( $name, $data ) );

		$this->add_action( $hook, '_print_script_data' );
	}

	/**
	 * Adds custom javascript to the page footer.
	 *
	 * @since 1.1.3
	 * @api
	 *
	 * @param string $jscript The javascript code.
	 */
	public function script( $jscript ) {
		$this->_add( 'js_data_hook', true );

		// Determine which hook should print the data.
		$hook = ( is_admin() ? 'admin_footer' : 'wp_footer' );

		// Enqueue the data for output with javascript sources.
		$this->_add( 'js_script', $jscript );

		$this->add_action( $hook, '_print_script_code' );
	}

	/**
	 * Enqueue a javascript file.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $url Full URL to the javascript file.
	 * @param  string $onpage A page hook; files will only be loaded on this page.
	 * @param  int $priority Loading order. The higher the number, the later it is loaded.
	 */
	public function js( $url, $onpage = 'all', $priority = 10 ) {
		$this->_prepare_js_or_css( $url, 'js', $onpage, $priority );
	}

	/**
	 * Enqueue a css file.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $url Full URL to the css filename.
	 * @param  string $onpage A page hook; files will only be loaded on this page.
	 * @param  int $priority Loading order. The higher the number, the later it is loaded.
	 */
	public function css( $url, $onpage = 'all', $priority = 10 ) {
		$this->_prepare_js_or_css( $url, 'css', $onpage, $priority );
	}

	/**
	 * Prepare to enqueue a javascript or css file.
	 *
	 * @since  1.0.7
	 * @internal
	 *
	 * @param  string $url Full URL to the javascript/css file.
	 * @param  string $type 'css' or 'js'
	 * @param  string $onpage A page hook; files will only be loaded on this page.
	 * @param  int $priority Loading order. The higher the number, the later it is loaded.
	 */
	protected function _prepare_js_or_css( $url, $type, $onpage, $priority ) {
		$this->_add( 'js_or_css', compact( 'url', 'type', 'onpage', 'priority' ) );

		$this->add_action( 'init', '_add_js_or_css' );
	}

	/**
	 * Returns the JS/CSS handle of the item.
	 * This is a private helper function used by array_map()
	 *
	 * @since  1.0.7
	 * @internal
	 */
	public function _get_script_handle( $item ) {
		if ( ! property_exists( $item, 'handle' ) ) {
			$item->handle = '';
		}

		return $item->handle;
	}

	/**
	 * Enqueues either a css or javascript file
	 *
	 * @since  1.0.0
	 * @internal
	 */
	public function _add_js_or_css() {
		global $wp_styles, $wp_scripts;

		$scripts = $this->_get( 'js_or_css' );
		$this->_clear( 'js_or_css' );

		// Prevent adding the same URL twice.
		$done_urls = array();

		foreach ( $scripts as $script ) {
			extract( $script ); // url, type, onpage, priority

			// Skip Front-End files in Admin Dashboard.
			if ( 'front' === $onpage && is_admin() ) { continue; }

			// Prevent adding the same URL twice.
			if ( in_array( $url, $done_urls ) ) { continue; }
			$done_urls[] = $url;

			$type = ( 'css' === $type || 'style' === $type ? 'css' : 'js' );

			// The $handle values are intentionally not cached:
			// Any plugin/theme could add new handles at any moment...
			$handles = array();
			if ( 'css' == $type ) {
				if ( ! is_a( $wp_styles, 'WP_Styles' ) ) {
					$wp_styles = new WP_Styles();
				}
				$handles = array_values(
					array_map(
						array( $this, '_get_script_handle' ),
						$wp_styles->registered
					)
				);
				$type_callback = '_enqueue_style_callback';
			} else {
				if ( ! is_a( $wp_scripts, 'WP_Scripts' ) ) {
					$wp_scripts = new WP_Scripts();
				}
				$handles = array_values(
					array_map(
						array( $this, '_get_script_handle' ),
						$wp_scripts->registered
					)
				);
				$type_callback = '_enqueue_script_callback';
			}

			if ( in_array( $url, $handles ) ) {
				$alias = $url;
				$url = '';
			} else {
				// Get the filename from the URL, then sanitize it and prefix "wpmu-"
				$urlparts = explode( '?', $url, 2 );
				$alias = 'wpmu-' . sanitize_title( basename( $urlparts[0] ) );
			}
			$onpage = empty( $onpage ) ? 'all' : $onpage;

			if ( ! is_admin() ) {
				$hook = 'wp_enqueue_scripts';
			} else {
				$hook = 'admin_enqueue_scripts';
			}

			$item = compact( 'url', 'alias', 'onpage' );
			$this->_add( $type, $item );

			$this->add_action( $hook, $type_callback, 100 + $priority );
		}
	}

	/**
	 * Action hook for enqueue style (for PHP <5.3 only)
	 *
	 * @since  1.0.1
	 * @internal
	 */
	public function _enqueue_style_callback() {
		global $hook_suffix;

		$items = $this->_get( 'css' );
		$this->_clear( 'css' );
		$hook = $hook_suffix;

		if ( empty( $hook ) ) { $hook = 'front'; }

		foreach ( $items as $item ) {
			extract( $item ); // url, alias, onpage

			if ( empty( $onpage ) ) { $onpage = 'all'; }

			// onpage == 'all' will always load the script.
			// otherwise onpage must match the enqueue-hook.
			if ( 'all' == $onpage || $hook == $onpage ) {
				if ( empty( $url ) ) {
					wp_enqueue_style( $alias );
				} else {
					wp_enqueue_style( $alias, $url );
				}
			}
		}
	}

	/**
	 * Action hook for enqueue script (for PHP <5.3 only)
	 *
	 * @since  1.0.1
	 * @internal
	 */
	public function _enqueue_script_callback() {
		global $hook_suffix;

		$items = $this->_get( 'js' );
		$this->_clear( 'js' );
		$hook = $hook_suffix;

		if ( empty( $hook ) ) { $hook = 'front'; }

		foreach ( $items as $item ) {
			extract( $item ); // url, alias, onpage

			if ( empty( $onpage ) ) { $onpage = 'all'; }

			// onpage == 'all' will always load the script.
			// otherwise onpage must match the enqueue-hook.
			if ( 'all' == $onpage || $hook == $onpage ) {
				// Load the Media-library functions.
				if ( 'wpmu:media' === $url ) {
					wp_enqueue_media();
					continue;
				}

				// Register script if it has an URL.
				if ( ! empty( $url ) ) {
					wp_register_script( $alias, $url, array( 'jquery' ), false, true );
				}

				// Enqueue the script for output in the page footer.
				wp_enqueue_script( $alias );
			}
		}
	}

	/**
	 * Prints extra script data to the page.
	 *
	 * @action `wp_head`
	 * @since  1.1.1
	 * @internal
	 */
	public function _print_script_data() {
		$data = $this->_get( 'js_data' );
		$this->_clear( 'js_data' );

		// Append javascript data to the script output.
		if ( is_array( $data ) ) {
			$collected = array();

			foreach ( $data as $item ) {
				if ( ! is_array( $item ) ) { continue; }
				$key = sanitize_html_class( $item[0] );
				$obj = array( 'window.' . $key => $item[1] );
				$collected = self::$core->array->merge_recursive_distinct( $collected, $obj );
			}

			echo '<script>';
			foreach ( $collected as $var => $value ) {
				printf(
					'%1$s = %2$s;',
					$var,
					json_encode( $value )
				);
			}
			echo '</script>';
		}
	}


	/**
	 * Prints extra javascript code to the page.
	 *
	 * @action `wp_foot`
	 * @since  1.1.3
	 * @internal
	 */
	public function _print_script_code() {
		$data = $this->_get( 'js_script' );
		$this->_clear( 'js_script' );

		// Append javascript data to the script output.
		if ( is_array( $data ) ) {
			foreach ( $data as $item ) {
				printf(
					'<script>try { %1$s } catch( err ){ window.console.log(err.message); }</script>',
					$item
				);
			}
		}
	}

	/**
	 * Display an admin notice.
	 *
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $text Text to display.
	 * @param  string $class Message-type [updated|error]
	 * @param  string $screen Limit message to this screen-ID
	 * @param  string $id Message ID. Prevents adding duplicate messages.
	 */
	public function admin_message( $text, $class = '', $screen = '', $id = '' ) {
		switch ( $class ) {
			case 'red':
			case 'err':
			case 'error':
				$class = 'error';
				break;

			case 'warning':
			case 'orange':
				$class = 'warning';
				break;

			case 'info':
			case 'blue':
				$class = 'info';
				break;

			default:
				$class = 'updated';
				break;
		}

		// Check if the message is already queued...
		$items = self::_sess_get( 'admin_notice' );
		foreach ( $items as $key => $data ) {
			if (
				$data['text'] == $text &&
				$data['class'] == $class &&
				$data['screen'] == $screen
			) {
				return; // Don't add duplicate message to queue.
			}

			/**
			 * `$id` prevents adding duplicate messages.
			 *
			 * @since 1.1.0
			 */
			if ( ! empty( $id ) && $data['id'] == $id ) {
				return; // Don't add duplicate message to queue.
			}
		}

		self::_sess_add( 'admin_notice', compact( 'text', 'class', 'screen', 'id' ) );
		$this->add_action( 'admin_notices', '_admin_notice_callback', 1 );
		$this->add_action( 'network_admin_notices', '_admin_notice_callback', 1 );
	}

	/**
	 * Action hook for admin notices (for PHP <5.3 only)
	 *
	 * @since  1.0.1
	 * @internal
	 */
	public function _admin_notice_callback() {
		$items = self::_sess_get( 'admin_notice' );
		self::_sess_clear( 'admin_notice' );
		$screen_info = get_current_screen();
		$screen_id = $screen_info->id;

		foreach ( $items as $item ) {
			extract( $item ); // text, class, screen, id
			if ( empty( $screen ) || $screen_id == $screen ) {
				printf(
					'<div class="%1$s notice notice-%1$s is-dismissible %3$s"><p>%2$s</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">%4$s</span></button></div>',
					esc_attr( $class ),
					$text,
					esc_attr( $id ),
					__( 'Dismiss this notice.' )
				);
			}
		}
	}

	/**
	 * Checks the DB for persistent data from last request.
	 * If persistent data exists the appropriate hooks are set to process them.
	 *
	 * @since  1.0.7
	 * @internal
	 */
	public function _check_admin_notices() {
		if ( self::_sess_have( 'admin_notice' ) ) {
			$this->add_action( 'admin_notices', '_admin_notice_callback', 1 );
			$this->add_action( 'network_admin_notices', '_admin_notice_callback', 1 );
		}
	}

}