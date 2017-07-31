<?php
/**
 * Plugin Name: Membership 2
 * Plugin URI:  https://wordpress.org/plugins/membership
 * Version:     4.0.1.3
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
		$plugin_name = 'Membership 2 (Free)';
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
	define( 'MS_PLUGIN_VERSION', '4.0.1.3' );

	/**
	 * Free or pro plugin?
	 * This only affects some display settings, it does not really lock/unlock
	 * any premium features...
	 *
	 * @since  1.0.3.2
	 */
	define( 'MS_IS_PRO', false );

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

	// Free-version configuration
	$cta_label = __( 'Get Members!', 'membership2' );
	$drip_param = 'Membership';


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
}

/**
 * This is a hack to prevent cookie issue in IE11 and EDGE
 * Need to refactor in later
 *
 * @since 1.0.2.8
 * @todo Move this code into a different class. Simply call here via MS_TheClass::check_ms_ajax()
 */
if ( isset( $_REQUEST['ms_ajax'] ) ) {
	if ( 1 == $_REQUEST['ms_ajax'] ) {
		add_action( 'wp_ajax_ms_login', 'ms_ajax_login' );
		add_action( 'wp_ajax_nopriv_ms_login', 'ms_ajax_login' );

		function ms_ajax_login() {
			$resp = array();
			check_ajax_referer( 'ms-ajax-login' );

			if ( empty( $_POST['username'] ) && ! empty( $_POST['log'] ) ) {
				$_POST['username'] = $_POST['log'];
			}
			if ( empty( $_POST['password'] ) && ! empty( $_POST['pwd'] ) ) {
				$_POST['password'] = $_POST['pwd'];
			}
			if ( empty( $_POST['remember'] ) && ! empty( $_POST['rememberme'] ) ) {
				$_POST['remember'] = $_POST['rememberme'];
			}

			// Nonce is checked, get the POST data and sign user on
			$info = array(
				'user_login' 	=> $_POST['username'],
				'user_password' => $_POST['password'],
				'remember' 		=> (bool) isset( $_POST['remember'] ) ? $_POST['remember'] : false,
			);

			$user_signon = wp_signon( $info, false );

			if ( is_wp_error( $user_signon ) ) {
				$resp['error'] = __( 'Wrong username or password', 'membership2' );
			} else {
				$resp['loggedin'] = true;
				$resp['success'] = __( 'Logging in...', 'membership2' );

				/**
				 * Allows a custom redirection after login.
				 * Empty value will use the default redirect option of the login form.
				 */

				// TODO: These filters are never called!
				//       This code is too early to allow any other plugin to register a filter handler...
				$enforce = false;
				if ( isset( $_POST['redirect_to'] ) ) {
					$resp['redirect'] = apply_filters(
						'ms-ajax-login-redirect',
						$_POST['redirect_to'],
						$user_signon->ID
					);
				} else {
					$resp['redirect'] = apply_filters(
						'ms_url_after_login',
						$_POST['redirect_to'],
						$enforce
					);
				}

				//checking domains
				if ( is_plugin_active_for_network( 'domain-mapping/domain-mapping.php' ) ) {
					$url1 = parse_url( home_url() );
					$url2 = parse_url( $resp['redirect'] );
					if (strpos($url2['host'], $url1['host']) === false) {
						//add 'auth' param for set cookie when mapped domains
						$resp['redirect'] = add_query_arg( array('auth' => wp_generate_auth_cookie( $user_signon->ID, time() + MINUTE_IN_SECONDS )), $resp['redirect']);
					}
				}
			}

			echo json_encode( $resp );
			exit();
		}
	}
}


function membership2_init_old_app() {
	require_once 'app_old/membership.php';
}

function membership2_is_old_app() {
	return true != get_option( 'm2_use_new_version' );
}

function membership2_use_m2() {
	update_option( 'm2_use_new_version', true );
}

if ( ! defined( 'IS_UNIT_TEST' ) && membership2_is_old_app() ) {
	membership2_init_old_app();
	return;
}

membership2_init_app();
