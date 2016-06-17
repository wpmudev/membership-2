<?php
/* start:pro */
/**
 * Plugin Name: Membership 2 Pro
 * Plugin URI:  https://premium.wpmudev.org/project/membership/
 * Version:     1.0.3.2
 * Build Stamp: BUILDTIME
 * Description: The most powerful, easy to use and flexible membership plugin for WordPress sites available.
 * Author:      WPMU DEV
 * Author URI:  http://premium.wpmudev.org/
 * WDP ID:      1003656
 * License:     GPL2
 * License URI: http://opensource.org/licenses/GPL-2.0
 * Text Domain: membership2
 *
 * @package Membership2
 */
/* end:pro *//* start:free */
/**
 * Plugin Name: Membership 2
 * Plugin URI:  https://wordpress.org/plugins/membership
 * Version:     4.0.1.1
 * Build Stamp: BUILDTIME
 * Description: The most powerful, easy to use and flexible membership plugin for WordPress sites available.
 * Author:      WPMU DEV
 * Author URI:  http://premium.wpmudev.org/
 * WDP ID:      1003656
 * License:     GPL2
 * License URI: http://opensource.org/licenses/GPL-2.0
 * Text Domain: membership2
 *
 * @package Membership2
 */
/* end:free */

/**
 * Copyright notice
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * Authors: Philipp Stracker, Fabio Jun Onishi, Victor Ivanov, Jack Kitterhing, Rheinard Korf, Ashok Kumar Nath
 * Contributors: Joji Mori, Patrick Cohen
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
 */

/**
 * Initializes constants and create the main plugin object MS_Plugin.
 * This function is called *instantly* when this file was loaded.
 *
 * @since  1.0.0
 */

