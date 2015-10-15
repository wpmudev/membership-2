<?php
/**
 * This file initializes testing or development features for M2 plugin.
 *
 * It is not included in the public plugin archive, so it can only be used when
 * you have access to the code repository!
 *
 * Note that this file does not always use best-practices and the code is not
 * intended as official example/demo code, but is kept simple and short - it's
 * internal code after all ;)
 *
 * @since  1.0.2.4
 *
 * @package Membership2
 * @subpackage Controller
 */

add_filter( 'ms_controller_help_get_tabs', '__dev_m2_help_tabs' );
function __dev_m2_help_tabs( $tabs ) {
	return $tabs;

	// Not finished yet.
	$base_url = 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG . '-help&tab=';
	$tabs['helpers'] = array(
		'title' => 'Test: Helpers',
		'url' => $base_url . 'dev_helpers',
	);

	add_filter( 'ms_view_help_render_callback', '__dev_m2_view_tab_helpers', 10, 3 );
	return $tabs;
}

function __dev_m2_view_tab_helpers( $callback, $tab, $data ) {
	return $callback;
}