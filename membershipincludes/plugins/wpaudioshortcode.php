<?php
// Membership plugin add-on: WPAudio shortcode
// Version: 1.0
// Author: Barry
// Description:
// This fix adds in the WPAudio shortcode to the admin area of the membership plugin

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