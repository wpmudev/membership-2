<?php

function M_Upgrade($from = false) {

	switch($from) {

		case 1:
		case 2:		M_Alterfor2();
					break;

		case 3:		M_Alterfor3();
					break;

		case 4:
		case 5:		M_Alterfor4();
					break;

		case 6:		M_Alterfor4();
					M_Alterfor5();
					break;

		case 7:		M_Alterfor4();
					M_Alterfor5();
					M_Alterfor6();
					break;

		case 8:		break;

		case false:	M_Createtables();
					break;

		default:	M_Createtables();
					break;
	}

}

function M_Alterfor6() {
	global $wpdb;

	$sql = "ALTER TABLE " . membership_db_prefix($wpdb, 'membership_relationships') . " ADD `usinggateway` varchar(50) NULL DEFAULT 'admin'  AFTER `order_instance`;";
	$wpdb->query( $sql );

	$sql = "ALTER TABLE " . membership_db_prefix($wpdb, 'membership_relationships') . " ADD INDEX  (`user_id`);";
	$wpdb->query( $sql );

	$sql = "ALTER TABLE " . membership_db_prefix($wpdb, 'membership_relationships') . " ADD INDEX  (`sub_id`);";
	$wpdb->query( $sql );

	$sql = "ALTER TABLE " . membership_db_prefix($wpdb, 'membership_relationships') . " ADD INDEX  (`usinggateway`)";;
	$wpdb->query( $sql );

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'member_payments') . "` (
	  	`id` bigint(11) NOT NULL auto_increment,
		`member_id` bigint(20) default NULL,
		`sub_id` bigint(20) default NULL,
		`level_id` bigint(20) default NULL,
		`level_order` int(11) default NULL,
		`paymentmade` datetime default NULL,
		`paymentexpires` datetime default NULL,
		PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

}

function M_Alterfor5() {
	global $wpdb;

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'pings') . "` (
	  	`id` bigint(20) NOT NULL auto_increment,
		`pingname` varchar(250) default NULL,
		`pinginfo` text,
		`pingtype` varchar(10) default NULL,
		PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'ping_history') . "` (
	  	`id` bigint(20) NOT NULL auto_increment,
		`ping_id` bigint(20) default NULL,
		`ping_sent` timestamp NULL default NULL,
		`ping_info` text,
		`ping_return` text,
		PRIMARY KEY  (`id`),
		KEY `ping_id` (`ping_id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'levelmeta') . "` (
	  	`id` bigint(20) NOT NULL auto_increment,
		`level_id` bigint(20) default NULL,
		`meta_key` varchar(250) default NULL,
		`meta_value` text,
		`meta_stamp` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `level_id` (`level_id`,`meta_key`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscriptionmeta') . "` (
	  	`id` bigint(20) NOT NULL auto_increment,
		`sub_id` bigint(20) default NULL,
		`meta_key` varchar(250) default NULL,
		`meta_value` text,
		`meta_stamp` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `sub_id` (`sub_id`,`meta_key`)
	);";

	$wpdb->query($sql);
}

function M_Alterfor4() {
	global $wpdb;

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'urlgroups') . "` (
	  `id` bigint(20) NOT NULL auto_increment,
	  `groupname` varchar(250) default NULL,
	  `groupurls` text,
	  `isregexp` int(11) default '0',
	  `stripquerystring` int(11) default '0',
	  PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'communications') . "` (
	  `id` bigint(11) NOT NULL auto_increment,
	  `subject` varchar(250) default NULL,
	  `message` text,
	  `periodunit` int(11) default NULL,
	  `periodtype` varchar(5) default NULL,
	  `periodprepost` varchar(5) default NULL,
	  `lastupdated` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
	  `active` int(11) default '0',
	  `periodstamp` bigint(20) default '0',
	  PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);
}

