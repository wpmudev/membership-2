<?php
/**
 * @copyright Incsub (http://incsub.com/)
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
 *
*/

/**
 * Upgrade DB model.
 *
 * Manages DB upgrading.
 *
 * IMPORTANT: Make sure that the snapshot_data() function is up-to-date!
 * Things that are missed during back-up might be lost forever...
 *
 * @since 1.0.0
 *
 * @package Membership2
 * @subpackage Model
 */
class MS_Model_Upgrade extends MS_Model {

	/**
	 * Initialize upgrading check.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		self::update();

		MS_Factory::load( 'MS_Model_Upgrade' );

		self::maybe_restore();
		add_action( 'init', array( __CLASS__, 'maybe_reset' ) );

		do_action( 'ms_model_upgrade_init' );
	}

	/**
	 * Upgrade database.
	 *
	 * @since 1.0.0
	 * @param  bool $force Also execute update logic when version did not change.
	 */
	public static function update( $force = false ) {
		static $Done = false;

		if ( $Done && ! $force ) { return; }

		// Migration handler has its own valid_user check.
		self::check_migration_handler();

		// Updates are only triggered from Admin-Side by an Admin user.
		if ( ! self::valid_user() ) { return; }

		// Check for correct network-wide protection setup.
		self::check_network_setup();

		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$old_version = $settings->version; // Old: The version in DB.
		$new_version = MS_Plugin::instance()->version; // New: Version in file.

		$is_new_setup = empty( $old_version );

		// Compare current src version to DB version:
		// We only do UP-grades but no DOWN-grades!
		$version_changed = $old_version && version_compare( $old_version, $new_version, 'lt' );

		if ( $force || $version_changed ) {
			$Done = true;
			$msg = array();

			/*
			 * ----- General update logic, executed on every update ------------
			 */

			do_action(
				'ms_model_upgrade_before_update',
				$settings,
				$old_version,
				$new_version,
				$force
			);

			// Prepare the Update message.
			if ( ! $version_changed ) {
				$msg[] = sprintf(
					__( '<strong>Membership 2</strong> is set up for version %1$s!' , MS_TEXT_DOMAIN ),
					$new_version
				);
			} else {
				$msg[] = sprintf(
					__( '<strong>Membership 2</strong> was updated to version %1$s!' , MS_TEXT_DOMAIN ),
					$new_version
				);
			}

			// Every time the plugin is updated we clear the cache.
			MS_Factory::clear();

			// Create missing Membership pages.
			$new_pages = MS_Model_Pages::create_missing_pages();

			if ( ! empty( $new_pages ) ) {
				$msg[] = sprintf(
					__( 'New Membership pages created: "%1$s".', MS_TEXT_DOMAIN ),
					implode( '", "', $new_pages )
				);
			}

			// Remove an old version of Protected Content
			if ( $version_changed ) {
				self::remove_old_copy();
			}

			// Note: We do not create menu items on upgrade! Users might have
			// intentionally removed the items from the menu...

			/*
			 * ----- Version-Specific update logic -----------------------------
			 */

			// Upgrade from a 0.x version to 1.0.x or higher
			if ( version_compare( $old_version, '1.0.0.0', 'lt' ) ) {
				self::_upgrade_1_0_0_0();
			}

			/*
			 * ----- General update logic, executed on every update ------------
			 */

			$settings->version = $new_version;
			$settings->save();

			// Display a message after the page is reloaded.
			if ( ! $is_new_setup ) {
				lib2()->ui->admin_message( implode( '<br>', $msg ), '', '', 'ms-update' );
			}

			do_action(
				'ms_model_upgrade_after_update',
				$settings,
				$old_version,
				$new_version,
				$force
			);

			// This will reload the current page.
			MS_Plugin::flush_rewrite_rules();
		}
	}


	#
	#
	# ##########################################################################
	# ##########################################################################
	#
	#

	/**
	 * Upgrade from any 1.0.x version to a higher version.
	 */
	static private function _upgrade_1_0_0_0() {
		self::snapshot( '1.0.0.0' );

		/*
		 * Demo update process
		 */
		/*
		{
			lib2()->updates->add( 'wp_clear_scheduled_hook', 'ms_cron_check_membership_status' );
			lib2()->updates->add( 'wp_clear_scheduled_hook', 'ms_cron_process_communications' );
		}

		// Execute all queued actions!
		lib2()->updates->plugin( MS_TEXT_DOMAIN );
		lib2()->updates->execute();
		*/
	}

	#
	# ##########################################################################
	#

