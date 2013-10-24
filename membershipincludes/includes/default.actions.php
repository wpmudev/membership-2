<?php

function M_create_subscription( $user_id, $sub_id, $gateway ) {

	$member = new M_Membership( $user_id );

	if( !empty($member) && method_exists( $member, 'create_subscription') ) {
		$member->create_subscription( $sub_id, $gateway );
	}

}
add_action( 'membership_create_subscription', 'M_create_subscription', 10, 3 );

function M_drop_subscription( $user_id, $sub_id ) {

	$member = new M_Membership( $user_id );

	if( !empty($member) && method_exists( $member, 'drop_subscription') ) {
		$member->drop_subscription( $sub_id );
	}

}
add_action( 'membership_drop_subscription', 'M_drop_subscription', 10, 2 );

/*
* Redirect actions
*/

function membership_set_positive_no_redirect() {

	global $membership_redirect_to_protected, $membership_first_url_group;

	if( $membership_first_url_group == false ) {
		$membership_first_url_group = 'positive';
	}

	membership_debug_log( __('I have found the url in my positive list - so I am going to set the system to not redirect.','membership') );

	do_action('membership_set_redirect', false );

}
add_action('membership_set_positive_no_redirect', 'membership_set_positive_no_redirect');

function membership_set_negative_redirect() {

	global $membership_redirect_to_protected, $membership_first_url_group;

	if( $membership_first_url_group == false ) {
		$membership_first_url_group = 'negative';
	}

	membership_debug_log( __('I have found the url in my negative list - so I am going to set the system to redirect.','membership') );

	do_action('membership_set_redirect', true );

}
add_action('membership_set_negative_redirect', 'membership_set_negative_redirect');

function membership_set_redirect( $setto = true ) {

	global $wp_filter;

	//print_r($wp_filter['pre_get_posts']);
	//die();

	global $membership_redirect_to_protected;

	$membership_redirect_to_protected = $setto;

	if($setto) {
		membership_debug_log( __('I am setting the redirect flag to : true','membership') );
	} else {
		membership_debug_log( __('I am setting the redirect flag to : false','membership') );
	}
}
add_action('membership_set_redirect', 'membership_set_redirect');