function M_Alterfor3() {
	global $wpdb;

	$sql = "RENAME TABLE " . membership_db_prefix($wpdb, 'membership_levels', false) . " TO " . membership_db_prefix($wpdb, 'membership_levels') . ";";
	$wpdb->query($sql);

	$sql = "RENAME TABLE " . membership_db_prefix($wpdb, 'membership_relationships', false) . " TO " . membership_db_prefix($wpdb, 'membership_relationships') . ";";
	$wpdb->query($sql);

	$sql = "RENAME TABLE " . membership_db_prefix($wpdb, 'membership_rules', false) . " TO " . membership_db_prefix($wpdb, 'membership_rules') . ";";
	$wpdb->query($sql);

	$sql = "RENAME TABLE " . membership_db_prefix($wpdb, 'subscriptions', false) . " TO " . membership_db_prefix($wpdb, 'subscriptions') . ";";
	$wpdb->query($sql);

	$sql = "RENAME TABLE " . membership_db_prefix($wpdb, 'subscriptions_levels', false) . " TO " . membership_db_prefix($wpdb, 'subscriptions_levels') . ";";
	$wpdb->query($sql);

	$sql = "RENAME TABLE " . membership_db_prefix($wpdb, 'subscription_transaction', false) . " TO " . membership_db_prefix($wpdb, 'subscription_transaction') . ";";
	$wpdb->query($sql);

}

function M_Alterfor2() {
	global $wpdb;

	$sql = "ALTER TABLE `" . membership_db_prefix($wpdb, 'subscriptions_levels') . "` ADD `level_period_unit` varchar(1) NULL DEFAULT 'd'  AFTER `level_order`;";

	$wpdb->query($sql);
}

function M_Createtables() {

	global $wpdb;

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'membership_levels') . "` (
	  `id` bigint(20) NOT NULL auto_increment,
	  `level_title` varchar(250) default NULL,
	  `level_slug` varchar(250) default NULL,
	  `level_active` int(11) default '0',
	  `level_count` bigint(20) default '0',
	  PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'membership_relationships') . "` (
	  	`rel_id` bigint(20) NOT NULL auto_increment,
		`user_id` bigint(20) default '0',
		`sub_id` bigint(20) default '0',
		`level_id` bigint(20) default '0',
		`startdate` datetime default NULL,
		`updateddate` datetime default NULL,
		`expirydate` datetime default NULL,
		`order_instance` bigint(20) default '0',
		`usinggateway` varchar(50) default 'admin',
		PRIMARY KEY  (`rel_id`),
		KEY `user_id` (`user_id`),
		KEY `sub_id` (`sub_id`),
		KEY `usinggateway` (`usinggateway`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'membership_rules') . "` (
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

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscriptions') . "` (
	  `id` bigint(20) NOT NULL auto_increment,
	  `sub_name` varchar(200) default NULL,
	  `sub_active` int(11) default '0',
	  `sub_public` int(11) default '0',
	  `sub_count` bigint(20) default '0',
	  `sub_description` text,
	  PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscriptions_levels') . "` (
	  	`sub_id` bigint(20) default NULL,
		`level_id` bigint(20) default NULL,
		`level_period` int(11) default NULL,
		`sub_type` varchar(20) default NULL,
		`level_price` int(11) default '0',
		`level_currency` varchar(5) default NULL,
		`level_order` bigint(20) default '0',
		`level_period_unit` varchar(1) default 'd',
		KEY `sub_id` (`sub_id`),
	 	KEY `level_id` (`level_id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscription_transaction') . "` (
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

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'urlgroups') . "` (
	  `id` bigint(20) NOT NULL auto_increment,
	  `groupname` varchar(250) default NULL,
	  `groupurls` text,
	  `isregexp` int(11) default '0',
	  `stripquerystring` int(11) default '0',
	  PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'communications') . "` (
	  `id` bigint(11) NOT NULL auto_increment,
	  `subject` varchar(250) default NULL,
	  `message` text,
	  `periodunit` int(11) default NULL,
	  `periodtype` varchar(5) default NULL,
	  `periodprepost` varchar(5) default NULL,
	  `lastupdated` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
	  `active` int(11) default '0',
	  `periodstamp` bigint(20) default '0',
	  PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'pings') . "` (
	  	`id` bigint(20) NOT NULL auto_increment,
		`pingname` varchar(250) default NULL,
		`pingurl` varchar(250) default NULL,
		`pinginfo` text,
		`pingtype` varchar(10) default NULL,
		PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'ping_history') . "` (
	  	`id` bigint(20) NOT NULL auto_increment,
		`ping_id` bigint(20) default NULL,
		`ping_sent` timestamp NULL default NULL,
		`ping_info` text,
		`ping_return` text,
		PRIMARY KEY  (`id`),
		KEY `ping_id` (`ping_id`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'levelmeta') . "` (
	  	`id` bigint(20) NOT NULL auto_increment,
		`level_id` bigint(20) default NULL,
		`meta_key` varchar(250) default NULL,
		`meta_value` text,
		`meta_stamp` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `level_id` (`level_id`,`meta_key`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscriptionmeta') . "` (
	  	`id` bigint(20) NOT NULL auto_increment,
		`sub_id` bigint(20) default NULL,
		`meta_key` varchar(250) default NULL,
		`meta_value` text,
		`meta_stamp` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
		PRIMARY KEY  (`id`),
		UNIQUE KEY `sub_id` (`sub_id`,`meta_key`)
	);";

	$wpdb->query($sql);

	$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'member_payments') . "` (
	  	`id` bigint(11) NOT NULL auto_increment,
		`member_id` bigint(20) default NULL,
		`sub_id` bigint(20) default NULL,
		`level_id` bigint(20) default NULL,
		`level_order` int(11) default NULL,
		`paymentmade` datetime default NULL,
		`paymentexpires` datetime default NULL,
		PRIMARY KEY  (`id`)
	);";

	$wpdb->query($sql);

	do_action( 'membership_create_new_tables' );
}

