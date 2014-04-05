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

/**  Include WPMUDev Dashboard class */
require_once dirname( __FILE__ ) . '/extra/wpmudev-dash-notification.php';

/** Constant defineing plugin text domain. */
define('MS_TEXT_DOMAIN', 'wpmudev_membership' );

/** Constant used in wp_enqueue_style and wp_enqueue_script version. */
define('MS_VERSION_DT', '2014-04-04' );

/** Plugin name dir constant */
define( 'MS_PLUGIN_NAME', dirname( plugin_basename( __FILE__ ) ) );

/** Instantiate the plugin */
new MS_Plugin();

/**
 * Sets up and loads the Membership plugin.
 *
 * Initialises the autoloader and required plugin hooks.
 * Control of plugin is passed to the MVC implementation found
 * inside the /app/ folder.
 *
 * @since 4.0.0
 *
 * @return object
 */
class MS_Plugin {

	/**
	 * The plugin name.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _name
	 */
	private $_name;
	
	/**
	 * The plugin file.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _file
	 */
	private $_file;	
	
	/**
	 * The plugin path.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _dir
	 */
	private $_dir;	

	/**
	 * The plugin URL.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var _url
	 */
	private $_url;

	/**
	 * Register hooks and loads the plugin.
	 */
	function __construct() {
		
		/** Actions to execute before construction is complete. */
		do_action( 'membership_plugin_loading' ); 
		
		/** Creates the class autoloader */
		spl_autoload_register( array( &$this, 'class_loader' ) );
				
// 		add_action( 'plugins_loaded', array( &$this,'plugin_localization' ) );
// 		spl_autoload_register( array($this, 'plugin_loader' ));
		$plugin = plugin_basename(__FILE__);
		$this->plugin_file = __FILE__;
		$this->plugin_dir = plugin_dir_path(__FILE__) . 'app/';
		$this->plugin_url = plugin_dir_url(__FILE__) . 'app/';
// 		add_filter( "plugin_action_links_$plugin", array( &$this,'plugin_settings_link' ) );
// 		add_filter( "network_admin_plugin_action_links_$plugin", array( &$this, 'plugin_settings_link' ) );

		/** Actions to execute when plugin is loaded. */
		do_action( 'membership_plugin_loaded' ); 

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
		foreach ( $namespaces as $namespace ) {
			switch ( $namespace ) {
			
				/** Use /app/ path and structure only for MS_ classes */
				case "MS_":
				
					if ( substr( $class, 0, strlen( $namespace ) ) == $namespace ) {
						$sub_path = strtolower( str_replace( 'MS_', '', $class ) );
						$path_array = explode( '_', $sub_path );
						array_pop( $path_array );
						$sub_path = implode( '_', $path_array );
						$filename = $basedir . str_replace( '_', DIRECTORY_SEPARATOR, "_app_{$sub_path}_" ) . strtolower( str_replace( '_', 
						'-', "class-{$class}.php" ) );
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
	 * Load plugin localization files.
	 *
	 * Files located in plugin subfolder ./languages.
	 *
	 * @since 4.0.0
	 * @access private
	 *
	 * @return void
	 */	
	private function plugin_localization() {
		load_plugin_textdomain( MS_TEXT_DOMAIN, false, MS_PLUGIN_NAME . '/languages/' );
	}


	/**
	 * Add link to settings page in plugins page.
	 *
	 * @since 4.0.0
	 * @access private
	 *
	 * @param array $links Wordpress default array of links.
	 * @return array Array of links with settings page links added.
	 */
	private function plugin_settings_link( $links ) {
		if ( is_multisite() ) {
			$settings_link = '';
		} else {
			$settings_link = '';
		}
		array_unshift( $links, $settings_link );
		return $links;
	}	
}