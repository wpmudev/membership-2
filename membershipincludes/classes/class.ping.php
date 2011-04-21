<?php
if(!class_exists('M_Ping')) {

	class M_Ping {

		var $build = 1;

		var $db;
		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships', 'membermeta', 'communications', 'urlgroups', 'ping_history', 'pings');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;
		var $membermeta;
		var $communications;
		var $urlgroups;
		var $ping_history;
		var $pings;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $ping;
		var $id;

		var $pingconstants = array(	'%blogname%' => '',
									'%blogurl%' => '',
									'%username%' => '',
									'%usernicename%' => '',
									'%networkname%' => '',
									'%networkurl%' => '',
									'%subscriptionname%' => '',
									'%levelname%' => '',
									'%timestamp%' => ''
									);

		function __construct( $id = false) {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			$this->id = $id;

		}

		function M_Ping( $id = false ) {
			$this->__construct( $id );
		}

		function ping_name() {
			$this->ping = $this->get_ping();

			return $this->ping->pingname;
		}

		function ping_url() {
			$this->ping = $this->get_ping();

			return $this->ping->pingurl;
		}

		function get_ping( $force = false ) {

			if(!empty($this->ping) && !$force) {
				return $this->ping;
			} else {
				$sql = $this->db->prepare( "SELECT * FROM {$this->pings} WHERE id = %d ", $this->id );

				return $this->db->get_row( $sql );
			}

		}

		function get_specifc_ping( $id ) {

			$sql = $this->db->prepare( "SELECT * FROM {$this->pings} WHERE id = %d ", $id );

			return $this->db->get_row( $sql );

		}

		function editform() {

			$this->ping = $this->get_ping();

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Ping name','membership') . '</th>';
			echo '<td valign="top"><input name="pingname" type="text" size="50" title="' . __('Ping name') . '" style="width: 50%;" value="' . esc_attr(stripslashes($this->ping->pingname)) . '" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Ping URL','membership') . '</th>';
			echo '<td valign="top"><input name="pingurl" type="text" size="50" title="' . __('Ping URL') . '" style="width: 50%;" value="' . esc_attr(stripslashes($this->ping->pingurl)) . '" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Ping data','automessage') . '</th>';
			echo '<td valign="top"><textarea name="pinginfo" rows="15" cols="40" style="float: left; width: 40%;">' . esc_html(stripslashes($this->ping->pinginfo)) . '</textarea>';
			// Display some instructions for the message.
			echo '<div class="instructions" style="float: left; width: 40%; margin-left: 10px;">';
			echo __('You can use the following constants within the message body to embed database information.','membership');
			echo '<br /><em>';

			echo implode('<br/>', array_keys(apply_filters('membership_ping_constants_list', $this->pingconstants)) );

			echo '</em><br/>' . __('One entry per line. e.g. key=value','membership');
			echo '</div>';

			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Strip query strings from URL','membership') . '</th>';
			echo '<td valign="top" align="left">';
			echo '<select name="pingtype">';
				echo '<option value="GET"';
				if($this->ping->pingtype == 'GET') echo ' selected="selected"';
				echo '>' . __('GET', 'membership') . '</option>';
				echo '<option value="POST"';
				if($this->ping->pingtype == 'POST') echo ' selected="selected"';
				echo '>' . __('POST', 'membership') . '</option>';
			echo '</select>';
			echo '</td></tr>';

			echo '</table>';

		}

		function addform() {

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Ping name','membership') . '</th>';
			echo '<td valign="top"><input name="pingname" type="text" size="50" title="' . __('Ping name') . '" style="width: 50%;" value="" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Ping URL','membership') . '</th>';
			echo '<td valign="top"><input name="pingurl" type="text" size="50" title="' . __('Ping URL') . '" style="width: 50%;" value="" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Ping data','automessage') . '</th>';
			echo '<td valign="top"><textarea name="pinginfo" rows="15" cols="40" style="float: left; width: 40%;"></textarea>';
			// Display some instructions for the message.
			echo '<div class="instructions" style="float: left; width: 40%; margin-left: 10px;">';
			echo __('You can use the following constants within the message body to embed database information.','membership');
			echo '<br /><em>';

			echo implode('<br/>', array_keys(apply_filters('membership_ping_constants_list', $this->pingconstants)) );

			echo '</em><br/>' . __('One entry per line. e.g. key=value','membership');

			echo '</div>';
			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Strip query strings from URL','membership') . '</th>';
			echo '<td valign="top" align="left">';
			echo '<select name="pingtype">';
				echo '<option value="GET"';
				echo '>' . __('GET', 'membership') . '</option>';
				echo '<option value="POST"';
				echo '>' . __('POST', 'membership') . '</option>';
			echo '</select>';
			echo '</td></tr>';

			echo '</table>';

		}

		function add() {

			$insert = array(
								"pingname"	=> 	$_POST['pingname'],
								"pingurl"	=>	$_POST['pingurl'],
								"pinginfo"	=>	$_POST['pinginfo'],
								"pingtype"	=>	$_POST['pingtype']
							);

			return $this->db->insert( $this->pings, $insert );

		}

		function update() {

			$updates = array(
								"pingname"	=> 	$_POST['pingname'],
								"pingurl"	=>	$_POST['pingurl'],
								"pinginfo"	=>	$_POST['pinginfo'],
								"pingtype"	=>	$_POST['pingtype']
							);

			return $this->db->update( $this->pings, $updates, array( "id" => $this->id) );

		}

		function delete() {

			$sql = $this->db->prepare( "DELETE FROM {$this->pings} WHERE id = %d", $this->id );

			return $this->db->query( $sql );

		}

		// History
		function get_history() {
			$sql = $this->db->prepare( "SELECT * FROM {$this->ping_history} WHERE ping_id = %d ORDER BY ping_sent DESC LIMIT 0, 50 ", $this->id );

			return $this->db->get_results( $sql );
		}

		function get_history_item( $history_id ) {
			$sql = $this->db->prepare( "SELECT * FROM {$this->ping_history} WHERE id = %d ", $history_id );

			return $this->db->get_row( $sql );
		}

		function add_history( $sent, $return ) {

			$insert = array(
							"ping_id"		=> 	$this->id,
							"ping_sent"		=>	gmdate( 'Y-m-d H:i:s' ),
							"ping_info"		=>	serialize($sent),
							"ping_return"	=>	serialize($return)
						);

			return $this->db->insert( $this->ping_history, $insert );
		}

		function update_history( $history_id, $sent, $return ) {
			$update = array(
							"ping_id"		=> 	$this->id,
							"ping_sent"		=>	gmdate( 'Y-m-d H:i:s' ),
							"ping_info"		=>	serialize($sent),
							"ping_return"	=>	serialize($return)
						);

			return $this->db->update( $this->ping_history, $update, array( "id" => $history_id ) );
		}

		// processing
		function send_ping( $sub_id = false, $level_id = false, $user_id = false ) {

			$this->ping = $this->get_ping();

			if( !class_exists( 'WP_Http' ) ) {
			    include_once( ABSPATH . WPINC. '/class-http.php' );
			}

			$pingdata = $this->pingconstants;

			if(empty($user_id)) {
				$user = wp_get_current_user();
				$member = new M_Membership( $user->ID );
			} else {
				$member = new M_Membership( $user_id );
			}

			foreach($pingdata as $key => $value) {
				switch($key) {
					case '%blogname%':			$pingdata[$key] = get_option('blogname');
												break;

					case '%blogurl%':			$pingdata[$key] = get_option('home');
												break;

					case '%username%':			$pingdata[$key] = $member->user_login;
												break;

					case '%usernicename%':		$pingdata[$key] = $member->user_nicename;
												break;

					case '%networkname%':		$pingdata[$key] = get_site_option('site_name');
												break;

					case '%networkurl%':		$pingdata[$key] = get_site_option('siteurl');
												break;

					case '%subscriptionname%':	if(!$sub_id) {
													$ids = $member->get_subscription_ids();
													if(!empty($ids)) {
														$sub_id = $ids[0];
													}
												}

												if(!empty($sub_id)) {
													$sub =& new M_Subscription( $sub_id );
													$pingdata[$key] = $sub->sub_name();
												} else {
													$pingdata[$key] = '';
												}

												break;

					case '%levelname%':			if(!$level_id) {
													$ids = $member->get_level_ids();
													if(!empty($ids)) {
														$levels = $ids[0];
													}
												}

												if(!empty($levels->level_id)) {
													$level =& new M_Level( $levels->level_id );
													$pingdata[$key] = $level->level_title();
												} else {
													$pingdata[$key] = '';
												}
												break;

					case '%timestamp%':			$pingdata[$key] = time();
												break;

					default:					$pingdata[$key] = apply_filter( 'membership_pingfield_' . $key, '' );
												break;
				}
			}

			$url = $this->ping->pingurl;

			// Globally replace the values in the ping and then make it into an array to send
			$pingmessage = str_replace(array_keys($pingdata), array_values($pingdata), $this->ping->pinginfo);
			$pingmessage = array_map( 'trim', explode("\n", $pingmessage) );

			// make the ping message into a sendable bit of text
			$pingtosend = array();
			foreach($pingmessage as $key => $value) {
				$temp = explode("=", $value);
				$pingtosend[$temp[0]] = $temp[1];
			}

			// Send the request
			if( class_exists( 'WP_Http' ) ) {
				$request = new WP_Http;

				switch( $this->ping->pingtype ) {
					case 'GET':		$url = untrailingslashit($url) . "?";
					 				foreach($pingtosend as $key => $val) {
										if(substr($url, -1) != '?') $url .= "&";
										$url .= $key . "=" . urlencode($val);
									}
									$result = $request->request( $url, array( 'method' => 'GET', 'body' => '' ) );
									break;

					case 'POST':	$result = $request->request( $url, array( 'method' => 'POST', 'body' => $pingtosend ) );
									break;
				}

				/*
				'headers': an array of response headers, such as "x-powered-by" => "PHP/5.2.1"
				'body': the response string sent by the server, as you would see it with you web browser
				'response': an array of HTTP response codes. Typically, you'll want to have array('code'=>200, 'message'=>'OK')
				'cookies': an array of cookie information
				*/

				$this->add_history( $pingtosend, $result );
			}


		}

		function resend_historic_ping( $history_id, $rewrite ) {
			$history = $this->get_history_item( $history_id );

			if(!empty($history)) {
				$this->id = $history->ping_id;
				$ping = $this->get_specifc_ping( $history->ping_id );

				if( !class_exists( 'WP_Http' ) ) {
				    include_once( ABSPATH . WPINC. '/class-http.php' );
				}

				$url = $ping->pingurl;
				$pingtosend = unserialize($history->ping_info);

				// Send the request
				if( class_exists( 'WP_Http' ) ) {
					$request = new WP_Http;

					switch( $ping->pingtype ) {
						case 'GET':		$url = untrailingslashit($url) . "?";
						 				foreach($pingtosend as $key => $val) {
											if(substr($url, -1) != '?') $url .= "&";
											$url .= $key . "=" . urlencode($val);
										}
										$result = $request->request( $url, array( 'method' => 'GET', 'body' => '' ) );
										break;

						case 'POST':	$result = $request->request( $url, array( 'method' => 'POST', 'body' => $pingtosend ) );
										break;
					}

					/*
					'headers': an array of response headers, such as "x-powered-by" => "PHP/5.2.1"
					'body': the response string sent by the server, as you would see it with you web browser
					'response': an array of HTTP response codes. Typically, you'll want to have array('code'=>200, 'message'=>'OK')
					'cookies': an array of cookie information
					*/

					if($rewrite) {
						$this->add_history( $pingtosend, $result );
					} else {
						$this->update_history( $history_id, $pingtosend, $result );
					}
				}
			}
		}

	}
}

