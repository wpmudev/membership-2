<?php

class paypalexpress extends M_Gateway {

	var $gateway = 'paypalexpress';
	var $title = 'PayPal Express';

	function paypalexpress() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));
	}

	function mysettings() {

		echo "<p>" . __('Placeholder : The settings for the PayPal Express payment gateway will be here.','membership') . "</p>";

	}

	function update() {

		// default action is to return true
		return true;

	}

}

M_register_gateway('paypalexpress', 'paypalexpress');

?>