<?php
/**
 * Database Meta Table
 *
 * Persists data into meta table
 *
 * @since  1.2
 *
 */
class MS_Helper_Database_TableMeta extends MS_Helper {

    // Meta types
	const MEMBERSHIP_TYPE = 'Membership';
    const INVOICE_TYPE = 'Invoice';
    const RELATIONSHIP_TYPE = 'RelationShip';
    const TRANSACTION_TYPE = 'TransactionLog';
    const COMMUNICATION_TYPE = 'Communication';


    /**
     * Add metadata for the specified object.
     *
     *
     * @param string $meta_type  Type of object metadata is for (e.g., comment, post, or user)
     * @param int    $object_id  ID of the object metadata is for
     * @param string $meta_key   Metadata key
     * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
     *
     * @since 1.2
     *
     * @return int|false The meta ID on success, false on failure.
     */
    public static function save( $meta_type, $object_id, $meta_key, $meta_value ) {
        global $wpdb;

        if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
            return false;
        }

        $object_id = absint( $object_id );
        if ( ! $object_id ) {
            return false;
        }
        
        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::META );

        if ( !$table_name ) {
            return false;
        }

        $meta_key   = wp_unslash( $meta_key );
	    $meta_value = wp_unslash( $meta_value );
        $meta_value = maybe_serialize( $meta_value );

        $result = $wpdb->insert( $table_name, array(
            'object_id'     => $object_id,
            'object_type'   => $meta_type,
            'meta_key'      => $meta_key,
            'meta_value'    => $meta_value,
            'date_created'  => MS_Helper_Period::current_date( 'Y-m-d H:i:s' )
        ) );

        if ( ! $result )
            return false;

        $meta_id = (int) $wpdb->insert_id;

        wp_cache_delete( $object_id, $meta_type . '_ms_meta' );

        return $meta_id;
    }

    /**
     * Update metadata for the specified object. If no value already exists for the specified object
     * ID and metadata key, the metadata will be added.
     *
     * @param string $meta_type  Type of object metadata is for (e.g., comment, post, or user)
     * @param int    $object_id  ID of the object metadata is for
     * @param string $meta_key   Metadata key
     * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
     *
     * @since 1.2
     *
     * @return int|bool Meta ID if the key didn't exist, true on successful update, false on failure.
     */
    public static function update( $meta_type, $object_id, $meta_key, $meta_value ) {
        global $wpdb;

        if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
            return false;
        }

        $object_id = absint( $object_id );
        if ( ! $object_id ) {
            return false;
        }

        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        if ( !$table_name ) {
            return false;
        }

        $raw_meta_key   = $meta_key;
        $meta_key       = wp_unslash( $meta_key );
        $passed_value   = $meta_value;
        $meta_value     = wp_unslash( $meta_value );        

        $old_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $table_name WHERE meta_key = %s AND `object_type` = %s AND `object_id` = %d", $meta_key, $meta_type, $object_id ) );
        if ( empty( $old_ids ) ) {
            return self::save( $meta_type, $object_id, $raw_meta_key, $passed_value );
        }

        $_meta_value    = $meta_value;
	    $meta_value     = maybe_serialize( $meta_value );
        $where          = array( 
                                'object_id' => $object_id, 
                                'meta_key' => $meta_key, 
                                'object_type' => $meta_type 
                                );

        $result = $wpdb->update( $table_name, array(
            'meta_value'    => $meta_value,
            'date_updated'  => MS_Helper_Period::current_date( 'Y-m-d H:i:s' )
        ), $where );
        if ( ! $result )
            return false;
        
        wp_cache_delete( $object_id, $meta_type . '_ms_meta' );

        return true;
    }

    /**
     * Retrieve metadata for the specified object.
     *
     * @param string $meta_type Type of object metadata is for (e.g., comment, post, or user)
     * @param int    $object_id ID of the object metadata is for
     * @param string $meta_key  Optional. Metadata key. If not specified, retrieve all metadata for
     * 		                    the specified object.
     * @param bool    $single Whether to return only the first value of the specified $meta_key.
     * @param array   $default_values Default values
     *
     * @since 1.2
     *
     * @return mixed Single metadata value, or array of values
     */
    public static function get( $meta_type, $object_id, $meta_key, $single, $default_values = array() ) {
        global $wpdb;
        
        if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
            return $default_values;
        }

        $object_id = absint( $object_id );
        if ( ! $object_id ) {
            return $default_values;
        }

        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        if ( !$table_name ) {
            return $default_values;
        }

        $meta_cache = wp_cache_get( $object_id, $meta_type . '_ms_meta' );

        if ( $meta_cache ) {
            if ( isset( $meta_cache[$meta_key] ) ) {
                if ( $single )
                    return maybe_unserialize( $meta_cache[$meta_key][0] );
                else
                    return array_map( 'maybe_unserialize', $meta_cache[$meta_key] );
            }
        } else {
            $meta_value = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM $table_name WHERE meta_key = %s AND `object_type` = %s AND `object_id` = %d", $meta_key, $meta_type, $object_id ) );

            if ( !empty( $meta_value ) ) {
                wp_cache_set( $object_id, $meta_value, $meta_type . '_ms_meta' );
                if ( $single )
                    return maybe_unserialize( $meta_value );
                else
                    return array_map( 'maybe_unserialize', $meta_value );
            }
        }

        return false;
    }

    /**
     * Delete metadata for the specified object.
     *
     *
     * @param string $meta_type  Type of object metadata is for (e.g., comment, post, or user)
     * @param int    $object_id  ID of the object metadata is for
     * @param string $meta_key   Metadata key
     *
     * @since 1.2
     *
     * @return bool True on successful delete, false on failure.
     */
    public static function delete( $meta_type, $object_id, $meta_key ) {
        global $wpdb;

        if ( ! $meta_type || ! $meta_key || ! is_numeric( $object_id ) ) {
            return false;
        }

        $object_id = absint( $object_id );
        if ( ! $object_id ) {
            return false;
        }

        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        if ( !$table_name ) {
            return false;
        }

        $meta_key = wp_unslash( $meta_key );

        $query = "DELETE FROM $table_name WHERE `object_type` = %s AND `object_id` = %d AND `meta_key` = %s";
        $count = $wpdb->query( $wpdb->prepare( $query, $meta_type, $object_id, $meta_key ) );

        if ( !$count ){
            return false;
        }
         
        wp_cache_delete( $object_id, $meta_type . '_ms_meta' );

        return true;
    }


    /**
     * Delete all metadata for the specified object.
     *
     *
     * @param string $meta_type  Type of object metadata is for (e.g., comment, post, or user)
     * @param int    $object_id  ID of the object metadata is for
     *
     * @since 1.2
     *
     * @return bool True on successful delete, false on failure.
     */
    public static function delete_all( $meta_type, $object_id) {
        global $wpdb;

        if ( ! $meta_type || ! is_numeric( $object_id ) ) {
            return false;
        }

        $object_id = absint( $object_id );
        if ( ! $object_id ) {
            return false;
        }

        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        if ( !$table_name ) {
            return false;
        }
        
        $query = "DELETE FROM $table_name WHERE `object_type` = %s AND `object_id` = %d";
        $count = $wpdb->query( $wpdb->prepare( $query, $meta_type, $object_id ) );

        if ( !$count ){
            return false;
        }
         
        wp_cache_delete( $object_id, $meta_type . '_ms_meta' );

        return true;
    }

    /**
     * Retrieve all metadata keys for the specified object.
     *
     * @param string $meta_type Type of object metadata is for (e.g., comment, post, or user)
     * @param int    $object_id ID of the object metadata is for
     *
     * @since 1.2
     *
     * @return mixed Single metadata value, or array of values
     */
    public static function keys( $meta_type, $object_id , $serialize = false ) {

        global $wpdb;

        if ( ! $meta_type || ! is_numeric( $object_id ) ) {
            return false;
        }

        $object_id = absint( $object_id );
        if ( ! $object_id ) {
            return false;
        }

        $table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        if ( !$table_name ) {
            return false;
        }

        $meta_value = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM $table_name WHERE  `object_type` = %s AND `object_id` = %d", $meta_type, $object_id ) );

        if ( $serialize ) {
            return array_map( 'maybe_unserialize', $meta_value );
        } else {
            return $meta_value;
        }
            
    }


    /**
     * Retrieve all metadata for the specified object.
     *
     * @param string $meta_type Type of object metadata is for (e.g., comment, post, or user)
     * @param int    $object_id ID of the object metadata is for
     *
     * @since 1.2
     *
     * @return mixed Single metadata value, or array of values
     */
    public static function meta( $meta_type, $object_id , $meta_key = '', $single = false ) {

        global $wpdb;

        if ( ! $meta_type || ! is_numeric( $object_id ) ) {
            return false;
        }

        $object_id = absint( $object_id );
        if ( ! $object_id ) {
            return false;
        }

        $meta_cache = wp_cache_get( $object_id, $meta_type . '_ms_meta');

        if ( !$meta_cache ) {
            $meta_cache = update_meta_cache( $meta_type, array( $object_id ) );
            $meta_cache = $meta_cache[$object_id];
        }

        if ( isset( $meta_cache[$meta_key] ) ) {
            if ( $single )
                return maybe_unserialize( $meta_cache[$meta_key][0] );
            else
                return array_map('maybe_unserialize', $meta_cache[$meta_key]);
        }

        if ( $single )
            return '';
        else
            return array();
            
    }
}

?>