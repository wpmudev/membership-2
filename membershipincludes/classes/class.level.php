<?php
if(!class_exists('M_Level')) {

	class M_Level {

		var $id = false;

		var $db;
		var $tables = array('membership_levels', 'membership_rules', 'subscriptions_levels', 'membership_relationships', 'levelmeta');

		var $membership_levels;
		var $membership_rules;
		var $subscriptions_levels;
		var $membership_relationships;
		var $levelmeta;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $level;
		var $ruledetails = array();

		// Active rules
		var $positiverules = array();
		var $negativerules = array();

		var $lastlevelid;

		function __construct( $id = false , $fullload = false, $loadtype = array('public', 'core') ) {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			$this->id = $id;

			if($fullload) {
				$this->load_rules( $loadtype );
			}

		}

		function M_Level( $id = false, $fullload = false, $admin = false ) {
			$this->__construct( $id, $fullload, $admin );
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

			$this->ruledetails[$type] = $this->db->get_results( $sql );

			return $this->ruledetails[$type];

		}

		function delete($forced = false) {

			if($this->count() == 0 || $forced) {
				$sql = $this->db->prepare( "DELETE FROM {$this->membership_levels} WHERE id = %d", $this->id);

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

				do_action('membership_level_update', $this->id);

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

			do_action('membership_level_add', $this->id);

			return true; // for now

		}

		function toggleactivation($forced = false) {

			$this->dirty = true;

			if($this->count() == 0 || $forced) {
				$sql = $this->db->prepare( "UPDATE {$this->membership_levels} SET level_active = NOT level_active WHERE id = %d", $this->id);

				return $this->db->query($sql);
			} else {
				return false;
			}

		}
		// UI functions


		function load_rules( $loadtype = array('public','core') ) {

			global $M_Rules;

			//positiverules
			$positive = $this->get_rules('positive');

			//negativerules
			$negative = $this->get_rules('negative');

			//print_r($positive);
			//print_r($negative);

			if(!empty($positive)) {
				$key = 0;
				foreach( (array) $positive as $key => $rule) {
					if(isset($M_Rules[$rule->rule_area]) && class_exists($M_Rules[$rule->rule_area])) {
						$this->positiverules[$key] = new $M_Rules[$rule->rule_area];

						if( in_array($this->positiverules[$key]->rulearea, $loadtype) ) {
							$this->positiverules[$key]->on_positive(maybe_unserialize($rule->rule_value));
							$key++;
						} else {
							unset($this->positiverules[$key]);
						}

					}
				}
			}

			if(!empty($negative)) {
				$key = 0;
				foreach( (array) $negative as $key => $rule) {
					if(isset($M_Rules[$rule->rule_area]) && class_exists($M_Rules[$rule->rule_area])) {
						$this->negativerules[$key] = new $M_Rules[$rule->rule_area];

						if( in_array($this->negativerules[$key]->rulearea, $loadtype) ) {
							$this->negativerules[$key]->on_negative(maybe_unserialize($rule->rule_value));
							$key++;
						} else {
							unset($this->negativerules[$key]);
						}

					}
				}
			}

		}

		function has_positive_rule($rulename) {

			if(!empty($this->positiverules)) {
				foreach($this->positiverules as $key => $rule) {
					if($rule->name == $rulename) {
						return true;
					}
				}
			}

			return false;

		}

		function has_negative_rule($rulename) {

			if(!empty($this->negativerules)) {
				foreach($this->negativerules as $key => $rule) {
					if($rule->name == $rulename) {
						return true;
					}
				}
			}

			return false;

		}

		function has_rule($rulename) {

			if($this->has_negative_rule($rulename) || $this->has_positive_rule($rulename)) {
				return true;
			} else {
				return false;
			}

		}

		// pass thrus

		function positive_pass_thru($rulename, $function, $arg) {

			if(!empty($this->positiverules)) {
				foreach($this->positiverules as $key => $rule) {
					if($rule->name == $rulename) {
						return $rule->$function('positive', $arg);
					}
				}
			}

			return false;

		}

		function negative_pass_thru($rulename, $function, $arg) {

			if(!empty($this->negativerules)) {
				foreach($this->negativerules as $key => $rule) {
					if($rule->name == $rulename) {
						return $rule->$function('negative', $arg);
					}
				}
			}

			return false;

		}

		// Counting
		function count( ) {

			$sql = $this->db->prepare( "SELECT count(*) as levelcount FROM {$this->membership_relationships} WHERE level_id = %d", $this->id );

			return $this->db->get_var( $sql );

		}

		// Meta information
		function get_meta($key, $default = false) {

			$sql = $this->db->prepare( "SELECT meta_value FROM {$this->levelmeta} WHERE meta_key = %s AND level_id = %d", $key, $this->id);

			$row = $this->db->get_var( $sql );

			if(empty($row)) {
				return $default;
			} else {
				return $row;
			}

		}

		function add_meta($key, $value) {

			return $this->insertorupdate( $this->levelmeta, array( 'level_id' => $this->id, 'meta_key' => $key, 'meta_value' => $value) );

		}

		function update_meta($key, $value) {

			return $this->insertorupdate( $this->levelmeta, array( 'level_id' => $this->id, 'meta_key' => $key, 'meta_value' => $value) );

		}

		function delete_meta($key) {

			$sql = $this->db->prepare( "DELETE FROM {$this->levelmeta} WHERE meta_key = %s AND level_id = %d", $key, $this->id);

			return $this->db->query( $sql );

		}

		function insertorupdate( $table, $query ) {

				$fields = array_keys($query);
				$formatted_fields = array();
				foreach ( $fields as $field ) {
					$form = '%s';
					$formatted_fields[] = $form;
				}
				$sql = "INSERT INTO `$table` (`" . implode( '`,`', $fields ) . "`) VALUES ('" . implode( "','", $formatted_fields ) . "')";
				$sql .= " ON DUPLICATE KEY UPDATE ";

				$dup = array();
				foreach($fields as $field) {
					$dup[] = "`" . $field . "` = VALUES(`" . $field . "`)";
				}

				$sql .= implode(',', $dup);

				return $this->db->query( $this->db->prepare( $sql, $query ) );

		}

	}

}
?>