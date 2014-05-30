<?php
/*
Plugin Name: Membership Premium Development
Version: 4.0.0
Plugin URI: http://premium.wpmudev.org/project/membership
Description: The most powerful, easy to use and flexible membership plugin for WordPress, Multisite and BuddyPress sites available. Offer downloads, posts, pages, forums and more to paid members.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
WDP ID: 140
License: GNU General Public License (Version 2 - GPLv2)
Text Domain: wpmudev_membership
 */

/**
 * @copyright Incsub (http://incsub.com/)
 * Author - Fabio Jun, Rheinard Korf
 * Contributors - Joji Mori 
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
require_once dirname( __FILE__ ) . '/extra/wpmudev-dash-notification.php';

/** 
 * Add WordPress core functionality: WP_List_Table
 */
if( ! function_exists( '_ms_debug_log' ) ) {
	function _ms_debug_log( $message ) {
	
		if( defined( 'MS_MEMBERSHIP_DEBUG' ) && MS_MEMBERSHIP_DEBUG == true ) {
	
			if( is_array( $message ) || is_object( $message ) ) {
				$message = print_r( $message, true );
			}
			if( defined( 'MS_MEMBERSHIP_DEBUG_LEVEL' ) && MS_MEMBERSHIP_DEBUG_LEVEL == 'adv' ) {
				$message .= ' - ' . print_r( debug_backtrace(), true );
			}
			error_log( $message );
		}
	}
}

/**
 * Membership text domain.
 *
 * @since 4.0.0
 */
define('MS_TEXT_DOMAIN', 'wpmudev_membership' );

/**
 * Constant used in wp_enqueue_style and wp_enqueue_script version.
 *
 * @since 4.0.0
 * @todo Decide if its still needed.
 */
define('MS_VERSION_DT', '2014-04-04' );

/**
 * Plugin name dir constant.
 *
 * @since 4.0.0
 */
define( 'MS_PLUGIN_NAME', dirname( plugin_basename( __FILE__ ) ) );

/**
 * Plugin version
 *
 * @since 4.0.0
 */
define( 'MS_PLUGIN_VERSION', '4.0.0.0' );

/**
 * Hooks 'membership_class_path_overrides'. 
 *
 * Overrides plugin class paths to adhere to naming conventions
 * where object names are separated by underscores or for special cases.
 *
 * @since 4.0.0
 *
 * @param  array $overrides Array passed in by filter.
 * @return array(class=>path) Classes with new file paths.
 */
function membership_class_path_overrides( $overrides ) {

	$overrides['MS_Controller_Admin_Bar'] =  "app/controller/class-ms-controller-admin-bar.php";
	$overrides['MS_Controller_Membership_Metabox'] =  "app/controller/class-ms-controller-membership-metabox.php";
	$overrides['MS_Helper_List_Table'] =  "app/helper/class-ms-helper-list-table.php";
	$overrides['MS_Helper_List_Table_Rule_Url_Group'] =  "app/helper/list-table/rule/class-ms-helper-list-table-rule-url-group.php";
	$overrides['MS_Model_Communication_After_Finishes'] =  "app/model/communication/class-ms-model-communication-after-finishes.php";
	$overrides['MS_Model_Communication_After_Payment_Made'] =  "app/model/communication/class-ms-model-communication-after-payment-made.php";
	$overrides['MS_Model_Communication_Before_Finishes'] =  "app/model/communication/class-ms-model-communication-before-finishes.php";
	$overrides['MS_Model_Communication_Before_Payment_Due'] =  "app/model/communication/class-ms-model-communication-before-payment-due.php";
	$overrides['MS_Model_Communication_Before_Trial_Finishes'] =  "app/model/communication/class-ms-model-communication-before-trial-finishes.php";
	$overrides['MS_Model_Communication_Credit_Card_Expire'] =  "app/model/communication/class-ms-model-communication-credit-card-expire.php";
	$overrides['MS_Model_Communication_Failed_Payment'] =  "app/model/communication/class-ms-model-communication-failed-payment.php";
	$overrides['MS_Model_Communication_Info_Update'] =  "app/model/communication/class-ms-model-communication-info-update.php";
	$overrides['MS_Model_Custom_Post_Type'] =  "app/model/class-ms-model-custom-post-type.php";
	$overrides['MS_Model_Gateway_Paypal_Single'] =  "app/model/gateway/class-ms-model-gateway-paypal-single.php";
	$overrides['MS_Model_Gateway_Paypal_Standard'] =  "app/model/gateway/class-ms-model-gateway-paypal-standard.php";
	$overrides['MS_Model_Rule_Post_Category'] = "app/model/rule/class-ms-model-rule-post-category.php";
	$overrides['MS_Model_Rule_Url_Group'] = "app/model/rule/class-ms-model-rule-url-group.php";
	$overrides['MS_Model_Membership_Relationship'] = "app/model/class-ms-model-membership_relationship.php";
	$overrides['MS_View_Admin_Bar'] =  "app/view/class-ms-view-admin-bar.php";
	$overrides['MS_View_Settings_Gateway_Manual'] =  "app/view/settings/class-ms-view-settings-gateway-manual.php";
	$overrides['MS_View_Settings_Gateway_Paypal'] =  "app/view/settings/class-ms-view-settings-gateway-paypal.php";
	$overrides['MS_View_Shortcode_Membership_Signup'] =  "app/view/shortcode/class-ms-view-shortcode-membership-signup.php";
	$overrides['MS_View_Shortcode_Membership_Login'] =  "app/view/shortcode/class-ms-view-shortcode-membership-login.php";
	$overrides['MS_View_Shortcode_Membership_Register_User'] =  "app/view/shortcode/class-ms-view-shortcode-membership-register-user.php";
	
	
	return $overrides;
}
add_filter( 'membership_class_path_overrides', 'membership_class_path_overrides' );

