<?php
/*
*	Simple coupons addon
*
*
*/

class M_Membership_coupons {


	function __construct() {

		add_action('membership_add_menu_items_bottom', array(&$this, 'add_menu') );

	}

	function M_Membership_coupons() {
		$this->__construct();
	}

	function add_menu() {
		add_submenu_page('membership', __('Membership Coupons','membership'), __('Edit Coupons','membership'), 'membershipadmin', "membershipcoupons", array(&$this,'handle_coupons_panel'));

	}

	function handle_coupons_updates() {

	}

	function handle_coupons_panel() {

	}


}

$M_coupons = new M_Membership_coupons();

?>