<?php
/*
Addon Name: Contacts form 7 shortcode
Description: DEPRECIATED : you can now place contact-form in the Admin only shortcode setting <a href='admin.php?page=membershipoptions&tab=posts'>here</a>.
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