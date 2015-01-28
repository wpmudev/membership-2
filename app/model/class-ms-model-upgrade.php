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
 * @since 1.0.0
 *
 * @package Membership
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
		global $wpdb;

		if ( $Done ) { return; }

		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$old_version = $settings->version; // Old: The version in DB.
		$new_version = MS_Plugin::instance()->version; // New: Version in file.

		$is_new_setup = empty( $old_version );

		// Compare current src version to DB version
		$version_changed = version_compare( $old_version, $new_version, '!=' );

		self::maybe_reset();

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
					__( '<strong>Protected Content</strong> is set up for version %1$s!' , MS_TEXT_DOMAIN ),
					$new_version
				);
			} else {
				$msg[] = sprintf(
					__( '<strong>Protected Content</strong> was updated to version %1$s!' , MS_TEXT_DOMAIN ),
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

			// Note: We do not create menu items on upgrade! Users might have
			// intentionally removed the items from the menu...

			/*
			 * ----- Version-Specific update logic -----------------------------
			 */

			// Upgrade logic from 1.0.0.0
			if ( version_compare( '1.0.0.0', $new_version, '=' ) ) {
				$args = array();
				$args['post_parent__not_in'] = array( 0 );
				$memberships = MS_Model_Membership::get_memberships( $args );

				foreach ( $memberships as $membership ) {
					$parent = MS_Factory::load( 'MS_Model_Membership', $membership->parent_id );
					if ( ! $parent->is_valid() ) {
						$membership->delete();
					}
				}
			}

			// Upgrade logic to 1.1.0.0
			if ( version_compare( '1.1.0.0', $new_version, '=' ) ) {
				// Add the 'special' meta key to all memberships
				$query = new WP_Query(
					array(
						'post_type' => MS_Model_Membership::$POST_TYPE,
						'post_status' => 'any',
						'nopaging' => true,
						'meta_query' => array(
							array(
								'key' => 'special',
								'compare' => 'NOT EXISTS',
								'value' => '',
							),
						),
					)
				);
				foreach ( $query->get_posts() as $post ) {
					update_post_meta( $post->ID, 'special', '' );
				}

				// Change the flag of the base membership
				$sql = "
					SELECT ID
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} m_type ON m_type.post_id = p.ID
					WHERE
						p.post_type = %s
						AND m_type.meta_key = 'protected_content'
						AND m_type.meta_value = '1'
				";
				$sql = $wpdb->prepare( $sql, MS_Model_Membership::$POST_TYPE );
				$item = $wpdb->get_results( $sql );
				$base = array_shift( $item );

				if ( ! empty( $base ) ) {
					update_post_meta( $base->ID, 'special', 'protected_content' );
				}

				// Rename the Add-On variable
				$data = get_option( 'MS_Model_Addon' );
				if ( ! isset( $data['active'] ) && isset( $data['addons'] ) ) {
					$data['active'] = $data['addons'];
					unset( $data['addons'] );
					update_option( 'MS_Model_Addon', $data );
				}
			}

			/*
			 * ----- General update logic, executed on every update ------------
			 */

			$settings->version = $new_version;
			$settings->save();

			// Display a message after the page is reloaded.
			if ( ! $is_new_setup ) {
				WDev()->message( implode( '<br>', $msg ), '', '', 'ms-update' );
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
			MS_Model_Communication::$POST_TYPE,
			MS_Model_Event::$POST_TYPE,
			MS_Model_Invoice::$POST_TYPE,
			MS_Model_Membership::$POST_TYPE,
			MS_Model_Relationship::$POST_TYPE,
			MS_Addon_Coupon_Model::$POST_TYPE,
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
		wp_safe_redirect( admin_url( 'admin.php?page=protected-content' ) );
		exit;
	}

	/**
	 * Returns a secure token to trigger db-cleanup (wipe all settings!)
	 *
	 * - Only one token is valid at any given time.
	 * - Each token has a timeout of max. 120 seconds.
	 * - Each token can be used once only.
	 *
	 * @since  1.1.0
	 * @return array Intended usage: add_query_param( $token, $url )
	 */
	static public function get_reset_token() {
		if ( ! is_user_logged_in() ) { return array(); }
		if ( ! is_admin() ) { return array(); }

		$one_time_key = uniqid();
		set_transient( 'ms_one_time_key-reset', $one_time_key, 120 );

		// Token is valid for 86 seconds because of usage of date('B')
		$plain = 'reset-' . date( 'B' ) . ':' . get_current_user_id() . '-' . $one_time_key;
		$token = array( 'reset_token' => wp_create_nonce( $plain ) );
		return $token;
	}

	/**
	 * Verfies the reset token in the $_GET collection
	 *
	 * $_GET['reset_token'] must match the current reset_token
	 * $_POST['confirm'] must have value 'reset'
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	static private function verify_reset_token() {
		if ( ! is_user_logged_in() ) { return false; }
		if ( ! is_admin() ) { return false; }

		if ( ! isset( $_GET['reset_token'] ) ) { return false; }
		$get_token = $_GET['reset_token'];

		if ( ! isset( $_POST['confirm'] ) ) { return false; }
		if ( 'reset' != $_POST['confirm'] ) { return false; }

		$one_time_key = get_transient( 'ms_one_time_key-reset' );
		delete_transient( 'ms_one_time_key-reset' );
		if ( empty( $one_time_key ) ) { return false; }

		// We verify the current and the previous beat
		$plain_token_1 = 'reset-' . date( 'B' ) . ':' . get_current_user_id() . '-' . $one_time_key;
		$plain_token_2 = 'reset-' . ( date( 'B' ) - 1 ) . ':' . get_current_user_id() . '-' . $one_time_key;

		if ( wp_verify_nonce( $get_token, $plain_token_1 ) ) { return true; }
		if ( wp_verify_nonce( $get_token, $plain_token_2 ) ) { return true; }

		return false;
	}

	/**
	 * Checks if valid reset-instructions are present. If yes, then whipe the
	 * plugin settings.
	 *
	 * @since  1.1.0
	 */
	static private function maybe_reset() {
		static $Done = false;

		if ( ! $Done ) {
			$Done = true;
			if ( self::verify_reset_token() ) {
				self::cleanup_db();
				WDev()->message( 'Your Protected Content data was reset!' );
				wp_safe_redirect( admin_url( 'admin.php?page=protected-content' ) );
				exit;
			}
		}
	}

};