	/**
	 * Used when upgrading from Membership to M2. If both Membership and
	 * Protected Content are installed when upgrading then the old
	 * "protected-content" folder may survive the upgrade and needs to be
	 * manually removed.
	 *
	 * @since  2.0.0
	 */
	static private function remove_old_copy() {
		$new_dir = WP_PLUGIN_DIR . '/membership';
		$old_dir = WP_PLUGIN_DIR . '/protected-content';
		$old_plugins = array(
			'protected-content/protected-content.php',
			'membership/membershippremium.php',
		);
		$new_plugin = plugin_basename( MS_Plugin::instance()->file );

		// Make sure that the current plugin is the official M2 one.
		if ( false === strpos( MS_Plugin::instance()->dir, $new_dir ) ) {
			// Cancel: This plugin is not the official plugin (maybe a backup or beta version)
			return;
		}

		// 1. See if there is a old copy of the plugin directory. Delete it.
		if ( is_dir( $old_dir ) && is_file( $old_dir . '/protected-content.php' ) ) {
			// Looks like the old version of this plugin is still installed. Remove it.
			try {
				unlink( $old_dir . '/protected-content.php' );
				array_map( 'unlink', glob( "$old_dir/*.*" ) );
				rmdir( $old_dir );
			} catch( Exception $e ) {
				// Something went wrong when removing the old plugin.
			}
		}

		// 2. See if WordPress uses an old plugin in the DB. Update it.
		if ( is_multisite() ) {
			$global_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
			foreach ( $global_plugins as $key => $the_path ) {
				if ( in_array( $the_path, $old_plugins ) ) {
					$global_plugins[$key] = $new_plugin;
				}
			}
			update_site_option( 'active_sitewide_plugins', $global_plugins );
		}

		$site_plugins = (array) get_option( 'active_plugins', array() );
		foreach ( $site_plugins as $key => $the_path ) {
			if ( in_array( $the_path, $old_plugins ) ) {
				$site_plugins[$key] = $new_plugin;
			}
		}
		update_option( 'active_plugins', $site_plugins );
	}

	#
	#
	# ##########################################################################
	# ##########################################################################
	#
	#


	/**
	 * Creates a current DB Snapshot and clears all items from the update queue.
	 *
	 * @since  1.1.0.5
	 * @param  string $next_version Used for snapshot file name.
	 */
	static private function snapshot( $next_version ) {
		// Simply create a snapshot that we can restore later.
		lib2()->updates->plugin( MS_TEXT_DOMAIN );
		lib2()->updates->snapshot(
			'upgrade_' . str_replace( '.', '_', $next_version ),
			self::snapshot_data()
		);

		lib2()->updates->clear();
	}

	/**
	 * Takes an __PHP_Incomplete_Class and casts it to a stdClass object.
	 * All properties will be made public in this step.
	 *
	 * @since  1.1.0
	 * @param  object $object __PHP_Incomplete_Class
	 * @return object
	 */
	static public function fix_object( $object ) {
		// preg_replace_callback handler. Needed to calculate new key-length.
		$fix_key = create_function(
			'$matches',
			'return ":" . strlen( $matches[1] ) . ":\"" . $matches[1] . "\"";'
		);

		// Serialize the object to a string.
		$dump = serialize( $object );

		// Change class-type to 'stdClass'.
		$dump = preg_replace( '/^O:\d+:"[^"]++"/', 'O:8:"stdClass"', $dump );

		// Strip "private" and "protected" prefixes.
		$dump = preg_replace_callback( '/:\d+:"\0.*?\0([^"]+)"/', $fix_key, $dump );

		// Unserialize the modified object again.
		return unserialize( $dump );
	}

	/**
	 * Returns the option-keys and post-IDs that should be backed-up.
	 *
	 * @since  1.1.0.2
	 * @internal
	 *
	 * @return object Snapshot data-definition.
	 */
	static private function snapshot_data() {
		global $wpdb;
		$data = (object) array();

		// Options.
		$sql = "
			SELECT option_name
			FROM {$wpdb->options}
			WHERE
				option_name LIKE 'ms_addon_%'
				OR option_name LIKE 'ms_model_%'
				OR option_name LIKE 'ms_gateway_%'
		";
		$data->options = $wpdb->get_col( $sql );

		// Posts and Post-Meta
		$sql = "
			SELECT ID
			FROM {$wpdb->posts}
			WHERE
				post_type IN (
					'ms_membership',
					'ms_relationship',
					'ms_event',
					'ms_invoice',
					'ms_communication'
					'ms_coupon'
				)
		";
		$data->posts = $wpdb->get_col( $sql );

		return $data;
	}

