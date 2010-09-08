<?php
// Membership plugin add-on: Contacts form 7 shortcode
// Version: 1.0
// Author: Barry
// Description:
// This fix adds in the Contacts form 7 shortcode to the admin area of the membership plugin

add_action('init', 'M_setup_contactform_shortcode');

function M_setup_contactform_shortcode() {
	if(is_admin()) {
		add_shortcode('contact-form', 'M_fakecontactformshortcode');
	}
}

function M_fakecontactformshortcode($atts, $content = null, $code = "") {
	return '';
}

?>