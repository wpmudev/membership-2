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
				$this->$table = membership_db_prefix($this->db, $table);
			}

			$this->transition_through_subscription();

		}

		function active_member() {

			$active = get_usermeta( $this->ID, $this->db->prefix . 'membership_active');

			if(empty($active) || $active == 'yes') {
				return true;
			} else {
				return false;
			}
		}

		function mark_for_expire( $sub_id ) {
			update_usermeta( $this->ID, '_membership_expire_next', $sub_id);

			do_action('membership_mark_for_expire', $sub_id, $this->ID);

		}

		function is_marked_for_expire($sub_id) {

			$markedsub_id = get_usermeta( $this->ID, '_membership_expire_next', true);

			if(!empty($markedsub_id) && $markedsub_id == $sub_id) {
				return true;
			} else {
				return false;
			}

		}

		function is_member() {

			$sql = $this->db->prepare( "SELECT count(*) FROM {$this->membership_relationships} WHERE user_id = %d", $this->ID );

			$res = $this->db->get_var($sql);

			if($res > 0) {
				return true;
			} else {
				return false;
			}

		}

		function has_subscription() {

			$sql = $this->db->prepare( "SELECT count(*) FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id != 0", $this->ID );

			$res = $this->db->get_var($sql);

			if($res > 0) {
				return true;
			} else {
				return false;
			}

		}

		function move_to($sub_id, $thislevel_id, $thislevel_order, $nextlevel) {

			if($this->on_sub($sub_id)) {

				if($nextlevel) {

					$this->move_subscription($sub_id, $sub_id, $nextlevel->level_id, $nextlevel->level_order);

				}

			}
		}

		function transition_through_subscription() {

			$relationships = $this->get_relationships();

			if($relationships) {
				foreach($relationships as $key => $rel) {
					if(mysql2date("U", $rel->expirydate) <= time()) {
						// expired, we need to move forwards
						if($this->is_marked_for_expire($rel->sub_id)) {
							$this->expire_subscription($rel->sub_id);
							delete_usermeta($this->ID, '_membership_expire_next');
							continue;
						}

						$subscription = new M_Subscription($rel->sub_id);
						$nextlevel = $subscription->get_next_level($rel->level_id, $rel->order_instance);

						if($nextlevel){
							if(empty($nextlevel->level_price)) {
								// this is a non paid level transition so we can go head
								$this->move_to($rel->sub_id, $rel->level_id, $rel->order_instance, $nextlevel);
							} else {

								// This is a paid level transition so check for a payment
								// Transition for now cos we are disabling everything when a payment fails
								$this->move_to($rel->sub_id, $rel->level_id, $rel->order_instance, $nextlevel);
							}
						} else {
							// there isn't a next level so expire this subscription
							$this->expire_subscription($rel->sub_id);
						}

					} else {
						// not expired we can ignore this for now
						continue;
					}

				}
			}

		}

		function expire_subscription($sub_id = false) {

			if(!apply_filters( 'pre_membership_expire_subscription', true, $sub_id, $this->ID )) {
				return false;
			}

			if(!$sub_id) {
				// expire all of the current subscriptions
				$this->db->query( $this->db->prepare( "DELETE FROM {$this->membership_relationships} WHERE user_id = %d", $this->ID ));
			} else {
				// expire just the passed subscription
				$this->db->query( $this->db->prepare( "DELETE FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $sub_id ));

			}

			do_action( 'membership_expire_subscription', $sub_id, $this->ID);

		}

		function create_subscription($sub_id) {

			$subscription = new M_Subscription( $sub_id );
			$levels = $subscription->get_levels();

			if(!empty($levels)) {

				foreach($levels as $key => $level) {
					if($level->level_order == 1) {

						$this->add_subscription($sub_id, $level->level_id, $level->level_order);
						break;
					}
				}

				return true;

			} else {
				return false;
			}

		}

		function has_active_payment($sub_id = false) {

			if(!$sub_id) {

			} else {

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

		function get_relationships() {

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id != 0", $this->ID );

			$result = $this->db->get_results( $sql );

			if(empty($result)) {
				return false;
			} else {
				return $result;
			}

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

			if(!apply_filters( 'pre_membership_add_level', true, $tolevel_id, $this->ID )) {
				return false;
			}

			if(!$this->on_level($tolevel_id)) {
				// Add into membership tables
				$this->db->insert($this->membership_relationships, array('user_id' => $this->ID, 'level_id' => $tolevel_id, 'startdate' => current_time('mysql'), 'updateddate' => current_time('mysql')));

				do_action( 'membership_add_level', $tolevel_id, $this->ID );
			}

		}

		function drop_level($fromlevel_id) {

			if(!apply_filters( 'pre_membership_drop_level', true, $fromlevel_id, $this->ID )) {
				return false;
			}

			if($this->on_level($fromlevel_id)) {

				$sql = $this->db->prepare( "DELETE FROM {$this->membership_relationships} WHERE user_id = %d AND level_id = %d AND sub_id = 0", $this->ID, $fromlevel_id);
				$this->db->query( $sql );

				do_action( 'membership_drop_level', $fromlevel_id, $this->ID );

			}


		}

		function move_level($fromlevel_id, $tolevel_id) {

			if(!apply_filters( 'pre_membership_move_level', true, $fromlevel_id, $tolevel_id, $this->ID )) {
				return false;
			}

			if(!$this->on_level($tolevel_id) && $this->on_level($fromlevel_id)) {

				$this->db->update( $this->membership_relationships, array('level_id' => $tolevel_id, 'updateddate' => current_time('mysql')), array('level_id' => $fromlevel_id, 'user_id' => $this->ID, 'sub_id' => 0) );

				do_action( 'membership_move_level', $fromlevel_id, $tolevel_id, $this->ID );
			}

		}

		function add_subscription($tosub_id, $tolevel_id = false, $to_order = false) {

			if(!apply_filters( 'pre_membership_add_subscription', true, $tosub_id, $tolevel_id, $to_order, $this->ID )) {
				return false;
			}

			if(!$this->on_sub($tosub_id)) {

				// grab the level information for this position
				$subscription = new M_Subscription( $tosub_id );
				$level = $subscription->get_level_at($tolevel_id, $to_order);

				if($level) {
					$start = current_time('mysql');
					$expires = gmdate( 'Y-m-d H:i:s', strtotime('+' . $level->level_period . ' days', strtotime($start) ));
					$this->db->insert($this->membership_relationships, array('user_id' => $this->ID, 'level_id' => $tolevel_id, 'sub_id' => $tosub_id, 'startdate' => $start, 'updateddate' => $start, 'expirydate' => $expires, 'order_instance' => $level->level_order));

					do_action( 'membership_add_subscription', $tosub_id, $tolevel_id, $to_order, $this->ID);
				}

			}

		}

		function drop_subscription($fromsub_id) {

			if(!apply_filters( 'pre_membership_drop_subscription', true, $fromsub_id, $this->ID )) {
				return false;
			}

			if($this->on_sub($fromsub_id)) {
				$sql = $this->db->prepare( "DELETE FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $fromsub_id);
				$this->db->query( $sql );

				do_action( 'membership_drop_subscription', $fromsub_id, $this->ID );
			}

		}

		function move_subscription($fromsub_id, $tosub_id, $tolevel_id, $to_order) {

			if(!apply_filters( 'pre_membership_move_subscription', true, $fromsub_id, $tosub_id, $tolevel_id, $to_order, $this->ID )) {
				return false;
			}

			if(!$this->on_level($tolevel_id, true) && $this->on_sub($fromsub_id)) {

				// grab the level information for this position
				$subscription = new M_Subscription( $tosub_id );
				$level = $subscription->get_level_at($tolevel_id, $to_order);

				if($level) {
					$start = current_time('mysql');
					$expires = gmdate( 'Y-m-d H:i:s', strtotime('+' . $level->level_period . ' days', strtotime($start) ));

					$this->db->update( $this->membership_relationships, array('sub_id' => $tosub_id, 'level_id' => $tolevel_id, 'updateddate' => $start, 'expirydate' => $expires, 'order_instance' => $level->level_order), array( 'sub_id' => $fromsub_id, 'user_id' => $this->ID ) );

					do_action( 'membership_move_subscription', $fromsub_id, $tosub_id, $tolevel_id, $to_order, $this->ID );
				}

			}

		}

		// Member operations

		function toggle_activation() {

			if(!apply_filters( 'pre_membership_toggleactive_user', true, $this->ID )) {
				return false;
			}

			$active = get_usermeta( $this->ID, $this->db->prefix . 'membership_active');

			if(empty($active) || $active == 'yes') {
				update_usermeta($this->ID, $this->db->prefix . 'membership_active', 'no');

				do_action('membership_deactivate_user', $this->ID);

			} else {
				update_usermeta($this->ID, $this->db->prefix . 'membership_active', 'yes');

				do_action('membership_activate_user', $this->ID);

			}

			return true; // for now
		}

		function deactivate() {
			update_usermeta($this->ID, $this->db->prefix . 'membership_active', 'no');
		}

		// Levels functions

		function has_levels() {
			if(!empty($this->levels)) {
				return true;
			} else {
				$levels = $this->get_level_ids();

				if(!empty($levels)) {
					return true;
				} else {
					return false;
				}
			}
		}

		function has_level($level_id = false) {
			// Returns true if the user has a level to process

			if($level_id) {
				return isset($this->levels[$level_id]);
			} else {
				return !empty($this->levels);
			}
		}

		function has_rule($rulename) {
			// shortcut function
			return $this->has_level_rule($rulename);
		}

		function has_level_rule($rulename) {

			if(!empty($this->levels)) {

				foreach( $this->levels as $key => $level ) {
					if($level->has_rule($rulename)) {
						return true;
					}
				}

			}

			return false;
		}

		function pass_thru( $rulename, $args ) {

			if(!empty($this->levels)) {

				$functions = array_keys($args);
				$values = array_values($args);

				foreach( $this->levels as $key => $level ) {
					if($level->has_positive_rule($rulename)) {
						return $level->positive_pass_thru($rulename, $functions[0], $values[0]);
					} elseif($level->has_negative_rule($rulename)) {
						return $level->negative_pass_thru($rulename, $functions[0], $values[0]);
					} else {
						return false;
					}
				}


			}

		}

		function assign_level($level_id, $fullload) {
			// Used to force assign a level on a user - mainly for non logged in users
			$this->levels[$level_id] = new M_Level( $level_id, $fullload );

		}

		function load_levels($fullload = false) {

			$levels = $this->get_level_ids();

			if(!empty($levels)) {
				foreach( (array) $levels as $key => $lev ) {
					if(!isset( $this->levels[$lev->level_id] )) {
						$this->levels[$lev->level_id] = new M_Level( $lev->level_id, $fullload );
					}
				}
			}

		}


	}


}

?>