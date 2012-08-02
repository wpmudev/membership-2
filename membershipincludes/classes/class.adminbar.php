<?php
if(!class_exists('M_Adminbar')) {

	class M_Adminbar {


		function __construct() {

			add_action( 'add_admin_bar_menus', array( &$this, 'add_admin_bar_items' ) );

		}

		function M_Adminbar() {
			$this->__construct();
		}

		// Add the admin bar menu item
		function add_admin_bar_enabled_item( $wp_admin_bar ) {

			global $M_options;

			$active = M_get_membership_active();

			if($active == 'yes') {
				/*
				$title = __('Membership', 'membership') . " : <span style='color:green; text-shadow: 1px 1px 0 #000;'>" . __('Enabled', 'membership') . "</span>";
				$metatitle = __('Click to Disable the Membership protection', 'membership');
				$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=deactivate"), 'toggle-plugin');
				*/
			} else {
				$title = __('Membership', 'membership') . " : <span style='color:red; text-shadow: 1px 1px 0 #000;'>" . __('Disabled', 'membership') . "</span>";
				$metatitle = __('Click to Enable the Membership protection', 'membership');
				$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=activate"), 'toggle-plugin');
				$wp_admin_bar->add_menu( array(
					'id'        => 'membership',
					'parent'    => 'top-secondary',
					'title'     => $title,
					'href'      => $linkurl,
					'meta'      => array(
						'class'     => $class,
						'title'     => $metatitle,
					),
				) );
			}


			if($active == 'yes') {
				// If enabled
				/*
				$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=deactivate"), 'toggle-plugin');
				$wp_admin_bar->add_menu( array(
					'parent'    => 'membership',
					'id'        => 'membershipdisable',
					'title'     => __('Disable Membership', 'membership'),
					'href'      => $linkurl,
				) );
				*/
			} else {
				// If disabled
				$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=activate"), 'toggle-plugin');
				$wp_admin_bar->add_menu( array(
					'parent'    => 'membership',
					'id'        => 'membershipenable',
					'title'     => __('Enable Membership', 'membership'),
					'href'      => $linkurl,
				) );

			}


		}

		function add_admin_bar_items() {
			add_action( 'admin_bar_menu', array( &$this, 'add_admin_bar_enabled_item' ), 8 );
		}


	}

}

$M_Adminbar = new M_Adminbar();
?>