// Ping integration functions and hooks
/*
do_action( 'membership_add_level', $tolevel_id, $this->ID );
do_action( 'membership_drop_level', $fromlevel_id, $this->ID );
do_action( 'membership_move_level', $fromlevel_id, $tolevel_id, $this->ID );

do_action( 'membership_add_subscription', $tosub_id, $tolevel_id, $to_order, $this->ID);
do_action( 'membership_drop_subscription', $fromsub_id, $this->ID );
do_action( 'membership_move_subscription', $fromsub_id, $tosub_id, $tolevel_id, $to_order, $this->ID );
*/

function M_ping_joinedlevel( $tolevel_id, $user_id ) {

	// Set up the level and find out if it has a joining ping
	$level =& new M_Level( $tolevel_id );

	$joiningping_id = $level->get_meta( 'joining_ping' );
	if(!empty($joiningping_id)) {
		$ping =& new M_Ping( $joiningping_id );

		$ping->send_ping( false, $tolevel_id, $user_id );
	}


}
add_action( 'membership_add_level', 'M_ping_joinedlevel', 10, 2 );

function M_ping_leftlevel( $fromlevel_id, $user_id ) {

	// Set up the level and find out if it has a leaving ping
	$level =& new M_Level( $fromlevel_id );

	$leavingping_id = $level->get_meta( 'leaving_ping' );
	if(!empty($leavingping_id)) {
		$ping =& new M_Ping( $leavingping_id );

		$ping->send_ping( false, $fromlevel_id, $user_id );
	}

}
add_action( 'membership_drop_level', 'M_ping_leftlevel', 10, 2 );

