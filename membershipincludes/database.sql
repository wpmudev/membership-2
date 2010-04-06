CREATE TABLE `wp_1_membership_levels` (
  `id` bigint(20) NOT NULL auto_increment,
  `level_title` varchar(250) default NULL,
  `level_slug` varchar(250) default NULL,
  `level_active` int(11) default '0',
  `level_count` bigint(20) default '0',
  PRIMARY KEY  (`id`)
);

CREATE TABLE `wp_1_membership_relationships` (
  `rel_id` bigint(20) NOT NULL auto_increment,
  `user_id` bigint(20) default '0',
  `sub_id` bigint(20) default '0',
  `level_id` bigint(20) default '0',
  `startdate` datetime default NULL,
  PRIMARY KEY  (`rel_id`)
);

CREATE TABLE `wp_1_membership_rules` (
  `level_id` bigint(20) NOT NULL default '0',
  `rule_ive` varchar(20) NOT NULL default '',
  `rule_area` varchar(20) NOT NULL default '',
  `rule_value` text,
  `rule_order` int(11) default '0',
  PRIMARY KEY  (`level_id`,`rule_ive`,`rule_area`),
  KEY `rule_area` (`rule_area`),
  KEY `rule_ive` (`rule_ive`)
);

CREATE TABLE `wp_1_subscriptions` (
  `id` bigint(20) NOT NULL auto_increment,
  `sub_name` varchar(200) default NULL,
  `sub_active` int(11) default '0',
  `sub_public` int(11) default '0',
  `sub_count` bigint(20) default '0',
  PRIMARY KEY  (`id`)
);

CREATE TABLE `wp_1_membership_relationships` (
  `rel_id` bigint(20) NOT NULL auto_increment,
  `user_id` bigint(20) default '0',
  `sub_id` bigint(20) default '0',
  `level_id` bigint(20) default '0',
  `startdate` datetime default NULL,
  PRIMARY KEY  (`rel_id`),
  KEY `user_id` (`user_id`),
  KEY `sub_id` (`sub_id`),
  KEY `level_id` (`level_id`)
);