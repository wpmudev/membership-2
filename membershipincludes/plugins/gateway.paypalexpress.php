<?php

class paypalexpress extends M_Gateway {

	var $gateway = 'paypalexpress';
	var $title = 'PayPal Express';

	function paypalexpress() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));
		add_action('M_gateways_transactions_' . $this->gateway, array(&$this, 'mytransactions'));
	}

	function mysettings() {
		echo "<p>" . __('Placeholder : The settings for the PayPal Express payment gateway will be here.','membership') . "</p>";
	}

	function mytransactions($type = 'past') {

		echo "type";

	}

	function update() {

		// default action is to return true
		return true;

	}

}

M_register_gateway('paypalexpress', 'paypalexpress');

?>