function M_Create_single_table( $name ) {

	global $wpdb;

	switch( $name ) {

		case membership_db_prefix($wpdb, 'membership_levels'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'membership_levels') . "` (
					  `id` bigint(20) NOT NULL auto_increment,
					  `level_title` varchar(250) default NULL,
					  `level_slug` varchar(250) default NULL,
					  `level_active` int(11) default '0',
					  `level_count` bigint(20) default '0',
					  PRIMARY KEY  (`id`)
					);";
					break;

		case membership_db_prefix($wpdb, 'membership_relationships'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'membership_relationships') . "` (
					  	`rel_id` bigint(20) NOT NULL auto_increment,
						`user_id` bigint(20) default '0',
						`sub_id` bigint(20) default '0',
						`level_id` bigint(20) default '0',
						`startdate` datetime default NULL,
						`updateddate` datetime default NULL,
						`expirydate` datetime default NULL,
						`order_instance` bigint(20) default '0',
						`usinggateway` varchar(50) default 'admin',
						PRIMARY KEY  (`rel_id`),
						KEY `user_id` (`user_id`),
						KEY `sub_id` (`sub_id`),
						KEY `usinggateway` (`usinggateway`)
					);";
					break;

		case membership_db_prefix($wpdb, 'membership_rules'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'membership_rules') . "` (
					  `level_id` bigint(20) NOT NULL default '0',
					  `rule_ive` varchar(20) NOT NULL default '',
					  `rule_area` varchar(20) NOT NULL default '',
					  `rule_value` text,
					  `rule_order` int(11) default '0',
					  PRIMARY KEY  (`level_id`,`rule_ive`,`rule_area`),
					  KEY `rule_area` (`rule_area`),
					  KEY `rule_ive` (`rule_ive`)
					);";
					break;

		case membership_db_prefix($wpdb, 'subscriptions'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscriptions') . "` (
					  `id` bigint(20) NOT NULL auto_increment,
					  `sub_name` varchar(200) default NULL,
					  `sub_active` int(11) default '0',
					  `sub_public` int(11) default '0',
					  `sub_count` bigint(20) default '0',
					  `sub_description` text,
					  PRIMARY KEY  (`id`)
					);";
					break;

		case membership_db_prefix($wpdb, 'subscriptions_levels'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscriptions_levels') . "` (
					  	`sub_id` bigint(20) default NULL,
						`level_id` bigint(20) default NULL,
						`level_period` int(11) default NULL,
						`sub_type` varchar(20) default NULL,
						`level_price` int(11) default '0',
						`level_currency` varchar(5) default NULL,
						`level_order` bigint(20) default '0',
						`level_period_unit` varchar(1) default 'd',
						KEY `sub_id` (`sub_id`),
					 	KEY `level_id` (`level_id`)
					);";
					break;

		case membership_db_prefix($wpdb, 'subscription_transaction'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscription_transaction') . "` (
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
					break;

		case membership_db_prefix($wpdb, 'urlgroups'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'urlgroups') . "` (
					  `id` bigint(20) NOT NULL auto_increment,
					  `groupname` varchar(250) default NULL,
					  `groupurls` text,
					  `isregexp` int(11) default '0',
					  `stripquerystring` int(11) default '0',
					  PRIMARY KEY  (`id`)
					);";
					break;

		case membership_db_prefix($wpdb, 'communications'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'communications') . "` (
					  `id` bigint(11) NOT NULL auto_increment,
					  `subject` varchar(250) default NULL,
					  `message` text,
					  `periodunit` int(11) default NULL,
					  `periodtype` varchar(5) default NULL,
					  `periodprepost` varchar(5) default NULL,
					  `lastupdated` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
					  `active` int(11) default '0',
					  `periodstamp` bigint(20) default '0',
					  PRIMARY KEY  (`id`)
					);";

		case membership_db_prefix($wpdb, 'pings'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'pings') . "` (
					  	`id` bigint(20) NOT NULL auto_increment,
						`pingname` varchar(250) default NULL,
						`pingurl` varchar(250) default NULL,
						`pinginfo` text,
						`pingtype` varchar(10) default NULL,
						PRIMARY KEY  (`id`)
					);";
					break;

		case membership_db_prefix($wpdb, 'ping_history'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'ping_history') . "` (
					  	`id` bigint(20) NOT NULL auto_increment,
						`ping_id` bigint(20) default NULL,
						`ping_sent` timestamp NULL default NULL,
						`ping_info` text,
						`ping_return` text,
						PRIMARY KEY  (`id`),
						KEY `ping_id` (`ping_id`)
					);";
					break;

		case membership_db_prefix($wpdb, 'levelmeta'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'levelmeta') . "` (
					  	`id` bigint(20) NOT NULL auto_increment,
						`level_id` bigint(20) default NULL,
						`meta_key` varchar(250) default NULL,
						`meta_value` text,
						`meta_stamp` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
						PRIMARY KEY  (`id`),
						UNIQUE KEY `level_id` (`level_id`,`meta_key`)
					);";
					break;

		case membership_db_prefix($wpdb, 'subscriptionmeta'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'subscriptionmeta') . "` (
					  	`id` bigint(20) NOT NULL auto_increment,
						`sub_id` bigint(20) default NULL,
						`meta_key` varchar(250) default NULL,
						`meta_value` text,
						`meta_stamp` timestamp NULL default NULL on update CURRENT_TIMESTAMP,
						PRIMARY KEY  (`id`),
						UNIQUE KEY `sub_id` (`sub_id`,`meta_key`)
					);";
					break;

		case membership_db_prefix($wpdb, 'member_payments'):
					$sql = "CREATE TABLE IF NOT EXISTS `" . membership_db_prefix($wpdb, 'member_payments') . "` (
					  	`id` bigint(11) NOT NULL auto_increment,
						`member_id` bigint(20) default NULL,
						`sub_id` bigint(20) default NULL,
						`level_id` bigint(20) default NULL,
						`level_order` int(11) default NULL,
						`paymentmade` datetime default NULL,
						`paymentexpires` datetime default NULL,
						PRIMARY KEY  (`id`)
					);";
					break;


	}

	$wpdb->query($sql);

}

