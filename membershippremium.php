<?php
/*
Plugin Name: Membership Premium
Version: 3.4.5 RC 3
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
set_membership_url(__FILE__);
set_membership_dir(__FILE__);

// Load required classes
// Rules class
require_once( membership_dir('membershipincludes/classes/class.rule.php') );
// Rules class
require_once( membership_dir('membershipincludes/classes/class.advancedrule.php') );
// Gateways class
require_once( membership_dir('membershipincludes/classes/class.gateway.php') );
// Levels class
require_once( membership_dir('membershipincludes/classes/class.level.php') );
// Subscriptions class
require_once( membership_dir('membershipincludes/classes/class.subscription.php') );
// Pagination class
require_once( membership_dir('membershipincludes/classes/class.pagination.php') );
// Members class
require_once( membership_dir('membershipincludes/classes/class.membership.php') );
// Shortcodes class
require_once( membership_dir('membershipincludes/classes/class.shortcodes.php') );
// Communications class
require_once( membership_dir('membershipincludes/classes/class.communication.php') );
// URL groups class
require_once( membership_dir('membershipincludes/classes/class.urlgroup.php') );
// Pings class
require_once( membership_dir('membershipincludes/classes/class.ping.php') );
// Add in the coupon
require_once( membership_dir('membershipincludes/classes/class.coupon.php') );
// Add in the Admin bar
require_once( membership_dir('membershipincludes/classes/class.adminbar.php') );
// Set up the default rules
require_once( membership_dir('membershipincludes/includes/default.rules.php') );
// Set up the default advanced rules
require_once( membership_dir('membershipincludes/includes/default.advrules.php') );

// Load the Cron process
require_once( membership_dir('membershipincludes/classes/membershipcron.php') );

// Create the default actions
require_once( membership_dir('membershipincludes/includes/default.actions.php') );

if (is_admin()) {
    include_once( membership_dir('membershipincludes/external/wpmudev-dash-notification.php') );
    // Administration interface
    // Add in the contextual help
    require_once( membership_dir('membershipincludes/classes/class.help.php') );
    // Add in the wizard and tutorial
    require_once( membership_dir('membershipincludes/classes/class.wizard.php') );
    require_once( membership_dir('membershipincludes/classes/class.tutorial.php') );
    // Add in the tooltips class - from social marketing app by Ve
    require_once( membership_dir('membershipincludes/includes/class_wd_help_tooltips.php') );
    // Add in the main class
    require_once( membership_dir('membershipincludes/classes/membershipadmin.php') );

    $membershipadmin = new membershipadmin();

    // Register an activation hook
    register_activation_hook(__FILE__, 'M_activation_function');
} else {
    // Public interface
    require_once( membership_dir('membershipincludes/classes/membershippublic.php') );

    $membershippublic = new membershippublic();
}

// Load secondary plugins
load_all_membership_addons();
load_membership_gateways();