function M_ping_movedlevel( $fromlevel_id, $tolevel_id, $user_id ) {

	M_ping_leftlevel( $fromlevel_id, $user_id );
	M_ping_joinedlevel( $tolevel_id, $user_id );

}
add_action( 'membership_move_level', 'M_ping_movedlevel', 10, 3 );

function M_ping_joinedsub( $tosub_id, $tolevel_id, $to_order, $user_id ) {

	$sub =& new M_Subscription( $tosub_id );
	$subjoiningping_id = $sub->get_meta( 'joining_ping' );

	if(!empty($subjoiningping_id)) {
		$ping =& new M_Ping( $subjoiningping_id );

		$ping->send_ping( $tosub_id, $tolevel_id, $user_id );
	}

	$level =& new M_Level( $tolevel_id );
	$joiningping_id = $level->get_meta( 'joining_ping' );

	if(!empty($joiningping_id)) {
		$ping =& new M_Ping( $joiningping_id );

		$ping->send_ping( $tosub_id, $tolevel_id, $user_id );
	}

}
add_action( 'membership_add_subscription', 'M_ping_joinedsub', 10, 4 );

function M_ping_leftsub( $fromsub_id, $fromlevel_id, $user_id ) {

	// Leaving the level
	M_ping_leftlevel( $fromlevel_id, $user_id );

	// Leaving the sub
	$sub =& new M_Subscription( $fromsub_id );
	$subleavingping_id = $sub->get_meta( 'leaving_ping' );

	if(!empty($subleavingping_id)) {
		$ping =& new M_Ping( $subleavingping_id );

		$ping->send_ping( $fromsub_id, false, $user_id );
	}

}
add_action( 'membership_drop_subscription', 'M_ping_leftsub', 10, 3 );

function M_ping_movedsub( $fromsub_id, $fromlevel_id, $tosub_id, $tolevel_id, $to_order, $user_id ) {

	M_ping_leftsub( $fromsub_id, $fromlevel_id, $user_id );
	M_ping_joinedsub( $tosub_id, $tolevel_id, $to_order, $user_id );

}
add_action( 'membership_move_subscription', 'M_ping_movedsub', 10, 6 );


?>