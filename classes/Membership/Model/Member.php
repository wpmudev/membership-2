<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * The member model class.
 *
 * @since 3.5
 *
 * @category Membership
 * @package Model
 * @subpackage Member
 */
class Membership_Model_Member extends WP_User {

	const CAP_MEMBERSHIP_ADMIN = 'membershipadmin';

	/**
	 * The database connection.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var wpdb
	 */
	private $_wpdb;

	/**
	 *
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var array
	 */
	private $subids;

	/**
	 *
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var array
	 */
	private $levids;

	/**
	 *
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @var array
	 */
	private $levels = array();

	public function __construct( $id = 0, $name = '', $blog_id = '' ) {
		global $wpdb;

		if ( $id != 0 ) {
			parent::__construct( $id, $name, $blog_id );
			membership_debug_log( sprintf( __( 'MEMBER: Loaded member %d', 'membership' ), $id ) );
		}

		$this->_wpdb = $wpdb;

		if ( $id != 0 && ( $this->has_cap('membershipadmin') || $this->has_cap('manage_options') || is_super_admin($this->ID) ) ) {
			// bail - user is admin or super admin
			return;
		}

		$this->transition_through_subscription();
	}

	public function active_member() {
		if ( $this->ID == 0 ) {
			// bail, stranger level is never considered an active member
			return false;
		}
		
		$active = get_user_meta( $this->ID, membership_db_prefix( $this->_wpdb, 'membership_active', false ), true );
		return apply_filters( 'membership_active_member', empty( $active ) || $active == 'yes', $this->ID );
	}

	public function mark_for_expire( $sub_id ) {
		update_user_meta( $this->ID, '_membership_expire_next', $sub_id );
		do_action( 'membership_mark_for_expire', $sub_id, $this->ID );
	}

	public function is_marked_for_expire($sub_id) {

		$markedsub_id = get_user_meta( $this->ID, '_membership_expire_next', true);

		if(!empty($markedsub_id) && $markedsub_id == $sub_id) {
			return apply_filters('membership_is_marked_for_expire', true, $this->ID);
		} else {
			return apply_filters('membership_is_marked_for_expire', false, $this->ID);
		}

	}

	public function is_member() {
		if ( $this->has_cap('membershipadmin') || $this->has_cap('manage_options') || is_super_admin($this->ID) ) {
			// bail, user is admin or super admin
			return true;
		}
		
		if ( $this->ID == 0 ) {
			// bail, stranger level is never considered a member
		}

		$res = $this->_wpdb->get_var( sprintf(
			'SELECT count(*) FROM %s WHERE user_id = %d',
			MEMBERSHIP_TABLE_RELATIONS,
			$this->ID
		) );

		return apply_filters( 'membership_is_member', $res > 0, $this->ID );
	}

	public function has_subscription() {
		if ( $this->has_cap('membershipadmin') || $this->has_cap('manage_options') || is_super_admin($this->ID) ) {
			// bail, user is admin or super admin
			return true;
		}
		
		if ( $this->ID == 0 ) {
			// bail, stranger level never has subscriptions
			return false;
		}

		$res = $this->_wpdb->get_var( sprintf(
			"SELECT count(*) FROM %s WHERE user_id = %d AND sub_id != 0",
			MEMBERSHIP_TABLE_RELATIONS,
			$this->ID
		) );

		return apply_filters( 'membership_has_subscription', $res > 0, $this->ID );
	}

	public function move_to( $sub_id, $thislevel_id, $thislevel_order, $nextlevel ) {
		if ( $this->on_sub( $sub_id ) ) {
			if ( $nextlevel ) {
				$this->move_subscription( $sub_id, $sub_id, $nextlevel->level_id, $nextlevel->level_order );
			}
		}
	}

