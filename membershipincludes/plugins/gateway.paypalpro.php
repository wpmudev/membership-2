<?php

class paypalpro extends M_Gateway {

	var $gateway = 'paypalpro';
	var $title = 'PayPal Pro';

	function paypalpro() {
		parent::M_Gateway();

		add_action('M_gateways_settings_' . $this->gateway, array(&$this, 'mysettings'));
	}

	function mysettings() {

		echo "<p>" . __('The settings for the PayPal Pro payment gateway will be here.','membership') . "</p>";

	}

	function update() {

		// default action is to return true
		return true;

	}

}

M_register_gateway('paypalpro', 'paypalpro');

?>