function M_add_possible_missing_fields( $table, $name, $type, $after, $key = false ) {

	global $wpdb;

	switch($name) {
		case 'usinggateway':	$defaults = $name . " " . $type . " default 'admin' AFTER " . $after;
								$sql = "ALTER TABLE " . $table . " ADD COLUMN " . $defaults;
								$wpdb->query( $sql );
								// Add the key
								$sql = "ALTER TABLE " . $table . " ADD INDEX  (`" . $name . "`)";
								$wpdb->query( $sql );
								break;

		default:				$defaults = $name . " " . $type . " AFTER " . $after;
								$sql = "ALTER TABLE " . $table . " ADD COLUMN " . $defaults;
								$wpdb->query( $sql );
								if($key) {
									$sql = "ALTER TABLE " . $table . " ADD INDEX  (`" . $name . "`)";
									$wpdb->query( $sql );
								}
								break;
	}

}


function M_verify_tables() {

	global $wpdb;

	$tables = M_build_database_structure();

	foreach( $tables as $name => $fields ) {

		echo "<p>" . __('Checking table : ', 'membership') . $name . " - ";

		$sql = "SHOW TABLES LIKE '{$name}';";
		$t = $wpdb->get_var( $sql );

		if($t == $name) {
			echo "<span style='color: green;'>" . __('Ok', 'membership') . "</span>";
			echo "</p>";

			echo "<p>" . __('Checking fields in table : ', 'membership') . $name . " - ";

			$sql = "SHOW COLUMNS FROM {$name};";
			$t = $wpdb->get_results( $sql );

			foreach( $fields as $fieldname => $type ) {
				$found = false;
				echo "<br/>" . $fieldname . " - ";
				foreach($t as $dbf) {
					if($dbf->Field == $fieldname && $dbf->Type == $type) {
						$found = true;
						break;
					}
				}
				if($found) {
					echo "<span style='color: green;'>" . __('Ok', 'membership') . "</span>";
				} else {
					echo "<span style='color: red;'>" . __('Missing or Incorrect', 'membership') . "</span>";
				}
			}

			echo "</p>";

		} else {
			echo "<span style='color: red;'>" . __('Missing', 'membership') . "</span>";
			echo "</p>";
		}

	}

}

