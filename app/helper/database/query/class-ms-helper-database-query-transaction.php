<?php
/**
 * Transaction Query
 *
 * Handle Transaction queries
 *
 * @since  1.0.3.7
 */
class MS_Helper_Database_Query_Transaction extends MS_Helper_Database_Query_Base_Core {

    /**
     * Initialize default options for the Query object
     *
     * @since 1.0.3.7
     */
    function init_query_options() {
        $this->table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::TRANSACTION_LOG );
        $this->meta_name  = MS_Helper_Database_TableMeta::TRANSACTION_TYPE;
        $this->default_query_vars = apply_filters( 
            'ms_helper_db_query_transaction_query_vars', 
                array(
                    'gateway_id', 'method', 'success', 'subscription_id', 'invoice_id', 'member_id'
                ) 
            );
        $this->search_fields = apply_filters( 
            'ms_helper_db_query_transaction_search_fields', 
                array(
                    'gateway_id', 'method', 'success', 'subscription_id', 'invoice_id', 'member_id'
                ) 
            );
        $this->name_field = 'gateway_id';
        $this->date_field = 'date_created';
    }

    /**
     * Custom where clause in query
     *
     * @since 1.0.3.7
     */
    function custom_where_clause( $where, $vars, $wpdb ) {

        if ( isset( $vars['method'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.method = %s", $vars['method'] );
		}

        if ( isset( $vars['success'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.success = %d", $vars['success'] );
		}

        if ( isset( $vars['subscription_id'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.subscription_id = %d", $vars['subscription_id'] );
		}

        if ( isset( $vars['invoice_id'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.invoice_id = %d", $vars['invoice_id'] );
		}

        if ( isset( $vars['member_id'] ) ) {
            $where  .= $wpdb->prepare( " AND {$this->table_name}.member_id = %d", $vars['member_id'] );
		}

		return apply_filters( 'ms_helper_db_query_transaction_query_where', $where, $vars, $wpdb, $this );
	}

    /**
	 * Returns a list of object_ids that have the specified Transaction State.
	 *
	 * @since  1.0.3.7
	 * @param  string|array $state A valid transaction state [err|ok|ignore].
	 * @return array List of object_ids.
	 */
    public static function get_state_ids( $state ) {
        global $wpdb;
        $table_name         = MS_Helper_Database::get_table_name( MS_Helper_Database::TRANSACTION_LOG );
        $meta_table_name    = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        $meta_name          = MS_Helper_Database_TableMeta::TRANSACTION_TYPE;

        $sql = "
		SELECT p.ID
		FROM
			{$table_name} p
			LEFT JOIN {$meta_table_name} state1 ON
				state1.object_id = p.ID AND state1.meta_key = 'success' AND state1.object_type = %s
			LEFT JOIN {$meta_table_name} state2 ON
				state2.object_id = p.ID AND state2.meta_key = 'manual_state' AND state2.object_type = %s
			INNER JOIN {$meta_table_name} method ON
				method.object_id = p.ID AND method.meta_key = 'method' AND method.object_type = %s
		WHERE
			LENGTH( method.meta_value ) > 0
		";
        if ( ! is_array( $state ) ) { $state = array( $state ); }
		$state_cond = array();

		foreach ( $state as $key ) {
			switch ( $key ) {
				case 'err':
					$state_cond[] = "(
						(state1.meta_value IS NULL OR state1.meta_value IN ('','0','err'))
						AND (state2.meta_value IS NULL OR state2.meta_value IN (''))
					)";
					break;

				case 'ok':
					$state_cond[] = "(
						state1.meta_value IN ('1','ok')
						OR state2.meta_value IN ('1','ok')
					)";
					break;

				case 'ignore':
					$state_cond[] = "(
						state1.meta_value IN ('ignore')
						OR state2.meta_value IN ('ignore')
					)";
					break;
			}
		}
		$sql .= 'AND (' . implode( ' OR ', $state_cond ) . ')';

        $sql = $wpdb->prepare( $sql, $meta_name, $meta_name, $meta_name );
		$ids = $wpdb->get_col( $sql );

        return $ids;
        
    }

    /**
	 * Returns a list of object_ids that have the specified source_id.
	 *
	 * This tries to find transactions for imported subscriptions.
	 *
	 * @since  1.0.3.7
	 * @param  string $source_id Subscription ID before import; i.e. original ID.
	 * @param  string $source The import source. Currently supported: 'm1'.
	 * @return array List of object_ids.
	 */
    public static function get_matched_ids( $source_id, $source ) {
        global $wpdb;
        $table_name         = MS_Helper_Database::get_table_name( MS_Helper_Database::TRANSACTION_LOG );
        $meta_table_name    = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        $meta_name          = MS_Helper_Database_TableMeta::TRANSACTION_TYPE;

        $sql = "
		SELECT p.ID
		FROM
			{$table_name} p
			LEFT JOIN {$meta_table_name} form ON
				form.object_id = p.ID AND form.meta_key = 'post' AND form.object_type = %s 
		";

        $source_int = intval( $source_id );
		$int_len = strlen( $source_int );

		switch ( $source ) {
			case 'm1':
				$sql .= "
				WHERE p.gateway_id = 'paypalstandard'
				AND form.meta_value REGEXP 's:6:\"custom\";s:[0-9]+:\"[0-9]+:[0-9]+:{$source_int}:'
				";
				break;

			case 'pay_btn':
				$sql .= "
				WHERE p.gateway_id = 'paypalstandard'
				AND form.meta_value LIKE '%%s:6:\"btn_id\";s:{$int_len}:\"{$source_int}\";%%'
				AND form.meta_value LIKE '%%s:11:\"payer_email\";%%'
				";
				break;
		}

		$sql = $wpdb->prepare( $sql, $meta_name );
		$ids = $wpdb->get_col( $sql );

        return $ids;
    }

    /**
	 * Checks if the specified transaction was already successfully processed
	 * to avoid duplicate payments.
	 *
	 * @since  1.0.3.7
	 * @param  string $gateway The payment gateway ID.
	 * @param  string $external_id The external transaction ID.
	 * @return bool True if the transaction was processed/paid already.
	 */
    public static function was_processed( $gateway, $external_id ) {
        global $wpdb;
        $table_name         = MS_Helper_Database::get_table_name( MS_Helper_Database::TRANSACTION_LOG );
        $meta_table_name    = MS_Helper_Database::get_table_name( MS_Helper_Database::META );
        $meta_name          = MS_Helper_Database_TableMeta::TRANSACTION_TYPE;

        $sql = "
		SELECT COUNT(1)
		FROM {$table_name} p
			INNER JOIN {$meta_table_name} ext_id ON
				ext_id.object_id=p.ID AND ext_id.meta_key='external_id' AND ext_id.object_type = %s
			LEFT JOIN {$meta_table_name} state2 ON
				state2.object_id = p.ID AND state2.meta_key = 'manual_state' AND state2.object_type = %s
		WHERE
			p.gateway_id = %s
			AND ext_id.meta_value = %s
			AND (
				p.success IN ('1','ok')
				OR state2.meta_value IN ('1','ok')
			)
		";

        $sql = $wpdb->prepare(
			$sql,
			$meta_name,
            $meta_name,
			$gateway,
			$external_id
		);
		$res = intval( $wpdb->get_var( $sql ) );
        return $res;
    }
}

?>