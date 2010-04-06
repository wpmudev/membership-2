<?php

class paypalpro extends M_Gateway {

	var $gateway = 'paypalpro';
	var $title = 'PayPal Pro';

	function paypalpro() {
		parent::M_Gateway();
	}

}

M_register_gateway('paypalpro', 'paypalpro');

?>