/**
 * Hooks 'membership_class_file_override'. 
 *
 * Overrides file class paths.
 *
 * @since 4.0.0
 *
 * @param  array $overrides Array passed in by filter.
 * @return array(class=>path) Classes with new file paths.
 */
function membership_class_file_override( $file ) {

	/** Override all list-table paths. */
	$file = str_replace( 'helper/list/table', 'helper/list-table', $file );

	return $file;
}
add_filter( 'membership_class_file_override', 'membership_class_file_override' );

/**
 * Primary Membership plugin class.
 *
 * Initialises the autoloader and required plugin hooks.
 * Control of plugin is passed to the MVC implementation found
 * inside the /app/ folder.
 *
 * @since 4.0.0
 *
 * @return object Plugin instance.
 */
class MS_Plugin {
	
	/**
	 * Singletone instance of the plugin.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var MS_Plugin
	 */
	private static $instance = null;

	/**
	 * The plugin name.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var name
	 */
	private $name;
	
	/**
	 * The plugin version.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var version
	 */
	private $version;
	
	/**
	 * The plugin file.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var file
	 */
	private $file;	
	
	/**
	 * The plugin path.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var dir
	 */
	private $dir;	

	/**
	 * The plugin URL.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _url
	 */
	private $url;

	/**
	 * The plugin settings.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var settings
	 */
	private $settings;
	
		/**
	 * The plugin add-on settings.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var addon
	 */
	private $addon;
	
	/**
	 * Plugin constructor.
	 *
	 * Set properties, registers hooks and loads the plugin.
	 *
	 * @since 4.0.0
	 */
	function __construct() {
		
		/** Setup plugin properties */
		$this->name = MS_PLUGIN_NAME;
		$this->version = MS_PLUGIN_VERSION;		
		$this->file = __FILE__;
		$this->dir = plugin_dir_path(__FILE__);
		$this->url = plugin_dir_url(__FILE__);
		
		/** Load textdomain, localization. */
		load_plugin_textdomain( MS_TEXT_DOMAIN, false, $this->name . '/languages/' );
		
		/** Actions to execute before construction is complete. */
		do_action( 'membership_plugin_loading', $this ); 
				
		/** Creates the class autoloader */
		spl_autoload_register( array( &$this, 'class_loader' ) );

		/** 
		 * Hooks init to register custom post types.
		 */
		add_action( 'init', array( &$this, 'register_custom_post_type' ), 1 );
		
		/**
		 * Hooks init to create the primary plugin controller.
		 */
		add_action( 'init', array( &$this, 'membership_plugin_constructing' ));
		
		$this->settings = apply_filters( 'membership_model_settings', MS_Model_Settings::load(), $this );
		$this->addon = apply_filters( 'membership_model_addon', MS_Model_Addon::load(), $this );

		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( &$this,'plugin_settings_link' ) );
		add_filter( 'network_admin_plugin_action_links_' . plugin_basename(__FILE__), array( &$this, 'plugin_settings_link' ) );

		/** Grab instance of self. */
		self::$instance = $this;
		
