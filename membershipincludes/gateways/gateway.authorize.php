<?php
/*
Addon Name: Authorize.net gateway
Description: The Authorize.net payment gateway
Author: Incsub
Author URI: http://premium.wpmudev.org
Gateway ID: authorize
*/

Membership_Gateway::register_gateway( 'authorize', 'Membership_Gateway_Authorize' );