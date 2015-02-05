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

		if ( $Done ) { return; }

		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$old_version = $settings->version; // Old: The version in DB.
		$new_version = MS_Plugin::instance()->version; // New: Version in file.

		$is_new_setup = empty( $old_version );

		// Compare current src version to DB version:
		// We only do UP-grades but no DOWN-grades!
		$version_changed = version_compare( $old_version, $new_version, 'lt' );

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

			// Upgrade from a 0.x version to 1.0.x or higher
			if ( version_compare( $old_version, '1.0.0.0', 'lt' ) ) {
				self::_upgrade_0_x();
			}

			// Upgrade from any 1.0.x version to 1.1.x or higher
			if ( version_compare( $old_version, '1.1.0.0', 'lt' ) ) {
				self::_upgrade_1_0_x();
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
	 * Upgrade from any 0.x version to a higher version.
	 */
	static private function _upgrade_0_x() {
		$args = array();
		$args['post_parent__not_in'] = array( 0 );
		$memberships = MS_Model_Membership::get_memberships( $args );

		// Delete orphans (junk-data introduced by early bug)
		foreach ( $memberships as $membership ) {
			$parent = MS_Factory::load( 'MS_Model_Membership', $membership->parent_id );
			if ( ! $parent->is_valid() ) {
				$membership->delete();
			}
		}
	}

	/**
	 * Upgrade from any 1.0.x version to a higher version.
	 */
	static private function _upgrade_1_0_x() {
		global $wpdb;

		/*
		 * Add-ons
		 *
		 * 1. The key-name 'addon' changed to 'active'
		 */
		{
			$data = get_option( 'MS_Model_Addon' );
			// 1.
			if ( ! isset( $data['active'] ) && isset( $data['addons'] ) ) {
				$data['active'] = $data['addons'];
				unset( $data['addons'] );
			}
###	##	#	update_option( 'MS_Model_Addon', $data );
		}

		/*
		 * Settings
		 *
		 * 1. The key 'is_first_membership' was introduced
		 * 1. The key 'import' was introduced
		 */
		{
			$data = get_option( 'MS_Model_Settings' );
			// 1.
			if ( ! isset( $data['is_first_membership'] ) ) {
				$data['is_first_membership'] = false;
			}
			// 2.
			if ( ! isset( $data['import'] ) ) {
				$data['import'] = array();
			}
###	##	#	update_option( 'MS_Model_Settings', $data );
		}

		/*
		 * Memberships
		 *
		 * 1. The key 'parent_id' was dropped
		 * 2. The key 'protected_content' was dropped
		 * 3. Types 'content_type' and 'tier' were replaced by 'simple'
		 * 4. Key 'rules' was migrated to 'rule_values'
		 */
		$args = array(
			'post_type' => 'ms_membership',
			'post_status' => 'any',
			'nopaging' => true,
		);
		$query = new WP_Query( $args );
		$items = $query->get_posts();
		foreach ( $items as $item ) {
			// 1.
###	##	#	delete_post_meta( $item->ID, 'parent_id' );
			$item->post_parent = 0;
			// 2.
			$is_base = get_post_meta( $item->ID, 'protected_content', true );
###	##	#	delete_post_meta( $item->ID, 'protected_content' );
			if ( ! empty( $is_base ) ) {
###	##	#		update_post_meta( $item->ID, 'type', 'base' );
			}
			// 3.
			$type = get_post_meta( $item->ID, 'type', true );
			if ( $type != 'dripped' ) {
###	##	#		update_post_meta( $item->ID, 'type', 'simple' );
			}
			// 4.
			$rules = get_post_meta( $item->ID, 'rules', true );
			$serialized = array();
			foreach ( $rules as $key => $data ) {
				$data = self::fix_object( $data );
				$data->rule_value = WDev()->get_array( $data->rule_value );
				$data->dripped = WDev()->get_array( $data->dripped );
				$access = array();

				foreach ( $data->rule_value as $id => $state ) {
					if ( $state ) {
						if ( isset( $data->dripped[$id] )
							&& is_array( $data->dripped[$id] )
						) {
							// TODO: The dripped-content keys have different names in 1.0.x!!!
							$access[] = array(
								'id' => $id,
								'dripped' => array(
									$data->dripped[$id]['type'],
									$data->dripped[$id]['date'],
									$data->dripped[$id]['delay_unit'],
									$data->dripped[$id]['delay_type'],
								),
							);
						} else {
							// TODO: Handle special rules - URL, Comment, Read-More!!!
							$access[] = $id;
						}
					}
				}

				if ( ! empty( $access ) ) {
					$serialized[$key] = $access;
				}
			}
###	##	#	set_post_meta( $item->ID, 'rules', $serialized );

###	##	#	wp_update_post( $item );
		}
		// Cleanup
		foreach ( $items as $item ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $item->ID );
			// This will remove all deprecated properties from DB.
###	##	#	$membership->save();
		}


		die();
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