function membership2_init_app() {
	if ( defined( 'MS_PLUGIN' ) ) {
		/* start:pro */$plugin_name = 'Membership 2 Pro';/* end:pro */
		/* start:free */$plugin_name = 'Membership 2 (Free)';/* end:free */
		if ( is_admin() ) {
			// Can happen in Multisite installs where a sub-site has activated the
			// plugin and then the plugin is also activated in network-admin.
			printf(
				'<div class="notice error"><p><strong>%s</strong>: %s</p></div>',
				sprintf(
					esc_html__( 'Could not load the plugin %s, because another version of the plugin is already loaded', 'membership2' ),
					$plugin_name
				),
				esc_html( MS_PLUGIN . ' (v' . MS_PLUGIN_VERSION . ')' )
			);
		}
		return;
	}

	/**
	 * Plugin version
	 *
	 * @since  1.0.0
	 */
	define(
		'MS_PLUGIN_VERSION'
		/* start:pro */, '1.0.3.0'/* end:pro */
		/* start:free */, '4.0.1.0'/* end:free */
	);

	/**
	 * Free or pro plugin?
	 * This only affects some display settings, it does not really lock/unlock
	 * any premium features...
	 *
	 * @since  1.0.3.2
	 */
	define(
		'MS_IS_PRO'
		/* start:pro */,true/* end:pro */
		/* start:free */,false/* end:free */
	);

	/**
	 * Plugin main-file.
	 *
	 * @since  1.0.3.0
	 */
	define( 'MS_PLUGIN_FILE', __FILE__ );

	/**
	 * Plugin identifier constant.
	 *
	 * @since  1.0.0
	 */
	define( 'MS_PLUGIN', plugin_basename( __FILE__ ) );

	/**
	 * Plugin name dir constant.
	 *
	 * @since  1.0.0
	 */
	define( 'MS_PLUGIN_NAME', dirname( MS_PLUGIN ) );

	/**
	 * Plugin name dir constant.
	 *
	 * @since  1.0.3
	 */
	define( 'MS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

	$externals = array(
		dirname( __FILE__ ) . '/lib/wpmu-lib/core.php',
		dirname( __FILE__ ) . '/lib/wdev-frash/module.php',
	);

	/* start:free */
	// Free-version configuration
	$cta_label = __( 'Get Members!', 'membership2' );
	$drip_param = 'Membership';
	/* end:free */

	/* start:pro */
	// Pro-Only configuration.
	$cta_label = false;
	$drip_param = false;
	$externals[] = dirname( __FILE__ ) . '/lib/wpmudev-dashboard/wpmudev-dash-notification.php';

	// WPMUDEV Dashboard.
	global $wpmudev_notices;
	$wpmudev_notices[] = array(
		'id' => 1003656,
		'name' => 'Membership 2 Pro',
		'screens' => array(
			'toplevel_page_membership-2',
			'membership-2_page_membership2-members',
			'membership-2_page_membership2-setup',
			'membership-2_page_membership2-billing',
			'membership-2_page_membership2-coupons',
			'membership-2_page_membership2-addon',
			'membership-2_page_membership2-settings',
		),
	);
	/* end:pro */

	foreach ( $externals as $path ) {
		if ( file_exists( $path ) ) { require_once $path; }
	}

	// Register the current plugin, for pro and free plugins!
	do_action(
		'wdev-register-plugin',
		/*             Plugin ID */ plugin_basename( __FILE__ ),
		/*          Plugin Title */ 'Membership 2',
		/* https://wordpress.org */ '/plugins/membership/',
		/*      Email Button CTA */ $cta_label,
		/*  getdrip Plugin param */ $drip_param
	);

	/**
	 * Prepare rating message.
	 *
	 * @return string Message to display.
	 */
	function _membership2_rating_message() {
		return __( "Hey %s, you've been using %s for a while now, and we hope you're happy with it.", 'membership2' ) .
			'<br />' .
			__( "We're constantly working to improve our plugins, and it helps a lot when members just like you share feedback!", 'membership2' );
	}
	add_filter(
		'wdev-rating-message-' . plugin_basename( __FILE__ ),
		'_membership2_rating_message'
	);

	/**
	 * Translation.
	 *
	 * Tip:
	 *   The translation files must have the filename [TEXT-DOMAIN]-[locale].mo
	 *   Example: membership2-en_EN.mo  /  membership2-de_DE.mo
	 */
	function _membership2_translate_plugin() {
		load_plugin_textdomain(
			'membership2',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
	add_action( 'plugins_loaded', '_membership2_translate_plugin' );

	if ( (defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'WDEV_DEBUG' ) && WDEV_DEBUG) ) {
		// Load development/testing code before the plugin is initialized.
		$testfile = dirname( __FILE__ ) . '/tests/wp/init.php';
		if ( file_exists( $testfile ) ) { include $testfile; }
	}

	// Initialize the M2 class loader.
	$loader = new MS_Loader();

	/**
	 * Create an instance of the plugin object.
	 *
	 * This is the primary entry point for the Membership plugin.
	 *
	 * @since  1.0.0
	 */
	MS_Plugin::instance();
}

/**
 * Class-Loader code.
 * Initialises the autoloader and required plugin hooks.
 *
 * @since  1.0.0
 */
class MS_Loader {

	/**
	 * Plugin constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		add_filter(
			'ms_class_path_overrides',
			array( $this, 'ms_class_path_overrides' )
		);

		// Creates the class autoloader.
		// Special: Method `class_loader` can be private and it will work here!
		spl_autoload_register( array( $this, 'class_loader' ) );
	}

	/**
	 * Hooks 'ms_class_path_overrides'.
	 *
	 * Overrides plugin class paths to adhere to naming conventions
	 * where object names are separated by underscores or for special cases.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $overrides Array passed in by filter.
	 * @return array(class=>path) Classes with new file paths.
	 */
	public function ms_class_path_overrides( $overrides ) {
		$models_base = 'app/model/';
		$models = array(
			'MS_Model_Communication_After_Finishes' => 'communication/class-ms-model-communication-after-finishes.php',
			'MS_Model_Communication_After_Payment_Due' => 'communication/class-ms-model-communication-after-payment-due.php',
			'MS_Model_Communication_Before_Finishes' => 'communication/class-ms-model-communication-before-finishes.php',
			'MS_Model_Communication_Before_Payment_Due' => 'communication/class-ms-model-communication-before-payment-due.php',
			'MS_Model_Communication_Before_Trial_Finishes' => 'communication/class-ms-model-communication-before-trial-finishes.php',
			'MS_Model_Communication_Credit_Card_Expire' => 'communication/class-ms-model-communication-credit-card-expire.php',
			'MS_Model_Communication_Failed_Payment' => 'communication/class-ms-model-communication-failed-payment.php',
			'MS_Model_Communication_Info_Update' => 'communication/class-ms-model-communication-info-update.php',
			'MS_Model_Communication_Registration_Free' => 'communication/class-ms-model-communication-registration-free.php',
		);

		foreach ( $models as $key => $path ) {
			$overrides[ $key ] = $models_base . $path;
		}

		return $overrides;
	}

	/**
	 * Class autoloading callback function.
	 *
	 * Uses the **MS_** namespace to autoload classes when called.
	 * Avoids creating include functions for each file in the MVC structure.
	 * **MS_** namespace ONLY will be based on folder structure in /app/
	 *
	 * @since  1.0.0
	 *
	 * @param  string $class Uses PHP autoloader function.
	 * @return boolean
	 */
	private function class_loader( $class ) {
		static $Path_overrides = null;

		/**
		 * Actions to execute before the autoloader loads a class.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_class_loader_pre_processing', $this );

		$basedir = dirname( __FILE__ );
		$class = trim( $class );

		if ( null === $Path_overrides ) {
			/**
			 * Adds and Filters class path overrides.
			 *
			 * @since  1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$Path_overrides = apply_filters( 'ms_class_path_overrides', array(), $this );
		}

		if ( array_key_exists( $class, $Path_overrides ) ) {
			/**
			 * Case 1: The class-path is explicitly defined in $Path_overrides.
			 * Simply use the defined path to load the class.
			 */
			$file_path = $basedir . '/' . $Path_overrides[ $class ];

			/**
			 * Overrides the filename and path.
			 *
			 * @since  1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$file_path = apply_filters( 'ms_class_file_override', $file_path, $this );

			if ( is_file( $file_path ) ) {
				include_once $file_path;
			}

			return true;
		} elseif ( 'MS_' == substr( $class, 0, 3 ) ) {
			/**
			 * Case 2: The class-path is not explicitely defined in $Path_overrides.
			 * Use /app/ path and class-name to build the file-name.
			 */

			$path_array = explode( '_', $class );
			array_shift( $path_array ); // Remove the 'MS' prefix from path.
			$alt_dir = array_pop( $path_array );
			$sub_path = implode( '/', $path_array );

			$filename = str_replace( '_', '-', 'class-' . $class . '.php' );
			$file_path = trim( strtolower( $sub_path . '/' . $filename ), '/' );
			$file_path_alt = trim( strtolower( $sub_path . '/' . $alt_dir . '/' . $filename ), '/' );
			$candidates = array();

			/* start:pro */
			// First check if we have a premium version of the class.
			$candidates[] = $basedir . '/premium/' . $file_path;
			$candidates[] = $basedir . '/premium/' . $file_path_alt;
			/* end:pro */

			// If no premium class is found check for default app class.
			$candidates[] = $basedir . '/app/' . $file_path;
			$candidates[] = $basedir . '/app/' . $file_path_alt;

			foreach ( $candidates as $path ) {
				if ( is_file( $path ) ) {
					include_once $path;
					return true;
				}
			}
		}

		return false;
	}
};


/**
 * This is a hack to prevent cookie issue in IE11 and EDGE
 * Need to refactor in later
 *
 * @since 1.0.2.8
 * @todo Move this code into a different class. Simply call here via MS_TheClass::check_ms_ajax()
 */
if ( isset( $_REQUEST['ms_ajax'] ) ) {
	if ( 1 == $_REQUEST['ms_ajax'] ) {
		add_action( 'wp_ajax_ms_login', 'ms_ajax_login' );
		add_action( 'wp_ajax_nopriv_ms_login', 'ms_ajax_login' );

		function ms_ajax_login() {
			$resp = array();
			check_ajax_referer( 'ms-ajax-login' );

			if ( empty( $_POST['username'] ) && ! empty( $_POST['log'] ) ) {
				$_POST['username'] = $_POST['log'];
			}
			if ( empty( $_POST['password'] ) && ! empty( $_POST['pwd'] ) ) {
				$_POST['password'] = $_POST['pwd'];
			}
			if ( empty( $_POST['remember'] ) && ! empty( $_POST['rememberme'] ) ) {
				$_POST['remember'] = $_POST['rememberme'];
			}

			// Nonce is checked, get the POST data and sign user on
			$info = array(
				'user_login' => $_POST['username'],
				'user_password' => $_POST['password'],
				'remember' => (bool) isset( $_POST['remember'] ) ? $_POST['remember'] : false,
			);

			$user_signon = wp_signon( $info, false );

			if ( is_wp_error( $user_signon ) ) {
				$resp['error'] = __( 'Wrong username or password', 'membership2' );
			} else {
				$resp['loggedin'] = true;
				$resp['success'] = __( 'Logging in...', 'membership2' );

				/**
				 * Allows a custom redirection after login.
				 * Empty value will use the default redirect option of the login form.
				 */

				// TODO: These filters are never called!
				//       This code is too early to allow any other plugin to register a filter handler...
				$enforce = false;
				if ( isset( $_POST['redirect_to'] ) ) {
					$resp['redirect'] = apply_filters(
						'ms-ajax-login-redirect',
						$_POST['redirect_to'],
						$user_signon->ID
					);
				} else {
					$resp['redirect'] = apply_filters(
						'ms_url_after_login',
						$_POST['redirect_to'],
						$enforce
					);
				}
			}

			echo json_encode( $resp );
			exit();
		}
	}
}


/* start:free */
function membership2_init_old_app() {
	require_once 'app_old/membership.php';
}

function membership2_is_old_app() {
	return true != get_option( 'm2_use_new_version' );
}

function membership2_use_m2() {
	update_option( 'm2_use_new_version', true );
}

if ( ! defined( 'IS_UNIT_TEST' ) && membership2_is_old_app() ) {
	membership2_init_old_app();
	return;
}
/* end:free */

/* start:pro */
membership2_init_app();
/* end:pro */
