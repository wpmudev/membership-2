<?php
// We initially need to make sure that this function exists, and if not then include the file that has it.
if ( !function_exists( 'is_plugin_active_for_network' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

// Addons loading code

function get_membership_addons() {
	if ( is_dir( membership_dir('membershipincludes/addons') ) ) {
		if ( $dh = opendir( membership_dir('membershipincludes/addons') ) ) {
			$mem_addons = array();
			while ( ( $addon = readdir( $dh ) ) !== false )
				if ( substr( $addon, -4 ) == '.php' )
					$mem_addons[] = $addon;
			closedir( $dh );
			sort( $mem_addons );

			return apply_filters('membership_available_addons', $mem_addons);
		}
	}

	return false;

}

function load_membership_addons() {

	$addons = get_option('membership_activated_addons', array());

	if ( is_dir( membership_dir('membershipincludes/addons') ) ) {
		if ( $dh = opendir( membership_dir('membershipincludes/addons') ) ) {
			$mem_addons = array();
			while ( ( $addon = readdir( $dh ) ) !== false )
				if ( substr( $addon, -4 ) == '.php' )
					$mem_addons[] = $addon;
			closedir( $dh );
			sort( $mem_addons );

			$mem_addons = apply_filters('membership_available_addons', $mem_addons);

			foreach( $mem_addons as $mem_addon ) {
				if(in_array($mem_addon, $addons)) {
					include_once( membership_dir('membershipincludes/addons/' . $mem_addon) );
				}
			}
		}
	}

	do_action( 'membership_addons_loaded' );
}

function load_all_membership_addons() {
	if ( is_dir( membership_dir('membershipincludes/addons') ) ) {
		if ( $dh = opendir( membership_dir('membershipincludes/addons') ) ) {
			$mem_addons = array();
			while ( ( $addon = readdir( $dh ) ) !== false )
				if ( substr( $addon, -4 ) == '.php' )
					$mem_addons[] = $addon;
			closedir( $dh );
			sort( $mem_addons );

			$mem_addons = apply_filters('membership_available_addons', $mem_addons);

			foreach( $mem_addons as $mem_addon )
				include_once( membership_dir('membershipincludes/addons/' . $mem_addon) );
		}
	}

	do_action( 'membership_addons_loaded' );
}

// Gateways loading code

function get_membership_gateways() {
	if ( is_dir( membership_dir('membershipincludes/gateways') ) ) {
		if ( $dh = opendir( membership_dir('membershipincludes/gateways') ) ) {
			$mem_gateways = array();
			while ( ( $gateway = readdir( $dh ) ) !== false )
				if ( substr( $gateway, -4 ) == '.php' )
					$mem_gateways[] = $gateway;
			closedir( $dh );
			sort( $mem_gateways );

			return apply_filters('membership_available_gateways', $mem_gateways);
		}
	}

	return false;

}

function load_membership_gateways() {

	$gateways = get_option('membership_activated_gateways', array());

	if ( is_dir( membership_dir('membershipincludes/gateways') ) ) {
		if ( $dh = opendir( membership_dir('membershipincludes/gateways') ) ) {
			$mem_gateways = array();
			while ( ( $gateway = readdir( $dh ) ) !== false )
				if ( substr( $gateway, -4 ) == '.php' )
					$mem_gateways[] = $gateway;
			closedir( $dh );
			sort( $mem_gateways );

			$mem_gateways = apply_filters('membership_available_gateways', $mem_gateways);

			foreach( $mem_gateways as $mem_gateway ) {
				$check_gateway = str_replace('gateway.', '', str_replace('.php', '', $mem_gateway));
				if(in_array($check_gateway, $gateways)) {
					include_once( membership_dir('membershipincludes/gateways/' . $mem_gateway) );
				}
			}
		}
	}

	do_action( 'membership_gateways_loaded' );
}

function load_all_membership_gateways() {
	if ( is_dir( membership_dir('membershipincludes/gateways') ) ) {
		if ( $dh = opendir( membership_dir('membershipincludes/gateways') ) ) {
			$mem_gateways = array();
			while ( ( $gateway = readdir( $dh ) ) !== false )
				if ( substr( $gateway, -4 ) == '.php' )
					$mem_gateways[] = $gateway;
			closedir( $dh );
			sort( $mem_gateways );

			$mem_gateways = apply_filters('membership_available_gateways', $mem_gateways);

			foreach( $mem_gateways as $mem_gateway )
				include_once( membership_dir('membershipincludes/gateways/' . $mem_gateway) );
		}
	}

	do_action( 'membership_gateways_loaded' );
}

function set_membership_url($base) {

	global $M_membership_url;

	if(defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename($base))) {
		$M_membership_url = trailingslashit(WPMU_PLUGIN_URL);
	} elseif(defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/membership/' . basename($base))) {
		$M_membership_url = trailingslashit(WP_PLUGIN_URL . '/membership');
	} else {
		$M_membership_url = trailingslashit(WP_PLUGIN_URL . '/membership');
	}

	if ( is_ssl() ) {
		$M_membership_url = preg_replace('#http://#i', 'https://', $M_membership_url);
	}
}

function set_membership_dir($base) {

	global $M_membership_dir;

	if(defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename($base))) {
		$M_membership_dir = trailingslashit(WPMU_PLUGIN_DIR);
	} elseif(defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/membership/' . basename($base))) {
		$M_membership_dir = trailingslashit(WP_PLUGIN_DIR . '/membership');
	} else {
		$M_membership_dir = trailingslashit(WP_PLUGIN_DIR . '/membership');
	}


}