	/**
	 * Completely whipe all Membership data from Database.
	 *
	 * Note: This function is not used currently...
	 *
	 * @since 1.0.0
	 */
	static private function cleanup_db() {
		global $wpdb;
		$sql = array();
		$trash_ids = array();

		// Delete membership meta-data from users.
		$users = MS_Model_Member::get_members( );
		foreach ( $users as $user ) {
			$user->delete_all_membership_usermeta();
			$user->save();
		}

		// Determine IDs of Membership Pages.
		$page_types = MS_Model_Pages::get_page_types();
		foreach ( $page_types as $type => $name ) {
			$page_id = MS_Model_Pages::get_setting( $type );
			$trash_ids[] = $page_id;
		}

		/**
		 * Delete all plugin settings.
		 * Settings are saved by classes that extend MS_Model_option
		 */
		foreach ( MS_Model_Gateway::get_gateways() as $option ) { $option->delete(); }
		MS_Factory::load( 'MS_Model_Addon' )->delete();
		MS_Factory::load( 'MS_Model_Pages' )->delete();
		MS_Factory::load( 'MS_Model_Settings' )->delete();

		/**
		 * Delete transient data
		 * Transient data is saved by classed that extend MS_Model_Transient
		 */
		MS_Factory::load( 'MS_Model_Simulate' )->delete();

		/**
		 * Delete all plugin content.
		 * Content is saved by classes that extend MS_Model_CustomPostType
		 */
		$ms_posttypes = array(
			MS_Model_Communication::get_post_type(),
			MS_Model_Event::get_post_type(),
			MS_Model_Invoice::get_post_type(),
			MS_Model_Membership::get_post_type(),
			MS_Model_Relationship::get_post_type(),
			MS_Addon_Coupon_Model::get_post_type(),
		);

		foreach ( $ms_posttypes as $type ) {
			$sql[] = $wpdb->prepare(
				"DELETE FROM $wpdb->posts WHERE post_type = %s;",
				$type
			);
		}

		// Remove orphaned post-metadata.
		$sql[] = "DELETE FROM $wpdb->postmeta WHERE NOT EXISTS (SELECT 1 FROM wp_posts tmp WHERE tmp.ID = post_id);";

		// Clear all WP transient cache.
		$sql[] = "DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%';";

		foreach ( $sql as $s ) {
			$wpdb->query( $s );
		}

		// Move Membership pages to trash.
		foreach ( $trash_ids as $id ) {
			wp_delete_post( $id, true );
		}

		// Clear all data from WP Object cache.
		wp_cache_flush();

		// Redirect to the main page.
		wp_safe_redirect( MS_Controller_Plugin::get_admin_url() );
		exit;
	}

