<?php

class amazon extends M_Gateway {

	var $gateway = 'amazon';
	var $title = 'Amazon payments';

	function amazon() {
		parent::M_Gateway();
	}

}

M_register_gateway('amazon', 'amazon');

?>