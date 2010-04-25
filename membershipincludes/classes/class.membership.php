<?php

if(!class_exists('M_Membership')) {

	class M_Membership extends WP_User {

		var $db;

		var $tables = array('membership_relationships', 'membership_levels');

		var $membership_relationships, $membership_levels;

		var $subscriptions;
		var $levels;

		function M_Membership( $id, $name = '' ) {

			global $wpdb;

			parent::WP_User( $id, $name = '' );

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

			if(empty($this->subscriptions)) {

				$sql = $this->db->prepare( "SELECT sub_id FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id > 0", $this->ID );

				$this->subscriptions = $this->db->get_col( $sql );
			}

			return $this->subscriptions;
		}

		function get_level_ids() {

			if(empty($this->levels)) {

				$sql = $this->db->prepare( "SELECT level_id FROM {$this->membership_relationships} WHERE user_id = %d AND level_id > 0", $this->ID );

				$this->levels = $this->db->get_col( $sql );

			}

			return $this->levels;

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

		function increase_levelcount($level_id) {

			$sql = $this->db->prepare( "UPDATE {$this->membership_levels} SET level_count = level_count + 1 WHERE id = %d", $level_id );
			return $this->db->query( $sql );

		}

		function decrease_levelcount($level_id) {

			$sql = $this->db->prepare( "UPDATE {$this->membership_levels} SET level_count = level_count - 1 WHERE id = %d", $level_id );
			return $this->db->query( $sql );

		}

		function add_level($tolevel_id) {

			if(!$this->on_level($tolevel_id)) {

				$this->db->insert($this->membership_relationships, array('user_id' => $this->ID, 'level_id' => $tolevel_id, 'startdate' => current_time('mysql')));
				$this->increase_levelcount($tolevel_id);
			}

		}

		function drop_level($fromlevel_id) {

			if($this->on_level($fromlevel_id)) {

				$sql = $this->db->prepare( "DELETE FROM {$this->membership_relationships} WHERE user_id = %d AND level_id = %d AND sub_id = 0", $this->ID, $fromlevel_id);
				$this->db->query( $sql );
				$this->decrease_levelcount($fromlevel_id);
			}


		}

		function move_level($fromlevel_id, $tolevel_id) {

			if(!$this->on_level($tolevel_id) && $this->on_level($fromlevel_id)) {

				$this->db->update( $this->membership_relationships, array('level_id' => $tolevel_id, 'startdate' => current_time('mysql')), array('level_id' => $fromlevel_id, 'user_id' => $this->ID, 'sub_id' => 0) );
				$this->decrease_levelcount($fromlevel_id);
				$this->increase_levelcount($tolevel_id);
			}

		}

		function add_subscription($tosub_id, $tolevel_id = false) {

		}

		function drop_subscription($fromsub_id) {

		}

		function move_subscription($fromsub_id, $tosub_id, $tolevel_id = false) {

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


	}


}

?>