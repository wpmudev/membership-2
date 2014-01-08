<?php

if ( !class_exists( 'M_Communication' ) ) {

	class M_Communication {

		/** @var wpdb */
		var $db;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $comm;

		var $id;

		var $commconstants = array(
			'%blogname%'         => '',
			'%blogurl%'          => '',
			'%username%'         => '',
			'%usernicename%'     => '',
			'%userdisplayname%'  => '',
			'%userfirstname%'    => '',
			'%userlastname%'     => '',
			'%networkname%'      => '',
			'%networkurl%'       => '',
			'%subscriptionname%' => '',
			'%levelname%'        => '',
			'%accounturl%'       => ''
		);

		function __construct( $id = false ) {
			global $wpdb;

			$this->db = $wpdb;
			$this->id = absint( $id );
		}

		function get_communication() {
			$commsql = $this->db->prepare( "SELECT * FROM " . MEMBERSHIP_TABLE_COMMUNICATIONS . " WHERE id = %d ", $this->id );
			return $this->db->get_row( $commsql );
		}

		function get_active_subscriptions() {
			$where = array();
			$orderby = array();

			$where[] = "sub_active = 1";

			$orderby[] = 'id ASC';

			$sql = 'SELECT * FROM ' . MEMBERSHIP_TABLE_SUBSCRIPTIONS;

			if ( !empty( $where ) ) {
				$sql .= " WHERE " . implode( ' AND ', $where );
			}

			if ( !empty( $orderby ) ) {
				$sql .= " ORDER BY " . implode( ', ', $orderby );
			}

			return $this->db->get_results( $sql );
		}

		function addform() {

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __( 'Message to be sent', 'membership' ) . '</th>';
			echo '<td valign="top">';

			echo '<select name="periodunit">';
			for ( $n = 0; $n <= 365; $n++ ) {
				echo "<option value='$n'";
				echo ">";
				echo $n;
				echo "</option>";
			}
			echo '</select>&nbsp;';
			echo '<select name="periodtype">';
			echo "<option value='d'";
			echo ">";
			echo __( 'day(s)', 'membership' );
			echo "</option>";

			echo "<option value='m'";
			echo ">";
			echo __( 'month(s)', 'membership' );
			echo "</option>";

			echo "<option value='y'";
			echo ">";
			echo __( 'year(s)', 'membership' );
			echo "</option>";
			echo '</select>&nbsp;';
			echo '<select name="periodprepost">';
			echo "<option value='pre'";
			echo ">";
			echo __( 'before a subscription expires', 'membership' );
			echo "</option>";

			echo "<option value='post'";
			echo ">";
			echo __( 'after a subscription is paid', 'membership' );
			echo "</option>";
			echo '</select>';

			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __( 'For subscription', 'membership' ) . '</th>';
			echo '<td valign="top">';

			echo '<select name="subscription_id">';
			echo '<option value="0">' . __( 'All', 'membership' ) . '</option>';
			$subscriptions = (array)$this->get_active_subscriptions();
			foreach ( $subscriptions as $sub ) {
				echo '<option value="' . $sub->id . '">' . $sub->sub_name . '</option>';
			}
			echo '</select>';

			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __( 'Message Subject', 'membership' ) . '</th>';
			echo '<td valign="top"><input name="subject" type="text" size="50" title="' . __( 'Message subject', 'membership' ) . '" style="width: 50%;" value="" /></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th style="" scope="row" valign="top">' . __( 'Message', 'membership' ) . '</th>';
			echo '<td valign="top">';
			wp_editor( '', 'message' );
			// Display some instructions for the message.
			echo '<div class="instructions">';
			echo __( 'You can use the following constants within the message body to embed database information.', 'membership' );
			echo '<br>';
			echo '<br>';

			echo implode( '<br>', array_keys( apply_filters( 'membership_comm_constants_list', $this->commconstants ) ) );

			echo '</div>';
			echo '</td>';
			echo '</tr>';

			echo '</table>';
		}

		function editform() {
			$this->comm = $this->get_communication();

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __( 'Message to be sent', 'membership' ) . '</th>';
			echo '<td valign="top">';

			echo '<select name="periodunit">';
			for ( $n = 0; $n <= 365; $n++ ) {
				echo "<option value='$n'";
				if ( $this->comm->periodunit == $n )
					echo ' selected="selected" ';
				echo ">";
				echo $n;
				echo "</option>";
			}
			echo '</select>&nbsp;';
			echo '<select name="periodtype">';
			echo "<option value='d'";
			if ( $this->comm->periodtype == 'd' )
				echo ' selected="selected" ';
			echo ">";
			echo __( 'day(s)', 'membership' );
			echo "</option>";

			echo "<option value='m'";
			if ( $this->comm->periodtype == 'm' )
				echo ' selected="selected" ';
			echo ">";
			echo __( 'month(s)', 'membership' );
			echo "</option>";

			echo "<option value='y'";
			if ( $this->comm->periodtype == 'y' )
				echo ' selected="selected" ';
			echo ">";
			echo __( 'year(s)', 'membership' );
			echo "</option>";
			echo '</select>&nbsp;';
			echo '<select name="periodprepost">';
			echo "<option value='pre'";
			if ( $this->comm->periodprepost == 'pre' )
				echo ' selected="selected" ';
			echo ">";
			echo __( 'before a subscription expires', 'membership' );
			echo "</option>";

			echo "<option value='post'";
			if ( $this->comm->periodprepost == 'post' )
				echo ' selected="selected" ';
			echo ">";
			echo __( 'after a subscription is paid', 'membership' );
			echo "</option>";
			echo '</select>';

			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __( 'For subscription', 'membership' ) . '</th>';
			echo '<td valign="top">';

			echo '<select name="subscription_id">';
			echo '<option value="0">' . __( 'All', 'membership' ) . '</option>';
			$subscriptions = (array)$this->get_active_subscriptions();
			foreach ( $subscriptions as $sub ) {
				echo '<option value="' . $sub->id . '"' . selected( $sub->id, $this->comm->sub_id, false ) . '>' . $sub->sub_name . '</option>';
			}
			echo '</select>';


			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __( 'Message Subject', 'membership' ) . '</th>';
			echo '<td valign="top"><input name="subject" type="text" size="50" title="' . __( 'Message subject', 'membership' ) . '" style="width: 50%;" value="' . esc_attr( stripslashes( $this->comm->subject ) ) . '" /></td>';
			echo '</tr>';

			echo '<tr>';
			echo '<th scope="row" valign="top">' . __( 'Message', 'membership' ) . '</th>';
			echo '<td valign="top">';
			wp_editor( stripslashes( $this->comm->message ), 'message' );
			// Display some instructions for the message.
			echo '<div class="instructions">';
			echo __( 'You can use the following constants within the message body to embed database information.', 'membership' );
			echo '<br>';
			echo '<br>';

			echo implode( '<br>', array_keys( apply_filters( 'membership_comm_constants_list', $this->commconstants ) ) );

			echo '</div>';
			echo '</td>';
			echo '</tr>';

			echo '</table>';
		}

		function add() {
			switch ( $_POST['periodtype'] ) {
				case 'd':
					$time = strtotime( '+' . $_POST['periodunit'] . ' days' ) - time();
					break;
				case 'm':
					$time = strtotime( '+' . $_POST['periodunit'] . ' months' ) - time();
					break;
				case 'y':
					$time = strtotime( '+' . $_POST['periodunit'] . ' years' ) - time();
					break;
			}

			return $this->db->insert( MEMBERSHIP_TABLE_COMMUNICATIONS, array(
				"periodunit"    => $_POST['periodunit'],
				"periodtype"    => $_POST['periodtype'],
				"periodprepost" => $_POST['periodprepost'],
				"subject"       => $_POST['subject'],
				"message"       => stripslashes( $_POST['message'] ),
				"periodstamp"   => $_POST['periodprepost'] == 'pre' ? -$time : $time,
				"sub_id"        => $_POST['subscription_id']
			) );
		}

		function update() {
			switch ( $_POST['periodtype'] ) {
				case 'd':
					$time = strtotime( '+' . $_POST['periodunit'] . ' days' ) - time();
					break;
				case 'm':
					$time = strtotime( '+' . $_POST['periodunit'] . ' months' ) - time();
					break;
				case 'y':
					$time = strtotime( '+' . $_POST['periodunit'] . ' years' ) - time();
					break;
			}

			$updates = array(
				"periodunit"    => $_POST['periodunit'],
				"periodtype"    => $_POST['periodtype'],
				"periodprepost" => $_POST['periodprepost'],
				"subject"       => $_POST['subject'],
				"message"       => stripslashes( $_POST['message'] ),
				"periodstamp"   => $_POST['periodprepost'] == 'pre' ? -$time : $time,
				"sub_id"        => $_POST['subscription_id']
			);

			return $this->db->update( MEMBERSHIP_TABLE_COMMUNICATIONS, $updates, array( "id" => $this->id ) );
		}

		function delete() {
			return $this->db->delete( MEMBERSHIP_TABLE_COMMUNICATIONS, array( 'id' => $this->id ), array( '%d' ) );
		}

		function toggle() {
			if ( !$this->id ) {
				return false;
			}

			$this->dirty = true;
			return $this->db->query( sprintf(
				'UPDATE %s SET active = NOT active WHERE id = %d',
				MEMBERSHIP_TABLE_COMMUNICATIONS,
				$this->id
			) );
		}

		function send_message( $user_id, $sub_id = false, $level_id = false ) {
			global $wp_better_emails;

			$member = Membership_Plugin::factory()->get_member( $user_id );
			if ( !filter_var( $member->user_email, FILTER_VALIDATE_EMAIL ) ) {
				return;
			}

			$this->comm = $this->get_communication();
			$commdata = apply_filters( 'membership_comm_constants_list', $this->commconstants );

			foreach ( array_keys( $commdata ) as $key ) {
				switch ( $key ) {
					case '%blogname%':
						$commdata[$key] = get_option( 'blogname' );
						break;

					case '%blogurl%':
						$commdata[$key] = get_option( 'home' );
						break;

					case '%username%':
						$commdata[$key] = $member->user_login;
						break;

					case '%usernicename%':
						$commdata[$key] = $member->user_nicename;
						break;

					case '%userdisplayname%':
						$commdata[$key] = $member->display_name;
						break;

					case '%userfirstname%':
						$commdata[$key] = $member->user_firstname;
						break;

					case '%userlastname%':
						$commdata[$key] = $member->user_lastname;
						break;

					case '%networkname%':
						$commdata[$key] = get_site_option( 'site_name' );
						break;

					case '%networkurl%':
						$commdata[$key] = get_site_option( 'siteurl' );
						break;

					case '%subscriptionname%':
						if ( !$sub_id ) {
							$ids = $member->get_subscription_ids();
							if ( !empty( $ids ) ) {
								$sub_id = $ids[0];
							}
						}

						if ( !empty( $sub_id ) ) {
							$sub = new M_Subscription( $sub_id );
							$commdata[$key] = $sub->sub_name();
						} else {
							$commdata[$key] = '';
						}

						break;

					case '%levelname%':
						if ( !$level_id ) {
							$ids = $member->get_level_ids();
							if ( !empty( $ids ) ) {
								$level_id = $ids[0]->level_id;
							}
						}

						if ( !empty( $level_id ) ) {
							$level = new M_Level( $level_id );
							$commdata[$key] = $level->level_title();
						} else {
							$commdata[$key] = '';
						}
						break;

					case '%accounturl%':
						$commdata[$key] = M_get_account_permalink();
						break;

					default:
						$commdata[$key] = apply_filters( 'membership_commfield_' . $key, '', $user_id );
						break;
				}
			}

			// Globally replace the values in the ping and then make it into an array to send
			$original_commmessage = str_replace( array_keys( $commdata ), array_values( $commdata ), stripslashes( $this->comm->message ) );

			$html_message = wpautop( $original_commmessage );
			$text_message = strip_tags( preg_replace( '/\<a .*?href="(.*?)".*?\>.*?\<\/a\>/is', '$0 [$1]', $original_commmessage ) );

			add_filter( 'wp_mail_content_type', 'M_Communications_set_html_content_type' );

			$lambda_function = false;
			if ( $wp_better_emails ) {
				$html_message = apply_filters( 'wpbe_html_body', $wp_better_emails->template_vars_replacement( $wp_better_emails->set_email_template( $html_message, 'template' ) ) );
				$text_message = apply_filters( 'wpbe_plaintext_body', $wp_better_emails->template_vars_replacement( $wp_better_emails->set_email_template( $text_message, 'plaintext_template' ) ) );

				// lets use WP Better Email to wrap communication content if the plugin is used
				$lambda_function = create_function( '', sprintf( 'return "%s";', addslashes( $text_message ) ) );
				add_filter( 'wpbe_plaintext_body', $lambda_function );
				add_filter( 'wpbe_plaintext_body', 'stripslashes', 11 );
			} elseif ( !defined( 'MEMBERSHIP_DONT_WRAP_COMMUNICATION' ) ) {
				$html_message = "<html><head></head><body>{$html_message}</body></html>";
			}

			@wp_mail( $member->user_email, stripslashes( $this->comm->subject ), $html_message );

			remove_filter( 'wp_mail_content_type', 'M_Communications_set_html_content_type' );
			if ( $lambda_function ) {
				remove_filter( 'wpbe_plaintext_body', $lambda_function );
				remove_filter( 'wpbe_plaintext_body', 'stripslashes', 11 );
			}
		}

	}

}

