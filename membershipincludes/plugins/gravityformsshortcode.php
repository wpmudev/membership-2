<?php
// Membership plugin add-on: Gravity forms shortcode
// Version: 1.0
// Author: Barry
// Description:
// This fix adds in the gravity forms shortcode to the admin area of the membership plugin
// Provides an alternative to this previous fix: http://premium.wpmudev.org/forums/topic/membership-plugin-and-gravity-forms

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