<?php
/**
 * Migrate DB model.
 *
 * Manages DB migration.
 *
 *
 * @since  1.1.2
 *
 * @package Membership2
 * @subpackage Model
 */
 class MS_Model_Migrate extends MS_Model {

	/**
	 * Initialize migrate check.
	 *
	 * @since  1.1.2
	 */
	public static function init() {
		self::tables_exist();

		if ( self::needs_migration() ) {
			MS_Model_Settings::set_special_view( 'MS_View_MigrationDb' );
			
			add_action( 'wp_ajax_ms_do_migration', array( __CLASS__, 'process_migration' ) );
			add_action( 'wp_ajax_ms_check_migration', array( __CLASS__, 'check_migration' ) );
		} else {
			//falback
			add_action( 'wp_ajax_ms_do_migration', array( __CLASS__, 'revert_view' ) );
			add_action( 'wp_ajax_ms_check_migration', array( __CLASS__, 'revert_view' ) );
		}
	}

	/**
	 * Check if database talble was installed
	 * Most likely someone just copied the files
	 *
	 * @since 1.1.2
	 */
	private static function tables_exist() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		if ( !isset( $settings->database_set ) || !$settings->database_set ) {
			$database_set 			= MS_Helper_Database_Install::install();
			$settings->database_set = $database_set;
			$settings->save();
		}
	}


	/**
	 * Checks if the current installation should migrate data
	 *
	 * @since 1.1.2
	 *
	 * @return bool
	 */
	static function needs_migration() {
		global $wpdb;
		return ( MS_Helper_Database::post_type_exists( 'ms_communication_log', $wpdb ) 
				|| MS_Helper_Database::post_type_exists( 'ms_transaction_log', $wpdb ) 
				|| MS_Helper_Database::post_type_exists( 'ms_event', $wpdb ) );
	}

	/**
	 * Setup data to be used for the progress bar
	 *
	 * @since 1.1.2
	 *
	 * @return Array
	 */
	static function init_migration_data() {
		global $wpdb;
		$comm_log 	= MS_Helper_Database::post_type_total( 'ms_communication_log', $wpdb );
		$trans_log 	= MS_Helper_Database::post_type_total( 'ms_transaction_log', $wpdb );
		$event_log 	= MS_Helper_Database::post_type_total( 'ms_event', $wpdb );
		$data = array(
			'total' 	=> ( $comm_log + $trans_log + $event_log ),
			'processes' => array()
		);
		if ( $comm_log > 0 ) {
			$data['processes'][] = array(
				'step' 	=> 'comm_log',
				'name' 	=> __( 'Communication Logs', 'membership2' ),
				'total'	=> $comm_log
			);
		}
		if ( $trans_log > 0 ) {
			$data['processes'][] = array(
				'step' 	=> 'trans_log',
				'name' 	=> __( 'Transaction Logs', 'membership2' ),
				'total'	=> $trans_log
			);
		}
		if ( $event_log > 0 ) {
			$data['processes'][] = array(
				'step' 	=> 'event_log',
				'name' 	=> __( 'Event Logs', 'membership2' ),
				'total'	=> $event_log
			);
		}
		$data['stages'] = count( $data['processes'] );
		return $data;
	}

	/**
	 * Check migration progress
	 * Mainly for the progress bar
	 * 
	 * @return application/json
	 */
	static function check_migration() {
		$valid 	= is_admin() && check_ajax_referer( 'ms_check_migration', 'security', false );
		
		if ( ! $valid ) {
			wp_send_json_error( array(
				'message' => __( 'Invalid access', 'membership2' )
			) );
		}

		$migration_data = get_transient( 'ms_migrate_process_percentage' );
		if ( !$migration_data ) {
			$migration_data = array(
				'percent' 	=> 0,
				'message'	=> __( 'Initializing migration', 'membership' )
			);
		}
		if ( $migration_data['percent'] == 100 ) {
			delete_transient( 'ms_migrate_process_percentage' );
			MS_Model_Settings::reset_special_view();
		}
		$current_pass = get_transient( 'ms_migrate_process_pass' );
		if ( $current_pass ) {
			$migration_data['pass'] = $current_pass;
			delete_transient( 'ms_migrate_process_pass' );
		}
		
		wp_send_json_success( $migration_data );
	}

	/**
	 * Revert view incase accessed wrongly
	 * 
	 */
	static function revert_view() {
		MS_Model_Settings::reset_special_view();
		wp_send_json_error();
	}

	/**
	 * Process Migration in batches of 10
	 * Ajax action to process the migration
	 *
	 * @since 1.1.2
	 *
	 * @return application/json
	 */
	static function process_migration() {

		$valid 	= is_admin() && check_ajax_referer( 'ms_do_migration', 'security', false );
		
		if ( ! $valid ) {
			set_transient( 'ms_migrate_process_percentage', array(
				'percent' 	=> 0,
				'message'	=> __( 'Invalid access', 'membership2' )
			) );
		}

		$pass = isset( $_POST['pass'] ) ? $_POST['pass'] : 0;

		$migration_data 	= get_transient( 'ms_migrate_data' ); 
		if ( !$migration_data ) {
			$migration_data = self::init_migration_data();
			set_transient( 'ms_migrate_data', $migration_data );
		}

		$total 				= $migration_data['total'];
		$total_processes 	= count( $migration_data['processes'] );
		if ( !empty( $migration_data ) 
			&& $total > 0  && $total_processes > 0 ) {
			$completed 	= get_transient( 'ms_migrate_process_done' );
			if ( $completed && $total_processes > 1 ) {
				unset ( $migration_data['processes'][0] );
				set_transient( 'ms_migrate_data', $migration_data );
				$process = next( $migration_data['processes'] );
			} else {
				$process = $migration_data['processes'][0];
			}
			$page 		= ceil( $process['total'] / 10 );
			$percentage = ( ( $pass + 1 ) * $page );
			$percent 	= intval( $percentage / $total * 100 );
			$step 		= $process['step'];

			set_transient( 'ms_migrate_process_percentage', array(
				'percent' 	=> $percent,
				'message'	=> sprintf( __( '%d of %d records processed for %s ', 'membership' ), $percentage , $total, $process['name'] )
			) );

			self::migrate_log_tables( $process['total'], $step );

			set_transient( 'ms_migrate_process_pass', $pass + 1 );
		} else {
			set_transient( 'ms_migrate_process_percentage', array(
				'percent' 	=> 100,
				'message'	=> __( 'Nothing to migrate', 'membership' )
			) );
		}

		
	}


	/**
	 * Migrate custom log post type data to custom tables
	 * This checks if the custom post type exists before starting the upgrade
	 * TODO : Modify this to work in chunks incase the date is huge
	 *
	 * @since 1.1.2
	 *
	 */
	static function migrate_log_tables( $total, $step = 'comm_log' ) {
	
		global $wpdb;
		$response 			= '';
		$communication_log 	= 'ms_communication_log';
		$transaction_log 	= 'ms_transaction_log';
		$event 				= 'ms_event';
		$post_ids 			= array();
		$insert_data 		= array();
		$sql 				= "SELECT * FROM $wpdb->posts WHERE post_type = %s LIMIT %d, 10";
		$meta_sql 			= "SELECT * FROM $wpdb->postmeta WHERE post_id = %d";
		$page 				= get_transient( 'ms_migrate_process_page_'.$step );
		if ( !$page ) {
			$page = 0;
		}
		if ( $step == 'comm_log' ) {
			
			if ( MS_Helper_Database::post_type_exists( $communication_log, $wpdb ) ) {
				$table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::COMMUNICATION_LOG );
				$query 		= $wpdb->prepare( $sql, $communication_log, $page );
				$results 	= $wpdb->get_results( $query );
				
				foreach ( $results as $post ){
					$post_ids[] 			= $post->ID;
					$metadata 				= $wpdb->get_results( $wpdb->prepare( $meta_sql, $post->ID ) );
					$data 					= array();
					$data['date_created'] 	= $post->post_date;
					$data['title'] 			= $post->title;
					foreach ( $metadata as $mdata ){
						if ( $mdata->meta_key  === 'sent') {
							$data['sent'] = $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'recipient') {
							$data['recipient'] = $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'subscription_id') {
							$data['subscription_id'] = $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'trace') {
							$data['trace'] = $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'user_id') {
							$data['author'] = $mdata->meta_value;
						}
					}
					$insert_data[] = $data;
				}
				if ( !empty( $insert_data ) ) {
					foreach ( $insert_data as $data ){
						$wpdb->insert( $table_name, $data );
					}
					$insert_data = null;
					$insert_data = array();
				}
			}
		}
		if ( $step == 'trans_log' ) {
			if ( MS_Helper_Database::post_type_exists( $transaction_log, $wpdb ) ) {
				$table_name 			= MS_Helper_Database::get_table_name( MS_Helper_Database::TRANSACTION_LOG );
				$meta_table_name    	= MS_Helper_Database::get_table_name( MS_Helper_Database::META );
				$meta_name          	= MS_Helper_Database_TableMeta::TRANSACTION_TYPE;
				$query 					= $wpdb->prepare( $sql, $transaction_log, $page );
				$results 				= $wpdb->get_results( $query );
				$insert_defaults		= array( 'gateway_id', 'method', 'success', 'subscription_id', 'invoice_id', 'member_id', 'amount', 'custom_data', 'user_id');
				$insert_meta_data 		= array();
			
				foreach ( $results as $post ){
					$post_ids[] 				= $post->ID;
					$metadata 					= $wpdb->get_results( $wpdb->prepare( $meta_sql, $post->ID ) );
					$inner_meta 				= array();
					$data 						= array();
					$data['date_created'] 		= $post->post_date;
					$data['last_updated'] 		= $post->post_modified;
					$inner_meta['object_type'] 	= $meta_name;
					foreach ( $metadata as $mdata ){
						if ( in_array( $mdata->meta_key, $insert_defaults ) ) {
							$data[$mdata->meta_key] 	= $mdata->meta_value;
						} else {
							$inner_meta['meta_key'] 	= $mdata->meta_key;
							$inner_meta['meta_value'] 	= $mdata->meta_value;
							$inner_meta['date_created'] = $post->post_date;
						}
					}
					$insert_data[] 		= $data;
					$insert_meta_data[] = $inner_meta;
				}
				if ( !empty( $insert_data ) ) {
					foreach ( $insert_data as $data ){
						$result = $wpdb->insert( $table_name, $data );
						if ( false !== $result ) {
							$id = $wpdb->insert_id;
							foreach ( $insert_meta_data as $meta ){
								$meta['object_id'] = $id;
								$wpdb->insert( $meta_table_name, $meta );
							}
						}
					}
					$insert_data = null;
					$insert_data = array();
				}
			}
		}
		if ( $step == 'event_log' ) {
			if ( MS_Helper_Database::post_type_exists( $event, $wpdb ) ) {
				$table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::EVENT_LOG );
				$query 		= $wpdb->prepare( $sql, $event, $page );
				$results 	= $wpdb->get_results( $query );
				foreach ( $results as $post ){
					$post_ids[] 			= $post->ID;
					$metadata 				= $wpdb->get_results( $wpdb->prepare( $meta_sql, $post->ID ) );
					$data 					= array();
					$data['date_created'] 	= $post->post_date;
					foreach ( $metadata as $mdata ){
						if ( $mdata->meta_key  === 'name') {
							$data['name'] 	= $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'membership_id') {
							$data['membership_id'] = $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'ms_relationship_id') {
							$data['ms_relationship_id'] = $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'event_topic') {
							$data['event_topic'] = $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'user_id') {
							$data['user_id'] = $data->mdata;
						}
						if ( $mdata->meta_key  === 'event_type') {
							$data['event_type'] = $mdata->meta_value;
						}
						if ( $mdata->meta_key  === 'description') {
							$data['description'] = $mdata->meta_value;
						}
					}
					$insert_data[] = $data;
				}
				if ( !empty( $insert_data ) ) {
					foreach ( $insert_data as $data ){
						$wpdb->insert( $table_name, $data );
					}
					$insert_data = null;
					$insert_data = array();
				}
			}
		}

		$total_processed = get_transient( 'ms_migrate_process_total_'.$step );
		if ( !$total_processed ) {
			$total_processed = count( $post_ids );
		} else {
			$total_processed = $total_processed + count( $post_ids );
		}

		set_transient( 'ms_migrate_process_total_'.$step, $total_processed );

		if ( $total_processed > 0 && $total_processed < $total ) {
			$next_page 	= $page + 1;
			$pages 		= ceil( $total / 10 );
			set_transient( 'ms_migrate_process_page_'.$step, $next_page );
			$response = sprintf( __( '%d out of %d in', 'membership2' ), $next_page, $pages ) ;
		} else {
			set_transient( 'ms_migrate_process_done', $step );
			delete_transient( 'ms_migrate_process_page_'.$step );
			delete_transient( 'ms_migrate_process_total_'.$step );
			$response = __( 'Done', 'membership2' );
		}
		

		if ( !empty( $post_ids ) && is_array( $post_ids ) ) {
			$how_many 		= count( $post_ids );
			$placeholders 	= array_fill( 0, $how_many, '%d' );
			$format 		= implode( ', ', $placeholders );
			$sql 			= "DELETE FROM $wpdb->postmeta WHERE post_id in($format)";
			$sql_posts 		= "DELETE FROM $wpdb->posts WHERE ID in($format)";
			$wpdb->query( $wpdb->prepare( $sql, $post_ids ) );
			$wpdb->query( $wpdb->prepare( $sql_posts, $post_ids ) );
		}
	}
 }
?>