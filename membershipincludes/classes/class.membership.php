<?php

if(!class_exists('M_Membership')) {

	class M_Membership extends WP_User {

		var $db;

		var $tables = array('membership_relationships', 'membership_levels', 'subscriptions');

		var $membership_relationships, $membership_levels, $subscriptions;

		var $subids;
		var $levids;

		var $levels = array();

		function M_Membership( $id, $name = '' ) {

			global $wpdb;

			if($id != 0) {
				parent::WP_User( $id, $name = '' );
			}


			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = $wpdb->prefix . $table;
			}

		}

		function active_member() {

			$active = get_usermeta( $this->ID, $this->db->prefix . 'membership_active');

			if(empty($active) || $active == 'no') {
				return false;
			} else {
				return true;
			}
		}

		function get_subscription_ids() {

			if(empty($this->subids)) {

				$sql = $this->db->prepare( "SELECT sub_id FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id > 0", $this->ID );

				$this->subids = $this->db->get_col( $sql );
			}

			return $this->subids;
		}

		function get_level_ids() {

			if(empty($this->levids)) {

				$sql = $this->db->prepare( "SELECT level_id, sub_id FROM {$this->membership_relationships} WHERE user_id = %d AND level_id > 0", $this->ID );

				$this->levids = $this->db->get_results( $sql );

			}

			return $this->levids;

		}

		function on_level($level_id, $include_subs = false) {

			$sql = $this->db->prepare( "SELECT rel_id FROM {$this->membership_relationships} WHERE user_id = %d AND level_id = %d", $this->ID, $level_id );

			if(!$include_subs) {
				$sql .= $this->db->prepare( " AND sub_id = 0" );
			}

			$result = $this->db->get_col( $sql );

			if(empty($result)) {
				return false;
			} else {
				return true;
			}

		}

		function on_sub($sub_id) {

			$sql = $this->db->prepare( "SELECT rel_id FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $sub_id );

			$result = $this->db->get_col( $sql );

			if(empty($result)) {
				return false;
			} else {
				return true;
			}

		}

		function add_level($tolevel_id) {

			if(!$this->on_level($tolevel_id)) {

				$this->db->insert($this->membership_relationships, array('user_id' => $this->ID, 'level_id' => $tolevel_id, 'startdate' => current_time('mysql'), 'updateddate' => current_time('mysql')));

			}

		}

		function drop_level($fromlevel_id) {

			if($this->on_level($fromlevel_id)) {

				$sql = $this->db->prepare( "DELETE FROM {$this->membership_relationships} WHERE user_id = %d AND level_id = %d AND sub_id = 0", $this->ID, $fromlevel_id);
				$this->db->query( $sql );

			}


		}

		function move_level($fromlevel_id, $tolevel_id) {

			if(!$this->on_level($tolevel_id) && $this->on_level($fromlevel_id)) {

				$this->db->update( $this->membership_relationships, array('level_id' => $tolevel_id, 'updateddate' => current_time('mysql')), array('level_id' => $fromlevel_id, 'user_id' => $this->ID, 'sub_id' => 0) );

			}

		}

		function add_subscription($tosub_id, $tolevel_id = false) {

			if(!$this->on_sub($tosub_id)) {
				$this->db->insert($this->membership_relationships, array('user_id' => $this->ID, 'level_id' => $tolevel_id, 'sub_id' => $tosub_id, 'startdate' => current_time('mysql'), 'updateddate' => current_time('mysql')));

			}

		}

		function drop_subscription($fromsub_id) {

			if($this->on_sub($fromsub_id)) {
				$sql = $this->db->prepare( "DELETE FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $fromsub_id);
				$this->db->query( $sql );

			}

		}

		function move_subscription($fromsub_id, $tosub_id, $tolevel_id) {

			if(!$this->on_level($tolevel_id, true) && $this->on_sub($fromsub_id)) {
				$this->db->update( $this->membership_relationships, array('sub_id' => $tosub_id, 'level_id' => $tolevel_id, 'updateddate' => current_time('mysql')), array( 'sub_id' => $fromsub_id, 'user_id' => $this->ID ) );
			}

		}

		// Member operations

		function toggle_activation() {

			$active = get_usermeta( $this->ID, $this->db->prefix . 'membership_active');

			if(empty($active) || $active == 'no') {
				update_usermeta($this->ID, $this->db->prefix . 'membership_active', 'yes');
			} else {
				update_usermeta($this->ID, $this->db->prefix . 'membership_active', 'no');
			}

			return true; // for now


		}

		// Levels functions

		function assign_level($level_id) {
			// Used to force assign a level on a user - mainly for non logged in users
			$this->levels[$level_id] = new M_Level( $level_id );

		}

		function has_level($level_id = false) {
			// Returns true if the user has a level to process

			if($level_id) {
				return isset($this->levels[$level_id]);
			} else {
				return !empty($this->levels);
			}
		}

		function load_levels() {

		}


	}


}

?>