function membership_url($extended) {

	global $M_membership_url;

	return $M_membership_url . $extended;

}

function membership_dir($extended) {

	global $M_membership_dir;

	return $M_membership_dir . $extended;


}

/*
Function based on the function wp_upload_dir, which we can't use here because it insists on creating a directory at the end.
*/
function membership_wp_upload_url() {
	global $switched;

	$siteurl = get_option( 'siteurl' );
	$upload_path = get_option( 'upload_path' );
	$upload_path = trim($upload_path);

	$main_override = is_multisite() && defined( 'MULTISITE' ) && is_main_site();

	if ( empty($upload_path) ) {
		$dir = WP_CONTENT_DIR . '/uploads';
	} else {
		$dir = $upload_path;
		if ( 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos($dir, ABSPATH) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $dir );
		}
	}

	if ( !$url = get_option( 'upload_url_path' ) ) {
		if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) )
			$url = WP_CONTENT_URL . '/uploads';
		else
			$url = trailingslashit( $siteurl ) . $upload_path;
	}

	if ( defined('UPLOADS') && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	if ( is_multisite() && !$main_override && (!isset( $switched ) || $switched === false ) ) {
		if ( defined( 'BLOGUPLOADDIR' ) ) {
			$dir = untrailingslashit( BLOGUPLOADDIR );
		}
		if ( defined( 'UPLOADS' ) ) {
			$url = str_replace( UPLOADS, 'files', $url );
		}
	}

	$bdir = $dir;
	$burl = $url;

	return trailingslashit($burl);
}

function membership_wp_upload_dir() {
	global $switched;

	$siteurl = get_option( 'siteurl' );
	$upload_path = get_option( 'upload_path' );
	$upload_path = trim($upload_path);

	$main_override = is_multisite() && defined( 'MULTISITE' ) && is_main_site();

	if ( empty($upload_path) ) {
		$dir = WP_CONTENT_DIR . '/uploads';
	} else {
		$dir = $upload_path;
		if ( 'wp-content/uploads' == $upload_path ) {
			$dir = WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos($dir, ABSPATH) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			$dir = path_join( ABSPATH, $dir );
		}
	}

	if ( !$url = get_option( 'upload_url_path' ) ) {
		if ( empty($upload_path) || ( 'wp-content/uploads' == $upload_path ) || ( $upload_path == $dir ) )
			$url = WP_CONTENT_URL . '/uploads';
		else
			$url = trailingslashit( $siteurl ) . $upload_path;
	}

	if ( defined('UPLOADS') && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		$dir = ABSPATH . UPLOADS;
		$url = trailingslashit( $siteurl ) . UPLOADS;
	}

	if ( is_multisite() && !$main_override && ( !isset( $switched ) || $switched === false ) ) {
		if ( defined( 'BLOGUPLOADDIR' ) )
			$dir = untrailingslashit(BLOGUPLOADDIR);
		$url = str_replace( UPLOADS, 'files', $url );
	}

	$bdir = $dir;
	$burl = $url;

	return trailingslashit($bdir);
}

