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
 * Activate lib3()->debug->dump() without having to enable WP_DEBUG
 * Default: Same as WP_DEBUG
 *     define( 'WDEV_DEBUG', true );
 *
 * Disable lib3()->debug->dump() for Ajax requests
 * Default: Same as WDEV_DEBUG
 *     define( 'WDEV_AJAX_DEBUG', false );
 *
 * Modify or disable the P3P HTTP header for all responses.
 * Default: 'CP="NOI"'.
 *     define( 'WDEV_SEND_P3P', false ) // Disable P3P
 *     define( 'WDEV_SEND_P3P', 'CP="CAO OUR"' ) // Overwrite default P3P header
 */

$version = '3.0.5';

if ( ! function_exists( 'lib3' ) ) {
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
	 *   lib3()->ui->admin_message();
	 */
	function lib3() {
		return TheLib3_Wrap::get_obj();
	}
}

// ============================================================================
// Internal module definition:

// Define the absolute paths to all class files of this submodule.
$dirname = dirname( __FILE__ ) . '/inc/';
$files = array(
	'TheLib'         => $dirname . 'class-thelib.php',
	'TheLib_Core'    => $dirname . 'class-thelib-core.php',
	'TheLib_Array'   => $dirname . 'class-thelib-array.php',
	'TheLib_Debug'   => $dirname . 'class-thelib-debug.php',
	'TheLib_Html'    => $dirname . 'class-thelib-html.php',
	'TheLib_Net'     => $dirname . 'class-thelib-net.php',
	'TheLib_Session' => $dirname . 'class-thelib-session.php',
	'TheLib_Updates' => $dirname . 'class-thelib-updates.php',
	'TheLib_Ui'      => $dirname . 'class-thelib-ui.php',
);

if ( ! class_exists( 'TheLib3_Wrap' ) ) {
	/**
	 * The wrapper class is used to handle situations when some plugins include
	 * different versions of TheLib.
	 *
	 * TheLib3_Wrap will always keep the latest version of TheLib for later usage.
	 *
	 * @internal Use function `lib3()` instead!
	 */
	class TheLib3_Wrap {
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
				self::$object = new TheLib_Core();
			}
			return self::$object;
		}
	} // End: TheLib3_Wrap
}
// Stores the lib-directory if it contains the highest version files.
TheLib3_Wrap::set_version( $version, $files );
