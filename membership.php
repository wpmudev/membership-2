<?php
/*
Plugin Name: Membership system - core plugin
Version: 0.1
Plugin URI:
Description: A Membership system plugin
Author:
Author URI:

Copyright 2010  (email: )

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

if(is_admin()) {
	// Administration interface
	require_once('membershipincludes/includes/functions.php');

	// Load required classes
	require_once('membershipincludes/classes/class.rule.php');
	require_once('membershipincludes/classes/class.gateway.php');
	require_once('membershipincludes/classes/class.level.php');
	require_once('membershipincludes/classes/class.subscription.php');
	require_once('membershipincludes/classes/class.membership.php');
	// Set up the default rules
	require_once('membershipincludes/includes/default.rules.php');

	// Load secondary plugins
	load_membership_plugins();

	require_once('membershipincludes/classes/membershipadmin.php');

	$membershipadmin =& new membershipadmin();

} else {
	// Public interface
	require_once('membershipincludes/includes/functions.php');

	// Load required classes
	require_once('membershipincludes/classes/class.rule.php');
	require_once('membershipincludes/classes/class.gateway.php');
	require_once('membershipincludes/classes/class.level.php');
	require_once('membershipincludes/classes/class.subscription.php');
	require_once('membershipincludes/classes/class.membership.php');
	// Set up the default rules
	require_once('membershipincludes/includes/default.rules.php');

	// Load secondary plugins
	load_membership_plugins();

	require_once('membershipincludes/classes/membershippublic.php');

	$membershippublic =& new membershippublic();

}

?>