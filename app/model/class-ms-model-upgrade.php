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
					__( '<strong>Membership2</strong> is set up for version %1$s!' , MS_TEXT_DOMAIN ),
					$new_version
				);
			} else {
				$msg[] = sprintf(
					__( '<strong>Membership2</strong> was updated to version %1$s!' , MS_TEXT_DOMAIN ),
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
				self::_upgrade_1_0_0_0();
			}

			// Upgrade from any 1.0.x version to 1.1.x or higher
			if ( version_compare( $old_version, '1.1.0.0', 'lt' ) ) {
				self::_upgrade_1_1_0_0();
			}

			// Upgrade from any 1.1.x version to 1.1.0.3 or higher
			if ( version_compare( $old_version, '1.1.0.3', 'lt' ) ) {
				self::_upgrade_1_1_0_3();
			}

			// Upgrade from any 1.1.x version to 1.1.0.5 or higher
			if ( version_compare( $old_version, '1.1.0.5', 'lt' ) ) {
				self::_upgrade_1_1_0_5();
			}

			// Upgrade from any 1.1.x version to 1.1.0.8 or higher
			if ( version_compare( $old_version, '1.1.0.8', 'lt' ) ) {
				self::_upgrade_1_1_0_8();
			}

			// Upgrade from any 1.1.x version to 1.1.1.4 or higher
			if ( version_compare( $old_version, '1.1.1.4', 'lt' ) ) {
				self::_upgrade_1_1_1_4();
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
	 * Upgrade from any 0.x version to a higher version.
	 */
	static private function _upgrade_1_0_0_0() {
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

	#
	# ##########################################################################
	#

	/**
	 * Upgrade from any 1.0.x version to a higher version.
	 */
	static private function _upgrade_1_1_0_0() {
		self::snapshot( '1.1.0.0' );

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
		$base_values = array();
		$has_dripped_posts = false;
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
				if ( 'dripped' != $type ) {
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
					$data->rule_value = array_fill_keys( array_keys( $drip_data ), 1 );
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
							if ( 'post' == $key ) {
								$has_dripped_posts = true;
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

					// Make sure the protected item is listed in the base rule.
					if ( ! isset( $base_values[$key] ) ) {
						$base_values[$key] = array();
					}
					foreach ( $access as $ind => $state ) {
						if ( is_numeric( $ind ) ) {
							$id = 0;
							if ( is_array( $state ) ) {
								// Dripped rule.
								if ( isset( $state['id'] ) ) {
									$id = $state['id'];
								}
							} else {
								// Normal rule.
								$id = $state;
							}
							if ( $id && ! in_array( $id, $base_values[$key] ) ) {
								$base_values[$key][] = $id;
							}
						} elseif ( is_string( $ind ) ) {
							// URL groups.
							$base_values[$key][$ind] = $state;
						}
					}
				}
			}
			lib2()->updates->add( 'wp_update_post', $membership );
			// We set the base rules a bit later.
			if ( $base && isset( $base->ID ) && $membership->ID != $base->ID ) {
				lib2()->updates->add( 'update_post_meta', $membership->ID, 'rule_values', $serialized );
			}
		}
		// Set the base rules after all memberships were parsed.
		if ( $base && isset( $base->ID ) ) {
			lib2()->updates->add( 'update_post_meta', $base->ID, 'rule_values', $base_values );
		}
		// When dripped rules publish posts then the "Individual Posts" Addon is needed.
		if ( $has_dripped_posts ) {
			$addons = MS_Factory::load( 'MS_Model_Addon' );
			lib2()->updates->add( array( $addons, 'enable' ), MS_Model_Addon::ADDON_POST_BY_POST );
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
		lib2()->updates->plugin( MS_TEXT_DOMAIN );
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

	#
	# ##########################################################################
	#

	/**
	 * Upgrade from any 1.1.x version to 1.1.0.3 or higher
	 */
	static private function _upgrade_1_1_0_3() {
		global $wpdb;

		self::snapshot( '1.1.0.3' );

		/*
		 * Rename payment gateway IDs
		 *
		 * 1. The gateway 'paypal_single' was renamed to 'paypalsingle'
		 * 1. The gateway 'paypal_standard' was renamed to 'paypalstandard'
		 */
		{
			$sql = "
				SELECT ID
				FROM {$wpdb->posts}
				WHERE post_type IN ( 'ms_relationship', 'ms_invoice' )
			";
			$posts = $wpdb->get_col( $sql );

			foreach ( $posts as $post_id ) {
				$gateway = get_post_meta( $post_id, 'gateway_id', true );
				// 1.
				if ( 'paypal_single' == $gateway ) {
					lib2()->updates->add( 'update_post_meta', $post_id, 'gateway_id', 'paypalsingle' );
				}
				// 2.
				if ( 'paypal_standard' == $gateway ) {
					lib2()->updates->add( 'update_post_meta', $post_id, 'gateway_id', 'paypalstandard' );
				}
			}
		}

		// Execute all queued actions!
		lib2()->updates->plugin( MS_TEXT_DOMAIN );
		lib2()->updates->execute();
	}

	#
	# ##########################################################################
	#

	/**
	 * Upgrade from any 1.1.x version to 1.1.0.5 or higher
	 */
	static private function _upgrade_1_1_0_5() {
		self::snapshot( '1.1.0.5' );

		/*
		 * When upgrading from 1.0 to 1.1 the payment gateway details were lost
		 * due to a new name of the option-keys.
		 *
		 * We try to restore the lost settings now.
		 * 1. option_name 'MS_Model_Gateway_Authorize' -> 'ms_gateway_authorize'
		 * 2. option_name 'MS_Model_Gateway_Manual' -> 'ms_gateway_manual'
		 * 3. option_name 'MS_Model_Gateway_Paypal_Single' -> 'ms_gateway_paypalsingle'
		 * 4. option_name 'MS_Model_Gateway_Paypal_Standard' -> 'ms_gateway_paypalstandard'
		 * 5. option_name 'MS_Model_Gateway_Stripe' -> 'ms_gateway_stripe'
		 */
		{
			$matching = array(
				'ms_gateway_authorize' => 'MS_Model_Gateway_Authorize',
				'ms_gateway_manual' => 'MS_Model_Gateway_Manual',
				'ms_gateway_paypalsingle' => 'MS_Model_Gateway_Paypal_Single',
				'ms_gateway_paypalstandard' => 'MS_Model_Gateway_Paypal_Standard',
				'ms_gateway_stripe' => 'MS_Model_Gateway_Stripe',
			);

			foreach ( $matching as $new_key => $old_key ) {
				$old_val = get_option( $old_key );
				if ( ! get_option( $new_key ) && is_array( $old_val ) ) {
					switch ( $old_val['id'] ) {
						case 'paypal_single': $old_val['id'] = 'paypalsingle'; break;
						case 'paypal_standard': $old_val['id'] = 'paypalstandard'; break;
					}

					lib2()->updates->add( 'update_option', $new_key, $old_val );
				}
			}
		}

		// Execute all queued actions!
		lib2()->updates->plugin( MS_TEXT_DOMAIN );
		lib2()->updates->execute();
	}

	#
	# ##########################################################################
	#

	/**
	 * Upgrade from any 1.1.x version to 1.1.0.8 or higher
	 */
	static private function _upgrade_1_1_0_8() {
		self::snapshot( '1.1.0.8' );

		/*
		 * We introduce the new Add-on "Category Protection" which was a core
		 * rule until now, which means it was always active. So activate it
		 * when upgrading to the new version!
		 */
		{
			$addons = MS_Factory::load( 'MS_Model_Addon' );
			lib2()->updates->add( array( $addons, 'enable' ), MS_Addon_Category::ID );
		}

		// Execute all queued actions!
		lib2()->updates->plugin( MS_TEXT_DOMAIN );
		lib2()->updates->execute();
	}

	#
	# ##########################################################################
	#

	/**
	 * Upgrade from any 1.1.x version to 1.1.1.4 or higher
	 */
	static private function _upgrade_1_1_1_4() {
		self::snapshot( '1.1.1.4' );

		/*
		 * Invoice structure changes:
		 * - Field 'trial_period' renamed to 'uses_trial'
		 * - New Field added 'trial_price'
		 * - New Field added 'trial_ends'
		 */
		{
			$args = array(
				'post_status' => 'any',
				'post_type' => MS_Model_Invoice::$POST_TYPE,
				'posts_per_page' => 0,
				'nopaging' => true,
			);
			// Get a list of all invoices.
			$invoices = get_posts( $args );

			$trial_match = array();
			foreach ( $invoices as $post ) {
				$is_trial = get_post_meta( $post->ID, 'trial_period', true );
				$is_trial = lib2()->is_true( $is_trial );

				if ( $is_trial ) {
					$subscription_id = intval( get_post_meta( $post->ID, 'ms_relationship_id', true ) );
					$invoice_number = intval( get_post_meta( $post->ID, 'invoice_number', true ) );

					$paid_args = array(
						'meta_query' => array(
							'relation' => 'AND',
							array(
								'key' => 'ms_relationship_id',
								'value' => $subscription_id,
								'compare' => '=',
							),
							array(
								'key' => 'trial_period',
								'value' => '',
								'compare' => '=',
							),
							array(
								'key' => 'invoice_number',
								'value' => $invoice_number + 1,
								'compare' => '=',
							)
						)
					);
					$paid_invoice = MS_Model_Invoice::get_invoices( $paid_args );

					if ( ! empty( $paid_invoice ) ) {
						$trial_match[$post->ID] = reset( $paid_invoice );
					}
				} else {
					// Normal invoice. Add new fields with default values.
					lib2()->updates->add( 'update_post_meta', $post->ID, 'uses_trial', '' );
					lib2()->updates->add( 'update_post_meta', $post->ID, 'trial_price', '0' );
					lib2()->updates->add( 'update_post_meta', $post->ID, 'trial_ends', '' );
				}
			}

			foreach ( $trial_match as $trial_id => $paid_invoice ) {
				$trial_invoice = MS_Factory::load( 'MS_Model_Invoice', $trial_id );
				$subscription = $trial_invoice->get_subscription();
				$trial_ends = $subscription->trial_expire_date;

				lib2()->updates->add( 'update_post_meta', $paid_invoice->id, 'uses_trial', '1' );
				lib2()->updates->add( 'update_post_meta', $paid_invoice->id, 'trial_ends', $trial_ends );
				lib2()->updates->add( 'wp_delete_post', $trial_id, true );

				if ( $subscription->current_invoice_number == $trial_invoice->invoice_number ) {
					lib2()->updates->add(
						'update_post_meta',
						$subscription->id,
						'current_invoice_number',
						$paid_invoice->invoice_number
					);
				}
			}
		}

		// Execute all queued actions!
		lib2()->updates->plugin( MS_TEXT_DOMAIN );
		lib2()->updates->execute();
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
		wp_safe_redirect( admin_url( 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG ) );
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
		set_transient( 'ms_one_time_key-' . $action, $one_time_key, 120 );

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

		$one_time_key = get_transient( 'ms_one_time_key-' . $action );
		delete_transient( 'ms_one_time_key-' . $action );
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
			wp_safe_redirect( admin_url( 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG ) );
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
					'<a href="' . admin_url( 'admin.php?page=' . MS_Controller_Plugin::MENU_SLUG ) . '">',
					'</a>'
				);

				wp_die( '', 'Snapshot Restored' );
			}
		}
	}

};
