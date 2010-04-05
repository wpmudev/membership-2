<?php

if(!class_exists('M_Membership')) {

	class M_Membership extends WP_User {

		var $db;

		var $tables = array('membership_relationships');

		var $membership_relationships;

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

		function move_subscription() {

		}

		function move_level() {

		}

		function add_level() {

		}

		function delete_level() {

		}

		function delete_subscription() {

		}


	}


}

?>