<?php
/*
Addon Name: Contacts form 7 shortcode
Description: If you are using the Contacts form 7 plugin then enable this to protect the form shortcode.
Author: Barry (Incsub)
Author URI: http://caffeinatedb.com
*/

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