	private function transition_through_subscription() {

		do_action('membership_start_transition', $this->ID);

		$relationships = $this->get_relationships();

		if( $relationships !== false ) {

			membership_debug_log( __('MEMBER: Have relationships so starting transition check' , 'membership') );

			foreach($relationships as $rel) {
				$sub_type = $this->_wpdb->get_var( sprintf( 'SELECT sub_type FROM %s WHERE sub_id = %d AND level_id = %d', membership_db_prefix( $this->_wpdb, 'subscriptions_levels' ), $rel->sub_id, $rel->level_id ) );
				if ( $sub_type == 'indefinite' ) {
					continue;
				}

				membership_debug_log( __('MEMBER: Processing transition - ' , 'membership') . print_r($rel, true) );

				// Add 6 hours to the expiry date to give a grace period?
				if( strtotime(apply_filters('membership_gateway_exp_window',"+ 6 hours"), mysql2date("U", $rel->expirydate)) <= time() ) {

					membership_debug_log( __('MEMBER: Transition has passed expiration timestamp - ' , 'membership') . print_r($rel, true) );

					// expired, we need to remove the subscription
					if($this->is_marked_for_expire($rel->sub_id)) {
						$this->expire_subscription($rel->sub_id);
						delete_user_meta($this->ID, '_membership_expire_next');

						membership_debug_log( sprintf(__('MEMBER: Membership has been marked for expiration - %d' , 'membership'), $rel->sub_id) );

						continue;
					}

					// Need to check if we are on a solo payment and have a valid payment or the next level is free.
					$onsolo = get_user_meta( $this->ID, 'membership_signup_gateway_is_single', true );
					if(!empty($onsolo) && $onsolo == 'yes') {
						// We are on a solo gateway so need some extra checks
						// Grab the subscription
						$subscription = Membership_Plugin::factory()->get_subscription($rel->sub_id);
						// Get the next level we will be moving onto
						$nextlevel = $subscription->get_next_level($rel->level_id, $rel->order_instance);

						membership_debug_log( sprintf(__('MEMBER: Membership level to move to for subscription - %d - ' , 'membership'), $rel->sub_id) . print_r($nextlevel, true) );

						if($nextlevel) {
							// We have a level to move to - let's check it
							if(empty($nextlevel->level_price) || $nextlevel->level_price == 0 ) {
								// The next level is a free one, so I guess we just move to it
								$this->move_to($rel->sub_id, $rel->level_id, $rel->order_instance, $nextlevel);

								membership_debug_log( sprintf(__('MEMBER: Moved to new level for - %d' , 'membership'), $rel->sub_id) );
							} else {
								// The next level is a charged one so we need to make sure we have a payment
								if( $this->has_active_payment( $rel->sub_id, $nextlevel->level_id, $nextlevel->level_order ) ) {
									// We have a current payment for the level we are going to move to
									$this->move_to($rel->sub_id, $rel->level_id, $rel->order_instance, $nextlevel);

									membership_debug_log( sprintf(__('MEMBER: Moved to new level for - %d' , 'membership'), $rel->sub_id) );
								} else {
									// We don't have a payment for this next level so we have to expire it.
									$this->expire_subscription($rel->sub_id);

									membership_debug_log( sprintf(__('MEMBER: Expired subscription as no next level for - %d' , 'membership'), $rel->sub_id) );
								}
							}
						} else {
							// We're at the end so need to expire this subscription
							$this->expire_subscription($rel->sub_id);

							membership_debug_log( sprintf(__('MEMBER: Expired subscription as no next level for - %d' , 'membership'), $rel->sub_id) );
						}

					} else {
						$subscription = Membership_Plugin::factory()->get_subscription($rel->sub_id);
						$nextlevel = $subscription->get_next_level($rel->level_id, $rel->order_instance);

						membership_debug_log( sprintf(__('MEMBER: Membership level to move to for subscription - %d - ' , 'membership'), $rel->sub_id) . print_r($nextlevel, true) );

						if ( $nextlevel ) {
							$this->move_to( $rel->sub_id, $rel->level_id, $rel->order_instance, $nextlevel );
							membership_debug_log( sprintf( __( 'MEMBER: Moved to new level for - %d', 'membership' ), $rel->sub_id ) );
						} else {
							// there isn't a next level so expire this subscription
							$this->expire_subscription( $rel->sub_id );

							membership_debug_log( sprintf( __( 'MEMBER: Expired subscription as no next level for - %d', 'membership' ), $rel->sub_id ) );
						}
					}

				} else {
					// not expired we can ignore this for now
					membership_debug_log( __('MEMBER: Transition check not expired - ' , 'membership') . print_r($rel, true) );

					continue;
				}

			}
		}

		do_action('membership_end_transition', $this->ID);

	}

