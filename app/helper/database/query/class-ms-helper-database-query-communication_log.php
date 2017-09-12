<?php
/**
 * Communication Log Query
 *
 * Handle Communication Log queries
 *
 * @since  1.0.3.7
 */
class MS_Helper_Database_Query_Communication_Log extends MS_Helper_Database_Query_Base_Core {

    /**
     * Initialize default options for the Query object
     *
     * @since 1.0.3.7
     */
    function init_query_options() {
        $this->table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::COMMUNICATION_LOG );
        $this->default_query_vars = apply_filters( 
            'ms_helper_db_query_communication_query_vars', 
                array(
                    'sent', 'recipient', 'subscription_id'
                ) 
            );
        $this->search_fields = apply_filters( 
            'ms_helper_db_query_communication_search_fields', 
                array(
                    'sent', 'recipient', 'subscription_id'
                ) 
            );

        $this->name_field = 'sent';
        $this->date_field = 'date_created';
    }

}

?>