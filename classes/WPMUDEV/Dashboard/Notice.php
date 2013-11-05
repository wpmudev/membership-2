<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

class WPMUDEV_Dashboard_Notice {

	var $version = '2.0';

	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	public function init() {
		if ( !class_exists( 'WPMUDEV_Update_Notifications' ) && current_user_can( 'install_plugins' ) && is_admin() ) {
			remove_action( 'admin_notices', 'wdp_un_check', 5 );
			remove_action( 'network_admin_notices', 'wdp_un_check', 5 );
			if ( file_exists( WP_PLUGIN_DIR . '/wpmudev-updates/update-notifications.php' ) ) {
				add_action( 'all_admin_notices', array( &$this, 'activate_notice' ), 5 );
			} else {
				add_action( 'all_admin_notices', array( &$this, 'install_notice' ), 5 );
				add_filter( 'plugins_api', array( &$this, 'filter_plugin_info' ), 10, 3 );
			}
		}
	}

	public function filter_plugin_info( $res, $action, $args ) {
		global $wp_version;
		$cur_wp_version = preg_replace( '/-.*$/', '', $wp_version );

		if ( $action == 'plugin_information' && strpos( $args->slug, 'install_wpmudev_dash' ) !== false ) {
			$res = new stdClass;
			$res->name = 'WPMU DEV Dashboard';
			$res->slug = 'wpmu-dev-dashboard';
			$res->version = '';
			$res->rating = 100;
			$res->homepage = 'http://premium.wpmudev.org/project/wpmu-dev-dashboard/';
			$res->download_link = "http://premium.wpmudev.org/wdp-un.php?action=install_wpmudev_dash";
			$res->tested = $cur_wp_version;

			return $res;
		}

		return false;
	}

	public function auto_install_url() {
		$function = is_multisite() ? 'network_admin_url' : 'admin_url';
		return wp_nonce_url( $function( "update.php?action=install-plugin&plugin=install_wpmudev_dash" ), "install-plugin_install_wpmudev_dash" );
	}

	public function activate_url() {
		$function = is_multisite() ? 'network_admin_url' : 'admin_url';
		return wp_nonce_url( $function( 'plugins.php?action=activate&plugin=wpmudev-updates%2Fupdate-notifications.php' ), 'activate-plugin_wpmudev-updates/update-notifications.php' );
	}

	public function install_notice() {
		echo '<div class="error fade"><p>' . sprintf( __( 'Easily get updates, support, and one-click WPMU DEV plugin/theme installations right from in your dashboard - <strong><a href="%s" title="Install Now &raquo;">install the free WPMU DEV Dashboard plugin</a></strong>. &nbsp;&nbsp;&nbsp;<small><a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">(find out more)</a></small>', 'wpmudev' ), $this->auto_install_url() ) . '</p></div>';
	}

	public function activate_notice() {
		echo '<div class="updated fade"><p>' . sprintf( __( 'Updates, Support, Premium Plugins, Community - <strong><a href="%s" title="Activate Now &raquo;">activate the WPMU DEV Dashboard plugin now</a></strong>.', 'wpmudev' ), $this->activate_url() ) . '</a></p></div>';
	}

}