	public function expire_subscription( $sub_id = false ) {
		if ( !apply_filters( 'pre_membership_expire_subscription', true, $sub_id, $this->ID ) ) {
			return false;
		}

		if ( !$sub_id ) {
			// expire all of the current subscriptions
			$this->_wpdb->delete( MEMBERSHIP_TABLE_RELATIONS, array( 'user_id' => $this->ID ), array( '%d' ) );
		} else {
			// expire just the passed subscription
			$this->_wpdb->delete( MEMBERSHIP_TABLE_RELATIONS, array( 'user_id' => $this->ID, 'sub_id' => $sub_id ), array( '%d', '%d' ) );
		}

		// Update users start and expiry meta
		delete_user_meta( $this->ID, 'start_current_' . $sub_id );
		delete_user_meta( $this->ID, 'expire_current_' . $sub_id );
		delete_user_meta( $this->ID, 'sent_msgs_' . $sub_id );

		$expiring = get_user_meta( $this->ID, '_membership_expire_next', true );
		if ( $expiring == $sub_id ) {
			delete_user_meta( $this->ID, '_membership_expire_next' );
		}

		do_action( 'membership_expire_subscription', $sub_id, $this->ID );
	}

	public function create_subscription( $sub_id, $gateway = 'admin' ) {

		global $blog_id;

		if ( !$this->active_member() ) {
			$this->toggle_activation();
		}

		$subscription = Membership_Plugin::factory()->get_subscription( $sub_id );
		$levels = $subscription->get_levels();
		if ( !empty( $levels ) ) {
			foreach ( $levels as $level ) {
				if ( $level->level_order == 1 ) {
					$this->add_subscription( $sub_id, $level->level_id, $level->level_order, $gateway );

					// Check if a coupon transient already exists
					if ( defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && MEMBERSHIP_GLOBAL_TABLES === true ) {
						if ( function_exists( 'get_site_transient' ) ) {
							$trying = get_site_transient( 'm_coupon_' . $blog_id . '_' . $this->ID . '_' . $sub_id );
						} else {
							$trying = get_transient( 'm_coupon_' . $blog_id . '_' . $this->ID . '_' . $sub_id );
						}
					} else {
						$trying = get_transient( 'm_coupon_' . $blog_id . '_' . $this->ID . '_' . $sub_id );
					}

					// If there is a coupon transient do our coupon count magic
					if ( $trying != false && is_array( $trying ) ) {
						if ( !empty( $trying['coupon_id'] ) ) {
							$coupon = new M_Coupon( $trying['coupon_id'] );
							// Add one to the coupon count
							$coupon->increment_coupon_used();
							// Store the coupon details in the usermeta
							update_user_meta( $this->ID, 'm_coupon_' . $sub_id, $trying );
						}

						if ( defined( 'MEMBERSHIP_GLOBAL_TABLES' ) && MEMBERSHIP_GLOBAL_TABLES === true ) {
							if ( function_exists( 'delete_site_transient' ) ) {
								delete_site_transient( 'm_coupon_' . $blog_id . '_' . $this->ID . '_' . $sub_id );
							} else {
								delete_transient( 'm_coupon_' . $blog_id . '_' . $this->ID . '_' . $sub_id );
							}
						} else {
							delete_transient( 'm_coupon_' . $blog_id . '_' . $this->ID . '_' . $sub_id );
						}
					}

					break;
				}
			}

			return true;
		}

		return false;
	}

	public function remove_active_payment( $sub_id, $level_order, $stamp ) {
		$subscription = Membership_Plugin::factory()->get_subscription( $sub_id );
		$level = $subscription->get_level_at_position( $level_order );

		return $this->_wpdb->query( sprintf(
			"DELETE FROM %s WHERE member_id = %d AND sub_id = %d AND level_id = %d AND level_order = %d AND paymentmade = %d",
			MEMBERSHIP_TABLE_MEMBER_PAYMENTS,
			$this->ID,
			$sub_id,
			$level->id,
			$level_order,
			$stamp
		) );
	}