function M_repair_tables() {

	global $wpdb;

	$tables = M_build_database_structure();

	foreach( $tables as $name => $fields ) {

		echo "<p>" . __('Checking table : ', 'membership') . $name . " - ";

		$sql = "SHOW TABLES LIKE '{$name}';";
		$t = $wpdb->get_var( $sql );

		if($t == $name) {
			echo "<span style='color: green;'>" . __('Ok', 'membership') . "</span>";
			echo "</p>";

			echo "<p>" . __('Checking fields in table : ', 'membership') . $name . " - ";

			$sql = "SHOW COLUMNS FROM {$name};";
			$t = $wpdb->get_results( $sql );

			$pfield = '';
			foreach( $fields as $fieldname => $type ) {
				$found = false;
				echo "<br/>" . $fieldname . " - ";
				foreach($t as $dbf) {
					//print_r($dbf);
					if($dbf->Field == $fieldname && $dbf->Type == $type) {
						$found = true;
						break;
					}
				}
				if($found) {
					echo "<span style='color: green;'>" . __('Ok', 'membership') . "</span>";
				} else {

					M_add_possible_missing_fields( $name, $fieldname, $type, $pfield );

					echo "<span style='color: red;'>" . __('Fixed', 'membership') . "</span>";
				}
				$pfield = $fieldname;
			}

			echo "</p>";

		} else {

			M_Create_single_table( $name );

			echo "<span style='color: red;'>" . __('Fixed', 'membership') . "</span>";
			echo "</p>";
		}

	}

}