	/**
	 * Makes sure that network-wide protection works by ensuring that the plugin
	 * is also network-activated.
	 *
	 * @since  2.0.0
	 */
	static private function check_network_setup() {
		static $Network_Checked = false;

		if ( ! $Network_Checked ) {
			$Network_Checked = true;

			// This is only relevant for multisite installations.
			if ( ! is_multisite() ) { return; }

			if ( MS_Plugin::is_network_wide() ) {
				// This function does not exist in network admin
				if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
					require_once ABSPATH . '/wp-admin/includes/plugin.php';
				}

				if ( ! is_plugin_active_for_network( MS_PLUGIN ) ) {
					activate_plugin( MS_PLUGIN, null, true );
					lib2()->ui->admin_message(
						__( 'Info: Membership2 is not activated network-wide', MS_TEXT_DOMAIN )
					);
				}
			}
		}
	}

	/**
	 * This function checks if we arrive here after a migration, i.e. after the
	 * user updated Membership Premium or Protected Content to M2
	 *
	 * @since  1.0.0
	 */
	static private function check_migration_handler() {
		$migrate = '';
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		// Check Migration from old Membership plugin.
		$option_m1 = '_wpmudev_update_to_m2';
		$option_m1_free = '_wporg_update_to_m2';
		$from_m1 = get_site_option( $option_m1 );
		$from_m1_free = get_site_option( $option_m1_free );

		if ( $from_m1 || $from_m1_free ) {
			$migrate = 'm1';

			delete_site_option( $option_m1 );
			delete_site_option( $option_m1_free );
			$settings->set_special_view( 'MS_View_MigrationM1' );
		}

		$view = $settings->get_special_view();

		if ( $migrate || 'MS_View_MigrationM1' == $view ) {
			if ( ! empty( $_REQUEST['skip_import'] ) ) {
				$settings->reset_special_view();
				wp_safe_redirect(
					esc_url_raw( remove_query_arg( array( 'skip_import' ) ) )
				);
				exit;
			} else {
				$settings->set_special_view( 'MS_View_MigrationM1' );

				// Complete the migration when the import is done.
				add_action(
					'ms_import_action_done',
					array( 'MS_Model_Settings', 'reset_special_view' )
				);
			}
		}
	}

	/**
	 * Returns a secure token to trigger advanced admin actions like db-reset
	 * or restoring a snapshot.
	 *
	 * - Only one token is valid at any given time.
	 * - Each token has a timeout of max. 120 seconds.
	 * - Each token can be used once only.
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $action Like a nonce, this is the action to execute.
	 * @return array Intended usage: add_query_param( $token, $url )
	 */
	static public function get_token( $action ) {
		if ( ! is_user_logged_in() ) { return array(); }
		if ( ! is_admin() ) { return array(); }

		$one_time_key = uniqid();
		MS_Factory::set_transient( 'ms_one_time_key-' . $action, $one_time_key, 120 );

		// Token is valid for 86 seconds because of usage of date('B')
		$plain = $action . '-' . date( 'B' ) . ':' . get_current_user_id() . '-' . $one_time_key;
		$token = array( 'ms_token' => wp_create_nonce( $plain ) );
		return $token;
	}

	/**
	 * Verfies the admin token in the $_GET collection
	 *
	 * $_GET['ms_token'] must match the current ms_token
	 * $_POST['confirm'] must have value 'yes'
	 *
	 * @since  1.1.0
	 * @internal
	 *
	 * @param  string $action Like a nonce, this is the action to execute.
	 * @return bool
	 */
	static private function verify_token( $action ) {
		if ( ! self::valid_user() ) { return false; }

		if ( empty( $_GET['ms_token'] ) ) { return false; }
		$get_token = $_GET['ms_token'];

		if ( empty( $_POST['confirm'] ) ) { return false; }
		if ( 'yes' != $_POST['confirm'] ) { return false; }

		$one_time_key = MS_Factory::get_transient( 'ms_one_time_key-' . $action );
		MS_Factory::delete_transient( 'ms_one_time_key-' . $action );
		if ( empty( $one_time_key ) ) { return false; }

		// We verify the current and the previous beat
		$plain_token_1 = $action . '-' . date( 'B' ) . ':' . get_current_user_id() . '-' . $one_time_key;
		$plain_token_2 = $action . '-' . ( date( 'B' ) - 1 ) . ':' . get_current_user_id() . '-' . $one_time_key;

		if ( wp_verify_nonce( $get_token, $plain_token_1 ) ) { return true; }
		if ( wp_verify_nonce( $get_token, $plain_token_2 ) ) { return true; }

		return false;
	}

	/**
	 * Verifies the following conditions:
	 * - Current user is logged in and has admin permissions
	 * - The request is an wp-admin request
	 * - The request is not an Ajax call
	 *
	 * @since  1.1.0.4
	 * @return bool True if all conditions are true
	 */
	static private function valid_user() {
		if ( ! is_user_logged_in() ) { return false; }
		if ( ! is_admin() ) { return false; }
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) { return false; }
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) { return false; }
		if ( ! MS_Model_Member::is_admin_user() ) { return false; }

		return true;
	}

	/**
	 * Checks if valid reset-instructions are present. If yes, then whipe the
	 * plugin settings.
	 *
	 * @since  1.1.0
	 */
	static public function maybe_reset() {
		static $Reset_Done = false;

		if ( ! $Reset_Done ) {
			$Reset_Done = true;
			if ( ! self::verify_token( 'reset' ) ) { return false; }

			self::cleanup_db();
			$msg = __( 'Your Membership2 data was reset!', MS_TEXT_DOMAIN );
			lib2()->ui->admin_message( $msg );

			wp_safe_redirect( MS_Controller_Plugin::get_admin_url( 'MENU_SLUG' ) );
			exit;
		}
	}

	/**
	 * Checks if valid restore-options are specified. If they are, the snapshot
	 * will be restored.
	 *
	 * @since  1.1.0.4
	 */
	static private function maybe_restore() {
		static $Restore_Done = false;

		if ( ! $Restore_Done ) {
			$Restore_Done = true;
			if ( empty( $_POST['restore_snapshot'] ) ) { return false; }
			$snapshot = $_POST['restore_snapshot'];

			if ( ! self::verify_token( 'restore' ) ) { return false; }

			lib2()->updates->plugin( MS_TEXT_DOMAIN );
			if ( lib2()->updates->restore( $snapshot ) ) {
				printf(
					'<p>' .
					__( 'The Membership2 Snapshot "%s" was restored!', MS_TEXT_DOMAIN ) .
					'</p>',
					$snapshot
				);

				printf(
					'<p><b>' .
					__( 'To prevent auto-updating the DB again we stop here!', MS_TEXT_DOMAIN ) .
					'</b></p>'
				);

				printf(
					'<p>' .
					__( 'You now have the option to <br />(A) downgrade the plugin to an earlier version via FTP or <br />(B) to %sre-run the upgrade process%s.', MS_TEXT_DOMAIN ) .
					'</p>',
					'<a href="' . MS_Controller_Plugin::get_admin_url( 'MENU_SLUG' ) . '">',
					'</a>'
				);

				wp_die( '', 'Snapshot Restored' );
			}
		}
	}

};
