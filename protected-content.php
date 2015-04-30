<?php
/**
Plugin Name: Membership2
Plugin URI:  https://premium.wpmudev.org/project/membership/
Version:     2.0.0.0
Description: The most powerful, easy to use and flexible membership plugin for WordPress sites available.
Author:      WPMU DEV
Author URI:  http://premium.wpmudev.org/
WDP ID:      928907
License:     GNU General Public License (Version 2 - GPLv2)
Text Domain: membership2
*/

/**
 * @copyright Incsub (http://incsub.com/)
 *
 * Authors: Fabio Jun Onishi, Philipp Stracker, Victor Ivanov, Jack Kitterhing, Rheinard Korf
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
 *
*/

/**
 * Plugin version
 *
 * @since 1.0.0
 */
define( 'MS_PLUGIN_VERSION', '2.0.0.0' );

/**
 * Plugin text domain.
 *
 * @since 1.0.0
 */
define( 'MS_TEXT_DOMAIN', 'membership2' );

/**
 * Plugin name dir constant.
 *
 * @since 1.0.0
 */
define( 'MS_PLUGIN_NAME', dirname( plugin_basename( __FILE__ ) ) );

/**
 * Include WPMUDev Dashboard
 */
global $wpmudev_notices;
$wpmudev_notices[] = array(
	'id' => 928907,
	'name' => 'Membership2',
	'screens' => array(
		'toplevel_page_membership2',
		'membership2_page_membership2-members',
		'membership2_page_membership2-setup',
		'membership2_page_membership2-billing',
		'membership2_page_membership2-coupons',
		'membership2_page_membership2-addon',
		'membership2_page_membership2-settings',
	)
);

$externals = array(
	dirname( __FILE__ ) . '/extra/wpmudev-dashboard/wpmudev-dash-notification.php',
	dirname( __FILE__ ) . '/extra/wpmu-lib/core.php',
);

foreach ( $externals as $path ) {
	require_once $path;
}

/**
 * Hooks 'ms_class_path_overrides'.
 *
 * Overrides plugin class paths to adhere to naming conventions
 * where object names are separated by underscores or for special cases.
 *
 * @since 1.0.0
 *
 * @param  array $overrides Array passed in by filter.
 * @return array(class=>path) Classes with new file paths.
 */
function ms_class_path_overrides( $overrides ) {
	// MODELS
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

	foreach ( $models as $key => $path ) { $overrides[ $key ] = $models_base . $path; }

	return $overrides;
}
add_filter( 'ms_class_path_overrides', 'ms_class_path_overrides' );

/**
 * Primary Membership plugin class.
 *
 * Initialises the autoloader and required plugin hooks.
 * Control of plugin is passed to the MVC implementation found
 * inside the /app/ folder.
 *
 * Note: Even all properties are marked private, they are made public via the
 * magic __get() function.
 *
 * @since 1.0.0
 *
 * @return object Plugin instance.
 */
class MS_Plugin {

	/**
	 * Singletone instance of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var MS_Plugin
	 */
	private static $instance = null;

	/**
	 * Modifier values.
	 *
	 * @since 1.1.0.5
	 *
	 * @var array
	 */
	private static $modifiers = array();

	/**
	 * The plugin name.
	 *
	 * @since 1.0.0
	 * @var name
	 */
	private $name;

	/**
	 * The plugin version.
	 *
	 * @since 1.0.0
	 * @var version
	 */
	private $version;

	/**
	 * The plugin file.
	 *
	 * @since 1.0.0
	 * @var file
	 */
	private $file;

	/**
	 * The plugin path.
	 *
	 * @since 1.0.0
	 * @var dir
	 */
	private $dir;

	/**
	 * The plugin URL.
	 *
	 * @since 1.0.0
	 * @var url
	 */
	private $url;

	/**
	 * The plugin settings.
	 *
	 * @since 1.0.0
	 * @var settings
	 */
	private $settings;

	/**
	 * The plugin add-on settings.
	 *
	 * @since 1.0.0
	 * @var addon
	 */
	private $addon;

	/**
	 * The main controller of the plugin.
	 *
	 * @since 1.0.0
	 * @var controller
	 */
	private $controller;

	/**
	 * The API controller (for convenience)
	 *
	 * @since  2.0.0
	 * @var MS_Controller_Api
	 */
	public static $api = null;

	/**
	 * Plugin constructor.
	 *
	 * Set properties, registers hooks and loads the plugin.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		/**
		 * Actions to execute before the plugin construction starts.
		 *
		 * @since 2.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_init', $this );

		/**
		 * @since      1.0.0
		 * @deprecated since 2.0.0
		 */
		do_action( 'ms_plugin_construct_start', $this );

