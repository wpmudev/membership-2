<?php
function load_membership_plugins() {
	if ( is_dir( plugin_dir_path(__FILE__) . 'membershipincludes/plugins' ) ) {
		if ( $dh = opendir( plugin_dir_path(__FILE__) . 'membershipincludes/plugins' ) ) {
			$mem_plugins = array ();
			while ( ( $plugin = readdir( $dh ) ) !== false )
				if ( substr( $plugin, -4 ) == '.php' )
					$mem_plugins[] = $plugin;
			closedir( $dh );
			sort( $mem_plugins );
			foreach( $mem_plugins as $mem_plugin )
				include_once( plugin_dir_path(__FILE__) . 'membershipincludes/plugins/' . $mem_plugin );
		}
	}
}

function membership_url($base) {

	if(defined('WPMU_PLUGIN_URL') && defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename($base))) {
		return trailingslashit(WPMU_PLUGIN_URL);
	} elseif(defined('WP_PLUGIN_URL') && defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/membership/' . basename($base))) {
		return trailingslashit(WP_PLUGIN_URL . '/membership');
	}

}

function membership_dir($base) {

	if(defined('WPMU_PLUGIN_DIR') && file_exists(WPMU_PLUGIN_DIR . '/' . basename($base))) {
		return trailingslashit(WPMU_PLUGIN_URL);
	} elseif(defined('WP_PLUGIN_DIR') && file_exists(WP_PLUGIN_DIR . '/membership/' . basename($base))) {
		return trailingslashit(WP_PLUGIN_DIR . '/membership');
	}

}
?>