<?php
/**
 * Eventlog Query
 *
 * Handle Eventlog queries
 *
 * @since  1.0.3.7
 */
class MS_Helper_Database_Query_Eventlog extends MS_Helper_Database_Query_Base_Core {

    /**
     * Initialize default options for the Query object
     *
     * @since 1.0.3.7
     */
    function init_query_options() {
        $this->table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::EVENT_LOG );
        $this->default_query_vars = apply_filters( 
            'ms_helper_db_query_eventlog_query_vars', 
                array(
                    'membership_id', 'ms_relationship_id', 'user_id', 'event_topic', 'event_type'
                ) 
            );
        $this->search_fields = apply_filters( 
            'ms_helper_db_query_eventlog_search_fields', 
                array(
                    'user_id', 'event_topic', 'event_type'
                ) 
            );
        $this->name_field = 'name';
        $this->date_field = 'date_created';
    }

    /**
     * Custom where clause in query
     *
     * @since 1.0.3.7
     */
    function custom_where_clause( $where, $vars, $wpdb ) {

        if ( isset( $vars['membership_id'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.membership_id = %d", $vars['membership_id'] );
		}

        if ( isset( $vars['ms_relationship_id'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.ms_relationship_id = %d", $vars['ms_relationship_id'] );
		}

        if ( isset( $vars['user_id'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.user_id = %d", $vars['user_id'] );
		}

        if ( isset( $vars['event_topic'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.event_topic = %s", $vars['event_topic'] );
		}

        if ( isset( $vars['event_type'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.event_type = %s", $vars['event_type'] );
		}

		return apply_filters( 'ms_helper_db_query_eventlog_query_where', $where, $vars, $wpdb, $this );
	}
}

?>