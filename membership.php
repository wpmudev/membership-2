<?php
/*
Plugin Name: Membership Premium Old version
Version: 1.0.2
Plugin URI: http://premium.wpmudev.org
Description: After you have activated the new Premium version you can deactivate this version and it will disappear from this list.
Author: Barry (Incsub)
Author URI: http://mapinated.com
WDP ID: 140

Copyright 2010  (email: admin@incsub.com)

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

// Check if premium is already activated or not
$M_plugins = array_merge((array) wp_get_mu_plugins(), (array) wp_get_active_and_valid_plugins());
$M_plugins = array_map('basename', $M_plugins);

if(!in_array('membershippremium.php', $M_plugins)) {
	// Membership premium isn't activated so just carry on here
	// Load the config file
	require_once('membershipincludes/includes/membership-config.php');
	// Load the common functions
	require_once('membershipincludes/includes/functions.php');
	// Set up my location
	set_membership_url(__FILE__);
	set_membership_dir(__FILE__);

	if(is_admin()) {
		// Administration interface

		// Load required classes
		require_once('membershipincludes/classes/class.rule.php');
		require_once('membershipincludes/classes/class.gateway.php');
		require_once('membershipincludes/classes/class.level.php');
		require_once('membershipincludes/classes/class.subscription.php');
		require_once('membershipincludes/classes/class.membership.php');
		// Set up the default rules
		require_once('membershipincludes/includes/default.rules.php');
		require_once('membershipincludes/includes/default.admin.rules.php');

		require_once('membershipincludes/classes/membershipadmin.php');

		$membershipadmin =& new membershipadmin();

	} else {
		// Public interface

		// Load required classes
		require_once('membershipincludes/classes/class.rule.php');
		require_once('membershipincludes/classes/class.gateway.php');
		require_once('membershipincludes/classes/class.level.php');
		require_once('membershipincludes/classes/class.subscription.php');
		require_once('membershipincludes/classes/class.membership.php');
		// Set up the default rules
		require_once('membershipincludes/includes/default.rules.php');

		require_once('membershipincludes/classes/membershippublic.php');

		$membershippublic =& new membershippublic();

	}

	// Load secondary plugins
	load_membership_plugins();
}

?>