<?php

/**
 * Helper class to hold the custom database table information
 *
 * @since 1.2
 */
class MS_Helper_Database extends MS_Helper {

    /**
     * Curent tables
     */
    static $tables = array();

    const EVENT_LOG = 'event_log';
    const COMMUNICATION_LOG = 'communication_log';
    const TRANSACTION_LOG = 'transaction_log';
    const META = 'meta';

    /**
     * Get all the used table names
     *
     * @since 1.0.3.7
     *
     * @return Array
     */
    public static function table_names() {
        global $wpdb;

        return apply_filters( 
            'ms_helper_database_table_names', 
            array(
                self::EVENT_LOG             => $wpdb->prefix. 'ms_event_log',
                self::COMMUNICATION_LOG     => $wpdb->prefix. 'ms_communication_log',
                self::TRANSACTION_LOG       => $wpdb->prefix. 'ms_transaction_log',
                self::META                  => $wpdb->prefix. 'ms_meta',
            )
        );
    }

    /**
	 * Database charset
	 *
	 * @param bool/WP_DB object - optional wpdb object
	 *
	 * @since 1.2
	 *
	 * @return String
     */
    public static function charset( $db = false ) {
		if ( !$db ) {
			global $wpdb;
			$db = $wpdb;
		}
        return $db->get_charset_collate();;
    }

    /**
     * Get Table Name
     *
     * @param String $name - the name of the table
	 *
	 * @since 1.2
	 *
     * @return String/bool
     */
    public static function get_table_name( $name ) {
        if ( empty( self::$tables ) ) {
            self::$tables = self::table_names();
        }
        return isset( self::$tables[$name] ) ? self::$tables[$name] : false;
    }

    /**
     * Check if a table does not exists
     *
     * @since 1.2
     *
     * @return Boolean
     */
    public static function table_not_exist( $table_name, $db = false ) {
        if ( !$db ) {
			global $wpdb;
			$db = $wpdb;
		}
        return ( $db->get_var( $wpdb->prepare( "show tables like %s", $table_name ) ) != $table_name );
    }

    /**
     * Generate slug based on field
     *
     * @param String $table_name - the table name
     * @param String $title - the title
     * @param String $field - the table field name
	 *
	 * @since 1.2
	 *
     * @return String
     */
    public static function generate_slug( $table_name, $title, $field ='title' ) {
        global $wpdb;

        $sanitized_title 	= sanitize_title( $title );
        $title  			= $wpdb->esc_like( $sanitized_title );
        $title  			= '%' . $title . '%';
        $sql    			= "SELECT count(ID) FROM $table_name WHERE $field LIKE %s";
        $total  			= $wpdb->get_var( $wpdb->prepare( $sql, $title ) );

        if ( $total > 0 ) {
            return sprintf( "%s-%d", $sanitized_title, ( $total + 1 ) );
        } else {
            return $sanitized_title;
        }
	}
	
	/**
	 * Check if post type exists
	 *
	 * @param String $post_type - the post type
	 * @param bool|object $db - WP_DB object
	 *
	 * @return bool
	 */
	public static function post_type_exists( $post_type , $db = false ) {
		if ( !$db ) {
			global $wpdb;
			$db = $wpdb;
		}
		$sql 	= "SELECT count(ID) FROM $db->posts WHERE post_type = %s";
		$total 	= $db->get_var( $db->prepare( $sql, $post_type ) );
		return ( $total > 0 );
	}
}
?>