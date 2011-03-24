<?php
/*
*	Membership plugin configuration file v1.0
*
*	http://www.youtube.com/watch?v=BOJrmTF3TCs
*
*/

// Determines the number of posts to show in the posts rule
if(!defined('MEMBERSHIP_POST_COUNT')) define( 'MEMBERSHIP_POST_COUNT', 25);
// Determines the number of pages to show in the pages rule
if(!defined('MEMBERSHIP_PAGE_COUNT')) define( 'MEMBERSHIP_PAGE_COUNT', 25);
// Determines the maximum charge listed in the charges drop down
if(!defined('MEMBERSHIP_MAX_CHARGE')) define( 'MEMBERSHIP_MAX_CHARGE', 300);

// Allow comments to be shown in the negative comments rule
if(!defined('MEMBERSHIP_VIEW_COMMENTS')) define( 'MEMBERSHIP_VIEW_COMMENTS', false);

// Use a global table system - experimental
if(!defined('MEMBERSHIP_GLOBAL_TABLES')) define( 'MEMBERSHIP_GLOBAL_TABLES', false);

// Use a global table system - experimental
if(!defined('MEMBERSHIP_MASTER_ADMIN')) define( 'MEMBERSHIP_MASTER_ADMIN', 'admin');

if(!defined('MEMBERSHIP_SETACTIVATORAS_ADMIN')) define( 'MEMBERSHIP_SETACTIVATORAS_ADMIN', 'yes');

if(!defined('MEMBERSHIP_VERSION_KEY')) define( 'MEMBERSHIP_VERSION_KEY', 'yes');
?>