<?php
/*
Plugin Name: Membership Premium
Version: 3.5.beta.7
Plugin URI: http://premium.wpmudev.org/project/membership
Description: The most powerful, easy to use and flexible membership plugin for WordPress, Multisite and BuddyPress sites available. Offer downloads, posts, pages, forums and more to paid members.
Author: Incsub
Author URI: http://premium.wpmudev.org
WDP ID: 140
License: GNU General Public License (Version 2 - GPLv2)
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

// Load the new config file
require_once('membershipincludes/includes/config.php');
// Load the old config file - depreciated
require_once('membershipincludes/includes/membership-config.php');
// Load the common functions
require_once('membershipincludes/includes/functions.php');
// Set up my location
set_membership_url( __FILE__ );
set_membership_dir( __FILE__ );

// Load required classes
// Levels class
require_once( membership_dir( 'membershipincludes/classes/class.level.php' ) );
// Subscriptions class
require_once( membership_dir( 'membershipincludes/classes/class.subscription.php' ) );
// Pagination class
require_once( membership_dir( 'membershipincludes/classes/class.pagination.php' ) );
// Shortcodes class
require_once( membership_dir( 'membershipincludes/classes/class.shortcodes.php' ) );
// Communications class
require_once( membership_dir( 'membershipincludes/classes/class.communication.php' ) );
// URL groups class
require_once( membership_dir( 'membershipincludes/classes/class.urlgroup.php' ) );
// Pings class
require_once( membership_dir( 'membershipincludes/classes/class.ping.php' ) );
// Add in the coupon
require_once( membership_dir( 'membershipincludes/classes/class.coupon.php' ) );

// Load the Cron process
require_once( membership_dir( 'membershipincludes/classes/membershipcron.php' ) );

if ( is_admin() ) {
	// Administration interface
	// Add in the contextual help
	require_once( membership_dir( 'membershipincludes/classes/class.help.php' ) );
	// Add in the wizard and tutorial
	require_once( membership_dir( 'membershipincludes/classes/class.wizard.php' ) );
	require_once( membership_dir( 'membershipincludes/classes/class.tutorial.php' ) );
	// Add in the main class
	require_once( membership_dir( 'membershipincludes/classes/membershipadmin.php' ) );

	$membershipadmin = new membershipadmin();

	// Register an activation hook
	register_activation_hook( __FILE__, 'M_activation_function' );
} else {
	// Public interface
	require_once( membership_dir( 'membershipincludes/classes/membershippublic.php' ) );

	$membershippublic = new membershippublic();
}


/******************************************************************************/
/************** WE ARE IN THE PROCESS OF REWRITING THE PLUGIN *****************/
/********** EVERYTHING ABOVE IS OLD VERSION AND BELOW IS NEW VERSION **********/
/******************************************************************************/

// include WPMUDev Dashboard class
require_once dirname( __FILE__ ) . '/extra/wpmudev-dash-notification.php';

/**
 * Automatically loads classes for the plugin. Checks a namespace and loads only
 * approved classes.
 *
 * @since 3.5
 *
 * @param string $class The class name to autoload.
 * @return boolean Returns TRUE if the class is located. Otherwise FALSE.
 */
function membership_autoloader( $class ) {
	// backward compatibility
	switch ( $class ) {
		case 'M_Membership': $class = 'Membership_Model_Member'; break;
	}

	// class loading
	$basedir = dirname( __FILE__ );
	$namespaces = array( 'Membership', 'WPMUDEV' );
	foreach ( $namespaces as $namespace ) {
		if ( substr( $class, 0, strlen( $namespace ) ) == $namespace ) {
			$filename = $basedir . str_replace( '_', DIRECTORY_SEPARATOR, "_classes_{$class}.php" );
			if ( is_readable( $filename ) ) {
				require $filename;
				return true;
			}
		}
	}

	return false;
}

/**
 * Initializes database table constants and add them to global tables if MultiDB
 * is used.
 *
 * @since 3.5
 *
 * @global wpdb $wpdb
 */
function membership_init_db_table_constants() {
	global $wpdb;

	$global = defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && filter_var( MEMBERSHIP_GLOBAL_TABLES, FILTER_VALIDATE_BOOLEAN );

	$prefix = $global && isset( $wpdb->base_prefix ) ? $wpdb->base_prefix : $wpdb->prefix;
	define( 'MEMBERSHIP_TABLE_LEVELS',                   "{$prefix}m_membership_levels" );
	define( 'MEMBERSHIP_TABLE_RULES',                    "{$prefix}m_membership_rules" );
	define( 'MEMBERSHIP_TABLE_RELATIONS',                "{$prefix}m_membership_relationships" );
	define( 'MEMBERSHIP_TABLE_MEMBER_PAYMENTS',          "{$prefix}m_member_payments" );
	define( 'MEMBERSHIP_TABLE_SUBSCRIPTIONS',            "{$prefix}m_subscriptions" );
	define( 'MEMBERSHIP_TABLE_SUBSCRIPTION_LEVELS',      "{$prefix}m_subscriptions_levels" );
	define( 'MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION', "{$prefix}m_subscription_transaction" );
	define( 'MEMBERSHIP_TABLE_URLGROUPS',                "{$prefix}m_urlgroups" );
	define( 'MEMBERSHIP_TABLE_COMMUNICATIONS',           "{$prefix}m_communications" );

	if ( $global && defined( 'MULTI_DB_VERSION' ) && function_exists( 'add_global_table' ) ) {
		add_global_table( 'm_membership_levels' );
		add_global_table( 'm_membership_rules' );
		add_global_table( 'm_membership_relationships' );
		add_global_table( 'm_member_payments' );
		add_global_table( 'm_subscriptions' );
		add_global_table( 'm_subscriptions_levels' );
		add_global_table( 'm_subscription_transaction' );
		add_global_table( 'm_urlgroups' );
		add_global_table( 'm_communications' );
	}
}

/**
 * Instantiates the plugin and setups all modules.
 *
 * @since 3.5
 */
function membership_launch() {
	// setup environment
	define( 'MEMBERSHIP_BASEFILE', __FILE__ );
	define( 'MEMBERSHIP_ABSURL', plugins_url( '/membershipincludes/', __FILE__ ) );
	define( 'MEMBERSHIP_ABSPATH', dirname( __FILE__ ) );

	// database tables
	membership_init_db_table_constants();

	// plugin setup
	$plugin = Membership_Plugin::instance();
	$plugin->set_factory( new Membership_Factory() );

	$plugin->set_module( Membership_Module_System::NAME );
	$plugin->set_module( Membership_Module_Upgrade::NAME );

	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
	} elseif ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
	} else {
		if ( Membership_Plugin::is_enabled() ) {
			$plugin->set_module( Membership_Module_Protection::NAME );
		}

		if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) {
			$plugin->set_module( Membership_Module_Adminbar::NAME );
		}

		$plugin->set_module( Membership_Module_Frontend_Registration::NAME );
		if ( is_admin() ) {
//			$plugin->set_module( Membership_Module_Backend_Rules_Metabox::NAME ); // temporary deactivated, not ready to release
		} else {
			$plugin->set_module( Membership_Module_Frontend::NAME );
		}
	}

	do_action( 'membership_loaded', $plugin );
}

// register autoloader function
spl_autoload_register( 'membership_autoloader' );

// launch the plugin
membership_launch();

// Load secondary plugins
load_all_membership_addons();
load_membership_gateways();