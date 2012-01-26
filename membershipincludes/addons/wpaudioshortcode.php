<?php
/*
Addon Name: WPAudio shortcode
Description: DEPRECIATED : you can now place wpaudio in the Admin only shortcode setting <a href='admin.php?page=membershipoptions&tab=posts'>here</a>.
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