	public function record_active_payment( $sub_id, $level_order, $stamp ) {
		$rel = $this->_wpdb->get_row( sprintf( "SELECT * FROM %s WHERE user_id = %d AND sub_id = %d", MEMBERSHIP_TABLE_RELATIONS, $this->ID, $sub_id ) );
		if ( !$rel ) {
			return;
		}

		$subscription = Membership_Plugin::factory()->get_subscription( $sub_id );
		$level = $subscription->get_level_at_position( $level_order );
		if ( !$level ) {
			return;
		}

		$payment = array(
			'member_id'   => $this->ID,
			'sub_id'      => $sub_id,
			'level_id'    => $level->id,
			'level_order' => $level_order,
			'paymentmade' => gmdate( 'Y-m-d H:i:s', $stamp )
		);

		$expires = mysql2date( "U", $rel->expirydate );
		switch ( $level->level_period_unit ) {
			case 'd': $paymentexpires = strtotime( '+' . $level->level_period . ' days', $expires );
				break;
			case 'w': $paymentexpires = strtotime( '+' . $level->level_period . ' weeks', $expires );
				break;
			case 'm': $paymentexpires = strtotime( '+' . $level->level_period . ' months', $expires );
				break;
			case 'y': $paymentexpires = strtotime( '+' . $level->level_period . ' years', $expires );
				break;
		}
		$payment['paymentexpires'] = gmdate( 'Y-m-d H:i:s', $paymentexpires );

		$this->_wpdb->insert( MEMBERSHIP_TABLE_MEMBER_PAYMENTS, $payment );
	}

	public function has_active_payment( $sub_id, $nextlevel_id, $nextlevel_order ) {
		$id = $this->_wpdb->get_var( sprintf(
			"SELECT id FROM %s WHERE member_id = %d AND sub_id = %d AND level_id = %d AND level_order = %d AND paymentexpires >= CURTIME() ORDER BY paymentexpires DESC LIMIT 1",
			MEMBERSHIP_TABLE_MEMBER_PAYMENTS,
			$this->ID,
			$sub_id,
			$nextlevel_id,
			$nextlevel_order
		) );

		return !empty( $id );
	}

	public function get_subscription_ids() {
		global $M_options;
		
		if ( $this->ID == 0 ) {
			if ( isset($M_options['freeusersubscription']) ) {
				$this->subids = array(
					$M_options['freeusersubscription'],
				);
			}
		} elseif ( empty( $this->subids ) ) {
			$this->subids = $this->_wpdb->get_col( sprintf(
				'SELECT sub_id FROM %s WHERE user_id = %d AND sub_id > 0',
				MEMBERSHIP_TABLE_RELATIONS,
				$this->ID
			) );
		}

		return $this->subids;
	}

	public function get_level_ids() {	
		global $M_options;
		
		if ( $this->ID == 0 ) {
			if ( isset($M_options['strangerlevel']) ) {
				$this->levids = array(
					(object) array(
						'level_id' => $M_options['strangerlevel'],
						'sub_id' => 0,
					),
				);
			}
		} elseif ( empty( $this->levids ) ) {
			$this->levids = (array)$this->_wpdb->get_results( sprintf(
				'SELECT level_id, sub_id FROM %s WHERE user_id = %d AND level_id > 0',
				MEMBERSHIP_TABLE_RELATIONS,
				$this->ID
			) );
		}

		return $this->levids;
	}

	public function update_relationship_gateway( $rel_id, $fromgateway, $togateway ) {
		$this->_wpdb->update( MEMBERSHIP_TABLE_RELATIONS, array(
			'usinggateway' => $togateway,
		), array(
			'rel_id'       => $rel_id,
			'usinggateway' => $fromgateway,
		), array( '%s' ), array( '%d', '%s' ) );
	}

	public function get_relationships() {
		if ( $this->ID == 0 ) {
			return false;
		}
		
		$result = $this->_wpdb->get_results( sprintf(
			'SELECT * FROM %s WHERE user_id = %d AND sub_id != 0',
			MEMBERSHIP_TABLE_RELATIONS,
			$this->ID
		) );

		return !empty( $result ) ? $result : false;
	}

	public function on_level( $level_id, $include_subs = false, $check_order = false ) {
		if ( $this->ID == 0 ) {
			return false;
		}
		
		$sql = sprintf(
			"SELECT rel_id FROM %s WHERE user_id = %d AND level_id = %d",
			MEMBERSHIP_TABLE_RELATIONS,
			$this->ID,
			$level_id
		);

		if ( $include_subs === false ) {
			$sql .= $this->_wpdb->prepare( " AND sub_id = %d", 0 );
		}

		if ( $check_order !== false ) {
			$sql .= $this->_wpdb->prepare( " AND order_instance = %d", $check_order );
		}

		$result = $this->_wpdb->get_col( $sql );

		return apply_filters( 'membership_on_level', !empty( $result ), $level_id, $this->ID );
	}

