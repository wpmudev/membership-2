<?php

function M_Upgrade($from = false) {

	switch($from) {
		case false:	M_Createtables();
					break;

		default:	M_Createtables();
					break;
	}

}

function M_Createtables() {

	global $wpdb;

	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}membership_levels` (
	  `id` bigint(20) NOT NULL auto_increment,
	  `level_title` varchar(250) default NULL,
	  `level_slug` varchar(250) default NULL,
	  `level_active` int(11) default '0',
	  `level_count` bigint(20) default '0',
	  PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}membership_relationships` (
	  `rel_id` bigint(20) NOT NULL auto_increment,
	  `user_id` bigint(20) default '0',
	  `sub_id` bigint(20) default '0',
	  `level_id` bigint(20) default '0',
	  `startdate` datetime default NULL,
	  `updateddate` datetime default NULL,
	  `expirydate` datetime default NULL,
	  `order_instance` bigint(20) default '0',
	  PRIMARY KEY  (`rel_id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}membership_rules` (
	  `level_id` bigint(20) NOT NULL default '0',
	  `rule_ive` varchar(20) NOT NULL default '',
	  `rule_area` varchar(20) NOT NULL default '',
	  `rule_value` text,
	  `rule_order` int(11) default '0',
	  PRIMARY KEY  (`level_id`,`rule_ive`,`rule_area`),
	  KEY `rule_area` (`rule_area`),
	  KEY `rule_ive` (`rule_ive`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}subscriptions` (
	  `id` bigint(20) NOT NULL auto_increment,
	  `sub_name` varchar(200) default NULL,
	  `sub_active` int(11) default '0',
	  `sub_public` int(11) default '0',
	  `sub_count` bigint(20) default '0',
	  `sub_description` text,
	  PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE `{$wpdb->prefix}subscriptions_levels` (
	  `sub_id` bigint(20) default NULL,
	  `level_id` bigint(20) default NULL,
	  `level_period` int(11) default NULL,
	  `sub_type` varchar(20) default NULL,
	  `level_price` int(11) default '0',
	  `level_currency` varchar(5) default NULL,
	  `level_order` bigint(20) default '0',
	  KEY `sub_id` (`sub_id`),
	  KEY `level_id` (`level_id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}subscription_transaction` (
	  `transaction_ID` bigint(20) unsigned NOT NULL auto_increment,
	  `transaction_subscription_ID` bigint(20) NOT NULL default '0',
	  `transaction_user_ID` bigint(20) NOT NULL default '0',
	  `transaction_sub_ID` bigint(20) default '0',
	  `transaction_paypal_ID` varchar(30) default NULL,
	  `transaction_payment_type` varchar(20) default NULL,
	  `transaction_stamp` bigint(35) NOT NULL default '0',
	  `transaction_total_amount` bigint(20) default NULL,
	  `transaction_currency` varchar(35) default NULL,
	  `transaction_status` varchar(35) default NULL,
	  `transaction_duedate` date default NULL,
	  `transaction_gateway` varchar(50) default NULL,
	  `transaction_note` text,
	  `transaction_expires` datetime default NULL,
	  PRIMARY KEY  (`transaction_ID`),
	  KEY `transaction_gateway` (`transaction_gateway`),
	  KEY `transaction_subscription_ID` (`transaction_subscription_ID`)
	);";

	$wpdb->query($sql);
}

?>