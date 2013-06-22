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

function M_expire_subscription( $user_id, $sub_id ) {

	$member = new M_Membership( $user_id );

	if( !empty($member) && method_exists( $member, 'expire_subscription') ) {
		$member->expire_subscription( $sub_id );
	}

}
add_action( 'membership_expire_subscription', 'M_expire_subscription', 10, 2 );

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

function membership_excluded_urls( $exclude ) {

	global $M_options;

	if( !is_array($exclude) ) {
		if(!empty($exclude)) {
			$exclude = array( $exclude );
		} else {
			$exclude = array();
		}
	}

	if(defined('MEMBERSHIP_EXCLUDE_HOMEPAGE_FROM_PROTECTION') && MEMBERSHIP_EXCLUDE_HOMEPAGE_FROM_PROTECTION == true) {
		$exclude[] = get_home_url();
		$exclude[] = trailingslashit(get_home_url());
	}

	if(!empty($M_options['registration_page'])) {
		$exclude[] = get_permalink( (int) $M_options['registration_page'] );
		$exclude[] = untrailingslashit(get_permalink( (int) $M_options['registration_page'] ));
	}

	if(!empty($M_options['account_page'])) {
		$exclude[] = get_permalink( (int) $M_options['account_page'] );
		$exclude[] = untrailingslashit(get_permalink( (int) $M_options['account_page'] ));
	}

	if(!empty($M_options['nocontent_page'])) {
		$exclude[] = get_permalink( (int) $M_options['nocontent_page'] );
		$exclude[] = untrailingslashit(get_permalink( (int) $M_options['nocontent_page'] ));
	}

	if(!empty($wp_query->query_vars['protectedfile']) && !$forceviewing) {
		$exclude[] = $host;
		$exclude[] = untrailingslashit($host);
	}

	//print_r($exclude);

	return $exclude;

}
add_filter( 'membership_excluded_urls', 'membership_excluded_urls' );

?>