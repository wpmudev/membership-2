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

		if ( $Done ) { return; }

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

			// Upgrade from any 1.1.x version to 1.1.0.2 or higher
			if ( version_compare( $old_version, '1.1.0.2', 'lt' ) ) {
				self::_upgrade_1_1_0_2();
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
		// Create a snapshot of the 1.0.x data that can be restored.
		lib2()->updates->snapshot(
			'protected-content',
			'upgrade_1_0_x',
			self::snapshot_data()
		);

		lib2()->updates->clear();

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
			lib2()->updates->add( 'update_option', 'MS_Model_Addon', $data );
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
			lib2()->updates->add( 'update_option', 'MS_Model_Settings', $data );
		}

		/*
		 * Memberships
		 *
		 * 1. The key 'parent_id' was dropped
		 * 2. The key 'protected_content' was dropped
		 * 3. Types 'content_type' and 'tier' were replaced by 'simple'
		 * 4. Key 'rules' was migrated to 'rule_values'
		 *    4.1 Rule 'url_group' was renamed to 'url'
		 *    4.2 Rule 'more_tag' was renamed to 'content'
		 *    4.3 Rule 'comment' was merged with 'content'
		 */
		$args = array(
			'post_type' => 'ms_membership',
			'post_status' => 'any',
			'nopaging' => true,
		);
		$query = new WP_Query( $args );
		$memberships = $query->get_posts();
		// Find the base rules.
		$base = false;
		foreach ( $memberships as $membership ) {
			$is_base = get_post_meta( $membership->ID, 'protected_content', true );
			if ( ! empty( $is_base ) ) {
				$base = $membership;
				$base_rules = get_post_meta( $base->ID, 'rules', true );
				foreach ( $base_rules as $key => $data ) {
					if ( 'url_group' === $key ) { $key = 'url'; }
					$base_rules[$key] = self::fix_object( $data );
				}
				break;
			}
		}
		// Migrate data.
		foreach ( $memberships as $membership ) {
			// 1.
			lib2()->updates->add( 'delete_post_meta', $membership->ID, 'parent_id' );
			$membership->post_parent = 0;
			// 2.
			$is_base = get_post_meta( $membership->ID, 'protected_content', true );
			$is_base = ! empty( $is_base );
			if ( $is_base ) {
				lib2()->updates->add( 'delete_post_meta', $membership->ID, 'protected_content' );
				lib2()->updates->add( 'update_post_meta', $membership->ID, 'type', 'base' );
			} else {
				// 3.
				$type = get_post_meta( $membership->ID, 'type', true );
				if ( $type != 'dripped' ) {
					lib2()->updates->add( 'update_post_meta', $membership->ID, 'type', 'simple' );
				}
			}
			// 4.
			$rules = get_post_meta( $membership->ID, 'rules', true );
			if ( is_array( $rules ) ) { $rules = (object) $rules; }
			if ( ! is_object( $rules ) ) { $rules = new stdClass(); }
			$serialized = array();
			foreach ( $rules as $key => $data ) {
				// 4.1
				if ( 'url_group' === $key ) { $key = 'url'; }

				$data = self::fix_object( $data );
				$data->rule_value = lib2()->array->get( $data->rule_value );
				$data->dripped = lib2()->array->get( $data->dripped );
				$access = array();

				if ( ! empty( $data->dripped )
					&& ! empty( $data->dripped['dripped_type'] )
				) {
					$is_dripped = true;
					$drip_type = $data->dripped['dripped_type'];
					$drip_data = $data->dripped[ $drip_type ];
				} else {
					$is_dripped = false;
				}

				foreach ( $data->rule_value as $id => $state ) {
					if ( $state ) {
						if ( $is_dripped ) {
							// ----- Dripped access
							if ( ! isset( $drip_data[$id] ) ) {
								// No drip-details set, but access granted: Reveal instantly.
								$item_drip = array( 'instantly', '', '', '' );
							} else {
								$item_drip = array( $drip_type, '', '', '' );

								if ( 'specific_date' == $drip_type ) {
									if ( isset( $drip_data[$id]['spec_date'] ) ) {
										$item_drip[1] = $drip_data[$id]['spec_date'];
									}
								} else {
									if ( ! isset( $drip_data[$id]['period_type'] ) ) {
										$drip_data[$id]['period_type'] = 'days';
									}
									if ( isset( $drip_data[$id]['period_unit'] ) ) {
										$item_drip[2] = $drip_data[$id]['period_unit'];
										$item_drip[3] = $drip_data[$id]['period_type'];
									}
								}
							}
							$access[] = array(
								'id' => $id,
								'dripped' => $item_drip,
							);
						} else {
							// ----- Standard access
							if ( 'url' === $key ) {
								if ( ! $is_base ) {
									// First get the URL from base rule
									$base_url = $base_rules['url']->rule_value;
									if ( isset( $base_url[$id] ) ) {
										$url = $base_url[$id];
									} else {
										continue;
									}
								} else {
									$url = $state;
								}
								$hash = md5( $url );
								$access[$hash] = $url;
							} elseif ( 'more_tag' == $key ) {
								// 4.2
								if ( 'more_tag' == $id ) {
									if ( ! isset( $serialized['content'] ) ) {
										$serialized['content'] = array();
									}

									$serialized['content']['no_more'] = 1;
								}
							} elseif ( 'comment' == $key ) {
								// 4.3
								if ( ! isset( $serialized['content'] ) ) {
									$serialized['content'] = array();
								}

								if ( '2' == $state ) {
									$serialized['content']['cmt_none'] = 1;
								} elseif ( '1' == $state ) {
									$serialized['content']['cmt_read'] = 1;
								} elseif ( '0' == $state ) {
									$serialized['content']['cmt_full'] = 1;
								};
							} else {
								$access[] = $id;
							}
						}
					}
				}
				if ( ! empty( $access ) ) {
					$serialized[$key] = $access;
				}
			}
			lib2()->updates->add( 'update_post_meta', $membership->ID, 'rule_values', $serialized );
			lib2()->updates->add( 'wp_update_post', $membership );
		}

		/*
		 * Remove old cron hooks
		 *
		 * Names did change:
		 * ms_model_plugin_check_membership_status -> ms_cron_check_membership_status
		 * ms_model_plugin_process_communications  -> ms_cron_process_communications
		 *
		 * Only remove old hooks here: New hooks are added by MS_Model_Plugin.
		 */
		{
			lib2()->updates->add( 'wp_clear_scheduled_hook', 'ms_cron_check_membership_status' );
			lib2()->updates->add( 'wp_clear_scheduled_hook', 'ms_cron_process_communications' );
		}

		// Execute all queued actions!
		lib2()->updates->execute();

		// Cleanup
		if ( $base && isset( $base->ID ) ) {
			$base_membership = MS_Factory::load( 'MS_Model_Membership', $base->ID );
			$base_membership->type = 'base';
			$base_membership->save();
		}

		foreach ( $memberships as $membership ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership->ID );
			// This will remove all deprecated properties from DB.
			$membership->save();
		}
	}

	/**
	 * Upgrade from any 1.1.x version to 1.1.0.2 or higher
	 */
	static private function _upgrade_1_1_0_2() {
		// Simply create a snapshot that we can restore later.
		lib2()->updates->snapshot(
			'protected-content',
			'upgrade_1_1_0_2',
			self::snapshot_data()
		);
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
	static public function maybe_reset() {
		static $Done = false;

		if ( ! $Done ) {
			$Done = true;
			if ( self::verify_reset_token() ) {
				self::cleanup_db();
				lib2()->ui->admin_message( 'Your Protected Content data was reset!' );
				wp_safe_redirect( admin_url( 'admin.php?page=protected-content' ) );
				exit;
			}
		}
	}

};