function membership_upload_path() {

	$path = get_option('membership_fileupload_url', '');

	if(empty($path)) {
		return membership_wp_upload_dir();
	} else {
		return trailingslashit($path);
	}

}

function membership_upload_url() {

	$path = get_option('membership_fileupload_url', '');

	if(empty($path)) {
		return membership_wp_upload_url();
	} else {
		return trailingslashit($path);
	}

}

function membership_is_active($userdata, $password) {

	global $wpdb;

	// Checks if this member is an active one.
	if(!empty($userdata) && !is_wp_error($userdata)) {
		$id = $userdata->ID;

		if(get_user_meta($id, $wpdb->prefix . 'membership_active', true) == 'no') {
			return new WP_Error('member_inactive', __('Sorry, this account is not active.', 'membership'));
		}

	}

	return $userdata;

}

add_filter('wp_authenticate_user', 'membership_is_active', 30, 2);

function membership_assign_subscription($user_id) {

	global $M_options;

	if(!empty($M_options['freeusersubscription'])) {
		$member = new M_Membership($user_id);
		if($member) {
			$member->create_subscription($M_options['freeusersubscription']);
		}
	}

}

add_action('user_register', 'membership_assign_subscription', 30);

function membership_db_prefix(&$wpdb, $table, $useprefix = true) {

	if($useprefix) {
		$membership_prefix = 'm_';
	} else {
		$membership_prefix = '';
	}

	if( defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES == true ) {
		if(!empty($wpdb->base_prefix)) {
			return $wpdb->base_prefix . $membership_prefix . $table;
		} else {
			return $wpdb->prefix . $membership_prefix . $table;
		}
	} else {
		return $wpdb->prefix . $membership_prefix . $table;
	}

}

// Template based functions
function current_member() {

	$user = wp_get_current_user();
	$member = new M_Membership( $user->ID );

	if(!empty($member)) {
		return $member;
	} else {
		return false;
	}

}

function current_user_is_member() {

	$user = wp_get_current_user();
	$member = new M_Membership( $user->ID );

	if(!empty($member)) {
		return $member->is_member();
	} else {
		return false;
	}

}

function current_user_has_subscription() {

	$user = wp_get_current_user();
	$member = new M_Membership( $user->ID );

	if(!empty($member)) {
		return $member->has_subscription();
	} else {
		return false;
	}

}

function current_user_on_level( $level_id ) {

	$user = wp_get_current_user();
	$member = new M_Membership( $user->ID );

	if(!empty($member)) {
		return $member->on_level( $level_id, true );
	} else {
		return false;
	}

}

function current_user_on_subscription( $sub_id ) {

	$user = wp_get_current_user();
	$member = new M_Membership( $user->ID );

	if(!empty($member)) {
		return $member->on_sub( $sub_id );
	} else {
		return false;
	}

}

function M_remove_old_plugin( $plugins ) {

	if(array_key_exists('membership/membership.php', $plugins) && !in_array('membership.php', (array) array_map('basename', wp_get_active_and_valid_plugins() ))) {
		unset($plugins['membership/membership.php']);
	}

	return $plugins;
}

function get_last_transaction_for_user_and_sub($user_id, $sub_id) {

	global $wpdb;

	$sql = $wpdb->prepare( "SELECT * FROM " . membership_db_prefix($wpdb, 'subscription_transaction') . " WHERE transaction_user_ID = %d and transaction_subscription_ID = %d ORDER BY transaction_stamp DESC LIMIT 0,1", $user_id, $sub_id );

	return $wpdb->get_row( $sql );

}

function M_get_membership_active() {

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('get_blog_option')) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
			$membershipactive = get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, 'membership_active', 'no');
			if(function_exists('restore_current_blog')) {
				restore_current_blog();
			}
		} else {
			$membershipactive = get_option('membership_active', 'no');
		}
	} else {
		$membershipactive = get_option('membership_active', 'no');
	}

	return $membershipactive;

}

// Pages permalink functions
function M_get_registration_permalink() {
	global $M_options;

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('get_blog_option')) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
			$link = get_permalink( $M_options['registration_page'] );
			if(function_exists('restore_current_blog')) {
				restore_current_blog();
			}
		} else {
			$link = get_permalink( $M_options['registration_page'] );
		}
	} else {
		$link = get_permalink( $M_options['registration_page'] );
	}

	return $link;
}

