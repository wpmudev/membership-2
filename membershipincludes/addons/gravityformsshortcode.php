<?php
/*
Addon Name: Gravity Forms Shortcode
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