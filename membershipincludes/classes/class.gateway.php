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

		function settingsform() {

		}

		function updatesettings() {

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