		/** Setup plugin properties */
		$this->name = MS_PLUGIN_NAME;
		$this->version = MS_PLUGIN_VERSION;
		$this->file = __FILE__;
		$this->dir = plugin_dir_path( __FILE__ );
		$this->url = plugin_dir_url( __FILE__ );

		/**
		 * Filter the languages path before loading the textdomain.
		 *
		 * @uses load_plugin_textdomain()
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		load_plugin_textdomain(
			MS_TEXT_DOMAIN,
			false,
			apply_filters(
				'ms_plugin_languages_path',
				$this->name . '/languages/',
				$this
			)
		);

		// Creates the class autoloader.
		spl_autoload_register( array( $this, 'class_loader' ) );

		// Might refresh the Rewrite-Rules and reloads the page.
		add_action(
			'wp_loaded',
			array( $this, 'maybe_flush_rewrite_rules' ),
			1
		);

		/*
		 * Hooks init to register custom post types.
		 */
		add_action(
			'init',
			array( $this, 'register_custom_post_types' ),
			1
		);

		/*
		 * Hooks init to add rewrite rules and tags (both work in conjunction).
		 */
		add_action( 'init', array( $this, 'add_rewrite_rules' ), 1 );
		add_action( 'init', array( $this, 'add_rewrite_tags' ), 1 );

		// Plugin activation Hook
		register_activation_hook(
			__FILE__,
			array( $this, 'plugin_activation' )
		);

		/**
		 * Hooks init to create the primary plugin controller.
		 *
		 * We use the setup_theme hook because plugins_loaded is too early:
		 * wp_redirect (used by the update model) is initialized after
		 * plugins_loaded but before setup_theme.
		 */
		add_action(
			'setup_theme',
			array( $this, 'ms_plugin_constructing' )
		);

		/**
		 * Creates and Filters the Settings Model.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		$this->settings = MS_Factory::load( 'MS_Model_Settings' );

		/**
		 * Creates and Filters the Addon Model.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		$this->addon = MS_Factory::load( 'MS_Model_Addon' );

		add_filter(
			'plugin_action_links_' . plugin_basename( __FILE__ ),
			array( $this, 'plugin_settings_link' )
		);

		add_filter(
			'network_admin_plugin_action_links_' . plugin_basename( __FILE__ ),
			array( $this, 'plugin_settings_link' )
		);

		// Grab instance of self.
		self::$instance = $this;

		/**
		 * Actions to execute when the Plugin object has successfully constructed.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_construct_end', $this );
	}

	/**
	 * Loads primary plugin controllers.
	 *
	 * Related Action Hooks:
	 * - setup_theme
	 *
	 * @since 1.0.0
	 */
	public function ms_plugin_constructing() {
		/**
		 * Creates and Filters the Plugin Controller.
		 *
		 * ---> MAIN ENTRY POINT CONTROLLER FOR PLUGIN <---
		 *
		 * @uses  MS_Controller_Plugin
		 * @since 1.0.0
		 */
		$this->controller = MS_Factory::create( 'MS_Controller_Plugin' );
	}

	/**
	 * Register plugin custom post types.
	 *
	 * @since 1.0.0
	 */
	public function register_custom_post_types() {
		do_action( 'ms_plugin_register_custom_post_types_before', $this );

		$cpts = apply_filters(
			'ms_plugin_register_custom_post_types',
			array(
				MS_Model_Membership::$POST_TYPE => MS_Model_Membership::get_register_post_type_args(),
				MS_Model_Relationship::$POST_TYPE => MS_Model_Relationship::get_register_post_type_args(),
				MS_Model_Invoice::$POST_TYPE => MS_Model_Invoice::get_register_post_type_args(),
				MS_Model_Communication::$POST_TYPE => MS_Model_Communication::get_register_post_type_args(),
				MS_Model_Event::$POST_TYPE => MS_Model_Event::get_register_post_type_args(),
			)
		);

		foreach ( $cpts as $cpt => $args ) {
			MS_Helper_Utility::register_post_type( $cpt, $args );
		}
	}

	/**
	 * Add rewrite rules.
	 *
	 * @since 1.0.0
	 */
	public function add_rewrite_rules() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		// Gateway return - IPN.
		add_rewrite_rule(
			'^ms-payment-return/(.+)/?$',
			'index.php?paymentgateway=$matches[1]',
			'top'
		);