function M_get_subscription_permalink() {
	global $M_options;

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('get_blog_option')) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
			$link = get_permalink( $M_options['subscriptions_page'] );
			if(function_exists('restore_current_blog')) {
				restore_current_blog();
			}
		} else {
			$link = get_permalink( $M_options['subscriptions_page'] );
		}
	} else {
		$link = get_permalink( $M_options['subscriptions_page'] );
	}

	return $link;
}

function M_get_account_permalink() {
	global $M_options;

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('get_blog_option')) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
			$link = get_permalink( $M_options['account_page'] );
			if(function_exists('restore_current_blog')) {
				restore_current_blog();
			}
		} else {
			$link = get_permalink( $M_options['account_page'] );
		}
	} else {
		$link = get_permalink( $M_options['account_page'] );
	}

	return $link;
}

function M_get_noaccess_permalink() {
	global $M_options;

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('get_blog_option')) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
			$link = get_permalink( $M_options['nocontent_page'] );
			if(function_exists('restore_current_blog')) {
				restore_current_blog();
			}
		} else {
			$link = get_permalink( $M_options['nocontent_page'] );
		}
	} else {
		$link = get_permalink( $M_options['nocontent_page'] );
	}

	return $link;
}

function M_get_registrationcompleted_permalink() {
	global $M_options;

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('get_blog_option')) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}
			$link = get_permalink( $M_options['registrationcompleted_page'] );
			if(function_exists('restore_current_blog')) {
				restore_current_blog();
			}
		} else {
			$link = get_permalink( $M_options['registrationcompleted_page'] );
		}
	} else {
		$link = get_permalink( $M_options['registrationcompleted_page'] );
	}

	return $link;
}

function M_get_returnurl_permalink() {
	global $M_options;

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('get_blog_option')) {
			if(function_exists('switch_to_blog')) {
				switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
			}

			$link = get_permalink( $M_options['registrationcompleted_page'] );

			if(function_exists('restore_current_blog')) {
				restore_current_blog();
			}
		} else {
			$link = get_permalink( $M_options['registrationcompleted_page'] );
		}
	} else {
		$link = get_permalink( $M_options['registrationcompleted_page'] );
	}

	return $link;
}

function M_activation_function() {
	// This function is called when the plugin is activated.
	global $user;

	if(empty($user) || !method_exists($user, 'has_cap')) {
		$user = wp_get_current_user();
	}

	if($user->user_login == MEMBERSHIP_MASTER_ADMIN && !$user->has_cap('membershipadmin')) {
		$user->add_cap('membershipadmin');
	}
}

function M_normalize_shortcode ($string) {

	// Function from http://ie2.php.net/manual/en/function.strtr.php#90925 to remove accented characters for shortcodes

    $table = array(
        'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
        'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
        'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
        'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
        'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
    );

    return sanitize_title_with_dashes('level-' . strtolower(strtr($string, $table)));
}

// Function and filter to strip the decimal places from japanese amounts
function M_strip_decimal_places( $amount ) {

	$amount = number_format($amount,0,'.','');

	return $amount;
}
add_filter('membership_amount_JPY', 'M_strip_decimal_places');

function M_get_option($key, $default = false) {

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('get_blog_option')) {
			return get_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, $key, $default);
		} else {
			return get_option( $key , $default);
		}
	} else {
		return get_option( $key, $default);
	}

}

function M_update_option($key, $value) {

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('update_blog_option')) {
			return update_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, $key, $value);
		} else {
			return update_option( $key , $value);
		}
	} else {
		return update_option( $key, $value);
	}

}

function M_delete_option($key) {

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true) {
		if(function_exists('delete_blog_option')) {
			return delete_blog_option(MEMBERSHIP_GLOBAL_MAINSITE, $key );
		} else {
			return delete_option( $key );
		}
	} else {
		return delete_option( $key );
	}

}
function membership_get_current_coupon() {

	if(isset($_POST['coupon_code']) && !empty($_POST['coupon_code'])) {
		// Check that it is a valid coupon code - otherwise we'll return an error.
		$coupon = new M_Coupon( $_POST['coupon_code'] );

		return $coupon;
	} else {
		return false;
	}

}

