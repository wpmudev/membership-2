<?php

class Test extends M_Gateway {

	var $gateway = 'test';
	var $title = 'Test Gateway';

	function Test() {
		parent::M_Gateway();
	}

}

M_register_gateway('test', 'Test');

?>