	public function on_sub( $sub_id ) {
		if ( $this->ID == 0 ) {
			return false;
		}
		
		$result = $this->_wpdb->get_col( sprintf(
			'SELECT rel_id FROM %s WHERE user_id = %d AND sub_id = %d',
			MEMBERSHIP_TABLE_RELATIONS,
			$this->ID,
			$sub_id
		) );

		return apply_filters('membership_on_sub', !empty($result), $sub_id, $this->ID);
	}

	public function add_level( $tolevel_id ) {
		if ( !apply_filters( 'pre_membership_add_level', true, $tolevel_id, $this->ID ) ) {
			return false;
		}

		if ( !$this->on_level( $tolevel_id ) ) {
			// Add into membership tables
			$this->_wpdb->insert( MEMBERSHIP_TABLE_RELATIONS, array(
				'user_id'     => $this->ID,
				'level_id'    => $tolevel_id,
				'startdate'   => current_time('mysql'),
				'updateddate' => current_time('mysql'),
			) );

			do_action( 'membership_add_level', $tolevel_id, $this->ID );
		}
	}

	public function drop_level( $fromlevel_id ) {
		if ( !apply_filters( 'pre_membership_drop_level', true, $fromlevel_id, $this->ID ) ) {
			return false;
		}

		if ( $this->on_level( $fromlevel_id ) ) {
			$this->_wpdb->delete( MEMBERSHIP_TABLE_RELATIONS, array(
				'user_id'  => $this->ID,
				'level_id' => $fromlevel_id,
				'sub_id'   => 0,
			), array( '%d', '%d', '%d' ) );

			do_action( 'membership_drop_level', $fromlevel_id, $this->ID );
		}
	}

	public function move_level( $fromlevel_id, $tolevel_id, $sub_id = 0 ) {
		if ( !apply_filters( 'pre_membership_move_level', true, $fromlevel_id, $tolevel_id, $this->ID ) ) {
			return false;
		}

		if ( !$this->on_level( $tolevel_id ) && $this->on_level( $fromlevel_id ) ) {
			$this->_wpdb->update( MEMBERSHIP_TABLE_RELATIONS, array(
				'level_id'    => $tolevel_id,
				'updateddate' => current_time( 'mysql' )
			), array(
				'level_id' => $fromlevel_id,
				'user_id'  => $this->ID,
				'sub_id'   => $sub_id,
			), array( '%d', '%s' ), array( '%d', '%d', '%d' ) );

			do_action( 'membership_move_level', $fromlevel_id, $tolevel_id, $this->ID );
		}
	}

	public function add_subscription( $tosub_id, $tolevel_id = false, $to_order = false, $gateway = 'admin' ) {
		if ( !apply_filters( 'pre_membership_add_subscription', true, $tosub_id, $tolevel_id, $to_order, $this->ID ) || $this->on_sub( $tosub_id ) ) {
			return false;
		}

		// grab the level information for this position
		$subscription = Membership_Plugin::factory()->get_subscription( $tosub_id );
		$level = $subscription->get_level_at( $tolevel_id, $to_order );

		if ( $level ) {
			$now = current_time( 'mysql' );
			$start = strtotime( $now );
			switch ( $level->level_period_unit ) {
				case 'd': $period = 'days';
					break;
				case 'w': $period = 'weeks';
					break;
				case 'm': $period = 'months';
					break;
				case 'y': $period = 'years';
					break;
				default: $period = 'days';
					break;
			}
			//subscription end date
			$expires_sub = $this->get_subscription_expire_date( $subscription, $tolevel_id );
			//level end date
			$expires = strtotime( '+' . $level->level_period . ' ' . $period, $start );
			$expires = gmdate( 'Y-m-d H:i:s', $expires ? $expires : strtotime( '+365 days', $start )  );

			$this->_wpdb->insert( MEMBERSHIP_TABLE_RELATIONS, array(
				'user_id' => $this->ID,
				'level_id' => $tolevel_id,
				'sub_id' => $tosub_id,
				'startdate' => $now,
				'updateddate' => $now,
				'expirydate' => $expires,
				'order_instance' => $level->level_order,
				'usinggateway' => $gateway
			) );

			// Update users start and expiry meta
			update_user_meta( $this->ID, 'start_current_' . $tosub_id, $start );
			update_user_meta( $this->ID, 'expire_current_' . $tosub_id, $expires_sub );
			update_user_meta( $this->ID, 'using_gateway_' . $tosub_id, $gateway );

			do_action( 'membership_add_subscription', $tosub_id, $tolevel_id, $to_order, $this->ID );
		}

		return true;
	}