function M_Communication_get_members( $startatid = 0, $limit = 25 ) {
	global $wpdb;
	return $wpdb->get_col( sprintf( "SELECT user_id FROM %s WHERE sub_id != 0 AND user_id > %d ORDER BY user_id ASC LIMIT 0, %d", MEMBERSHIP_TABLE_RELATIONS, $startatid, $limit ) );
}

function M_Communication_get_startstamps( $user_id ) {

	global $wpdb;

	$sql = $wpdb->prepare( "SELECT * FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE", $user_id );
	$sql .= " 'start_current_" . '%' ."'";

	$results = $wpdb->get_results( $sql );

	if(!empty($results)) {
		return $results;
	} else {
		return false;
	}

}

function M_Communication_get_endstamps( $user_id ) {

	global $wpdb;

	$sql = $wpdb->prepare( "SELECT * FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE", $user_id );
	$sql .= " 'expire_current_" . '%' ."'";

	$results = $wpdb->get_results( $sql );

	if(!empty($results)) {
		return $results;
	} else {
		return false;
	}
}

function M_Communication_get_pre_messages( ) {
	global $wpdb;
	return $wpdb->get_results( "SELECT * FROM " . MEMBERSHIP_TABLE_COMMUNICATIONS . " WHERE periodstamp < 0 AND active = 1 ORDER BY periodstamp ASC" );
}

