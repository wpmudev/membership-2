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
			echo '<th style="" scope="row" valign="top">' . __('Message sent','automessage') . '</th>';
			echo '<td valign="top">';

			echo '<select name="period" style="width: 40%;">';
			for($n = 0; $n <= AUTOMESSAGE_POLL_MAX_DELAY; $n++) {
				echo "<option value='$n'";
				if($editing->menu_order == $n)  echo ' selected="selected" ';
				echo ">";
				switch($n) {
					case 0: 	echo __("Send immediately", 'automessage');
								break;
					case 1: 	echo __("1 day", 'automessage');
								break;
					default:	echo sprintf(__('%d days','automessage'),$n);
				}
				echo "</option>";
			}
			echo '</select>';
			echo '<input type="hidden" name="timeperiod" value="day" />';
			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message Subject','automessage') . '</th>';
			echo '<td valign="top"><input name="subject" type="text" size="50" title="' . __('Message subject') . '" style="width: 50%;" value="' . esc_attr($this->comm->subject) . '" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message','automessage') . '</th>';
			echo '<td valign="top"><textarea name="message" style="width: 50%; float: left;" rows="15" cols="40">' . esc_html($this->comm->message) . '</textarea>';
			// Display some instructions for the message.
			echo '<div class="instructions" style="float: left; width: 40%; margin-left: 10px;">';
			echo __('You can use the following constants within the message body to embed database information.','automessage');
			echo '<br /><br />';
			echo '%blogname%<br />';
			echo '%blogurl%<br />';
			echo '%username%<br />';
			echo '%usernicename%<br/>';
			echo '%sitename%<br/>';
			echo "%siteurl%<br/>";
			echo "%upgradeurl%<br/>";

			echo '</div>';
			echo '</td>';
			echo '</tr>';

			echo '</table>';

		}

		function editform() {

			$this->comm = $this->get_communication();

			print_r($this->comm);

			echo '<table class="form-table">';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message delay','automessage') . '</th>';
			echo '<td valign="top">';

			echo '<select name="period" style="width: 40%;">';
			for($n = 0; $n <= AUTOMESSAGE_POLL_MAX_DELAY; $n++) {
				echo "<option value='$n'";
				if($editing->menu_order == $n)  echo ' selected="selected" ';
				echo ">";
				switch($n) {
					case 0: 	echo __("Send immediately", 'automessage');
								break;
					case 1: 	echo __("1 day", 'automessage');
								break;
					default:	echo sprintf(__('%d days','automessage'),$n);
				}
				echo "</option>";
			}
			echo '</select>';
			echo '<input type="hidden" name="timeperiod" value="day" />';
			echo '</td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message Subject','automessage') . '</th>';
			echo '<td valign="top"><input name="subject" type="text" size="50" title="' . __('Message subject') . '" style="width: 50%;" value="' . esc_attr($this->comm->subject) . '" /></td>';
			echo '</tr>';

			echo '<tr class="form-field form-required">';
			echo '<th style="" scope="row" valign="top">' . __('Message','automessage') . '</th>';
			echo '<td valign="top"><textarea name="message" style="width: 50%; float: left;" rows="15" cols="40">' . esc_html($this->comm->message) . '</textarea>';
			// Display some instructions for the message.
			echo '<div class="instructions" style="float: left; width: 40%; margin-left: 10px;">';
			echo __('You can use the following constants within the message body to embed database information.','automessage');
			echo '<br /><br />';
			echo '%blogname%<br />';
			echo '%blogurl%<br />';
			echo '%username%<br />';
			echo '%usernicename%<br/>';
			echo '%sitename%<br/>';
			echo "%siteurl%<br/>";
			echo "%upgradeurl%<br/>";

			echo '</div>';
			echo '</td>';
			echo '</tr>';

			echo '</table>';

		}

		function add() {

		}

		function update() {

		}

	}
}
?>