function M_build_database_structure() {

	global $wpdb;

	$bi = 'bigint(20)';
	$biu = 'bigint(20) unsigned';
	$bi11 = 'bigint(11)';
	$bi35 = 'bigint(35)';
	$i = 'int(11)';
	$v1 = 'varchar(1)';
	$v5 = 'varchar(5)';
	$v10 = 'varchar(10)';
	$v30 = 'varchar(30)';
	$v35 = 'varchar(35)';
	$v50 = 'varchar(50)';
	$v20 = 'varchar(20)';
	$v200 = 'varchar(200)';
	$v250 = 'varchar(250)';
	$t = 'text';
	$jd = 'date';
	$d = 'datetime';
	$ts = 'timestamp';

	$structure = array( membership_db_prefix($wpdb, 'membership_levels') => array(	'id'	=>			$bi,
																					'level_title'	=>	$v250,
																					'level_slug'	=>	$v250,
																					'level_active'	=>	$i,
																					'level_count'	=>	$bi
																				),
						membership_db_prefix($wpdb, 'membership_relationships')	=>	array(	'rel_id'	=>	$bi,
																							'user_id'	=>	$bi,
																							'sub_id'	=>	$bi,
																							'level_id'	=>	$bi,
																							'startdate'	=>	$d,
																							'updateddate'	=>	$d,
																							'expirydate'	=>	$d,
																							'order_instance'	=>	$bi,
																							'usinggateway'	=>	$v50
																				),
						membership_db_prefix($wpdb, 'membership_rules')	=> array(	'level_id'	=>	$bi,
																					'rule_ive'	=>	$v20,
																					'rule_area'	=>	$v20,
																					'rule_value'	=>	$t,
																					'rule_order'	=>	$i
																				),
						membership_db_prefix($wpdb, 'subscriptions')	=>	array(	'id'	=>	$bi,
																					'sub_name'	=>	$v200,
																					'sub_active'	=>	$i,
																					'sub_public'	=>	$i,
																					'sub_count'		=>	$bi,
																					'sub_description'	=>	$t
																					),
						membership_db_prefix($wpdb, 'subscriptions_levels')	=>	array(	'sub_id'	=>	$bi,
																						'level_id'	=>	$bi,
																						'level_period'	=>	$i,
																						'sub_type'	=>	$v20,
																						'level_price'	=>	$i,
																						'level_currency'	=>	$v5,
																						'level_order'	=>	$bi,
																						'level_period_unit'	=>	$v1
																					),
						membership_db_prefix($wpdb, 'subscription_transaction')	=>	array(	'transaction_ID'	=>	$biu,
																							'transaction_subscription_ID'	=>	$bi,
																							'transaction_user_ID'	=>	$bi,
																							'transaction_sub_ID'	=>	$bi,
																							'transaction_paypal_ID'	=>	$v30,
																							'transaction_payment_type'	=>	$v20,
																							'transaction_stamp'	=>	$bi35,
																							'transaction_total_amount'	=>	$bi,
																							'transaction_currency'	=>	$v35,
																							'transaction_duedate'	=>	$jd,
																							'transaction_gateway'	=>	$v50,
																							'transaction_note'		=>	$t,
																							'transaction_expires'	=>	$d
																					),
						membership_db_prefix($wpdb, 'urlgroups')	=>	array(	'id'	=>	$bi,
																				'groupname'	=>	$v250,
																				'groupurls'	=>	$t,
																				'isregexp'	=>	$i,
																				'stripquerystring'	=>	$i
																			),
						membership_db_prefix($wpdb, 'communications')	=>	array(	'id'	=>	$bi11,
																					'subject'	=>	$v250,
																					'message'	=>	$t,
																					'periodunit'	=>	$i,
																					'periodtype'	=>	$v5,
																					'periodprepost'	=>	$v5,
																					'lastupdated'	=>	$ts,
																					'active'		=>	$i,
																					'periodstamp'	=>	$bi
																					),
						membership_db_prefix($wpdb, 'pings')	=>	array(	'id'	=>	$bi,
																			'pingname'	=>	$v250,
																			'pingurl'	=>	$v250,
																			'pinginfo'	=>	$t,
																			'pingtype'	=>	$v10
																		),
						membership_db_prefix($wpdb, 'ping_history')	=>	array(	'id'	=>	$bi,
																				'ping_id'	=>	$bi,
																				'ping_sent'	=>	$ts,
																				'ping_info'	=>	$t,
																				'ping_return'	=>	$t
																		),
						membership_db_prefix($wpdb, 'levelmeta')	=>	array(	'id'	=>	$bi,
																				'level_id'	=>	$bi,
																				'meta_key'	=>	$v250,
																				'meta_value'	=>	$t,
																				'meta_stamp'	=>	$ts
																		),
						membership_db_prefix($wpdb, 'subscriptionmeta')	=>	array( 	'id'	=>	$bi,
																					'sub_id'	=>	$bi,
																					'meta_key'	=>	$v250,
																					'meta_value'	=>	$t,
																					'meta_stamp'	=>	$ts
																			),
						membership_db_prefix($wpdb, 'member_payments')	=>	array(	'id'	=>	$bi11,
																					'member_id'	=>	$bi,
																					'sub_id'	=>	$bi,
																					'level_id'	=>	$bi,
																					'level_order'	=>	$i,
																					'paymentmade'	=>	$d,
																					'paymentexpires'	=>	$d
																			)
						);

	return $structure;

}

?>