function membership_price_in_text( $pricing ) {

	global $M_options;

	// Run a check to see if this is a fully free subscription
	if( !empty($pricing) ) {
		$free = true;
		foreach($pricing as $key => $price) {
			if(!empty($price['amount']) && $price['amount'] > 0 ) {
				$free = false;
			}
		}
	}
	// If it is then we just return nothing
	if( $free ) {
		return false;
	}

	// Otherwise we process
	$pd = array();
	$count = 1;

	if(empty($M_options['paymentcurrency'])) {
		$M_options['paymentcurrency'] = '&USD;';
	}

	switch( $M_options['paymentcurrency'] ) {
		case "USD": $cur = "$";
					break;

		case "GBP":	$cur = "&pound;";
					break;

		case "EUR":	$cur = "&euro;";
					break;

		default:	$cur = $M_options['paymentcurrency'];
					break;
	}

	foreach((array) $pricing as $key => $price) {

		switch($price['type']) {

			case 'finite':	if(empty($price['amount'])) $price['amount'] = '0';

							if($price['amount'] == '0') {
								if($count == 1) {
									$pd[$count] = sprintf( __('%s for ','membership'), __('free','membership') );

									switch( strtoupper($price['unit']) ) {
										case 'D':	if( $price['period'] == 1 ) {
														$pd[$count] .=  $price['period'] . __(' day','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' days','membership');
													}
													break;

										case 'W':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' week','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' weeks','membership');
													}
													break;

										case 'M':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' month','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' months','membership');
													}
													break;

										case 'Y':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' year','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' years','membership');
													}
													break;
									}

								} else {
									// Or last finite is going to be the end of the subscription payments
									$pd[$count] = sprintf( __('and then %s for ','membership'), __('free','membership') );

									switch( strtoupper($price['unit']) ) {
										case 'D':	if( $price['period'] == 1 ) {
														$pd[$count] .=  $price['period'] . __(' day','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' days','membership');
													}
													break;

										case 'W':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' week','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' weeks','membership');
													}
													break;

										case 'M':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' month','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' months','membership');
													}
													break;

										case 'Y':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' year','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' years','membership');
													}
													break;
									}

								}
							} else {
								if($count == 1) {
									$pd[$count] = sprintf( __('%s for ','membership'), $cur . apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.' , '')) );

									switch( strtoupper($price['unit']) ) {
										case 'D':	if( $price['period'] == 1 ) {
														$pd[$count] .=  $price['period'] . __(' day','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' days','membership');
													}
													break;

										case 'W':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' week','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' weeks','membership');
													}
													break;

										case 'M':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' month','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' months','membership');
													}
													break;

										case 'Y':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' year','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' years','membership');
													}
													break;
									}

								} else {
									// Or last finite is going to be the end of the subscription payments
									$pd[$count] = sprintf( __('and then %s for ','membership'), $cur . apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.' , '')) );

									switch( strtoupper($price['unit']) ) {
										case 'D':	if( $price['period'] == 1 ) {
														$pd[$count] .=  $price['period'] . __(' day','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' days','membership');
													}
													break;

										case 'W':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' week','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' weeks','membership');
													}
													break;

										case 'M':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' month','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' months','membership');
													}
													break;

										case 'Y':	if( $price['period'] == 1 ) {
														$pd[$count] .= $price['period'] . __(' year','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' years','membership');
													}
													break;
									}

								}
							}


							$count++;
							break;

			case 'indefinite':
							if(empty($price['amount'])) $price['amount'] = '0';

							if($price['amount'] == '0') {

								if($count == 1) {
									$pd[$count] = sprintf( __('%s ','membership'), __('free','membership') );
								} else {
									$pd[$count] = sprintf( __('and then %s ','membership'), __('free','membership') );
								}

							} else {

								if($count == 1) {
									$pd[$count] = sprintf( __('%s ','membership'), $cur . apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.' , '')) );
								} else {
									$pd[$count] = sprintf( __('and then %s ','membership'), $cur . apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.' , '')) );
								}

							}
							$count++;
							break;
			case 'serial':
							if(empty($price['amount'])) $price['amount'] = '0';

							if($price['amount'] == '0') {

								if($count == 1) {
									$pd[$count] = sprintf( __('%s every ','membership'), __('free','membership') );

									switch( strtoupper($price['unit']) ) {
										case 'D':	if( $price['period'] == 1 ) {
														$pd[$count] .=  __('day','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' days','membership');
													}
													break;

										case 'W':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('week','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' weeks','membership');
													}
													break;

										case 'M':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('month','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' months','membership');
													}
													break;

										case 'Y':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('year','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' years','membership');
													}
													break;
									}
								} else {
									$pd[$count] = sprintf( __('and then %s every ','membership'), __('free','membership') );

									switch( strtoupper($price['unit']) ) {
										case 'D':	if( $price['period'] == 1 ) {
														$pd[$count] .=  __('day','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' days','membership');
													}
													break;

										case 'W':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('week','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' weeks','membership');
													}
													break;

										case 'M':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('month','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' months','membership');
													}
													break;

										case 'Y':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('year','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' years','membership');
													}
													break;
									}
								}

							} else {

								if($count == 1) {
									$pd[$count] = sprintf( __('%s every ','membership'), $cur . apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.' , '')) );

									switch( strtoupper($price['unit']) ) {
										case 'D':	if( $price['period'] == 1 ) {
														$pd[$count] .=  __('day','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' days','membership');
													}
													break;

										case 'W':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('week','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' weeks','membership');
													}
													break;

										case 'M':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('month','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' months','membership');
													}
													break;

										case 'Y':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('year','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' years','membership');
													}
													break;
									}
								} else {
									$pd[$count] = sprintf( __('and then %s every ','membership'), $cur . apply_filters('membership_amount_' . $M_options['paymentcurrency'], number_format($price['amount'], 2, '.' , '')) );

									switch( strtoupper($price['unit']) ) {
										case 'D':	if( $price['period'] == 1 ) {
														$pd[$count] .=  __('day','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' days','membership');
													}
													break;

										case 'W':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('week','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' weeks','membership');
													}
													break;

										case 'M':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('month','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' months','membership');
													}
													break;

										case 'Y':	if( $price['period'] == 1 ) {
														$pd[$count] .= __('year','membership');
													} else {
														$pd[$count] .= $price['period'] . __(' years','membership');
													}
													break;
									}
								}

							}
							$count++;
							break;
		}
	}

	return ucfirst(implode(', ' , $pd));

}

function membership_redirect_to_protected() {

	global $M_options;

	if(defined('MEMBERSHIP_GLOBAL_TABLES') && MEMBERSHIP_GLOBAL_TABLES === true ) {
		if(function_exists('switch_to_blog')) {
			switch_to_blog(MEMBERSHIP_GLOBAL_MAINSITE);
		}
	}

	$url = get_permalink( (int) $M_options['nocontent_page'] );
	$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	if($url != $current_url && !headers_sent()) {
		wp_safe_redirect( $url );
		exit;
	} else {

	}

}

function membership_check_expression_match( $host, $list ) {

	$list = array_map('strtolower', array_map('trim', $list ));

	//reg expression match
	$matchstring = "";
	foreach($list as $key => $value) {
		if($matchstring != "") $matchstring .= "|";

		if( stripos($value, '\/') ) {
			$matchstring .= stripcslashes($value);
		} else {
			$matchstring .= $value;
		}

	}

	// switched to using a character that won't be in a url as the start and end markers
	$matchstring = "#" . $matchstring . "#i";

	if(preg_match($matchstring, $host, $matches) ) {
		return true;
	} else {
		return false;
	}
}

function membership_debug_log( $message ) {

	if( defined('MEMBERSHIP_DEBUG') && MEMBERSHIP_DEBUG == true ) {

		if( defined('MEMBERSHIP_DEBUG_LEVEL') && MEMBERSHIP_DEBUG_LEVEL == 'adv' ) {
			$message .= ' - ' . print_r( debug_backtrace(), true );
		}

		// We have debugging switched on
		switch( MEMBERSHIP_DEBUG_METHOD ) {
			case 'log': error_log( $message );
						break;

			case 'email':
						if( is_email( MEMBERSHIP_DEBUG_EMAIL ) ) {
							if( function_exists('wp_mail') ) {
								wp_mail( MEMBERSHIP_DEBUG_EMAIL, __('Membership Debug Message','membership'), $message );
							} else {
								error_log( $message , 1, MEMBERSHIP_DEBUG_EMAIL );
							}
						}
						break;

			default:
						do_action( 'membership_debug', MEMBERSHIP_DEBUG_METHOD, $message );
						do_action( 'membership_debug_' . MEMBERSHIP_DEBUG_METHOD, $message );
						break;
		}
	}

}