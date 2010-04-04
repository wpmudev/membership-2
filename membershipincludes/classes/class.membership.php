<?php

if(!class_exists('M_Membership')) {

	class M_Membership extends WP_User {

		function M_Membership( $id, $name = '' ) {
			parent::WP_User( $id, $name = '' );
		}



		function active_member() {
			return true;
		}

		function get_subscriptions() {

		}

		function get_levels() {

		}

		// Member operations

		function toggle_activation() {

		}

		function move_subscription() {

		}

		function move_level() {

		}

		function add_level() {

		}

		function delete_level() {

		}

		function delete_subscription() {

		}


	}


}

?>