	public function get_level_for_sub( $sub_id ) {
		return $this->_wpdb->get_var( sprintf(
			"SELECT level_id FROM %s WHERE user_id = %d AND sub_id = %d",
			MEMBERSHIP_TABLE_RELATIONS,
			$this->ID,
			$sub_id
		) );
	}

	public function drop_subscription( $fromsub_id ) {
		if ( !apply_filters( 'pre_membership_drop_subscription', true, $fromsub_id, $this->ID ) || !$this->on_sub( $fromsub_id ) ) {
			return false;
		}

		// Get the level for this subscription before removing it
		$fromlevel_id = $this->get_level_for_sub( $fromsub_id );

		$this->_wpdb->delete( MEMBERSHIP_TABLE_RELATIONS, array(
			'user_id' => $this->ID,
			'sub_id'  => $fromsub_id,
		), array( '%d', '%d' ) );

		// Update users start and expiry meta
		delete_user_meta( $this->ID, 'start_current_' . $fromsub_id );
		delete_user_meta( $this->ID, 'expire_current_' . $fromsub_id );
		delete_user_meta( $this->ID, 'sent_msgs_' . $fromsub_id );
		delete_user_meta( $this->ID, 'using_gateway_' . $fromsub_id );

		$expiring = get_user_meta( $this->ID, '_membership_expire_next', true );
		if ( $expiring == $fromsub_id ) {
			delete_user_meta( $this->ID, '_membership_expire_next' );
		}

		do_action( 'membership_drop_subscription', $fromsub_id, $fromlevel_id, $this->ID );

		return true;
	}

	public function move_subscription( $fromsub_id, $tosub_id, $tolevel_id, $to_order ) {
		if ( !apply_filters( 'pre_membership_move_subscription', true, $fromsub_id, $tosub_id, $tolevel_id, $to_order, $this->ID ) ) {
			return false;
		}

		membership_debug_log( sprintf( __( 'MEMBER: Moving subscription from %d to %d', 'membership' ), $fromsub_id, $tosub_id ) );

		$factory = Membership_Plugin::factory();

		// Check if existing level matches new one but it is a serial or indefinite level
		$subscription = $factory->get_subscription( $tosub_id );
		$nextlevel = $subscription->get_next_level( $tolevel_id, $to_order );

		if ( (!$this->on_level( $tolevel_id, true, $to_order ) ) || ( $this->on_level( $tolevel_id, true, $to_order ) && ( $nextlevel->sub_type == 'serial' || $nextlevel->sub_type == 'indefinite' ) ) && $this->on_sub( $fromsub_id ) ) {

			membership_debug_log( sprintf( __( 'MEMBER: New level to move to %d on order %d', 'membership' ), $tolevel_id, $to_order ) );

			// Get the level for this subscription before removing it
			$fromlevel_id = $this->get_level_for_sub( $fromsub_id );

			// grab the level information for this position
			$subscription = $factory->get_subscription( $tosub_id );
			$level = $subscription->get_level_at( $tolevel_id, $to_order );

			if ( $level ) {
				$period = 'days';
				$now = current_time( 'mysql' );
				$start = strtotime( $now );
				switch ( $level->level_period_unit ) {
					case 'd': $period = 'days';   break;
					case 'w': $period = 'weeks';  break;
					case 'm': $period = 'months'; break;
					case 'y': $period = 'years';  break;
				}
				//subscription start and end date
				$start_sub = ( $tosub_id == $fromsub_id ) ? get_user_meta( $this->ID, 'start_current_' . $fromsub_id, true ) : $start;
				$expires_sub = $this->get_subscription_expire_date( $subscription, $tolevel_id, $fromsub_id, $fromlevel_id );
				//level end date
				$expires = gmdate( 'Y-m-d H:i:s', strtotime( '+' . $level->level_period . ' ' . $period, $start ) );

				// Update users start and expiry meta
				delete_user_meta( $this->ID, 'start_current_' . $fromsub_id );
				delete_user_meta( $this->ID, 'expire_current_' . $fromsub_id );
				delete_user_meta( $this->ID, 'sent_msgs_' . $fromsub_id );

				// get the gateway and then remove it from the usermeta
				$gateway = get_user_meta( $this->ID, 'using_gateway_' . $fromsub_id, true );
				delete_user_meta( $this->ID, 'using_gateway_' . $fromsub_id );

				update_user_meta( $this->ID, 'start_current_' . $tosub_id, $start_sub );
				update_user_meta( $this->ID, 'expire_current_' . $tosub_id, $expires_sub );
				update_user_meta( $this->ID, 'using_gateway_' . $tosub_id, $gateway );

				$this->_wpdb->update( MEMBERSHIP_TABLE_RELATIONS, array(
					'sub_id'         => $tosub_id,
					'level_id'       => $tolevel_id,
					'updateddate'    => $now,
					'expirydate'     => $expires,
					'order_instance' => $level->level_order
				), array(
					'sub_id'  => $fromsub_id,
					'user_id' => $this->ID
				) );

				membership_debug_log( sprintf( __( 'MEMBER: Completed move to %d on order %d on sub %d', 'membership' ), $tolevel_id, $to_order, $tosub_id ) );

				do_action( 'membership_move_subscription', $fromsub_id, $fromlevel_id, $tosub_id, $tolevel_id, $to_order, $this->ID );
			}
		} else {
			membership_debug_log( sprintf( __( 'MEMBER: Already on level %d on order %d', 'membership' ), $tolevel_id, $to_order ) );
		}
	}

