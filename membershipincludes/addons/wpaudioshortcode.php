<?php
/*
Plugin Name: WPAudio shortcode
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

add_action('init', 'M_setup_wpaudio_shortcode');

function M_setup_wpaudio_shortcode() {
	if(is_admin()) {
		add_shortcode('wpaudio', 'M_fakewpaudioshortcode');
	}
}

function M_fakewpaudioshortcode($atts, $content = null, $code = "") {
	return '';
}

?>