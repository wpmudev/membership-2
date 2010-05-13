<?php

if(!class_exists('M_Gateway')) {

	class M_Gateway {

		var $db;

		// Class Identification
		var $gateway = 'Not Set';
		var $title = 'Not Set';

		// Tables
		var $tables = array('subscription_transaction');
		var $subscription_transaction;

		function M_Gateway() {

			global $wpdb;

			$this->db =& $wpdb;

			foreach($this->tables as $table) {
				$this->$table = $wpdb->prefix . $table;
			}

			// Actions and Filters
			add_filter('M_gateways_list', array(&$this, 'gateways_list'));

		}

		function gateways_list($gateways) {

			$gateways[$this->gateway] = $this->title;

			return $gateways;

		}

		function toggleactivation() {

			$active = get_option('M_active_gateways', array());

			if(array_key_exists($this->gateway, $active)) {
				unset($active[$this->gateway]);

				update_option('M_active_gateways', $active);

				return true;
			} else {
				$active[$this->gateway] = true;

				update_option('M_active_gateways', $active);

				return true;
			}

		}

		function activate() {

			$active = get_option('M_active_gateways', array());

			if(array_key_exists($this->gateway, $active)) {
				return true;
			} else {
				$active[$this->gateway] = true;

				update_option('M_active_gateways', $active);

				return true;
			}

		}

		function deactivate() {

			$active = get_option('M_active_gateways', array());

			if(array_key_exists($this->gateway, $active)) {
				unset($active[$this->gateway]);

				update_option('M_active_gateways', $active);

				return true;
			} else {
				return true;
			}

		}

		function settings() {

			global $page, $action;

			?>
			<div class='wrap nosubsub'>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php echo __('Edit &quot;','membership') . esc_html($this->title) . __('&quot; settings','membership'); ?></h2>

				<form action='?page=<?php echo $page; ?>' method='post' name='gatewaysettingsform'>

					<input type='hidden' name='action' id='action' value='updated' />
					<input type='hidden' name='gateway' id='gateway' value='<?php echo $this->gateway; ?>' />
					<?php
					wp_nonce_field('updated-' . $this->gateway);

					do_action('M_gateways_settings_' . $this->gateway);

					?>

					<p class="submit">
					<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
					</p>
				</form>

			</div> <!-- wrap -->
			<?php

		}

		function update() {

			// default action is to return true
			return true;

		}

		function transactions() {

			global $page, $action, $type;

			wp_reset_vars( array('type') );

			if(empty($type)) $type = 'past';

			?>
			<div class='wrap'>
				<div class="icon32" id="icon-plugins"><br></div>
				<h2><?php echo esc_html($this->title) . __(' transactions','membership'); ?></h2>

				<ul class="subsubsub">
					<li><a href="<?php echo add_query_arg('type', 'past'); ?>" class="rbutton <?php if($type == 'past') echo 'current'; ?>"><?php  _e('Recent transactions', 'membership'); ?></a> | </li>
					<li><a href="<?php echo add_query_arg('type', 'pending'); ?>" class="rbutton <?php if($type == 'pending') echo 'current'; ?>"><?php  _e('Pending transactions', 'membership'); ?></a> | </li>
					<li><a href="<?php echo add_query_arg('type', 'future'); ?>" class="rbutton <?php if($type == 'future') echo 'current'; ?>"><?php  _e('Future transactions', 'membership'); ?></a></li>
				</ul>

				<?php
					do_action('M_gateways_transactions_' . $this->gateway, $type);
				?>
			</div> <!-- wrap -->
			<?php

		}

	}

}

function M_register_gateway($gateway, $class) {

	global $M_Gateways;

	if(!is_array($M_Gateways)) {
		$M_Gateways = array();
	}

	$M_Gateways[$gateway] = new $class;

}

?>