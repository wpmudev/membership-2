<?php
///////////////////////////////////////////////////////////////////////////
/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
	add_action( 'admin_notices', 'wdp_un_check', 5 );
	add_action( 'network_admin_notices', 'wdp_un_check', 5 );

	function wdp_un_check() {
 		if ( class_exists( 'WPMUDEV_Update_Notifications' ) )
 			return;

		if ( $delay = get_site_option( 'un_delay' ) ) {
			if ( $delay <= time() && current_user_can( 'install_plugins' ) )
				echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
		} else {
			update_site_option( 'un_delay', strtotime( "+1 week" ) );
		}
	}
}
/* --------------------------------------------------------------------- */

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

function membership_upload_path() {

	// Get the fallback file location first
	$path = trailingslashit(get_option('home')) . get_option('upload_path');
	// if an override exists, then use that.
	$path = get_option('fileupload_url', $path);
	// return whatever we have left.
	return $path;

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

// Functions
if(!function_exists('M_register_rule')) {
	function M_register_rule($rule_name, $class_name, $section) {

		global $M_Rules, $M_SectionRules;

		if(!is_array($M_Rules)) {
			$M_Rules = array();
		}

		if(!is_array($M_SectionRules)) {
			$M_SectionRules = array();
		}

		if(class_exists($class_name)) {
			$M_SectionRules[$section][$rule_name] = $class_name;
			$M_Rules[$rule_name] = $class_name;
		} else {
			return false;
		}

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

// Add the admin bar menu item
function M_add_admin_bar_enabled_item( $wp_admin_bar ) {

	global $M_options;

	$active = M_get_membership_active();

	if($active == 'yes') {
		/*
		$title = __('Membership', 'membership') . " : <span style='color:green; text-shadow: 1px 1px 0 #000;'>" . __('Enabled', 'membership') . "</span>";
		$metatitle = __('Click to Disable the Membership protection', 'membership');
		$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=deactivate"), 'toggle-plugin');
		*/
	} else {
		$title = __('Membership', 'membership') . " : <span style='color:red; text-shadow: 1px 1px 0 #000;'>" . __('Disabled', 'membership') . "</span>";
		$metatitle = __('Click to Enable the Membership protection', 'membership');
		$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=activate"), 'toggle-plugin');
		$wp_admin_bar->add_menu( array(
			'id'        => 'membership',
			'parent'    => 'top-secondary',
			'title'     => $title,
			'href'      => $linkurl,
			'meta'      => array(
				'class'     => $class,
				'title'     => $metatitle,
			),
		) );
	}


	if($active == 'yes') {
		// If enabled
		/*
		$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=deactivate"), 'toggle-plugin');
		$wp_admin_bar->add_menu( array(
			'parent'    => 'membership',
			'id'        => 'membershipdisable',
			'title'     => __('Disable Membership', 'membership'),
			'href'      => $linkurl,
		) );
		*/
	} else {
		// If disabled
		$linkurl = wp_nonce_url(admin_url("admin.php?page=membership&amp;action=activate"), 'toggle-plugin');
		$wp_admin_bar->add_menu( array(
			'parent'    => 'membership',
			'id'        => 'membershipenable',
			'title'     => __('Enable Membership', 'membership'),
			'href'      => $linkurl,
		) );

	}


}

function M_add_admin_bar_items() {
	add_action( 'admin_bar_menu', 'M_add_admin_bar_enabled_item', 8 );
}
add_action( 'add_admin_bar_menus', 'M_add_admin_bar_items' );

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
?>