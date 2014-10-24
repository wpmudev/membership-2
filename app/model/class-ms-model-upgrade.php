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
		self::upgrade();

		MS_Factory::load( 'Ms_Model_Upgrade' );

		do_action( 'ms_model_upgrade_init' );
	}

	/**
	 * Upgrade database.
	 *
	 * @since 1.0.0
	 */
	public static function upgrade() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );

		// Compare current src version to DB version
		if ( version_compare( MS_Plugin::instance()->version, $settings->version, '!=' ) ) {

			// Upgrade logic from 1.0.0.0
			if ( '1.0.0.0' == $settings->version ) {
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

			$settings = MS_Factory::load( 'MS_Model_Settings' );
			$settings->version = MS_Plugin::instance()->version;
			$settings->save();

			flush_rewrite_rules();
			do_action( 'ms_model_upgrade_upgrade', $settings );
		}
	}

	/**
	 * Completely whipe all Membership data from Database.
	 *
	 * Note: This function is not used currently...
	 *
	 * @since 1.0.0
	 */
	public static function cleanup() {
		global $wpdb;
		$sql = array();

		// Delete membership meta-data from users.
		$users = MS_Model_Member::get_members( );
		foreach ( $users as $user ) {
			$user->delete_all_membership_usermeta();
			$user->save();
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
		 * Content is saved by classes that extend MS_Model_Custom_Post_Type
		 */
		$ms_posttypes = array(
			MS_Model_Communication::$POST_TYPE,
			MS_Model_Coupon::$POST_TYPE,
			MS_Model_Event::$POST_TYPE,
			MS_Model_Invoice::$POST_TYPE,
			MS_Model_Membership::$POST_TYPE,
			MS_Model_Membership_Relationship::$POST_TYPE,
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

		// Clear all data from WP Object cache
		wp_cache_flush();
	}

};