function M_Communication_get_post_messages() {
	global $wpdb;
	return $wpdb->get_results( "SELECT * FROM " . MEMBERSHIP_TABLE_COMMUNICATIONS . " WHERE periodstamp >= 0 AND active = 1 ORDER BY periodstamp ASC" );
}

add_action( 'membership_communications_process', 'M_Communication_process' );
function M_Communication_process() {
	// This function checks for any communication messages that need to be sent for this user and sends them
	$lastatid = M_get_option( 'membership_communication_last_user_processed', 0 );
	if ( empty( $lastatid ) ) {
		$lastatid = 0;
	}

	$members = M_Communication_get_members( $lastatid );
	if ( empty( $members ) ) {
		// do nothing
		if ( $lastatid != 0 ) {
			M_update_option( 'membership_communication_last_user_processed', 0 );
		}
	} else {
		// Our starting time
		$timestart = current_time( 'timestamp' );
		//Or processing limit
		$timelimit = 3; // max seconds for processing

		foreach ( (array)$members as $user_id ) {
			if ( current_time( 'timestamp' ) > $timestart + $timelimit ) {
				M_update_option( 'membership_communication_last_user_processed', $user_id );
				break;
			}

			if ( apply_filters( 'membership_prevent_communication', get_user_meta( $user_id, 'membership_signup_gateway_can_communicate', true ) ) != 'yes' ) {
				$starts = M_Communication_get_startstamps( $user_id );
				$comms = M_Communication_get_post_messages();

				if ( !empty( $starts ) && !empty( $comms ) ) {
					foreach ( $starts as $start ) {
						$starttime = $start->meta_value;
						$now = current_time( 'timestamp' );

						$sub_id = str_replace( 'start_current_', '', $start->meta_key );
						$sentalready = get_user_meta( $user_id, 'sent_msgs_' . $sub_id, true );

						if ( empty( $sentalready ) || !is_array( $sentalready ) ) {
							$sentalready = array();
						}

						foreach ( (array) $comms as $comm ) {
							if ( in_array( $comm->id, $sentalready ) || ( $comm->sub_id != $sub_id && $comm->sub_id > 0 ) ) {
								continue;
							}

							$withperiod = ($starttime + $comm->periodstamp);
							// Get 24 hour previous and after so we have a range in which to fit a communication
							$onedaybefore = strtotime( '-6 hours', $withperiod );
							$onedayafter = strtotime( '+6 hours', $withperiod );

							if ( ($now > $onedaybefore) && ($now < $onedayafter) ) {
								$message = new M_Communication( $comm->id );
								$sentalready[$comm->id] = $comm->id;
								$message->send_message( $user_id, $sub_id );
								break;
							}
						}

						update_user_meta( $user_id, 'sent_msgs_' . $sub_id, $sentalready );
					}
				}

				$ends = M_Communication_get_endstamps( $user_id );
				$comms = M_Communication_get_pre_messages();

				if ( !empty( $ends ) && !empty( $comms ) ) {
					foreach ( $ends as $end ) {
						$endtime = $end->meta_value;
						$now = current_time( 'timestamp' );

						$sub_id = str_replace( 'expire_current_', '', $end->meta_key );
						$sentalready = get_user_meta( $user_id, 'sent_msgs_' . $sub_id, true );

						if ( empty( $sentalready ) || !is_array( $sentalready ) ) {
							$sentalready = array();
						}

						foreach ( (array) $comms as $comm ) {
							if ( in_array( $comm->id, $sentalready ) || ( $comm->sub_id != $sub_id && $comm->sub_id > 0 ) ) {
								continue;
							}

							$withperiod = ($endtime + $comm->periodstamp);
							// Get 24 hour previous and after so we have a range in which to fit a communication
							$onedaybefore = strtotime( '-6 hours', $withperiod );
							$onedayafter = strtotime( '+6 hours', $withperiod );

							if ( ($now > $onedaybefore) && ($now < $onedayafter) ) {
								$message = new M_Communication( $comm->id );
								$sentalready[$comm->id] = $comm->id;
								$message->send_message( $user_id, $sub_id );
								break;
							}
						}

						update_user_meta( $user_id, 'sent_msgs_' . $sub_id, $sentalready );
					}
				}
			}
		}

		M_update_option( 'membership_communication_last_user_processed', $user_id );
	}
}

add_filter( 'cron_schedules', 'M_add_communications_time_period' );
function M_add_communications_time_period( $periods ) {
	if ( !is_array( $periods ) ) {
		$periods = array();
	}

	$periods['10mins'] = array( 'interval' => 10 * MINUTE_IN_SECONDS, 'display' => __( 'Every 10 Mins', 'membership' ) );
	$periods['5mins']  = array( 'interval' =>  5 * MINUTE_IN_SECONDS, 'display' => __( 'Every 5 Mins', 'membership' ) );

	return $periods;
}

add_action( 'init', 'M_setup_communications', 10 );
function M_setup_communications() {
	// Action to be called by the cron job
	$checkperiod = defined( 'MEMBERSHIP_COMMUNICATIONS_PROCESSING_CHECKLIMIT' ) && MEMBERSHIP_COMMUNICATIONS_PROCESSING_CHECKLIMIT == 10 ? '10mins' : '5mins';
	if ( !wp_next_scheduled( 'membership_communications_process' ) ) {
		wp_schedule_event( time(), $checkperiod, 'membership_communications_process' );
	}
}

function M_Communications_set_html_content_type() {
	return 'text/html';
}