<?php
/*
Plugin Name: Membership Premium
Version: 3.4.1.2
Plugin URI: http://premium.wpmudev.org/project/membership
Description: The most powerful, easy to use and flexible membership plugin for WordPress, Multisite and BuddyPress sites available. Offer downloads, posts, pages, forums and more to paid members.
Author: Barry (Incsub), Cole (Incsub)
Author URI: http://premium.wpmudev.org
WDP ID: 140

Copyright 2012  (email: admin@incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Load the config file
require_once('membershipincludes/includes/membership-config.php');
// Load the common functions
require_once('membershipincludes/includes/functions.php');
// Set up my location
set_membership_url(__FILE__);
set_membership_dir(__FILE__);

// Load required classes
require_once('membershipincludes/classes/class.rule.php');
require_once('membershipincludes/classes/class.gateway.php');
require_once('membershipincludes/classes/class.level.php');
require_once('membershipincludes/classes/class.subscription.php');
require_once('membershipincludes/classes/class.membership.php');
// Shortcodes class
require_once('membershipincludes/classes/class.shortcodes.php');
// Communications class
require_once('membershipincludes/classes/class.communication.php');
// URL groups class
require_once('membershipincludes/classes/class.urlgroup.php');
// Pings class
require_once('membershipincludes/classes/class.ping.php');
// Add in the coupon
require_once('membershipincludes/classes/class.coupon.php');
// Add in the Admin bar
require_once('membershipincludes/classes/class.adminbar.php');
// Set up the default rules
require_once('membershipincludes/includes/default.rules.php');

if(is_admin()) {
	include_once('membershipincludes/external/wpmudev-dash-notification.php');
	// Administration interface
	// Add in the contextual help
	require_once('membershipincludes/classes/class.help.php');
	// Add in the wizard and tutorial
	require_once('membershipincludes/classes/class.wizard.php');
	require_once('membershipincludes/classes/class.tutorial.php');
	// Add in the tooltips class - from social marketing app by Ve
	require_once('membershipincludes/includes/class_wd_help_tooltips.php');
	// Add in the main class
	require_once('membershipincludes/classes/membershipadmin.php');

	$membershipadmin = new membershipadmin();

	// Register an activation hook
	register_activation_hook( __FILE__, 'M_activation_function' );

} else {
	// Public interface
	require_once('membershipincludes/classes/membershippublic.php');

	$membershippublic = new membershippublic();

}

// Load secondary plugins
load_all_membership_addons();
load_membership_gateways();
