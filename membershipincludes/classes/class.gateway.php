<?php

if(!class_exists('M_Gateway')) {

	class M_Gateway {

		var $db;

		// Class Identification
		var $gateway = 'Not Set';
		var $title = 'Not Set';

		function M_Gateway() {

			global $wpdb;

			$this->db =& $wpdb;

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