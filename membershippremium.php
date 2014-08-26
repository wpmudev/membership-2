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
define( 'MS_PLUGIN_VERSION', '4.0.0.0.3' );

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
	$overrides['MS_Controller_Membership_Content_Type'] =  "app/controller/membership/class-ms-controller-membership-content-type.php";
	$overrides['MS_Controller_Membership_Metabox'] =  "app/controller/class-ms-controller-membership-metabox.php";
	$overrides['MS_Helper_List_Table'] =  "app/helper/class-ms-helper-list-table.php";
	$overrides['MS_Helper_List_Table_Rule_Buddypress_Blog'] =  "app/helper/list-table/rule/class-ms-helper-list-table-rule-buddypress-blog.php";
	$overrides['MS_Helper_List_Table_Rule_Buddypress_Group'] =  "app/helper/list-table/rule/class-ms-helper-list-table-rule-buddypress-group.php";
	$overrides['MS_Helper_List_Table_Rule_Custom_Post_Type'] =  "app/helper/list-table/rule/class-ms-helper-list-table-rule-custom-post-type.php";
	$overrides['MS_Helper_List_Table_Rule_Custom_Post_Type_Group'] =  "app/helper/list-table/rule/class-ms-helper-list-table-rule-custom-post-type-group.php";
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
	$overrides['MS_Model_Buddypress_Group_Creation'] = "app/model/rule/buddypress/class-ms-model-rule-buddypress-group-creation.php";
	$overrides['MS_Model_Buddypress_Private_Msg'] = "app/model/rule/buddypress/class-ms-model-rule-buddypress-private-msg.php";
	$overrides['MS_Model_Rule_Custom_Post_Type'] = "app/model/rule/class-ms-model-rule-custom-post-type.php";
	$overrides['MS_Model_Rule_Custom_Post_Type_Group'] = "app/model/rule/class-ms-model-rule-custom-post-type-group.php";
	$overrides['MS_Model_Rule_Url_Group'] = "app/model/rule/class-ms-model-rule-url-group.php";
	$overrides['MS_Model_Membership_Relationship'] = "app/model/class-ms-model-membership_relationship.php";
	$overrides['MS_View_Admin_Bar'] = "app/view/class-ms-view-admin-bar.php";
	$overrides['MS_View_Membership_Accessible_Content'] = "app/view/membership/class-ms-view-membership-accessible-content.php";
	$overrides['MS_View_Membership_Choose_Type'] = "app/view/membership/class-ms-view-membership-choose-type.php";
	$overrides['MS_View_Membership_Setup_Content_Type'] = "app/view/membership/class-ms-view-membership-setup-content-type.php";
	$overrides['MS_View_Membership_Setup_Dripped'] = "app/view/membership/class-ms-view-membership-setup-dripped.php";
	$overrides['MS_View_Membership_Setup_Payment'] = "app/view/membership/class-ms-view-membership-setup-payment.php";
	$overrides['MS_View_Membership_Setup_Protected_Content'] = "app/view/membership/class-ms-view-membership-setup-protected-content.php";
	$overrides['MS_View_Membership_Setup_Tier'] = "app/view/membership/class-ms-view-membership-setup-tier.php";
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
		
		/**
		 * Actions to execute before the plugin construction starts.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'membership_plugin_construct_pre_processing', $this );
		
		/** Setup plugin properties */
		$this->name = MS_PLUGIN_NAME;
		$this->version = MS_PLUGIN_VERSION;		
		$this->file = __FILE__;
		$this->dir = plugin_dir_path(__FILE__);
		$this->url = plugin_dir_url(__FILE__);
		
		/**
		 * Filter the languages path before loading the textdomain.
		 *
		 * @uses load_plugin_textdomain()
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */
		load_plugin_textdomain( MS_TEXT_DOMAIN, false, apply_filters( 'membership_plugin_languages_path', $this->name . '/languages/', $this ) );
						
		/** Creates the class autoloader */
		spl_autoload_register( array( &$this, 'class_loader' ) );

		/**
		 * Actions to execute before construction is complete.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'membership_plugin_loading', $this ); 

		/** 
		 * Hooks init to register custom post types.
		 */
		add_action( 'init', array( &$this, 'register_custom_post_type' ), 1 );
		
		/**
		 * Hooks init to create the primary plugin controller.
		 */
		add_action( 'init', array( &$this, 'membership_plugin_constructing' ) );
		
		/**
		 * Creates and Filters the Settings Model.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */		
		$this->settings = apply_filters( 'membership_model_settings', MS_Factory::load( 'MS_Model_Settings' ), $this );

		/**
		 * Creates and Filters the Addon Model.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */		
		$this->addon = apply_filters( 'membership_model_addon', MS_Factory::load( 'MS_Model_Addon' ), $this );		

		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( &$this,'plugin_settings_link' ) );
		add_filter( 'network_admin_plugin_action_links_' . plugin_basename(__FILE__), array( &$this, 'plugin_settings_link' ) );

		/** Grab instance of self. */
		self::$instance = $this;
		
		/**
		 * Actions to execute when the Plugin object has successfully constructed.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'membership_plugin_loaded', $this ); 

		/**
		 * Load membership integrations.
		 */
		MS_Integration::load_integrations();
	}

	/**
	 * Loads primary plugin controllers.
	 *
	 * @since 4.0.0
	 * @return void
	 */	
	public function membership_plugin_constructing() {
		/**
		 * Creates and Filters the Plugin Controller.
		 *
		 * Main entry point controller for plugin.
		 *
		 * @uses MS_Controller_Plugin
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */
		$this->controller = apply_filters( 'membership_controller_plugin', new MS_Controller_Plugin(), $this );		
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
		
		do_action( 'ms_plugin_register_custom_post_type', $this );
		
		$cpts = array(
				MS_Model_Membership::$POST_TYPE => MS_Model_Membership::get_register_post_type_args(),
				MS_Model_Membership_Relationship::$POST_TYPE => MS_Model_Membership_Relationship::get_register_post_type_args(),
				MS_Model_Invoice::$POST_TYPE => MS_Model_Invoice::get_register_post_type_args(),
				MS_Model_Communication::$POST_TYPE => MS_Model_Communication::get_register_post_type_args(),
				MS_Model_Coupon::$POST_TYPE => MS_Model_Coupon::get_register_post_type_args(),
				MS_Model_Event::$POST_TYPE => MS_Model_Event::get_register_post_type_args(),
				
		);
		foreach( $cpts as $cpt => $args ) {
			MS_Helper_Utility::register_post_type( $cpt, $args );
		}
		
		/** post_status "virtual" for pages not to be displayed in the menus but that users should not be editing. */
		register_post_status( 'virtual', array(
			'label' => __( 'Virtual', MS_TEXT_DOMAIN ),
			'public' => ( ! is_admin() ), //This trick prevents the virtual pages from appearing in the All Pages list but can be display on the front end.
			'exclude_from_search' => false,
			'show_in_admin_all_list' => false,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Virtual <span class="count">(%s)</span>', 'Virtual <span class="count">(%s)</span>' ),
		) );
		
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

		/**
		 * Actions to execute before the autoloader loads a class.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */
		do_action( 'membership_plugin_class_loader_pre_processing', $this );

		$basedir = dirname( __FILE__ );
		$namespaces = array( 'MS_' );
		
		/**
		 * Adds and Filters class path overrides.
		 *
		 * @since 4.0.0
		 * @param object $this The MS_Plugin object.
		 */
		$path_overrides = apply_filters( 'membership_class_path_overrides', array(), $this );
		
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
							
							/**
							 * Overrides the filename and path.
							 *
							 * @since 4.0.0
							 * @param object $this The MS_Plugin object.
							 */
							$filename = apply_filters( 'membership_class_file_override', $filename, $this );
							
							if ( is_readable( $filename ) ) {
								require $filename;
								return true;
							}
						}						
					} else {
						$filename = $basedir . '/' . $path_overrides[ $class ];
						
						/**
						 * Overrides the filename and path.
						 *
						 * @since 4.0.0
						 * @param object $this The MS_Plugin object.
						 */
						$filename = apply_filters( 'membership_class_file_override', $filename, $this );
						
						if ( is_readable( $filename ) ) {
							require $filename;
							return true;
						}						
					}
					break; 
				
				default:
						/**
						 * Actions to add additional namespaces to this autoloading function.
						 *
						 * @since 4.0.0
						 * @param object $this The MS_Plugin object.
						 */
						do_action( 'membership_plugin_class_loader_namespace', $namespace, $this );
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
			
			/**
			 * Filter the plugin settings link.  
			 *
			 * @since 4.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$settings_link = apply_filters( 'membership_plugin_settings_link', "<a href='admin.php?page=membership-settings'>$settings</a>", $this );
		} else {
			
			/**
			 * Filter the plugin settings link.  
			 *
			 * @since 4.0.0
			 * @param object $this The MS_Plugin object.
			 */
			$settings_link = apply_filters( 'membership_plugin_settings_link', "<a href='admin.php?page=membership-settings'>$settings</a>", $this );
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