	private function get_subscription_expire_date( $subscription, $tolevel_id = null, $fromsub_id = null, $fromlevel_id = null, $start = null ) {
		$now = strtotime( current_time( 'mysql' ) );
		$expires = ! empty( $start ) ? $start : $now ;

		//get ordered subscription levels
		$levels = $subscription->get_levels();

		$tolevel_order = 1;
		$fromlevel_order = 1;
		foreach( $levels as $level ) {
			if( ! empty( $tolevel_id ) && $level->level_id == $tolevel_id ) {
				$tolevel_order = $level->level_order;
				continue; 
			}
			if( ! empty( $fromsub_id ) && ! empty( $fromlevel_id ) && $fromsub_id == $subscription->id && $level->level_id == $fromlevel_id ) {
				$fromlevel_order = $level->level_order;
				continue;
			}
		}

		//sum every level period to get the subscription end date.		
		foreach( $levels as $level ) {
			if ( $level->level_order < $tolevel_order ) {
				//if entering in the middle of a subscription, jumping beginning levels, those not count
				continue;
			}
			if ( ($fromlevel_order < $tolevel_order) && $level->level_order == $fromlevel_order ) {
				//reset to current date if already a subscription member and moving to another level
				$expires = $now;
			}
			switch ( $level->level_period_unit ) {
				case 'd': $period = 'days';   break;
				case 'w': $period = 'weeks';  break;
				case 'm': $period = 'months'; break;
				case 'y': $period = 'years';  break;
			}
			$expires = strtotime( '+' . $level->level_period . ' ' . $period, $expires ) ;
			
			if( $level->sub_type == 'indefinite') {
				//never expires
				$expires = strtotime( '+10 years', $expires ); 

				break;
			}
			elseif( $level->sub_type == 'serial') {
				//sum only once and break - will not go to next level if exists
				break;			
			}
		}
		return $expires;
	}
	// Member operations

	public function toggle_activation() {

		if(!apply_filters( 'pre_membership_toggleactive_user', true, $this->ID )) {
			return false;
		}

		$active = get_user_meta( $this->ID, membership_db_prefix($this->_wpdb, 'membership_active', false), true );

		if(empty($active) || $active == 'yes') {
			update_user_meta($this->ID, membership_db_prefix($this->_wpdb, 'membership_active', false), 'no');

			do_action('membership_deactivate_user', $this->ID);

		} else {
			update_user_meta($this->ID, membership_db_prefix($this->_wpdb, 'membership_active', false), 'yes');

			do_action('membership_activate_user', $this->ID);

		}

		return true; // for now
	}

