<?php
if(!class_exists('M_Communication')) {

	class M_Communication {

		var $build = 1;

		var $db;
		var $tables = array('membership_levels', 'membership_rules', 'subscriptions', 'subscriptions_levels', 'membership_relationships', 'membermeta', 'communications', 'urlgroups');

		var $membership_levels;
		var $membership_rules;
		var $membership_relationships;
		var $subscriptions;
		var $subscriptions_levels;
		var $membermeta;
		var $communications;
		var $urlgroups;

		// if the data needs reloaded, or hasn't been loaded yet
		var $dirty = true;

		var $comm;

		function __construct( $id = false) {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = membership_db_prefix($this->db, $table);
			}

			$this->id = $id;

		}

		function M_Communication( $id = false ) {
			$this->__construct( $id );
		}

		function get_communication() {
			$commsql = $this->db->prepare( "SELECT * FROM {$this->communications} WHERE id = %d ", $this->id );

			return $this->db->get_row( $commsql );
		}

		function addform() {

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message to be sent','membership') . '</th>';
			echo '<td valign="top">';

			echo '<select name="periodunit">';
			for($n = 0; $n <= 365; $n++) {
				echo "<option value='$n'";
				echo ">";
				echo $n;
				echo "</option>";
			}
			echo '</select>&nbsp;';
			echo '<select name="periodtype">';
				echo "<option value='d'";
				echo ">";
				echo __('day(s)','membership');
				echo "</option>";

				echo "<option value='m'";
				echo ">";
				echo __('month(s)','membership');
				echo "</option>";

				echo "<option value='y'";
				echo ">";
				echo __('year(s)','membership');
				echo "</option>";
			echo '</select>&nbsp;';
			echo '<select name="periodprepost">';
				echo "<option value='pre'";
				echo ">";
				echo __('before a subscription expires','membership');
				echo "</option>";

				echo "<option value='post'";
				echo ">";
				echo __('after a subscription is paid','membership');
				echo "</option>";
			echo '</select>';

			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message Subject','membership') . '</th>';
			echo '<td valign="top"><input name="subject" type="text" size="50" title="' . __('Message subject', 'membership') . '" style="width: 50%;" value="" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message','membership') . '</th>';
			echo '<td valign="top"><textarea name="message" style="width: 50%; float: left;" rows="15" cols="40"></textarea>';
			// Display some instructions for the message.
			echo '<div class="instructions" style="float: left; width: 40%; margin-left: 10px;">';
			echo __('You can use the following constants within the message body to embed database information.','membership');
			echo '<br /><br />';
			echo '%blogname%<br />';
			echo '%blogurl%<br />';
			echo '%username%<br />';
			echo '%usernicename%<br/>';
			echo '%networkname%<br/>';
			echo "%networkurl%<br/>";
			echo "%upgradeurl%<br/>";
			echo "%subscriptionname%<br/>";
			echo "%levelname%<br/>";

			echo '</div>';
			echo '</td>';
			echo '</tr>';

			echo '</table>';

		}

		function editform() {

			$this->comm = $this->get_communication();

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message to be sent','membership') . '</th>';
			echo '<td valign="top">';

			echo '<select name="periodunit">';
			for($n = 0; $n <= 365; $n++) {
				echo "<option value='$n'";
				if($this->comm->periodunit == $n)  echo ' selected="selected" ';
				echo ">";
				echo $n;
				echo "</option>";
			}
			echo '</select>&nbsp;';
			echo '<select name="periodtype">';
				echo "<option value='d'";
				if($this->comm->periodtype == 'd')  echo ' selected="selected" ';
				echo ">";
				echo __('day(s)','membership');
				echo "</option>";

				echo "<option value='m'";
				if($this->comm->periodtype == 'm')  echo ' selected="selected" ';
				echo ">";
				echo __('month(s)','membership');
				echo "</option>";

				echo "<option value='y'";
				if($this->comm->periodtype == 'y')  echo ' selected="selected" ';
				echo ">";
				echo __('year(s)','membership');
				echo "</option>";
			echo '</select>&nbsp;';
			echo '<select name="periodprepost">';
				echo "<option value='pre'";
				if($this->comm->periodprepost == 'pre')  echo ' selected="selected" ';
				echo ">";
				echo __('before a subscription expires','membership');
				echo "</option>";

				echo "<option value='post'";
				if($this->comm->periodprepost == 'post')  echo ' selected="selected" ';
				echo ">";
				echo __('after a subscription is paid','membership');
				echo "</option>";
			echo '</select>';

			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message Subject','membership') . '</th>';
			echo '<td valign="top"><input name="subject" type="text" size="50" title="' . __('Message subject', 'membership') . '" style="width: 50%;" value="' . esc_attr(stripslashes($this->comm->subject)) . '" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message','membership') . '</th>';
			echo '<td valign="top"><textarea name="message" style="width: 50%; float: left;" rows="15" cols="40">' . esc_html(stripslashes($this->comm->message)) . '</textarea>';
			// Display some instructions for the message.
			echo '<div class="instructions" style="float: left; width: 40%; margin-left: 10px;">';
			echo __('You can use the following constants within the message body to embed database information.','membership');
			echo '<br /><br />';
			echo '%blogname%<br />';
			echo '%blogurl%<br />';
			echo '%username%<br />';
			echo '%usernicename%<br/>';
			echo '%networkname%<br/>';
			echo "%networkurl%<br/>";
			echo "%upgradeurl%<br/>";
			echo "%subscriptionname%<br/>";
			echo "%levelname%<br/>";

			echo '</div>';
			echo '</td>';
			echo '</tr>';

			echo '</table>';

		}

		function add() {

			switch($_POST['periodtype']) {
				case 'd':	$time = strtotime('+' . $_POST['periodunit'] . ' days') - time();
							break;
				case 'm':	$time = strtotime('+' . $_POST['periodunit'] . ' months') - time();
							break;
				case 'y':	$time = strtotime('+' . $_POST['periodunit'] . ' years') - time();
							break;
			}

			switch($_POST['periodprepost']) {
				case 'post':	$time = $time;
								break;
				case 'pre':		$time -= ($time * 2);
								break;
			}


			$insert = array(
								"periodunit"	=> 	$_POST['periodunit'],
								"periodtype"	=>	$_POST['periodtype'],
								"periodprepost"	=>	$_POST['periodprepost'],
								"subject"		=>	$_POST['subject'],
								"message"		=>	$_POST['message'],
								"periodstamp"	=> $time
							);

			return $this->db->insert( $this->communications, $insert );

		}

		function update() {

			switch($_POST['periodtype']) {
				case 'd':	$time = strtotime('+' . $_POST['periodunit'] . ' days') - time();
							break;
				case 'm':	$time = strtotime('+' . $_POST['periodunit'] . ' months') - time();
							break;
				case 'y':	$time = strtotime('+' . $_POST['periodunit'] . ' years') - time();
							break;
			}

			switch($_POST['periodprepost']) {
				case 'post':	$time = $time;
								break;
				case 'pre':		$time -= ($time * 2);
								break;
			}

			$updates = array(
								"periodunit"	=> 	$_POST['periodunit'],
								"periodtype"	=>	$_POST['periodtype'],
								"periodprepost"	=>	$_POST['periodprepost'],
								"subject"		=>	$_POST['subject'],
								"message"		=>	$_POST['message'],
								"periodstamp"	=> $time
							);

			return $this->db->update( $this->communications, $updates, array( "id" => $this->id) );

		}

		function delete() {
			$sql = $this->db->prepare( "DELETE FROM {$this->communications} WHERE id = %d", $this->id );

			return $this->db->query( $sql );
		}

		function toggle() {
			$sql = $this->db->prepare( "UPDATE {$this->communications} SET active = NOT active WHERE id = %d", $this->id);

			$this->dirty = true;

			return $this->db->query($sql);
		}

	}
}
?>