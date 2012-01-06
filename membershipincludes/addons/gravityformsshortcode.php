<?php
/*
Addon Name: Gravity Forms Shortcode
Description: If you want to protect the Gravity forms shortcode then activate this to ensure it shows in the shortcodes list.
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

add_action('init', 'M_setup_gravityforms_shortcode');

function M_setup_gravityforms_shortcode() {
	if(is_admin()) {
		add_shortcode('gravityform', 'M_fakegravityshortcode');
	}
}

function M_fakegravityshortcode($atts, $content = null, $code = "") {
	return '';
}

?>