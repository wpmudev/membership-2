<?php

if(!class_exists('WP_Membership')) {

	class WP_Membership extends WP_User {

		function WP_Membership( $id, $name = '' ) {
			parent::WP_User( $id, $name = '' );
		}


	}


}

?>