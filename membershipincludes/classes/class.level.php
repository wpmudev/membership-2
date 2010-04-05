<?php
if(!class_exists('M_Level')) {

	class M_Level {

		var $id = false;

		var $db;
		var $tables = array('membership_levels', 'membership_rules', 'subscriptions_levels');

		var $membership_levels;
		var $membership_rules;
		var $subscriptions_levels;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $level;
		var $rules = array();

		var $lastlevelid;

		function __construct( $id = false ) {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = $wpdb->prefix . $table;
			}

			$this->id = $id;

		}

		function M_Level( $id = false ) {
			$this->__construct();
		}

		// Fields

		function level_title() {

			if(empty($this->level)) {
				$level = $this->get();

				if($level) {
					return $level->level_title;
				} else {
					return false;
				}
			} else {
				return $this->level->level_title;
			}

		}

		// Gets

		function get() {

			if($this->dirty) {
				$sql = $this->db->prepare( "SELECT * FROM {$this->membership_levels} WHERE id = %d", $this->id);

				$this->level = $this->db->get_row($sql);

				$this->dirty = false;
			}

			return $this->level;

		}

		function get_rules($type) {

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_rules} WHERE level_id = %d AND rule_ive = %s ORDER BY rule_order ASC", $this->id, $type );

			$this->rules[$type] = $this->db->get_results( $sql );

			return $this->rules[$type];

		}

		function delete($forced = false) {

			if($forced) {
				$sql = $this->db->prepare( "DELETE FROM {$this->membership_levels} WHERE id = %d", $this->id);
			} else {
				$sql = $this->db->prepare( "DELETE FROM {$this->membership_levels} WHERE id = %d AND level_count = 0", $this->id);
			}

			$sql2 = $this->db->prepare( "DELETE FROM {$this->membership_rules} WHERE level_id = %d", $this->id);

			$sql3 = $this->db->prepare( "DELETE FROM {$this->subscriptions_levels} WHERE level_id = %d", $this->id);

			if($this->db->query($sql)) {

				$this->db->query($sql2);
				$this->db->query($sql3);

				$this->dirty = true;

				return true;

			} else {
				return false;
			}

		}

		function update() {

			$this->dirty = true;

			if($this->id < 0 ) {
				return $this->add();
			} else {
				$return = $this->db->update($this->membership_levels, array('level_title' => $_POST['level_title'], 'level_slug' => sanitize_title($_POST['level_title'])), array('id' => $this->id));

				// Remove the existing rules for this membership level
				$this->db->query( $this->db->prepare( "DELETE FROM {$this->membership_rules} WHERE level_id = %d", $this->id ) );

				// Process the new rules
				if(!empty($_POST['in-positive-rules'])) {
					$rules = explode(',', $_POST['in-positive-rules']);
					$count = 1;
					foreach( (array) $rules as $rule ) {
						if(!empty($rule)) {
							// Check if the rule has any information for it.
							if(isset($_POST[$rule])) {
								$ruleval = maybe_serialize($_POST[$rule]);
								// write it to the database
								$this->db->insert($this->membership_rules, array("level_id" => $this->id, "rule_ive" => 'positive', "rule_area" => $rule, "rule_value" => $ruleval, "rule_order" => $count++));
							}
						}

					}
				}

				if(!empty($_POST['in-negative-rules'])) {
					$rules = explode(',', $_POST['in-negative-rules']);
					$count = 1;
					foreach( (array) $rules as $rule ) {
						if(!empty($rule)) {
							// Check if the rule has any information for it.
							if(isset($_POST[$rule])) {
								$ruleval = maybe_serialize($_POST[$rule]);
								// write it to the database
								$this->db->insert($this->membership_rules, array("level_id" => $this->id, "rule_ive" => 'negative', "rule_area" => $rule, "rule_value" => $ruleval, "rule_order" => $count++));
							}
						}
					}
				}

			}

			return true; // for now

		}

		function add() {

			$this->dirty = true;

			if($this->id > 0 ) {
				return $this->update();
			} else {
				$return = $this->db->insert($this->membership_levels, array('level_title' => $_POST['level_title'], 'level_slug' => sanitize_title($_POST['level_title'])));

				$this->id = $this->db->insert_id;

				// Process the new rules
				if(!empty($_POST['in-positive-rules'])) {
					$rules = explode(',', $_POST['in-positive-rules']);
					$count = 1;
					foreach( (array) $rules as $rule ) {
						if(!empty($rule)) {
							// Check if the rule has any information for it.
							if(isset($_POST[$rule])) {
								$ruleval = maybe_serialize($_POST[$rule]);
								// write it to the database
								$this->db->insert($this->membership_rules, array("level_id" => $this->id, "rule_ive" => 'positive', "rule_area" => $rule, "rule_value" => $ruleval, "rule_order" => $count++));
							}
						}

					}
				}

				if(!empty($_POST['in-negative-rules'])) {
					$rules = explode(',', $_POST['in-negative-rules']);
					$count = 1;
					foreach( (array) $rules as $rule ) {
						if(!empty($rule)) {
							// Check if the rule has any information for it.
							if(isset($_POST[$rule])) {
								$ruleval = maybe_serialize($_POST[$rule]);
								// write it to the database
								$this->db->insert($this->membership_rules, array("level_id" => $this->id, "rule_ive" => 'negative', "rule_area" => $rule, "rule_value" => $ruleval, "rule_order" => $count++));
							}
						}
					}
				}

			}

			return true; // for now

		}

			function toggleactivation($forced = false) {

				$this->dirty = true;

				if($forced) {
					$sql = $this->db->prepare( "UPDATE {$this->membership_levels} SET level_active = NOT level_active WHERE id = %d", $this->id);
				} else {
					$sql = $this->db->prepare( "UPDATE {$this->membership_levels} SET level_active = NOT level_active WHERE id = %d AND level_count = 0", $this->id);
				}

				return $this->db->query($sql);

			}
		// UI functions

	}

}
?>