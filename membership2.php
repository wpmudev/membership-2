<?php
/**
 * Plugin Name: Membership 2 Pro
 * Plugin URI:  https://premium.wpmudev.org/project/membership/
 * Version:     1.0.2.8-Beta-3
 * Description: The most powerful, easy to use and flexible membership plugin for WordPress sites available.
 * Author:      WPMU DEV
 * Author URI:  http://premium.wpmudev.org/
 * WDP ID:      1003656
 * License:     GNU General Public License (Version 2 - GPLv2)
 * Text Domain: membership2
 *
 * @package Membership2
 */

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
function membership2_init_pro_app() {
	if ( defined( 'MS_PLUGIN' ) ) {
		if ( is_admin() ) {
			// Can happen in Multisite installs where a sub-site has activated the
			// plugin and then the plugin is also activated in network-admin.
			printf(
				'<div class="notice error"><p><strong>%s</strong>: %s</p></div>',
				sprintf(
					esc_html__( 'Could not load the plugin %s, because another version of the plugin is already loaded', 'membership2' ),
					'Membership 2 Pro'
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
	define( 'MS_PLUGIN_VERSION', '1.0.2.8-Beta-3' );

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

	$externals = array(
		dirname( __FILE__ ) . '/lib/wpmudev-dashboard/wpmudev-dash-notification.php',
		dirname( __FILE__ ) . '/lib/wpmu-lib/core.php',
		dirname( __FILE__ ) . '/lib/wdev-frash/module.php',
	);

	foreach ( $externals as $path ) {
		require_once $path;
	}

	// Register the current plugin.
	do_action(
		'wdev-register-plugin',
		/*             Plugin ID */ plugin_basename( __FILE__ ),
		/*          Plugin Title */ 'Membership 2',
		/* https://wordpress.org */ '/plugins/membership/',
		/*      Email Button CTA */ false,
		/*  getdrip Plugin param */ false
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

	if ( (defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'DEV_DEBUG' ) && WDEV_DEBUG) ) {
		// Load development/testing code before the plugin is initialized.
		$testfile = dirname( __FILE__ ) . '/tests/wp/init.php';
		if ( file_exists( $testfile ) ) { include $testfile; }
	}

	/**
	 * Create an instance of the plugin object.
	 *
	 * This is the primary entry point for the Membership plugin.
	 *
	 * @since  1.0.0
	 */
	MS_Plugin::instance();
}

if ( ! class_exists( 'MS_Plugin' ) ) {

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
 * @since  1.0.0
 *
 * @return object Plugin instance.
 */
class MS_Plugin {

	/**
	 * Singletone instance of the plugin.
	 *
	 * @since  1.0.0
	 *
	 * @var MS_Plugin
	 */
	private static $instance = null;

	/**
	 * Modifier values.
	 *
	 * @since  1.0.0
	 *
	 * @var array
	 */
	private static $modifiers = array();

	/**
	 * The WordPress internal plugin identifier.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $id;

	/**
	 * The plugin name.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $name;

	/**
	 * The plugin version.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $version;

	/**
	 * The plugin file.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $file;

	/**
	 * The plugin path.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $dir;

	/**
	 * The plugin URL.
	 *
	 * @since  1.0.0
	 * @var   string
	 */
	private $url;

	/**
	 * The plugin settings.
	 *
	 * @since  1.0.0
	 * @var   MS_Model_Settings
	 */
	private $settings;

	/**
	 * The plugin add-on settings.
	 *
	 * @since  1.0.0
	 * @var   MS_Model_Addon
	 */
	private $addon;

	/**
	 * The main controller of the plugin.
	 *
	 * @since  1.0.0
	 * @var   MS_Controller_Plugin
	 */
	private $controller;

	/**
	 * The API controller (for convenience)
	 *
	 * @since  1.0.0
	 * @var    MS_Controller_Api
	 */
	public static $api = null;

	/**
	 * Plugin constructor.
	 *
	 * Set properties, registers hooks and loads the plugin.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {

		/**
		 * Actions to execute before the plugin construction starts.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_init', $this );

		/**
		 * Deprecated action.
		 *
		 * @since  1.0.0
		 * @deprecated since 2.0.0
		 */
		do_action( 'ms_plugin_construct_start', $this );

		/** Setup plugin properties */
		$this->id = MS_PLUGIN;
		$this->name = MS_PLUGIN_NAME;
		$this->version = MS_PLUGIN_VERSION;
		$this->file = __FILE__;
		$this->dir = plugin_dir_path( __FILE__ );
		$this->url = plugin_dir_url( __FILE__ );

		add_filter(
			'ms_class_path_overrides',
			array( $this, 'ms_class_path_overrides' )
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

		// Plugin activation Hook.
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
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		$this->settings = MS_Factory::load( 'MS_Model_Settings' );

		/**
		 * Creates and Filters the Addon Model.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		$this->addon = MS_Factory::load( 'MS_Model_Addon' );

		add_filter(
			'plugin_action_links_' . MS_PLUGIN,
			array( $this, 'plugin_settings_link' )
		);

		add_filter(
			'network_admin_plugin_action_links_' . MS_PLUGIN,
			array( $this, 'plugin_settings_link' )
		);

		// Grab instance of self.
		self::$instance = $this;

		/**
		 * Actions to execute when the Plugin object has successfully constructed.
		 *
		 * @since  1.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'ms_plugin_construct_end', $this );
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
	 * Loads primary plugin controllers.
	 *
	 * Related Action Hooks:
	 * - setup_theme
	 *
	 * @since  1.0.0
	 */
	public function ms_plugin_constructing() {
		/**
		 * Creates and Filters the Plugin Controller.
		 *
		 * ---> MAIN ENTRY POINT CONTROLLER FOR PLUGIN <---
		 *
		 * @uses  MS_Controller_Plugin
		 * @since  1.0.0
		 */
		$this->controller = MS_Factory::create( 'MS_Controller_Plugin' );
	}

	/**
	 * Register plugin custom post types.
	 *
	 * @since  1.0.0
	 */
	public function register_custom_post_types() {
		do_action( 'ms_plugin_register_custom_post_types_before', $this );

		$cpts = apply_filters(
			'ms_plugin_register_custom_post_types',
			array(
				MS_Model_Membership::get_post_type() => MS_Model_Membership::get_register_post_type_args(),
				MS_Model_Relationship::get_post_type() => MS_Model_Relationship::get_register_post_type_args(),
				MS_Model_Invoice::get_post_type() => MS_Model_Invoice::get_register_post_type_args(),
				MS_Model_Communication::get_post_type() => MS_Model_Communication::get_register_post_type_args(),
				MS_Model_Event::get_post_type() => MS_Model_Event::get_register_post_type_args(),
			)
		);

		foreach ( $cpts as $cpt => $args ) {
			MS_Helper_Utility::register_post_type( $cpt, $args );
		}
	}

	/**
	 * Add rewrite rules.
	 *
	 * @since  1.0.0
	 */
	public function add_rewrite_rules() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		// Gateway return - IPN.
		add_rewrite_rule(
			'ms-payment-return/(.+)/?',
			'index.php?paymentgateway=$matches[1]',
			'top'
		);

		// Alternative payment return URL: Old Membership plugin.
		$use_old_ipn = apply_filters( 'ms_legacy_paymentreturn_url', true );
		if ( $use_old_ipn && ! class_exists( 'M_Membership' ) ) {
			add_rewrite_rule(
				'paymentreturn/(.+)/?',
				'index.php?paymentgateway=$matches[1]',
				'top'
			);
		}

		/* Media / download ----- */
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
		/* End: Media / download ----- */

		do_action( 'ms_plugin_add_rewrite_rules', $this );
	}

	/**
	 * Add rewrite tags.
	 *
	 * @since  1.0.0
	 */
	public function add_rewrite_tags() {
		// Membership site pages.
		add_rewrite_tag( '%ms_page%', '(.+)' );

		// Gateway return - IPN.
		add_rewrite_tag( '%paymentgateway%', '(.+)' );

		// Media / download.
		add_rewrite_tag( '%protectedfile%', '(.+)' );

		do_action( 'ms_plugin_add_rewrite_tags', $this );
	}

	/**
	 * Actions executed in plugin activation.
	 *
	 * @since  1.0.0
	 */
	public function plugin_activation() {
		// Prevent recursion during plugin activation.
		$refresh = lib3()->session->get( 'refresh_url_rules' );
		if ( $refresh ) { return; }

		// Update the Membership2 database entries after activation.
		MS_Model_Upgrade::update( true );

		do_action( 'ms_plugin_activation', $this );
	}

	/**
	 * Redirect page and request plugin to flush the WordPress rewrite rules
	 * on next request.
	 *
	 * @since  1.0.0
	 * @param string $url The URL to load after flushing the rewrite rules.
	 */
	static public function flush_rewrite_rules( $url = false ) {
		if ( isset( $_GET['ms_flushed'] ) && 'yes' == $_GET['ms_flushed'] ) {
			$refresh = true;
		} else {
			$refresh = lib3()->session->get( 'refresh_url_rules' );
		}

		if ( $refresh ) { return; }

		lib3()->session->add( 'refresh_url_rules', true );

		// The URL param is only to avoid cache.
		$url = esc_url_raw(
			add_query_arg( 'ms_ts', time(), $url )
		);
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Flush the WordPress rewrite rules.
	 *
	 * @since  1.0.0
	 */
	public function maybe_flush_rewrite_rules() {
		$refresh = lib3()->session->get_clear( 'refresh_url_rules' );
		if ( ! $refresh ) { return; }

		// Set up the plugin specific rewrite rules again.
		$this->add_rewrite_rules();
		$this->add_rewrite_tags();

		do_action( 'ms_plugin_flush_rewrite_rules', $this );

		$url = remove_query_arg( 'ms_ts' );
		$url = esc_url_raw( add_query_arg( 'ms_flushed', 'yes', $url ) );
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
			array_shift( $path_array );
			$alt_dir = array_pop( $path_array );
			$sub_path = implode( '/', $path_array );

			$filename = str_replace( '_', '-', 'class-' . $class . '.php' );
			$file_path = $basedir . '/app/' . strtolower( $sub_path . '/' . $filename );
			$file_path_alt = $basedir . '/app/' . strtolower( $sub_path . '/' . $alt_dir . '/' . $filename );

			/**
			 * Overrides the filename and path.
			 *
			 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @param array $links WordPress default array of links.
	 * @return array Array of links with settings page links added.
	 */
	public function plugin_settings_link( $links ) {
		if ( ! is_network_admin() ) {
			$text = __( 'Settings', 'membership2' );
			$url = MS_Controller_Plugin::get_admin_url( 'settings' );

			if ( $this->settings->initial_setup ) {
				$url = MS_Controller_Plugin::get_admin_url();
			}

			/**
			 * Filter the plugin settings link.
			 *
			 * @since  1.0.0
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
	 * @since  1.0.0
	 *
	 * @static
	 * @access public
	 *
	 * @return MS_Plugin
	 */
	public static function instance() {
		if ( ! self::$instance ) {
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key Name of the modifier.
	 * @return mixed The modifier value or null.
	 */
	public static function get_modifier( $key ) {
		$res = null;

		if ( isset( self::$modifiers[ $key ] ) ) {
			$res = self::$modifiers[ $key ];
		} elseif ( defined( $key ) ) {
			$res = constant( $key );
		}

		return $res;
	}

	/**
	 * Changes a modifier option.
	 *
	 * @see get_modifier() for more details.
	 * @since  1.0.0
	 * @api
	 *
	 * @param  string $key Name of the modifier.
	 * @param  mixed  $value Value of the modifier. `null` unsets the modifier.
	 */
	public static function set_modifier( $key, $value = null ) {
		if ( null === $value ) {
			unset( self::$modifiers[ $key ] );
		} else {
			self::$modifiers[ $key ] = $value;
		}
	}

	/**
	 * This funciton initializes the api property for easy access to the plugin
	 * API. This function is *only* called by MS_Controller_Api::__construct()!
	 *
	 * @since  1.0.0
	 * @internal
	 * @param MS_Controller_Api $controller The initialized API controller.
	 */
	public static function set_api( $controller ) {
		self::$api = $controller;
	}

	/**
	 * Returns property associated with the plugin.
	 *
	 * @since  1.0.0
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
} // end: if ! class_exists


/**
 * This is a hack to prevent cookie issue in IE11 and EDGE
 * Need to refactor in later
 *
 * @since 1.0.2.9
 */
if( isset( $_REQUEST['ms_ajax'] ) ) {
    if( $_REQUEST['ms_ajax'] == 1 ) {
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
            }else{
                $resp['loggedin'] = true;
		$resp['success'] = __( 'Logging in...', 'membership2' );
                
                /**
                * Allows a custom redirection after login.
                * Empty value will use the default redirect option of the login form.
                *
                * @since  1.0.0
                */
               $enforce = false;
               if( isset( $_POST['redirect_to'] ) ) {
                   $resp['redirect'] = apply_filters( 'ms-ajax-login-redirect', $_POST['redirect_to'], $user_signon->ID );
               }else{
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
}else{
    membership2_init_pro_app();
}