<?php

class paypalexpress extends M_Gateway {

	var $gateway = 'paypalexpress';
	var $title = 'PayPal Express';

	function paypalexpress() {
		parent::M_Gateway();
	}

}

M_register_gateway('paypalexpress', 'paypalexpress');

?>