<?php
/**
 * Plugin Name: WPMU Dev code library
 * Plugin URI:  http://premium.wpmudev.org/
 * Description: Framework to support creating WordPress plugins and themes.
 * Version:     3.0.5
 * Author:      WPMU DEV
 * Author URI:  http://premium.wpmudev.org/
 * Textdomain:  wpmu-lib
 *
 * ============================================================================
 *
 * Constants for wp-config.php
 *
 * Load the unminified JS/CSS files
 * Default: false
 *     define( 'WDEV_UNMINIFIED', true );
 *
 * Activate mslib3()->debug->dump() without having to enable WP_DEBUG
 * Default: Same as WP_DEBUG
 *     define( 'WDEV_DEBUG', true );
 *
 * Disable mslib3()->debug->dump() for Ajax requests
 * Default: Same as WDEV_DEBUG
 *     define( 'WDEV_AJAX_DEBUG', false );
 *
 * Modify or disable the P3P HTTP header for all responses.
 * Default: 'CP="NOI"'.
 *     define( 'WDEV_SEND_P3P', false ) // Disable P3P
 *     define( 'WDEV_SEND_P3P', 'CP="CAO OUR"' ) // Overwrite default P3P header
 */

$version = '3.0.5';

if ( ! function_exists( 'mslib3' ) ) {
	/**
	 * This is a shortcut function to access the latest TheLib_Core object.
	 *
	 * The shortcut function is called `lib3` because it is incompatible with
	 * the old WDev() function. The number "3" reflects the main version of this
	 * module.
	 *
	 * The main version is only increased when backwards compatibility fails!
	 *
	 * Usage:
	 *   mslib3()->ui->admin_message();
	 */
	function mslib3() {
		return MsTheLib3_Wrap::get_obj();
	}
}

// ============================================================================
// Internal module definition:

// Define the absolute paths to all class files of this submodule.
$dirname = dirname( __FILE__ ) . '/inc/';
$files = array(
	'MsTheLib'         => $dirname . 'class-thelib.php',
	'MsTheLib_Core'    => $dirname . 'class-thelib-core.php',
	'MsTheLib_Array'   => $dirname . 'class-thelib-array.php',
	'MsTheLib_Debug'   => $dirname . 'class-thelib-debug.php',
	'MsMsTheLib_Html'  => $dirname . 'class-thelib-html.php',
	'MsTheLib_Net'     => $dirname . 'class-thelib-net.php',
	'MsTheLib_Session' => $dirname . 'class-thelib-session.php',
	'MsTheLib_Updates' => $dirname . 'class-thelib-updates.php',
	'MsTheLib_Ui'      => $dirname . 'class-thelib-ui.php',
);

if ( ! class_exists( 'MsTheLib3_Wrap' ) ) {
	/**
	 * The wrapper class is used to handle situations when some plugins include
	 * different versions of TheLib.
	 *
	 * TheLib3_Wrap will always keep the latest version of TheLib for later usage.
	 *
	 * @internal Use function `mslib3()` instead!
	 */
	class MsTheLib3_Wrap {
		static protected $version = '0.0.0';
		static protected $files = array();
		static protected $object = null;

		/**
		 * Store the module files if they are the highest module-version
		 */
		static public function set_version( $version, $files ) {
			if ( null !== self::$object ) { return; }

			if ( version_compare( $version, self::$version, '>' ) ) {
				self::$version = $version;
				self::$files = $files;
			}
		}

		/**
		 * Return the module object.
		 */
		static public function get_obj() {
			if ( null === self::$object ) {
				foreach ( self::$files as $class_name => $class_file ) {
					if ( ! class_exists( $class_name ) && file_exists( $class_file ) ) {
						require_once $class_file;
					}
				}
				self::$object = new MsTheLib_Core();
			}
			return self::$object;
		}
	} // End: MsTheLib3_Wrap
}
// Stores the lib-directory if it contains the highest version files.
MsTheLib3_Wrap::set_version( $version, $files );

add_action( 'wp_enqueue_scripts', 'remove_csb_ui' );
function remove_csb_ui() {
	wp_dequeue_script( 'wpmu-wpmu-ui-3-min-js' );
	wp_deregister_script( 'wpmu-wpmu-ui-3-min-js' );
}
