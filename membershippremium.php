<?php
/*
Plugin Name: New Membership Premium
Version: 4.0.0
Plugin URI: http://premium.wpmudev.org/project/membership
Description: The most powerful, easy to use and flexible membership plugin for WordPress, Multisite and BuddyPress sites available. Offer downloads, posts, pages, forums and more to paid members.
Author: WPMU DEV
Author URI: http://premium.wpmudev.org
WDP ID: 140
License: GNU General Public License (Version 2 - GPLv2)
Text Domain: membership
 */

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+


// include WPMUDev Dashboard class
require_once dirname( __FILE__ ) . '/extra/wpmudev-dash-notification.php';

define('MS_TEXT_DOMAIN', 'wpmudev_membership' );
/**
 * Constant used in wp_enqueue_style and wp_enqueue_script version.
*/
define('MS_VERSION_DT', '2014-04-04' );
/**
 * Plugin name dir constant
*/
define( 'MS_PLUGIN_NAME', dirname( plugin_basename( __FILE__ ) ) );

new MS_Membership();
class MS_Membership {
	/**
	 * Register plugin hooks.
	 */
	function __construct() {
// 		add_action( 'init', array( &$this, 'plugin_loader' ) );
// 		add_action( 'plugins_loaded', array( &$this,'plugin_localization' ) );
// 		spl_autoload_register( array($this, 'plugin_loader' ));
		$plugin = plugin_basename(__FILE__);
		$this->plugin_file = __FILE__;
		$this->plugin_dir = plugin_dir_path(__FILE__) . 'app/';
		$this->plugin_url = plugin_dir_url(__FILE__) . 'app/';
// 		add_filter( "plugin_action_links_$plugin", array( &$this,'plugin_settings_link' ) );
// 		add_filter( "network_admin_plugin_action_links_$plugin", array( &$this, 'plugin_settings_link' ) );
	}
	/**
	 * Load classes to admin plugin settings and email send functions.
	 */
	function plugin_loader( $class ) {
		
		//auto loader 
		require_once $this->plugin_dir . 'class-hooker.php';
		require_once $this->plugin_dir . 'model/class-ms-model-custom-post-type.php';
		require_once $this->plugin_dir . 'model/class-ms-model-membership.php';
		require_once $this->plugin_dir . 'model/class-ms-model-rule.php';
		require_once $this->plugin_dir . 'model/class-ms-model-member.php';
		require_once $this->plugin_dir . 'controller/class-ms-controller-membership.php';
		require_once $this->plugin_dir . 'controller/class-ms-controller-member.php';
		
	}
	/**
	 * Load plugin localization files.
	 * Files located in plugin subfolder ./languages.
	 */
	function plugin_localization() {
		load_plugin_textdomain( MS_TEXT_DOMAIN, false, MS_PLUGIN_NAME . '/languages/' );
	}
	/**
	 * Add link to settings page in plugins page.
	 * @param array $links Wordpress default array of links.
	 * @return array Array of links with settings page links added.
	 */
	function plugin_settings_link( $links ) {
		if ( is_multisite() ) {
			$settings_link = '';
		} else {
			$settings_link = '';
		}
		array_unshift( $links, $settings_link );
		return $links;
	}	
}