		// Alternative payment return URL: Membership
		if ( MS_Model_Import_Membership::did_import() ) {
			add_rewrite_rule(
				'paymentreturn/(.+)/?',
				'index.php?paymentgateway=$matches[1]',
				'top'
			);
		}

		// Media / download
		$mmask = $settings->downloads['masked_url'];
		$mtype = $settings->downloads['protection_type'];

		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) && $mmask ) {
			if ( MS_Rule_Media_Model::PROTECTION_TYPE_HYBRID == $mtype ) {
				add_rewrite_rule(
					sprintf( '^%1$s/?$', $mmask ),
					'index.php?protectedfile=0',
					'top'
				);
			} else {
				add_rewrite_rule(
					sprintf( '^%1$s/([^/]+)', $mmask ),
					'index.php?protectedfile=$matches[1]',
					'top'
				);
			}
		}
		// End: Media / download

		do_action( 'ms_plugin_add_rewrite_rules', $this );
	}

	/**
	 * Add rewrite tags.
	 *
	 * @since 1.0.0
	 */
	public function add_rewrite_tags() {
		// Membership site pages.
		add_rewrite_tag( '%ms_page%', '(.+)' );

		// Gateway return - IPN.
		add_rewrite_tag( '%paymentgateway%', '(.+)' );

		// Media / download
		add_rewrite_tag( '%protectedfile%', '(.+)' );

		do_action( 'ms_plugin_add_rewrite_tags', $this );
	}

	/**
	 * Actions executed in plugin activation.
	 *
	 * @since 1.0.0
	 */
	public function plugin_activation() {
		// Prevent recursion during plugin activation.
		$refresh = lib2()->session->get( 'refresh_url_rules' );
		if ( $refresh ) { return; }

		// Update the Membership2 database entries after activation.
		MS_Model_Upgrade::update( true );

		do_action( 'ms_plugin_activation ', $this );
	}

	/**
	 * Redirect page and request plugin to flush the WordPress rewrite rules
	 * on next request.
	 *
	 * @since  1.0.4.4
	 * @param string $url The URL to load after flushing the rewrite rules.
	 */
	static public function flush_rewrite_rules( $url = false ) {
		$refresh = lib2()->session->get( 'refresh_url_rules' );
		if ( $refresh ) { return; }

		lib2()->session->add( 'refresh_url_rules', true );
		$url = esc_url_raw(
			add_query_arg( 'ms_ts', time(), $url )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Flush the WordPress rewrite rules.
	 *
	 * @since  1.0.4.4
	 */
	public function maybe_flush_rewrite_rules() {
		$refresh = lib2()->session->get_clear( 'refresh_url_rules' );
		if ( ! $refresh ) { return; }

		// Flush WP rewrite rules.
		flush_rewrite_rules();

		// Set up the plugin specific rewrite rules again.
		$this->add_rewrite_rules();
		$this->add_rewrite_tags();

		do_action( 'ms_plugin_flush_rewrite_rules', $this );

		$url = esc_url_raw( remove_query_arg( 'ms_ts' ) );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Class autoloading callback function.
	 *
	 * Uses the **MS_** namespace to autoload classes when called.
	 * Avoids creating include functions for each file in the MVC structure.
	 * **MS_** namespace ONLY will be based on folder structure in /app/
	 *
	 * @since 1.0.0
	 *
	 * @param  string $class Uses PHP autoloader function.
	 * @return boolean
	 */
	private function class_loader( $class ) {
		static $Path_overrides = null;

		/**
		 * Actions to execute before the autoloader loads a class.
		 *
		 * @since 1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_class_loader_pre_processing', $this );

		$basedir = dirname( __FILE__ );
		$class = trim( $class );

		if ( null === $Path_overrides ) {
			/**
			 * Adds and Filters class path overrides.
			 *
			 * @since 1.0.0
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
			 * @since 1.0.0
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
			array_shift( $path_array );
			$alt_dir = array_pop( $path_array );
			$sub_path = implode( '/', $path_array );

			$filename = str_replace( '_', '-', 'class-' . $class . '.php' );
			$file_path = $basedir . '/app/' . strtolower( $sub_path . '/' . $filename );
			$file_path_alt = $basedir . '/app/' . strtolower( $sub_path . '/' . $alt_dir . '/' . $filename );

			/**
			 * Overrides the filename and path.
			 *
			 * @since 1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$file_path = apply_filters( 'ms_class_file_override', $file_path, $this );
			$file_path_alt = apply_filters( 'ms_class_file_override', $file_path_alt, $this );

			if ( is_file( $file_path ) ) {
				include_once $file_path;
			} elseif ( is_file( $file_path_alt ) ) {
				include_once $file_path_alt;
			}

			return true;
		}

		return false;
	}

	/**
	 * Add link to settings page in plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param array $links WordPress default array of links.
	 * @return array Array of links with settings page links added.
	 */
	public function plugin_settings_link( $links ) {
		if ( ! is_network_admin() ) {
			$text = __( 'Settings', MS_TEXT_DOMAIN );
			$url = admin_url( 'admin.php?page='. MS_Controller_Plugin::MENU_SLUG . '-settings' );

			if ( $this->settings->initial_setup ) {
				$url = admin_url( 'admin.php?page='. MS_Controller_Plugin::MENU_SLUG );
			}

			/**
			 * Filter the plugin settings link.
			 *
			 * @since 1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$settings_link = apply_filters(
				'ms_plugin_settings_link',
				sprintf( '<a href="%s">%s</a>', $url, $text ),
				$this
			);
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	/**
	 * Returns singleton instance of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @static
	 * @access public
	 *
	 * @return MS_Plugin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new MS_Plugin();

			self::$instance = apply_filters(
				'ms_plugin_instance',
				self::$instance
			);
		}

		return self::$instance;
	}

	/**
	 * Returns plugin enabled status.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @static
	 *
	 * @return bool The status.
	 */
	public static function is_enabled() {
		return self::instance()->settings->plugin_enabled;
	}

	/**
	 * Returns plugin wizard status.
	 *
	 * @since 1.0.4.3
	 * @access public
	 *
	 * @static
	 *
	 * @return bool The status.
	 */
	public static function is_wizard() {
		return ! ! self::instance()->settings->initial_setup;
	}

	/**
	 * Returns the network-wide protection status.
	 *
	 * This flag can be changed by setting the MS_PROTECT_NETWORK flag to true
	 * in wp-config.php
	 *
	 * @since  2.0.0
	 * @return bool False means that only the current site is protected.
	 *         True means that memberships are shared among all network sites.
	 */
	public static function is_network_wide() {
		static $Networkwide = null;

		if ( null === $Networkwide ) {
			if ( ! defined( 'MS_PROTECT_NETWORK' ) ) {
				define( 'MS_PROTECT_NETWORK', false );
			}

			if ( MS_PROTECT_NETWORK && is_multisite() ) {
				$Networkwide = true;
			} else {
				$Networkwide = false;
			}
		}

		return $Networkwide;
	}

	/**
	 * Returns a modifier option.
	 * This is similar to a setting but more "advanced" in a way that there is
	 * no UI for it. A modifier can be set by the plugin (e.g. during Import
	 * the "no_messages" modifier is enabled) or via a const in wp-config.php
	 *
	 * A modifier is never saved in the database.
	 * It can be defined ONLY via MS_Plugin::set_modifier() or via wp-config.php
	 * The set_modifier() value will always take precedence over wp-config.php
	 * definitions.
	 *
	 * @since  1.1.0.5
	 * @api
	 *
	 * @param  string $key Name of the modifier.
	 * @return mixed The modifier value or null.
	 */
	public static function get_modifier( $key ) {
		$res = null;

		if ( isset( self::$modifiers[$key] ) ) {
			$res = self::$modifiers[$key];
		} elseif ( defined( $key ) ) {
			$res = constant( $key );
		}

		return $res;
	}

	/**
	 * Changes a modifier option.
	 * @see get_modifier() for more details.
	 *
	 * @since  1.1.0.5
	 * @api
	 *
	 * @param  string $key Name of the modifier.
	 * @param  mixed $value Value of the modifier. `null` unsets the modifier.
	 */
	public static function set_modifier( $key, $value = null ) {
		if ( null === $value ) {
			unset( self::$modifiers[$key] );
		} else {
			self::$modifiers[$key];
		}
	}

	/**
	 * This funciton initializes the api property for easy access to the plugin
	 * API. This function is *only* called by MS_Controller_Api::__construct()!
	 *
	 * @since 2.0.0
	 * @internal
	 * @param MS_Controller_Api $controller The initialized API controller.
	 */
	public static function set_api( $controller ) {
		self::$api = $controller;
	}

	/**
	 * Returns property associated with the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 * @param string $property The name of a property.
	 * @return mixed Returns mixed value of a property or NULL if a property doesn't exist.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property;
		}
	}
}

/**
 * Create an instance of the plugin object.
 *
 * This is the primary entry point for the Membership plugin.
 *
 * @since 1.0.0
 */
MS_Plugin::instance();
