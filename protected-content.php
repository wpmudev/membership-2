<?php
/**
Plugin Name: Protected Content
Plugin URI:  https://premium.wpmudev.org/project/protected-content/
Version:     1.0.4.7
Description: The most powerful, easy to use and flexible membership plugin for WordPress sites available.
Author:      WPMU DEV
Author URI:  http://premium.wpmudev.org/
WDP ID:      928907
License:     GNU General Public License (Version 2 - GPLv2)
Text Domain: protected-content
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
 * Include WPMUDev Dashboard
 */
global $wpmudev_notices;
$wpmudev_notices[] = array(
	'id' => 928907,
	'name' => 'Protected Content',
	'screens' => array(
		'toplevel_page_protected-content',
		'protect-content_page_protected-content-members',
		'protect-content_page_protected-content-setup',
		'protect-content_page_protected-content-billing',
		'protect-content_page_protected-content-coupons',
		'protect-content_page_protected-content-addon',
		'protect-content_page_protected-content-settings',
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
 * Plugin text domain.
 *
 * @since 1.0.0
 */
define( 'MS_TEXT_DOMAIN', 'protected-content' );

/**
 * Plugin name dir constant.
 *
 * @since 1.0.0
 */
define( 'MS_PLUGIN_NAME', dirname( plugin_basename( __FILE__ ) ) );

/**
 * Plugin version
 *
 * @since 1.0.0
 */
define( 'MS_PLUGIN_VERSION', '1.0.4.7' );

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
	// CONTROLLERS
	$controllers_base = 'app/controller/';
	$controllers = array(
		'MS_Controller_Admin_Bar' => 'class-ms-controller-admin-bar.php',
		'MS_Controller_Membership_Content_Type' => 'membership/class-ms-controller-membership-content-type.php',
		'MS_Controller_Membership_Metabox' => 'class-ms-controller-membership-metabox.php',
	);

	// HELPERS
	$helpers_base = 'app/helper/';
	$helpers = array(
		'MS_Helper_List_Table' => 'class-ms-helper-list-table.php',
		'MS_Helper_List_Table_Membership_Group' => 'list-table/class-ms-helper-list-table-membership-group.php',
		'MS_Helper_List_Table_Rule_Buddypress_Blog' => 'list-table/rule/class-ms-helper-list-table-rule-buddypress-blog.php',
		'MS_Helper_List_Table_Rule_Buddypress_Group' => 'list-table/rule/class-ms-helper-list-table-rule-buddypress-group.php',
		'MS_Helper_List_Table_Rule_Custom_Post_Type' => 'list-table/rule/class-ms-helper-list-table-rule-custom-post-type.php',
		'MS_Helper_List_Table_Rule_Custom_Post_Type_Group' => 'list-table/rule/class-ms-helper-list-table-rule-custom-post-type-group.php',
		'MS_Helper_List_Table_Rule_Url_Group' => 'list-table/rule/class-ms-helper-list-table-rule-url-group.php',
		'MS_Helper_List_Table_Rule_Replace_Menu' => 'list-table/rule/class-ms-helper-list-table-rule-replace-menu.php',
		'MS_Helper_List_Table_Rule_Replace_Menulocation' => 'list-table/rule/class-ms-helper-list-table-rule-replace-menulocation.php',
	);

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
		'MS_Model_Custom_Post_Type' => 'class-ms-model-custom-post-type.php',
		'MS_Model_Gateway_Paypal_Single' => 'gateway/class-ms-model-gateway-paypal-single.php',
		'MS_Model_Gateway_Paypal_Standard' => 'gateway/class-ms-model-gateway-paypal-standard.php',
		'MS_Model_Buddypress_Group_Creation' => 'rule/buddypress/class-ms-model-rule-buddypress-group-creation.php',
		'MS_Model_Buddypress_Private_Msg' => 'rule/buddypress/class-ms-model-rule-buddypress-private-msg.php',
		'MS_Model_Rule_Custom_Post_Type' => 'rule/class-ms-model-rule-custom-post-type.php',
		'MS_Model_Rule_Custom_Post_Type_Group' => 'rule/class-ms-model-rule-custom-post-type-group.php',
		'MS_Model_Rule_Url_Group' => 'rule/class-ms-model-rule-url-group.php',
		'MS_Model_Rule_Replace_Menu' => 'rule/class-ms-model-rule-replace-menu.php',
		'MS_Model_Rule_Replace_Menulocation' => 'rule/class-ms-model-rule-replace-menulocation.php',
		'MS_Model_Membership_Relationship' => 'class-ms-model-membership_relationship.php',
	);

	// VIEWS
	$views_base = 'app/view/';
	$views = array(
		'MS_View_Admin_Bar' => 'class-ms-view-admin-bar.php',
		'MS_View_Membership_Accessible_Content' => 'membership/class-ms-view-membership-accessible-content.php',
		'MS_View_Membership_Choose_Type' => 'membership/class-ms-view-membership-choose-type.php',
		'MS_View_Membership_Overview_Content_Type' => 'membership/overview/class-ms-view-membership-overview-content-type.php',
		'MS_View_Membership_Setup_Content_Type' => 'membership/class-ms-view-membership-setup-content-type.php',
		'MS_View_Membership_Setup_Dripped' => 'membership/class-ms-view-membership-setup-dripped.php',
		'MS_View_Membership_Setup_Payment' => 'membership/class-ms-view-membership-setup-payment.php',
		'MS_View_Membership_Setup_Protected_Content' => 'membership/class-ms-view-membership-setup-protected-content.php',
		'MS_View_Membership_Setup_Tier' => 'membership/class-ms-view-membership-setup-tier.php',
		'MS_View_Shortcode_Membership_Signup' => 'shortcode/class-ms-view-shortcode-membership-signup.php',
		'MS_View_Shortcode_Membership_Login' => 'shortcode/class-ms-view-shortcode-membership-login.php',
		'MS_View_Shortcode_Membership_Register_User' => 'shortcode/class-ms-view-shortcode-membership-register-user.php',
	);

	foreach ( $controllers as $key => $path ) { $overrides[ $key ] = $controllers_base . $path; }
	foreach ( $helpers as $key => $path ) { $overrides[ $key ] = $helpers_base . $path; }
	foreach ( $models as $key => $path ) { $overrides[ $key ] = $models_base . $path; }
	foreach ( $views as $key => $path ) { $overrides[ $key ] = $views_base . $path; }

	return $overrides;
}
add_filter( 'ms_class_path_overrides', 'ms_class_path_overrides' );

