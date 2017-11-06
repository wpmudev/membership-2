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

	const MIGRATION_PAGE 	= 20;

	const EVENT_POST_TYPE 	= 'ms_event';
	const COMM_POST_TYPE 	= 'ms_communication_log';
	const TRANS_POST_TYPE 	= 'ms_transaction_log';

	/**
	 * Initialize migrate check.
	 *
	 * @since  1.1.2
	 */
	public static function init() {
		self::tables_exist();

		$settings = MS_Factory::load( 'MS_Model_Settings' );

		if ( !$settings->ignore_migration ) {
			if ( self::needs_migration() ) {
				MS_Model_Settings::set_special_view( 'MS_View_MigrationDb' );
				
				add_action( 'wp_ajax_ms_do_migration', array( __CLASS__, 'process_migration' ) );
				add_action( 'wp_ajax_ms_ignore_migration', array( __CLASS__, 'ignore_migration' ) );
			} else {
				MS_Model_Settings::reset_special_view();
				$settings = MS_Factory::load( 'MS_Model_Settings' );
				$settings->ignore_migration = true;
				$settings->save();
			}
		}
		//falback
		add_action( 'wp_ajax_ms_do_migration', array( __CLASS__, 'revert_view' ) );
		add_action( 'wp_ajax_ms_ignore_migration', array( __CLASS__, 'revert_view' ) );
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
		return ( MS_Helper_Database::post_type_exists( self::COMM_POST_TYPE, $wpdb ) 
				|| MS_Helper_Database::post_type_exists( self::TRANS_POST_TYPE, $wpdb ) 
				|| MS_Helper_Database::post_type_exists( self::EVENT_POST_TYPE, $wpdb ) );
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
		$comm_log 	= MS_Helper_Database::post_type_total( self::COMM_POST_TYPE, $wpdb );
		$trans_log 	= MS_Helper_Database::post_type_total( self::TRANS_POST_TYPE, $wpdb );
		$event_log 	= MS_Helper_Database::post_type_total( self::EVENT_POST_TYPE, $wpdb );
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
		if ( $event_log > 0 ) {
			$data['processes'][] = array(
				'step' 	=> 'event_log',
				'name' 	=> __( 'Event Logs', 'membership2' ),
				'total'	=> $event_log
			);
		}
		if ( $trans_log > 0 ) {
			$data['processes'][] = array(
				'step' 	=> 'trans_log',
				'name' 	=> __( 'Transaction Logs', 'membership2' ),
				'total'	=> $trans_log
			);
		}
		$data['stages'] = count( $data['processes'] );
		$total_data = get_transient( 'ms_migrate_process_total_data' );
		if ( !$total_data ) {
			set_transient( 'ms_migrate_process_total_data', ( $comm_log + $trans_log + $event_log ) );
		}
		return $data;
	}

	/**
	 * Get current stage
	 * 
	 * @return bool/array
	 */
	static function get_current_stage() {
		$migration_data 	= self::init_migration_data();
		$total 				= $migration_data['total'];
		$total_processes 	= count( $migration_data['processes'] );
		if ( $total > 0  && $total_processes > 0 ) {
			return $migration_data['processes'][0];
		} else {
			return false;
		}
	}

	/**
	 * Revert view incase accessed wrongly
	 * 
	 */
	static function revert_view() {
		if ( is_admin() ) {
			MS_Model_Settings::reset_special_view();
		}
		wp_send_json_error( array(
			'message' => __( 'Nothing to migrate', 'membership2' )
		) );
	}

	/**
	 * Ignore migration
	 * Incase user data is too big or they just dont want to migrate
	 * 
	 */
	static function ignore_migration() {
		$valid 	= is_admin() && check_ajax_referer( 'ms_ignore_migration', 'security', false );
		
		if ( $valid ) {
			MS_Model_Settings::reset_special_view();
			$settings = MS_Factory::load( 'MS_Model_Settings' );
			$settings->ignore_migration = true;
			$settings->save();
			wp_send_json_success();
		}
		
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
			$migration_data = array(
				'percent' 	=> 0,
				'message'	=> __( 'Invalid access', 'membership2' )
			);
		} else {
			$process 	= self::get_current_stage();
			if ( $process ) {
				$pass = get_transient( 'ms_migrate_process_pass' );
				if ( !$pass ) {
					set_transient( 'ms_migrate_process_pass', 1 );
					$pass = 1;
				}
				$total_data = get_transient( 'ms_migrate_process_total_data' );
				$per_page 	= apply_filters( 'ms_migrate_batch_page', self::MIGRATION_PAGE );
				$step 		= $process['step'];
				$page 		= ceil( $process['total'] / $total_data );
				$percentage = ( $pass * $page );
				$percent 	= intval( $percentage / $total_data * 100 );
				if ( $process['total'] < $per_page ) {
					$page = $process['total'];
				} else {
					$page = $page * $per_page;
				}
				self::migrate_log_tables( $step );
				$migration_data = array(
					'percent' 	=> $percent,
					'message'	=> sprintf( __( '%d of %d records processed', 'membership' ), $page , $total_data )
				);
				set_transient( 'ms_migrate_process_pass', ( $pass + 1 ) );
				
			} else {
				delete_transient( 'ms_migrate_process_pass' );
				$migration_data = array(
					'percent' 	=> 100,
					'message'	=> __( 'Nothing left to migrate', 'membership' )
				);
			}
		}
		wp_send_json_success( $migration_data );
	}


	/**
	 * Migrate custom log post type data to custom tables
	 * This checks if the custom post type exists before starting the upgrade
	 *
	 * @since 1.1.2
	 *
	 */
	static function migrate_log_tables( $step = 'comm_log' ) {
	
		global $wpdb;
		$wpdb->show_errors();

		$pages 				= apply_filters( 'ms_migrate_batch_page', self::MIGRATION_PAGE );
		$response 			= '';
		$communication_log 	= self::COMM_POST_TYPE;
		$transaction_log 	= self::TRANS_POST_TYPE;
		$event 				= self::EVENT_POST_TYPE;
		$post_ids 			= array();
		$insert_data 		= array();
		$sql 				= "SELECT * FROM {$wpdb->posts} WHERE post_type = %s LIMIT {$pages}";
		$meta_sql 			= "SELECT * FROM {$wpdb->postmeta} WHERE post_id = %d";
		if ( $step == 'comm_log' ) {
			$table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::COMMUNICATION_LOG );
			$query 		= $wpdb->prepare( $sql, $communication_log );
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
		} else if ( $step == 'trans_log' ) {
			$table_name 			= MS_Helper_Database::get_table_name( MS_Helper_Database::TRANSACTION_LOG );
			$meta_table_name    	= MS_Helper_Database::get_table_name( MS_Helper_Database::META );
			$meta_name          	= MS_Helper_Database_TableMeta::TRANSACTION_TYPE;
			$query 					= $wpdb->prepare( $sql, $transaction_log );
			$results 				= $wpdb->get_results( $query );
			$insert_defaults		= array( 'gateway_id', 'method', 'success', 'subscription_id', 'invoice_id', 'member_id', 'amount', 'custom_data', 'user_id');
			$insert_meta_data 		= array();
		
			foreach ( $results as $post ) {
				$post_ids[] 				= $post->ID;
				$metadata 					= $wpdb->get_results( $wpdb->prepare( $meta_sql, $post->ID ) );
				$data 						= array();
				$data['date_created'] 		= $post->post_date;
				$data['last_updated'] 		= $post->post_modified;
				foreach ( $metadata as $mdata ) {
					$inner_meta 				= array();
					$inner_meta['object_type'] 	= $meta_name;
					if ( in_array( $mdata->meta_key, $insert_defaults ) ) {
						$data[$mdata->meta_key] 	= $mdata->meta_value;
					} else {
						$inner_meta['meta_key'] 	= $mdata->meta_key;
						$inner_meta['meta_value'] 	= $mdata->meta_value;
						$inner_meta['date_created'] = $post->post_date;
					}
					$insert_meta_data[] = $inner_meta;
				}
				$insert_data[] 	= $data;
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
		} else if ( $step == 'event_log' ) {
			$table_name = MS_Helper_Database::get_table_name( MS_Helper_Database::EVENT_LOG );
			$query 		= $wpdb->prepare( $sql, $event );
			$results 	= $wpdb->get_results( $query );
			foreach ( $results as $post ) {
				$post_ids[] 			= $post->ID;
				$metadata 				= $wpdb->get_results( $wpdb->prepare( $meta_sql, $post->ID ) );
				$data 					= array();
				$data['date_created'] 	= $post->post_date;
				foreach ( $metadata as $mdata ) {
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
						$data['user_id'] = $mdata->meta_value;
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

		$how_many = count( $post_ids );
		if ( $how_many > 0 ) {
			
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