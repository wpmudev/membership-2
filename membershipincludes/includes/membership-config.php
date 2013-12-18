<?php

// Membership plugin configuration file v1.0 - now replaced by config.php

// Determines the number of posts to show in the posts rule
if(!defined('MEMBERSHIP_POST_COUNT')) define( 'MEMBERSHIP_POST_COUNT', 25);
// Determines the number of pages to show in the pages rule
if(!defined('MEMBERSHIP_PAGE_COUNT')) define( 'MEMBERSHIP_PAGE_COUNT', 50);
// Determines the number of groups to show in the groups rule
if(!defined('MEMBERSHIP_GROUP_COUNT')) define( 'MEMBERSHIP_GROUP_COUNT', 50);
// Determines the maximum charge listed in the charges drop down
if(!defined('MEMBERSHIP_MAX_CHARGE')) define( 'MEMBERSHIP_MAX_CHARGE', 300);

// Allow comments to be shown in the negative comments rule
if(!defined('MEMBERSHIP_VIEW_COMMENTS')) define( 'MEMBERSHIP_VIEW_COMMENTS', false);

// Use a global table system - experimental
if(!defined('MEMBERSHIP_GLOBAL_TABLES')) define( 'MEMBERSHIP_GLOBAL_TABLES', false);
if(!defined('MEMBERSHIP_GLOBAL_MAINSITE')) define( 'MEMBERSHIP_GLOBAL_MAINSITE', 1);

// The master admin user - shouldn't need to change this unless you are not using the admin account
if(!defined('MEMBERSHIP_MASTER_ADMIN')) define( 'MEMBERSHIP_MASTER_ADMIN', 'admin');
// Sets the user who activated the plugin as a membership admin user
if(!defined('MEMBERSHIP_SETACTIVATORAS_ADMIN')) define( 'MEMBERSHIP_SETACTIVATORAS_ADMIN', 'yes');
//
if(!defined('MEMBERSHIP_VERSION_KEY')) define( 'MEMBERSHIP_VERSION_KEY', 'yes');
// Shows the add-ons page only to super-admins - no logner used.
if(!defined('MEMBERSHIP_ADDONS_ONLY_SUPERADMIN')) define( 'MEMBERSHIP_ADDONS_ONLY_SUPERADMIN', true);

// File protection - add a prefix to generated filenames
if(!defined('MEMBERSHIP_FILE_NAME_PREFIX')) define( 'MEMBERSHIP_FILE_NAME_PREFIX', '');
// File protection - increment the file id by a set amount
if(!defined('MEMBERSHIP_FILE_NAME_INCREMENT')) define( 'MEMBERSHIP_FILE_NAME_INCREMENT', 2771);
// Set to true if you want a category page with no posts to be redirected to a protected page
if(!defined('MEMBERSHIP_REDIRECT_ON_EMPTY_CATEGORYPAGE')) define('MEMBERSHIP_REDIRECT_ON_EMPTY_CATEGORYPAGE', false);

// Re-enable the deactivate user on registration and cancellation facility
if(!defined('MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION')) define('MEMBERSHIP_DEACTIVATE_USER_ON_CANCELATION', false);