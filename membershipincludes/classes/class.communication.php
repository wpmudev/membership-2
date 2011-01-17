<?php
if(!class_exists('M_Communication')) {

	class M_Communication {

		var $build = 1;

		var $db;
		var $tables = array('membership_levels', 'membership_rules', 'subscriptions_levels', 'membership_relationships');

		var $membership_levels;
		var $membership_rules;
		var $subscriptions_levels;
		var $membership_relationships;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $level;
		var $ruledetails = array();

		// Active rules
		var $positiverules = array();
		var $negativerules = array();

		var $lastlevelid;

		function __construct( $id = false) {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			$this->id = $id;

		}

		function M_Communication( $id = false ) {
			$this->__construct( $id );
		}



	}
}
?>