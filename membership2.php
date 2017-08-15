<?php
/**
 * Plugin Name: Membership 2 Pro
 * Plugin URI:  https://premium.wpmudev.org/project/membership/
 * Version:     1.0.4
 * Build Stamp: BUILDTIME
 * Description: The most powerful, easy to use and flexible membership plugin for WordPress sites available.
 * Author:      WPMU DEV
 * Author URI:  http://premium.wpmudev.org/
 * WDP ID:      1003656
 * License:     GPL2
 * License URI: http://opensource.org/licenses/GPL-2.0
 * Text Domain: membership2
 *
 * @package Membership2
 */

/**
 * Copyright notice
 *
 * @copyright Incsub (http://incsub.com/)
 *
 * Authors: Philipp Stracker, Fabio Jun Onishi, Victor Ivanov, Jack Kitterhing, Rheinard Korf, Ashok Kumar Nath
 * Contributors: Joji Mori, Patrick Cohen
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 */

/**
 * Initializes constants and create the main plugin object MS_Plugin.
 * This function is called *instantly* when this file was loaded.
 *
 * @since  1.0.0
 */

function membership2_init_app() {
	if ( defined( 'MS_PLUGIN' ) ) {
		$plugin_name = 'Membership 2 Pro';
		if ( is_admin() ) {
			// Can happen in Multisite installs where a sub-site has activated the
			// plugin and then the plugin is also activated in network-admin.
			printf(
				'<div class="notice error"><p><strong>%s</strong>: %s</p></div>',
				sprintf(
					esc_html__( 'Could not load the plugin %s, because another version of the plugin is already loaded', 'membership2' ),
					$plugin_name
				),
				esc_html( MS_PLUGIN . ' (v' . MS_PLUGIN_VERSION . ')' )
			);
		}
		return;
	}

	/**
	 * Plugin version
	 *
	 * @since  1.0.0
	 */
	define( 'MS_PLUGIN_VERSION', '1.0.4' );

	/**
	 * Free or pro plugin?
	 * This only affects some display settings, it does not really lock/unlock
	 * any premium features...
	 *
	 * @since  1.0.3.2
	 */
	define( 'MS_IS_PRO', true );

	/**
	 * Plugin main-file.
	 *
	 * @since  1.0.3.0
	 */
	define( 'MS_PLUGIN_FILE', __FILE__ );

	/**
	 * Plugin identifier constant.
	 *
	 * @since  1.0.0
	 */
	define( 'MS_PLUGIN', plugin_basename( __FILE__ ) );

	/**
	 * Plugin name dir constant.
	 *
	 * @since  1.0.0
	 */
	define( 'MS_PLUGIN_NAME', dirname( MS_PLUGIN ) );

	/**
	 * Plugin name dir constant.
	 *
	 * @since  1.0.3
	 */
	define( 'MS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

	/**
	 * Plugin base dir
	 */
	define( 'MS_PLUGIN_BASE_DIR', dirname( __FILE__ ) );

	$externals = array(
		dirname( __FILE__ ) . '/lib/wpmu-lib/core.php',
		dirname( __FILE__ ) . '/lib/wdev-frash/module.php',
	);

	// Pro-Only configuration.
	$cta_label = false;
	$drip_param = false;
	$externals[] = dirname( __FILE__ ) . '/lib/wpmudev-dashboard/wpmudev-dash-notification.php';

	// WPMUDEV Dashboard.
	global $wpmudev_notices;
	$wpmudev_notices[] = array(
		'id' => 1003656,
		'name' => 'Membership 2 Pro',
		'screens' => array(
			'toplevel_page_membership-2',
			'membership-2_page_membership2-members',
			'membership-2_page_membership2-setup',
			'membership-2_page_membership2-billing',
			'membership-2_page_membership2-coupons',
			'membership-2_page_membership2-addon',
			'membership-2_page_membership2-settings',
		),
	);

	foreach ( $externals as $path ) {
		if ( file_exists( $path ) ) { require_once $path; }
	}

	// Register the current plugin, for pro and free plugins!
	do_action(
		'wdev-register-plugin',
		/*             Plugin ID */ plugin_basename( __FILE__ ),
		/*          Plugin Title */ 'Membership 2',
		/* https://wordpress.org */ '/plugins/membership/',
		/*      Email Button CTA */ $cta_label,
		/*  getdrip Plugin param */ $drip_param
	);

	/**
	 * Prepare rating message.
	 *
	 * @return string Message to display.
	 */
	function _membership2_rating_message() {
		return __( "Hey %s, you've been using %s for a while now, and we hope you're happy with it.", 'membership2' ) .
			'<br />' .
			__( "We're constantly working to improve our plugins, and it helps a lot when members just like you share feedback!", 'membership2' );
	}
	add_filter(
		'wdev-rating-message-' . plugin_basename( __FILE__ ),
		'_membership2_rating_message'
	);

	/**
	 * Translation.
	 *
	 * Tip:
	 *   The translation files must have the filename [TEXT-DOMAIN]-[locale].mo
	 *   Example: membership2-en_EN.mo  /  membership2-de_DE.mo
	 */
	function _membership2_translate_plugin() {
		load_plugin_textdomain(
			'membership2',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}
	add_action( 'plugins_loaded', '_membership2_translate_plugin' );

	if ( (defined( 'WP_DEBUG' ) && WP_DEBUG) || (defined( 'WDEV_DEBUG' ) && WDEV_DEBUG) ) {
		// Load development/testing code before the plugin is initialized.
		$testfile = dirname( __FILE__ ) . '/tests/wp/init.php';
		if ( file_exists( $testfile ) ) { include $testfile; }
	}


	include MS_PLUGIN_BASE_DIR . '/app/ms-loader.php';

	if ( is_dir( MS_PLUGIN_BASE_DIR . '/premium' ) ) {
		include MS_PLUGIN_BASE_DIR . '/premium/ms-premium-loader.php';

		MS_Premium_Loader::instance();
	}

	// Initialize the M2 class loader.
	$loader = new MS_Loader();
	/**
	 * Create an instance of the plugin object.
	 *
	 * This is the primary entry point for the Membership plugin.
	 *
	 * @since  1.0.0
	 */
	MS_Plugin::instance();


	/**
	 * Ajax Logins
	 *
	 * @since 1.0.4
	 */
	MS_Auth::check_ms_ajax();
}

membership2_init_app();