/**
 * Hooks 'ms_class_file_override'.
 *
 * Overrides file class paths.
 *
 * @since 1.0.0
 *
 * @param  array $overrides Array passed in by filter.
 * @return array(class=>path) Classes with new file paths.
 */
function ms_class_file_override( $file ) {
	// Override all list-table paths.
	$file = str_replace( 'helper/list/table', 'helper/list-table', $file );
	return $file;
}
add_filter( 'ms_class_file_override', 'ms_class_file_override' );

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
	 * @access private
	 * @var MS_Plugin
	 */
	private static $instance = null;

	/**
	 * The plugin name.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var name
	 */
	private $name;

	/**
	 * The plugin version.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var version
	 */
	private $version;

	/**
	 * The plugin file.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var file
	 */
	private $file;

	/**
	 * The plugin path.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var dir
	 */
	private $dir;

	/**
	 * The plugin URL.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var url
	 */
	private $url;

	/**
	 * The plugin settings.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var settings
	 */
	private $settings;

	/**
	 * The plugin add-on settings.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var addon
	 */
	private $addon;

	/**
	 * The main controller of the plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var controller
	 */
	private $controller;

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
		 * @since 1.0.0
		 * @param object $this The MS_Plugin object.
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

		/*
		 * Load membership integrations.
		 */
		MS_Integration::load_integrations();

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
		 * Main entry point controller for plugin.
		 *
		 * @uses  MS_Controller_Plugin
		 * @since 1.0.0
		 * @param object $this The MS_Plugin object.
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
				MS_Model_Membership_Relationship::$POST_TYPE => MS_Model_Membership_Relationship::get_register_post_type_args(),
				MS_Model_Invoice::$POST_TYPE => MS_Model_Invoice::get_register_post_type_args(),
				MS_Model_Communication::$POST_TYPE => MS_Model_Communication::get_register_post_type_args(),
				MS_Model_Coupon::$POST_TYPE => MS_Model_Coupon::get_register_post_type_args(),
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
		// Gateway return - IPN.
		add_rewrite_rule(
			'^ms-payment-return/(.+)/?$',
			'index.php?paymentgateway=$matches[1]',
			'top'
		);

		// Media / download
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		if ( ! empty( $settings->downloads['protection_enabled'] )
			&& ! empty( $settings->downloads['masked_url'] )
		) {
			add_rewrite_rule(
				sprintf( '^%1$s(.*)/?$', $settings->downloads['masked_url'] ),
				'index.php?protectedfile=$matches[1]',
				'top'
			);
		}

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
		$refresh = WDev()->store_get( 'refresh_url_rules' );
		if ( $refresh ) { return; }

		// Update the Protected Content database entries after activation.
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
		$refresh = WDev()->store_get( 'refresh_url_rules' );
		if ( $refresh ) { return; }

		WDev()->store_add( 'refresh_url_rules', true );
		$url = add_query_arg( 'ms_ts', time(), $url );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Flush the WordPress rewrite rules.
	 *
	 * @since  1.0.4.4
	 */
	public function maybe_flush_rewrite_rules() {
		$refresh = WDev()->store_get_clear( 'refresh_url_rules' );
		if ( ! $refresh ) { return; }

		// Flush WP rewrite rules.
		flush_rewrite_rules();

		// Set up the plugin specific rewrite rules again.
		$this->add_rewrite_rules();
		$this->add_rewrite_tags();

		do_action( 'ms_plugin_flush_rewrite_rules', $this );

		$url = remove_query_arg( 'ms_ts' );
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
	 * @access private
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

		/**
		 * Case 1: The class-path is explicitly defined in $Path_overrides.
		 * Simply use the defined path to load the class.
		 */
		if ( array_key_exists( $class, $Path_overrides ) ) {
			$file_path = $basedir . '/' . $Path_overrides[ $class ];

			/**
			 * Overrides the filename and path.
			 *
			 * @since 1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$file_path = apply_filters( 'ms_class_file_override', $file_path, $this );

			include_once $file_path;
			return true;
		}
		/**
		 * Case 2: The class-path is not explicitely defined in $Path_overrides.
		 * Use /app/ path and class-name to build the file-name.
		 */
		else if ( substr( $class, 0, 3 ) == 'MS_' ) {
			$path_array = explode( '_', $class );
			array_shift( $path_array );
			array_pop( $path_array );
			$sub_path = implode( '/', $path_array );
			$filename = str_replace( '_', '-', 'class-' . $class . '.php' );
			$file_path = $basedir . '/app/' . strtolower( $sub_path . '/' . $filename );

			/**
			 * Overrides the filename and path.
			 *
			 * @since 1.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$file_path = apply_filters( 'ms_class_file_override', $file_path, $this );

			include_once $file_path;
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
