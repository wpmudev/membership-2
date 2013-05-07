<?php
if(!class_exists('membershipcron')) {

	class membershipcron {

		var $build = 1;

		var $db;

		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;

		function __construct() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			// Set up Actions
			add_action( 'init', array(&$this, 'set_up_schedule') );
			add_filter( 'cron_schedules', array(&$this, 'add_time_period') );

			if( !$this->get_expiring_relationships_count() >= 50 ) {
				// Schedule for quarter hourly to get number of processing down a bit
				add_action( 'membership_process_quarterhourly_cron', array( &$this, 'transition_user_through_subscriptions' ) );
			} else {
				// We don't have that many, so let's process hourly instead
				add_action( 'membership_process_hourly_cron', array( &$this, 'transition_user_through_subscriptions' ) );
			}



		}

		function membershipcron() {
			$this->__construct();
		}

		function add_time_period( $periods ) {

			if(!is_array($periods)) {
				$periods = array();
			}

			$periods['15mins'] = array( 'interval' => 900, 'display' => __('Every 15 Mins', 'membership') );

			return $periods;
		}

		function set_up_schedule() {

			if ( !wp_next_scheduled( 'membership_perform_cron_processes_quarterhourly' ) ) {
				wp_schedule_event(time(), '15mins', 'membership_perform_cron_processes_quarterhourly');
			}

			if ( !wp_next_scheduled( 'membership_perform_cron_processes_hourly' ) ) {
				wp_schedule_event(time(), 'hourly', 'membership_perform_cron_processes_hourly');
			}

		}

		function get_expiring_relationships_count() {

			$sql = $this->db->prepare( "SELECT count(*) FROM {$this->membership_relationships} WHERE sub_id != 0 AND expirydate <= %s ORDER BY expirydate ASC", gmdate( 'Y-m-d H:i:s', time() ) );

			$result = $this->db->get_var( $sql );

			if(empty($result)) {
				return 0;
			} else {
				membership_debug_log( sprintf(__('CRON: There are %d expiring relationships' , 'membership'), $result ) );

				return $result;
			}

		}

		function get_expiring_relationships() {

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_relationships} WHERE sub_id != 0 AND expirydate <= %s ORDER BY expirydate ASC LIMIT 0, 25", gmdate( 'Y-m-d H:i:s', time() ) );

			$result = $this->db->get_results( $sql );

			return $result;

		}

		function transition_user_through_subscriptions() {

			$relationships = $this->get_expiring_relationships();

			if( !empty($relationships) ) {

				membership_debug_log( __('CRON: Loaded relationships' , 'membership') . print_r($relationships, true) );

				foreach( $relationships as $rel ) {

					// Just creating a membership record for this user should automatically
					// start the transition through the subscription
					membership_debug_log( sprintf(__('CRON: Processing member %d' , 'membership'), $rel->user_id ) );

					$member = new M_Membership( $rel->user_id );

				}

			}

		}

		// Quearter hourly processing
		function membership_perform_cron_processes_quarterhourly() {

			do_action( 'membership_process_quarterhourly_cron' );

		}

		// Hourly processing
		function membership_perform_cron_processes_hourly() {

			do_action( 'membership_process_hourly_cron' );

		}

	}

}

// Instanticate the class
$membershipcron = new membershipcron();

// The cron job actions and calls back
function membership_perform_cron_processes_quarterhourly() {
	global $membershipcron;

	$membershipcron->membership_perform_cron_processes_quarterhourly();
}

function membership_perform_cron_processes_hourly() {
	global $membershipcron;

	$membershipcron->membership_perform_cron_processes_hourly();
}

add_action( 'membership_perform_cron_processes_quarterhourly', 'membership_perform_cron_processes_quarterhourly' );
add_action( 'membership_perform_cron_processes_hourly', 'membership_perform_cron_processes_hourly' );
?>