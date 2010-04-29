<?php
if(!class_exists('membershippublic')) {

	class membershippublic {

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
				$this->$table = $wpdb->prefix . $table;
			}

			// Set up Actions
			add_action( 'init', array(&$this, 'initialise_plugin') );


		}

		function membershippublic() {
			$this->__construct();
		}

		function initialise_plugin() {

			global $user, $member, $M_options;

			$M_options = get_option('membership_options', array());

			$user = wp_get_current_user();


			if($user->ID > 0) {
				// Logged in - check there settings, if they have any.
				$member = new M_Membership($user->ID);

			} else {
				// not logged in so limit based on stranger settings
				// need to grab the stranger settings
				$member = new M_Membership($user->ID);
				if(isset($M_options['strangerlevel']) && $M_options['strangerlevel'] != 0) {
					$member->assign_level($M_options['strangerlevel']);
				}

			}


		}


	}

}
?>