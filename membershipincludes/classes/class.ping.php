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

		function get_ping() {
			$sql = $this->db->prepare( "SELECT * FROM {$this->pings} WHERE id = %d ", $this->id );

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
			echo '<th style="" scope="row" valign="top">' . __('Ping data','automessage') . '</th>';
			echo '<td valign="top"><textarea name="pinginfo" rows="15" cols="40" style="float: left; width: 40%;">' . esc_html(stripslashes($this->ping->pinginfo)) . '</textarea>';
			// Display some instructions for the message.
			echo '<div class="instructions" style="float: left; width: 40%; margin-left: 10px;">';
			echo __('You can use the following constants within the message body to embed database information.','membership');
			echo '<br />';

			echo implode('<br/>', array_keys(apply_filters('membership_ping_constants_list', $this->pingconstants)) );

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
			echo '<th style="" scope="row" valign="top">' . __('Ping data','automessage') . '</th>';
			echo '<td valign="top"><textarea name="pinginfo" rows="15" cols="40" style="float: left; width: 40%;"></textarea>';
			// Display some instructions for the message.
			echo '<div class="instructions" style="float: left; width: 40%; margin-left: 10px;">';
			echo __('You can use the following constants within the message body to embed database information.','membership');
			echo '<br />';

			echo implode('<br/>', array_keys(apply_filters('membership_ping_constants_list', $this->pingconstants)) );

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
								"pinginfo"	=>	$_POST['pinginfo'],
								"pingtype"	=>	$_POST['pingtype']
							);

			return $this->db->insert( $this->pings, $insert );

		}

		function update() {

			$updates = array(
								"pingname"	=> 	$_POST['pingname'],
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

		}

		// processing
		function send_ping() {

		}

		function resend_historic_ping( $history_id, $rewrite = false ) {

		}

	}
}
?>