		/** Actions to execute when plugin is loaded. */
		do_action( 'membership_plugin_loaded', $this ); 
		
	}

	/**
	 * Loads primary plugin controllers.
	 *
	 * @since 4.0.0
	 * @return void
	 */	
	public function membership_plugin_constructing() {
		
		/** Main entry point controller for plugin. */
		$this->controller = new MS_Controller_Plugin();		
	}

	/**
	 * Register membership plugin custom post types. 
	 *
	 * @todo Better configure custom post type args
	 *
	 * @since 4.0.0
	 * @return void
	 */	
	public function register_custom_post_type() {
		
		/**
		 * Register the Membership post type. 
		 *
		 * @since 4.0.0
		 */
		register_post_type( 'ms_membership',
			apply_filters( 'ms_register_post_type_ms_membership',
				array(
					'labels' => array(
						'name' => __( 'Memberships', MS_TEXT_DOMAIN ),
						'singular_name' => __( 'Membership', MS_TEXT_DOMAIN ),
						'menu_name' => __( 'Membership', MS_TEXT_DOMAIN ),
						'all_items' => __( 'All Memberships', MS_TEXT_DOMAIN ),
						'add_new' => __('New Membership', MS_TEXT_DOMAIN ),
						'add_new_item' => __('New Membership', MS_TEXT_DOMAIN ),
						'edit' => __( 'Edit', MS_TEXT_DOMAIN ),
						'view_item' => __( 'View Membership', MS_TEXT_DOMAIN ),
						'search_items' => __( 'Search Memberships', MS_TEXT_DOMAIN ),
						'not_found' => __( 'No Memberships Found', MS_TEXT_DOMAIN )
					),
					'description' => __( 'Memberships user can join to.', MS_TEXT_DOMAIN ),
					'show_ui' => false,
					'show_in_menu' => false,
					'menu_position' => 70, // below Users
					'menu_icon' => $this->url . "/assets/images/members.png",
					'public' => true,
					'has_archive' => false,
					'publicly_queryable' => false,
					'supports' => false,
					'capability_type' => apply_filters( 'mp_memberships_capability', 'page' ),
					'hierarchical' => false
				) 
			) 
		);
		
		/**
		 * Register the Transaction post type. 
		 *
		 * @since 4.0.0
		 */		
		register_post_type( 'ms_transaction',
			apply_filters( 'ms_register_post_type_ms_transaction',
				array(
					'labels' => array(
						'name' => __( 'transactions', MS_TEXT_DOMAIN ),
						'singular_name' => __( 'transaction', MS_TEXT_DOMAIN ),
						'edit' => __( 'Edit', MS_TEXT_DOMAIN ),
						'view_item' => __( 'View transaction', MS_TEXT_DOMAIN ),
						'search_items' => __( 'Search transactions', MS_TEXT_DOMAIN ),
						'not_found' => __( 'No transactions Found', MS_TEXT_DOMAIN )
					),
					'description' => __( 'transactions user can join to.', MS_TEXT_DOMAIN ),					
					'public' => false,
					'has_archive' => false,
					'publicly_queryable' => false,
					'supports' => false,
					'capability_type' => apply_filters( 'mp_transactions_capability', 'page' ),
					'hierarchical' => false
				) 
			) 
		);
		
		/**
		 * Register the Communication post type. 
		 *
		 * @since 4.0.0
		 */		
		register_post_type( 'ms_communication',
			apply_filters( 'ms_register_post_type_ms_communication',
				array(
					'labels' => array(
						'name' => __( 'communications', MS_TEXT_DOMAIN ),
						'singular_name' => __( 'communication', MS_TEXT_DOMAIN ),
						'edit' => __( 'Edit', MS_TEXT_DOMAIN ),
						'view_item' => __( 'View communication', MS_TEXT_DOMAIN ),
						'search_items' => __( 'Search communications', MS_TEXT_DOMAIN ),
						'not_found' => __( 'No communications Found', MS_TEXT_DOMAIN )
					),
					'description' => __( 'communications user can join to.', MS_TEXT_DOMAIN ),
					'public' => false,
					'has_archive' => false,
					'publicly_queryable' => false,
					'supports' => false,
					'capability_type' => apply_filters( 'mp_communications_capability', 'page' ),
					'hierarchical' => false
				) 
			) 
		);
		
		/**
		 * Register the Coupon post type.
		 *
		 * @since 4.0.0
		 */
		register_post_type( 'ms_coupon',
			apply_filters( 'ms_register_post_type_ms_coupon',
				array(
					'labels' => array(
						'name' => __( 'coupons', MS_TEXT_DOMAIN ),
						'singular_name' => __( 'coupon', MS_TEXT_DOMAIN ),
						'edit' => __( 'Edit', MS_TEXT_DOMAIN ),
						'view_item' => __( 'View coupon', MS_TEXT_DOMAIN ),
						'search_items' => __( 'Search coupons', MS_TEXT_DOMAIN ),
						'not_found' => __( 'No coupons Found', MS_TEXT_DOMAIN )
				),
				'description' => __( 'coupons code to get discount in membership price.', MS_TEXT_DOMAIN ),
				'public' => false,
				'has_archive' => false,
				'publicly_queryable' => false,
				'supports' => false,
				'capability_type' => apply_filters( 'mp_coupons_capability', 'page' ),
				'hierarchical' => false
				)
			)
		);
	}
	
	
	/**
	 * Class autoloading callback function.
	 *
	 * Uses the **MS_** namespace to autoload classes when called. 
	 * Avoids creating include functions for each file in the MVC structure.
	 * **MS_** namespace ONLY will be based on folder structure in /app/
	 *
	 * @since 4.0.0
	 * @access private
	 *
	 * @param  string $class Uses PHP autoloader function.
	 * @return boolean
	 */
	private function class_loader( $class ) {

		$basedir = dirname( __FILE__ );
		$namespaces = array( 'MS_' );
		
		$path_overrides = apply_filters( 'membership_class_path_overrides', array() );
		
		/** 
		 * Restrict class autoloading to provided namespaces.
		 *
		 * This prevents autoloading from interfering with other plugins in their own namespaces.
		 *
		 * @since 4.0.0
		 */
		foreach ( $namespaces as $namespace ) {
			switch ( $namespace ) {
			
				/** Use /app/ path and structure only for MS_ classes */
				case "MS_":
					if ( !array_key_exists( trim( $class ), $path_overrides ) ) {
						if ( substr( $class, 0, strlen( $namespace ) ) == $namespace ) {
							$sub_path = strtolower( str_replace( 'MS_', '', $class ) );
							$path_array = explode( '_', $sub_path );
							array_pop( $path_array );
							$sub_path = implode( '_', $path_array );
							$filename = $basedir . str_replace( '_', DIRECTORY_SEPARATOR, "_app_{$sub_path}_" ) . strtolower( str_replace( '_', 
							'-', "class-{$class}.php" ) );
							$filename = apply_filters( 'membership_class_file_override', $filename );
							if ( is_readable( $filename ) ) {
								require $filename;
								return true;
							}
						}						
					} else {
						$filename = $basedir . '/' . $path_overrides[ $class ];
						$filename = apply_filters( 'membership_class_file_override', $filename );
						if ( is_readable( $filename ) ) {
							require $filename;
							return true;
						}						
					}
					break; 
			}
		}

		return false;
	}
	

	/**
	 * Add link to settings page in plugins page.
	 * 
	 * @todo Adjust multisite link. Maybe show wizard link for first access.
	 *
	 * @since 4.0.0
	 * @access private
	 *
	 * @param array $links Wordpress default array of links.
	 * @return array Array of links with settings page links added.
	 */
	public function plugin_settings_link( $links ) {
		$settings = __( 'Settings', MS_TEXT_DOMAIN );
		if ( is_multisite() ) {
			$settings_link = "<a href='admin.php?page=membership-settings'>$settings</a>";
		} else {
			$settings_link = "<a href='admin.php?page=membership-settings'>$settings</a>";
		}
		array_unshift( $links, $settings_link );

		return $links;
	}	
	
	/**
	 * Returns singletone instance of the plugin.
	 *
	 * @since 3.5
	 *
	 * @static
	 * @access public
	 *
	 * @param Object $instance Can use "new MS_Plugin()" to instantiate. Only once.
	 * @return MS_Plugin
	 */
	public static function instance( $instance = null ) {
		if ( ! $instance || 'MS_Plugin' != get_class( $instance ) ){
			if ( is_null( self::$instance ) ) {
				self::$instance = new MS_Plugin();
			}
		} else {
			if ( is_null( self::$instance ) ) {
				self::$instance = $instance;
			}
		}
	
		return self::$instance;
	}
	/**
	 * Returns plugin enabled status.
	 *
	 * @since 4.0
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
	 * Returns property associated with the plugin.
	 *
	 * @since 4.0
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
 * @since 4.0.0
 */
MS_Plugin::instance( new MS_Plugin() );