	public function deactivate() {
		update_user_meta($this->ID, membership_db_prefix($this->_wpdb, 'membership_active', false), 'no');
	}

	// Levels functions

	public function has_levels() {
		return !empty( $this->levels ) || count( $this->get_level_ids() ) > 0;
	}

	public function has_level($level_id = false) {
		if ( empty($this->levels) && ( $this->has_cap('membershipadmin') || $this->has_cap('manage_options') || is_super_admin($this->ID) ) ) {
			// User is admin or super admin and isn't using "view as" feature so can view everything
			return true;
		}

		if( $level_id && isset($this->levels[$level_id]) ) {
			return true;
		} else {
			return false;
		}
	}

	public function has_rule($rulename) {
		// shortcut function
		return $this->has_level_rule($rulename);
	}

	public function has_level_rule($rulename) {

		if(!empty($this->levels)) {

			foreach( $this->levels as $key => $level ) {
				if($level->has_rule($rulename)) {
					return true;
				}
			}

		}

		return false;
	}

	public function pass_thru( $rulename, $args ) {

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

	public function assign_level( $level_id, $fullload = true ) {
		// Used to force assign a level on a user - mainly for non logged in users
		$this->levels[$level_id] = Membership_Plugin::factory()->get_level( $level_id, $fullload, array( 'public', 'core', 'admin' ) );
	}

	public function assign_public_level($level_id, $fullload = true) {
		// Used to force assign a level on a user - mainly for non logged in users
		$this->levels[$level_id] = Membership_Plugin::factory()->get_level( $level_id, $fullload, array('public', 'core') );

	}

	public function assign_admin_level($level_id, $fullload) {
		// Used to force assign a level on a user - mainly for non logged in users
		$this->levels[$level_id] = Membership_Plugin::factory()->get_level( $level_id, $fullload, array('admin','core') );
	}

	public function assign_core_level($level_id, $fullload) {
		// Used to force assign a level on a user - mainly for non logged in users
		$this->levels[$level_id] = Membership_Plugin::factory()->get_level( $level_id, $fullload, array('core') );
	}

	public function load_levels( $fullload = false ) {
		foreach ( (array)$this->get_level_ids() as $lev ) {
			if ( !isset( $this->levels[$lev->level_id] ) ) {
				$this->levels[$lev->level_id] = Membership_Plugin::factory()->get_level( $lev->level_id, $fullload, array( 'public', 'core', 'admin' ) );
			}
		}
	}

	public function load_public_levels($fullload = false) {

		$this->load_levels( $fullload );

	}

	public function load_admin_levels($fullload = false) {

		$levels = $this->get_level_ids();

		if(!empty($levels)) {
			foreach( (array) $levels as $key => $lev ) {
				if(!isset( $this->levels[$lev->level_id] )) {
					$this->levels[$lev->level_id] = Membership_Plugin::factory()->get_level( $lev->level_id, $fullload, array('admin','core') );
				}
			}
		}

	}

	public function load_core_levels($fullload = false) {

		$levels = $this->get_level_ids();

		if(!empty($levels)) {
			foreach( (array) $levels as $key => $lev ) {
				if(!isset( $this->levels[$lev->level_id] )) {
					$this->levels[$lev->level_id] = Membership_Plugin::factory()->get_level( $lev->level_id, $fullload, array('core') );
				}
			}
		}

	}

	public function can_view_current_page() {
		if ( empty($this->levels) && ( $this->has_cap('membershipadmin') || $this->has_cap('manage_options') || is_super_admin($this->ID) ) ) {
			// bail, user is admin/super admin and is not using "view as" feature so can view everything
			return true;
		}

		$can_view = false;
		foreach ( $this->levels as $level ) {
			$can_view |= $level->can_view_current_page();
		}

		return apply_filters( 'membership_member_can_view_current_page', $can_view, $this );
	}

}

/**
 * Deprecated version of the member class. Do not use this class anymore.
 *
 * @deprecated since version 3.5
 *
 * @category Membership
 * @package Model
 * @subpackage Member
 */
class M_Membership extends Membership_Model_Member {}