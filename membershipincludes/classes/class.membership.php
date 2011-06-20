<?php

if(!class_exists('M_Membership')) {

	class M_Membership extends WP_User {

		var $db;

		var $tables = array('membership_relationships', 'membership_levels', 'subscriptions', 'user_queue', 'member_payments');

		var $membership_relationships, $membership_levels, $subscriptions, $user_queue, $member_payments;

		var $subids;
		var $levids;

		var $levels = array();

		function M_Membership( $id, $name = '' ) {

			global $wpdb;

			if($id != 0) {
				parent::__construct( $id, $name );
			}

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			$this->transition_through_subscription();

		}

		function active_member() {

			$active = get_user_meta( $this->ID, membership_db_prefix($this->db, 'membership_active', false), true);

			if(empty($active) || $active == 'yes') {
				return apply_filters( 'membership_active_member', true, $this->ID);
			} else {
				return apply_filters( 'membership_active_member', false, $this->ID);
			}
		}

		function mark_for_expire( $sub_id ) {
			update_user_meta( $this->ID, '_membership_expire_next', $sub_id);

			do_action('membership_mark_for_expire', $sub_id, $this->ID);

		}

		function is_marked_for_expire($sub_id) {

			$markedsub_id = get_user_meta( $this->ID, '_membership_expire_next', true);

			if(!empty($markedsub_id) && $markedsub_id == $sub_id) {
				return apply_filters('membership_is_marked_for_expire', true, $this->ID);
			} else {
				return apply_filters('membership_is_marked_for_expire', false, $this->ID);
			}

		}

		function is_member() {

			$sql = $this->db->prepare( "SELECT count(*) FROM {$this->membership_relationships} WHERE user_id = %d", $this->ID );

			$res = $this->db->get_var($sql);

			if($res > 0) {
				return apply_filters('membership_is_member', true, $this->ID);
			} else {
				return apply_filters('membership_is_member', false, $this->ID);
			}

		}

		function has_subscription() {

			$sql = $this->db->prepare( "SELECT count(*) FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id != 0", $this->ID );

			$res = $this->db->get_var($sql);

			if($res > 0) {
				return apply_filters('membership_has_subscription', true, $this->ID);
			} else {
				return apply_filters('membership_has_subscription', false, $this->ID);
			}

		}

		function move_to($sub_id, $thislevel_id, $thislevel_order, $nextlevel) {

			if($this->on_sub($sub_id)) {

				if($nextlevel) {

					$this->move_subscription($sub_id, $sub_id, $nextlevel->level_id, $nextlevel->level_order);

				}

			}
		}

		function current_subscription() {

			$sql = $this->db->prepare( "SELECT sub_id FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id != 0", $this->ID );

			$res = $this->db->get_results($sql);

			if(!empty($res)) {
				return apply_filters('membership_current_subscription', $res, $this->ID);
			} else {
				return apply_filters('membership_current_subscription', false, $this->ID);
			}

		}

		function started_on_level_in_sub( $sub_id ) {

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $sub_id );
			$results = $this->db->get_results( $sql );

			if(!empty($results)) {
				foreach($results as $key => $r) {
					return apply_filter('membership_started_on_level_in_sub', $sub_id, mysql2date("U", $rel->updateddate));
				}
			}

		}

		function ends_on_level_in_sub( $sub_id ) {

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $sub_id );
			$results = $this->db->get_results( $sql );

			if(!empty($results)) {
				foreach($results as $key => $r) {
					return apply_filter('membership_ends_on_level_in_sub', $sub_id, mysql2date("U", $rel->expirydate));
				}
			}

		}

		function transition_through_subscription() {

			do_action('membership_start_transition', $this->ID);

			$relationships = $this->get_relationships();

			if($relationships) {
				foreach($relationships as $key => $rel) {
					// Add 6 hours to the expiry date to give a grace period?
					if( strtotime("+ 6 hours", mysql2date("U", $rel->expirydate)) <= time() ) {
						// expired, we need to remove the subscription
						if($this->is_marked_for_expire($rel->sub_id)) {
							$this->expire_subscription($rel->sub_id);
							delete_user_meta($this->ID, '_membership_expire_next');
							continue;
						}

						// Need to check if we are on a solo payment and have a valid payment or the next level is free.
						$onsolo = get_user_meta( $user_id, 'membership_signup_gateway_is_single', true );
						if(!empty($onsolo) && $onsolo == 'yes') {
							// We are on a solo gateway so need some extra checks
							// Grab the subscription
							$subscription = new M_Subscription($rel->sub_id);
							// Get the next level we will be moving onto
							$nextlevel = $subscription->get_next_level($rel->level_id, $rel->order_instance);

							if($nextlevel) {
								// We have a level to move to - let's check it
								if(empty($nextlevel->level_price) || $nextlevel->level_price == 0 ) {
									// The next level is a free one, so I guess we just move to it
									$this->move_to($rel->sub_id, $rel->level_id, $rel->order_instance, $nextlevel);
								} else {
									// The next level is a charged one so we need to make sure we have a payment
									if( $this->has_active_payment( $rel->sub_id, $nextlevel->level_id, $nextlevel->level_order ) ) {
										// We have a current payment for the level we are going to move to
										$this->move_to($rel->sub_id, $rel->level_id, $rel->order_instance, $nextlevel);
									} else {
										// We don't have a payment for this next level so we have to expire it.
										$this->expire_subscription($rel->sub_id);
									}
								}
							} else {
								// We're at the end so need to expire this subscription
								$this->expire_subscription($rel->sub_id);
							}

						} else {
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
						}

					} else {
						// not expired we can ignore this for now
						continue;
					}

				}
			}

			do_action('membership_end_transition', $this->ID);

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

			// Update users start and expiry meta
			delete_user_meta( $this->ID, 'start_current_' . $sub_id );
			delete_user_meta( $this->ID, 'expire_current_' . $sub_id );
			delete_user_meta( $this->ID, 'sent_msgs_' . $sub_id );

			$expiring = get_user_meta( $this->ID, '_membership_expire_next', true);
			if($expiring = $sub_id) {
				delete_user_meta( $this->ID, '_membership_expire_next' );
			}

			do_action( 'membership_expire_subscription', $sub_id, $this->ID);

		}

		function create_subscription($sub_id, $gateway = 'admin') {

			if(!$this->active_member()) {
				$this->toggle_activation();
			}

			$subscription = new M_Subscription( $sub_id );
			$levels = $subscription->get_levels();

			if(!empty($levels)) {

				foreach($levels as $key => $level) {
					if($level->level_order == 1) {

						$this->add_subscription($sub_id, $level->level_id, $level->level_order, $gateway);
						break;
					}
				}

				return true;

			} else {
				return false;
			}

		}

		function remove_active_payment( $sub_id, $level_order, $stamp ) {

			$subscription = new M_Subscription($sub_id);
			$level = $subscription->get_level_at_position($level_order);

			$sql = $this->db->prepare( "DELETE FROM {$this->member_payments} WHERE member_id = %d AND sub_id = %d AND level_id = %d AND level_order = %d AND paymentmade = %d", $this->ID, $sub_id, $level->id, $level_order, $stamp);

			return $this->db->query( $sql );

		}

		function record_active_payment( $sub_id, $level_order, $stamp ) {


			$rel = $this->get_relationship( $sub_id );

			if($rel) {
				$subscription = new M_Subscription($sub_id);
				$level = $subscription->get_level_at_position($level_order);

				if($level) {
					$payment = array( 	'member_id'		=> $this->ID,
										'sub_id'		=>	$sub_id,
										'level_id'		=>	$level->id,
										'level_order'	=>	$level_order,
										'paymentmade'	=>	gmdate( 'Y-m-d H:i:s', $stamp )
									);

					$expires = mysql2date("U", $rel->expirydate);
					switch($level->level_period_unit) {
						case 'd': 	$paymentexpires = strtotime('+' . $level->level_period . ' days', $expires);
									break;
						case 'w':	$paymentexpires = strtotime('+' . $level->level_period . ' weeks', $expires);
									break;
						case 'm':	$paymentexpires = strtotime('+' . $level->level_period . ' months', $expires);
									break;
						case 'y':	$paymentexpires = strtotime('+' . $level->level_period . ' years', $expires);
									break;
					}
					$payment['paymentexpires'] = gmdate( 'Y-m-d H:i:s',  $paymentexpires);

					$this->db->insert( $this->member_payments, $payment);

				}

			}

		}

		function has_active_payment( $sub_id, $nextlevel_id, $nextlevel_order ) {

			$sql = $this->db->prepare( "SELECT id FROM {$this->member_payments} WHERE member_id = %d AND sub_id = %d AND level_id = %d AND level_order = %d AND paymentexpires >= CURTIME() ORDER BY paymentexpires DESC LIMIT 0,1", $this->ID, $sub_id, $nextlevel_id, $nextlevel_order);

			$row = $this->db->get_var( $sql );

			if(!empty($row)) {
				return true;
			} else {
				return false;
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

		function update_relationship_gateway( $rel_id, $fromgateway, $togateway ) {

			if(!empty($rel_id)) {
				$sql = $this->db->prepare( "UPDATE {$this->membership_relationships} SET usinggateway = %s WHERE rel_id = %d AND usinggateway = %s", $togateway, $rel_id, $fromgateway );

				$this->db->query( $sql );
			}

		}

		function get_relationship( $sub_id ) {

			$sql = $this->db->prepare( "SELECT * FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $sub_id );

			$result = $this->db->get_row( $sql );

			if(empty($result)) {
				return false;
			} else {
				return $result;
			}

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
				return apply_filters('membership_on_level', false, $level_id, $this->ID);
			} else {
				return apply_filters('membership_on_level', true, $level_id, $this->ID);
			}

		}

		function on_sub($sub_id) {

			$sql = $this->db->prepare( "SELECT rel_id FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $sub_id );

			$result = $this->db->get_col( $sql );

			if(empty($result)) {
				return apply_filters('membership_on_sub', false, $sub_id, $this->ID);
			} else {
				return apply_filters('membership_on_sub', true, $sub_id, $this->ID);
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

		function add_subscription($tosub_id, $tolevel_id = false, $to_order = false, $gateway = 'admin') {

			if(!apply_filters( 'pre_membership_add_subscription', true, $tosub_id, $tolevel_id, $to_order, $this->ID )) {
				return false;
			}

			if(!$this->on_sub($tosub_id)) {

				// grab the level information for this position
				$subscription = new M_Subscription( $tosub_id );
				$level = $subscription->get_level_at($tolevel_id, $to_order);

				if($level) {
					$start = current_time('mysql');
					switch($level->level_period_unit) {
						case 'd': $period = 'days'; break;
						case 'w': $period = 'weeks'; break;
						case 'm': $period = 'months'; break;
						case 'y': $period = 'years'; break;
						default: $period = 'days'; break;
					}
					$expires = gmdate( 'Y-m-d H:i:s', strtotime('+' . $level->level_period . ' ' . $period, strtotime($start) ));
					$this->db->insert($this->membership_relationships, array('user_id' => $this->ID, 'level_id' => $tolevel_id, 'sub_id' => $tosub_id, 'startdate' => $start, 'updateddate' => $start, 'expirydate' => $expires, 'order_instance' => $level->level_order, 'usinggateway' => $gateway ));

					// Update users start and expiry meta
					update_user_meta( $this->ID, 'start_current_' . $tosub_id, strtotime($start) );
					update_user_meta( $this->ID, 'expire_current_' . $tosub_id, strtotime($expires) );
					update_user_meta( $this->ID, 'using_gateway_' . $tosub_id, $gateway );

					do_action( 'membership_add_subscription', $tosub_id, $tolevel_id, $to_order, $this->ID);
				}

			}

		}

		function get_level_for_sub( $sub_id ) {

			$sql = $this->db->prepare( "SELECT level_id FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $sub_id );

			return $this->db->get_var( $sql );

		}

		function drop_subscription($fromsub_id) {

			if(!apply_filters( 'pre_membership_drop_subscription', true, $fromsub_id, $this->ID )) {
				return false;
			}

			if($this->on_sub($fromsub_id)) {
				// Get the level for this subscription before removing it
				$fromlevel_id = $this->get_level_for_sub( $fromsub_id );

				$sql = $this->db->prepare( "DELETE FROM {$this->membership_relationships} WHERE user_id = %d AND sub_id = %d", $this->ID, $fromsub_id);
				$this->db->query( $sql );

				// Update users start and expiry meta
				delete_user_meta( $this->ID, 'start_current_' . $fromsub_id );
				delete_user_meta( $this->ID, 'expire_current_' . $fromsub_id );
				delete_user_meta( $this->ID, 'sent_msgs_' . $fromsub_id );
				delete_user_meta( $this->ID, 'using_gateway_' . $fromsub_id );

				$expiring = get_user_meta( $this->ID, '_membership_expire_next', true);
				if($expiring = $fromsub_id) {
					delete_user_meta( $this->ID, '_membership_expire_next' );
				}

				do_action( 'membership_drop_subscription', $fromsub_id, $fromlevel_id, $this->ID );
			}

		}

		function move_subscription($fromsub_id, $tosub_id, $tolevel_id, $to_order) {

			if(!apply_filters( 'pre_membership_move_subscription', true, $fromsub_id, $tosub_id, $tolevel_id, $to_order, $this->ID )) {
				return false;
			}

			if(!$this->on_level($tolevel_id, true) && $this->on_sub($fromsub_id)) {
				// Get the level for this subscription before removing it
				$fromlevel_id = $this->get_level_for_sub( $fromsub_id );

				// grab the level information for this position
				$subscription = new M_Subscription( $tosub_id );
				$level = $subscription->get_level_at($tolevel_id, $to_order);

				if($level) {
					$start = current_time('mysql');
					switch($level->level_period_unit) {
						case 'd': $period = 'days'; break;
						case 'w': $period = 'weeks'; break;
						case 'm': $period = 'months'; break;
						case 'y': $period = 'years'; break;
						default: $period = 'days'; break;
					}
					$expires = gmdate( 'Y-m-d H:i:s', strtotime('+' . $level->level_period . ' ' . $period, strtotime($start) ));

					// Update users start and expiry meta
					delete_user_meta( $this->ID, 'start_current_' . $fromsub_id );
					delete_user_meta( $this->ID, 'expire_current_' . $fromsub_id );
					delete_user_meta( $this->ID, 'sent_msgs_' . $fromsub_id );
					// get the gateway and then remove it from the usermeta
					$gateway = get_user_meta( $this->ID, 'using_gateway_' . $fromsub_id, true );
					delete_user_meta( $this->ID, 'using_gateway_' . $fromsub_id );

					update_user_meta( $this->ID, 'start_current_' . $tosub_id, strtotime($start) );
					update_user_meta( $this->ID, 'expire_current_' . $tosub_id, strtotime($expires) );
					update_user_meta( $this->ID, 'using_gateway_' . $tosub_id, $gateway );

					$this->db->update( $this->membership_relationships, array('sub_id' => $tosub_id, 'level_id' => $tolevel_id, 'updateddate' => $start, 'expirydate' => $expires, 'order_instance' => $level->level_order), array( 'sub_id' => $fromsub_id, 'user_id' => $this->ID ) );

					do_action( 'membership_move_subscription', $fromsub_id, $fromlevel_id, $tosub_id, $tolevel_id, $to_order, $this->ID );
				}

			}

		}

		// Member operations

		function toggle_activation() {

			if(!apply_filters( 'pre_membership_toggleactive_user', true, $this->ID )) {
				return false;
			}

			$active = get_user_meta( $this->ID, membership_db_prefix($this->db, 'membership_active', false), true );

			if(empty($active) || $active == 'yes') {
				update_user_meta($this->ID, membership_db_prefix($this->db, 'membership_active', false), 'no');

				do_action('membership_deactivate_user', $this->ID);

			} else {
				update_user_meta($this->ID, membership_db_prefix($this->db, 'membership_active', false), 'yes');

				do_action('membership_activate_user', $this->ID);

			}

			return true; // for now
		}

		function deactivate() {
			update_user_meta($this->ID, membership_db_prefix($this->db, 'membership_active', false), 'no');
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

		function assign_level($level_id, $fullload = true) {
			// Used to force assign a level on a user - mainly for non logged in users
			$this->levels[$level_id] = new M_Level( $level_id, $fullload, array('public', 'core') );

		}

		function assign_public_level($level_id, $fullload = true) {
			// Used to force assign a level on a user - mainly for non logged in users
			$this->levels[$level_id] = new M_Level( $level_id, $fullload, array('public', 'core') );

		}

		function assign_admin_level($level_id, $fullload) {
			// Used to force assign a level on a user - mainly for non logged in users
			$this->levels[$level_id] = new M_Level( $level_id, $fullload, array('admin','core') );
		}

		function assign_core_level($level_id, $fullload) {
			// Used to force assign a level on a user - mainly for non logged in users
			$this->levels[$level_id] = new M_Level( $level_id, $fullload, array('core') );
		}

		function load_levels($fullload = false) {

			$levels = $this->get_level_ids();

			if(!empty($levels)) {
				foreach( (array) $levels as $key => $lev ) {
					if(!isset( $this->levels[$lev->level_id] )) {
						$this->levels[$lev->level_id] = new M_Level( $lev->level_id, $fullload, array('public', 'core') );
					}
				}
			}

		}

		function load_public_levels($fullload = false) {

			$this->load_levels( $fullload );

		}

		function load_admin_levels($fullload = false) {

			$levels = $this->get_level_ids();

			if(!empty($levels)) {
				foreach( (array) $levels as $key => $lev ) {
					if(!isset( $this->levels[$lev->level_id] )) {
						$this->levels[$lev->level_id] = new M_Level( $lev->level_id, $fullload, array('admin','core') );
					}
				}
			}

		}

		function load_core_levels($fullload = false) {

			$levels = $this->get_level_ids();

			if(!empty($levels)) {
				foreach( (array) $levels as $key => $lev ) {
					if(!isset( $this->levels[$lev->level_id] )) {
						$this->levels[$lev->level_id] = new M_Level( $lev->level_id, $fullload, array('core') );
					}
				}
			}

		}


	}


}

?>