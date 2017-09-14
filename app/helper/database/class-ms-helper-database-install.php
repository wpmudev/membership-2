<?php
/**
 * Install DB helper.
 *
 * Manages DB table creation.
 *
 * @since  1.0.3.7
 *
 * @package Membership2
 * @subpackage Helper
 */
class MS_Helper_Database_Install extends MS_Helper {

    /**
     * Install database tables
     *
     * @return bool
     */
    public static function install() {
        $databases_installed = array();
        self::create_tables();
        $tables = MS_Helper_Database::table_names();
        foreach ( $tables as $name => $table ){
            $databases_installed[] = MS_Helper_Database::table_not_exist( $table );
        }
        return in_array( false, $databases_installed ); //false will mean things went wrong
    }

    /**
     * Uninstall function
     * Drop all databas tables
     */
    public static function uninstall() {
        $tables = MS_Helper_Database::table_names();
        global $wpdb;
        foreach ( $tables as $name => $table ){
            if ( ! MS_Helper_Database::table_not_exist( $table ) ) {
                $wpdb->query( "DROP TABLE {$table}" );
            }
        }
    }

    /**
     * Create database tables incase they do not exist
     *
     * @since 1.0.3.7
     */
    public static function create_tables() {
    
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = MS_Helper_Database::charset();
		
		$max_index_length = 191;


        //Event log Table
        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::EVENT_LOG );
        if ( $table_name ) {

            if ( MS_Helper_Database::table_not_exist( $table_name ) ) {
                $sql = "CREATE TABLE {$table_name} (
                        `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        `name` VARCHAR(200) NOT NULL,
                        `membership_id` bigint(20) unsigned NULL,
                        `ms_relationship_id` bigint(20) unsigned NULL,
                        `user_id` bigint(20) unsigned NULL,
                        `event_topic` VARCHAR(45) NOT NULL,
                        `event_type` VARCHAR(250) NOT NULL,
                        `description` TEXT NULL,
                        `author` bigint(20) unsigned NULL,
                        `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
                        PRIMARY KEY (`ID`),
                        KEY `event_membership_id` (`membership_id`),
                        KEY `event_ms_relationship_id` (`ms_relationship_id`),
                        KEY `event_user_id` (`user_id`))
                        $charset_collate;";
                dbDelta( $sql );
            }
        }

        //Communication Log Table
        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::COMMUNICATION_LOG );
        if ( $table_name ) {

            if ( MS_Helper_Database::table_not_exist( $table_name ) ) {
                $sql = "CREATE TABLE {$table_name} (
                        `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        `sent` TINYINT(1) NOT NULL DEFAULT 0,
                        `title` VARCHAR(200) NULL,
                        `recipient` VARCHAR(200) NOT NULL,
                        `subscription_id` INT NULL,
                        `trace` LONGTEXT NULL,
                        `author` bigint(20) unsigned NULL,
                        `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
                        PRIMARY KEY (`ID`),
                        KEY `comm_log_sent` (`sent`),
                        KEY `comm_log_sent_rec` (`sent`, `recipient`, `subscription_id`))
                        $charset_collate;";
                dbDelta( $sql );
            }
        }

        //Transaction Log table
        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::TRANSACTION_LOG );
        if ( $table_name ) {

            if ( MS_Helper_Database::table_not_exist( $table_name ) ) {
                $sql = "CREATE TABLE {$table_name} (
                        `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        `gateway_id` VARCHAR(200) NULL,
                        `method` VARCHAR(100) NULL,
                        `success` VARCHAR(10) NULL,
                        `subscription_id` bigint(20) unsigned NULL,
                        `invoice_id` bigint(20) unsigned NULL,
                        `member_id` bigint(20) unsigned NULL,
                        `amount` DOUBLE(5,2) NULL,
                        `custom_data` LONGTEXT NULL,
                        `user_id` bigint(20) unsigned NULL,
                        `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
                        `last_updated` datetime NOT NULL default '0000-00-00 00:00:00',
                        PRIMARY KEY (`ID`),
                        KEY `transaction_gateway` (`gateway_id`($max_index_length)),
                        KEY `transaction_success` (`success`),
                        KEY `transaction_sub` (`subscription_id`, `member_id`),
                        KEY `transaction_invoice` (`invoice_id`))
                        $charset_collate;";
                dbDelta( $sql );
            }
        }

        //Meta table
        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        if ( $table_name ) {

            if ( MS_Helper_Database::table_not_exist( $table_name ) ) {
                $sql = "CREATE TABLE {$table_name} (
                        `ID` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                        `object_id` bigint(20) unsigned NOT NULL,
                        `object_type` ENUM('Membership', 'Invoice', 'RelationShip', 'TransactionLog', 'Communication') NOT NULL,
                        `meta_key` VARCHAR(255) default NULL,
                        `meta_value` LONGTEXT NULL,
                        `date_created` datetime NOT NULL default '0000-00-00 00:00:00',
                        `date_updated` datetime NOT NULL default '0000-00-00 00:00:00',
                        PRIMARY KEY (`ID`),
                        KEY meta_key (`meta_key`($max_index_length)),
                        KEY `meta_object_id_type` (`object_id` ASC, `object_type` ASC),
                        KEY `meta_key_object` (`object_id` ASC, `object_type` ASC, `meta_key` ASC))
                        $charset_collate;";
                dbDelta( $sql );
            }
        }
    }

}

?>