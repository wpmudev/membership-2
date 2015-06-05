<?php
/*
 * Prepare upgrade to Membership2.
 *
 * The Protected Content project is discontinued.
 * It should be automatically replaced by the Membership2 project (which
 * actually is the new name of Protected Content).
 *
 * We need to inform users that M2 is available and ask them to switch to the
 * new plugin. This migration handler takes care of getting the user
 * confirmation and also makes the update look like an auto-upgrade of the
 * Membership plugin, while in fact it switches the membership project to the M2
 * project.
 *
 * Also M2 is not compatible with this plugins data structure, so a migration
 * and manual adjustments are required after the update.
 */
class MS_M2_Migration_Handler {
	/**
	 * The WPMUDEV Project-ID of this plugin.
	 * This is the discontinued project ID.
	 */
	const OLD_PLUGIN_ID = 928907;

	/**
	 * The WPMUDEV Project-ID of the follow-up plugin.
	 * This is the ID of the new project that silently replaces the old project.
	 */
	const NEW_PLUGIN_ID = 1003656;

	/**
	 * When the big update is ready a flag is stored in site-options table using
	 * this option key.
	 * After the update the new plugin should remove this site-option again.
	 */
	const TEMP_OPTION_KEY = '_wpmudev_update_pc_to_m2';

	/**
	 * This is the URL our WPMUDEV dashboard uses to check for upgrades.
	 */
	const SERVER_URL = 'https://premium.wpmudev.org/wdp-un.php';

	/**
	 * The old plugin-key (before the update)
	 */
	const OLD_PLUGIN = 'protected-content/protected-content.php';

	/**
	 * The new plugin-key (after the update)
	 */
	const NEW_PLUGIN = 'membership/membership2.php';

	/**
	 * Add hooks to watch all upgrade requests
	 */
	static public function setup() {
		// Modifies the server response.
		add_filter( 'http_response', array( __CLASS__, 'http_response' ), 10, 3 );
		add_filter( 'pre_update_site_option_wdp_un_updates_available', array( __CLASS__, 'replace_plugin' ) );
		add_filter( 'site_option_wdp_un_updates_data', array( __CLASS__, 'modify_old_plugin' ) );
		add_filter( 'site_transient_wpmudev_local_projects', array( __CLASS__, 'modify_old_plugin' ) );

		// Add the new plugin to the update list after user confirmed update.
		add_filter( 'pre_set_site_transient_wpmudev_local_projects', array( __CLASS__, 'local_project_data' ) );
		add_filter( 'upgrader_pre_install', array( __CLASS__, 'prepare_update' ), 1, 2 );
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'forge_update_data' ), 20 );

		// Display a custom "Update available" message.
		add_action( 'admin_head', array( __CLASS__, 'hook_confirm_message' ) );

		// Ajax handlers.
		add_action( 'wp_ajax_wpmudev-unlock-' . self::NEW_PLUGIN_ID, array( __CLASS__, 'ajax_unlock' ) );
		add_action( 'wp_ajax_wpmudev-remind-' . self::NEW_PLUGIN_ID, array( __CLASS__, 'ajax_remind' ) );
	}

	/**
	 * A HTTP request was made. We check if it contains upgrade information from
	 * WPMUDEV - if it does we remove the Membership plugin details from the
	 * response.
	 *
	 * Filter: http_response
	 *
	 * @param  array  $response HTTP response.
	 * @param  array  $args     HTTP request arguments.
	 * @param  string $url      The request URL.
	 * @return array HTTP response.
	 */
	static public function http_response( $response, $args, $url ) {
		if ( false === strpos( $url, self::SERVER_URL ) ) {
			// Request was not made to the upgrade server, ignore it.
			return $response;
		}

		if ( empty( $response['body'] ) || 0 !== strpos( $response['body'], 'a:' ) ) {
			// Response from Upgrade server is not serialized array, ignore it.
			return $response;
		}

		$data = unserialize( $response['body'] );

		if ( isset( $data['projects'] ) && isset( $data['projects'][self::NEW_PLUGIN_ID] ) ) {
			// Analyze the plugin details sent from the update server
			$plugin_info = $data['projects'][self::NEW_PLUGIN_ID];

			if ( self::check_for_update( $plugin_info ) ) {
				// Hide M2 from the update response until user confirmed the update.
				unset( $data['projects'][self::NEW_PLUGIN_ID] );

				// Hide the "Our latest plugin: Membership2" notice - we have our own!
				if ( self::NEW_PLUGIN_ID == $data['latest_release'] ) {
					$data['latest_release'] = 0;
				}

				// Remove M2 from the latest plugins list.
				foreach ( $data['latest_plugins'] as $index => $id ) {
					if ( self::NEW_PLUGIN_ID == $id ) {
						unset( $data['latest_plugins'][$index] );
						break;
					}
				}

				$response['body'] = serialize( $data );
			}
		}

		return $response;
	}

	/**
	 * Puts the NEW plugin ID in the WPMU DEV Update cache and instructs it to
	 * remove the OLD plugin during update.
	 *
	 * Filter: pre_update_site_option_wdp_un_updates_available
	 *
	 * @param  mixed $value New value of site option.
	 * @return mixed Modified new value of site option.
	 */
	static public function replace_plugin( $value ) {
		$state = self::get_state();

		if ( $state->available && $state->confirmed ) {
			$value[self::OLD_PLUGIN_ID] = array(
				'type' => 'plugin',
				'filename' => plugin_basename( self::OLD_PLUGIN ),
				'url' => 'https://premium.wpmudev.org/project/membership/',
				'instructions_url' => 'https://premium.wpmudev.org/project/membership/#usage',
				'support_url' => 'http://premium.wpmudev.org/forums/tags/membership-pro/',
				'name' => 'Membership2',
				'thumbnail' => 'https://premium.wpmudev.org/wp-content/uploads/2014/10/Product-Content-280x158.png',
				'version' => '1',     // Old plugin is "Membership 1"
				'new_version' => '2', // New plugin is "Membership 2"
				'changelog' => '<p>Update to Membership2</p>',
				'autoupdate' => '1',
			);
		}

		return $value;
	}

	/**
	 * Modifies the version of the old (installed) plugin to enable us reverting
	 * the plugin version to an earlier number.
	 *
	 * Filter: site_option_wdp_un_updates_data
	 * Filter: site_transient_wpmudev_local_projects
	 */
	static public function modify_old_plugin( $value ) {
		$state = self::get_state();

		if ( $state->available && $state->confirmed ) {
			// wpmudev_local_projects
			if ( is_array( $value ) && isset( $value[self::OLD_PLUGIN_ID] ) ) {
				$value[self::OLD_PLUGIN_ID]['version'] = 'Membership1'; // The OLD version number.
			}

			// wdp_un_updates_data
			if ( is_array( $value ) && isset( $value['projects'] ) ) {
				$new_plugin = $state->info;
				$new_plugin['name'] = 'Membership2';
				$new_plugin['version'] = 'Membership2';
				$new_plugin['short_description'] .= '<p><strong>' . __( 'Important: This update will replace the plugin "Membership Pro"!<br>Please backup your Database before installing the new version', 'membership' ) . '</strong></p>';
				$new_plugin['thumbnail'] = 'https://premium.wpmudev.org/wp-content/uploads/2014/10/Product-Content-280x158.png';

				if ( ! isset( $value['projects'][self::OLD_PLUGIN_ID] ) ) {
					$value['projects'][self::OLD_PLUGIN_ID] = array();
				}

				$value['projects'][self::OLD_PLUGIN_ID] = array_merge(
					$value['projects'][self::OLD_PLUGIN_ID],
					$new_plugin
				);

			}
		}

		return $value;
	}

	/**
	 * Modifies the WordPress update_plugins data structure after it was read
	 * from the database.
	 *
	 * Filter: site_transient_update_plugins
	 *
	 * @param  array $value
	 * @return array
	 */
	static public function forge_update_data( $value ) {
		if ( ! is_object( $value ) ) { return $value; }
		if ( ! is_array( $value->response ) ) { return $value; }

		$state = self::get_state();

		if ( $state->available && $state->confirmed ) {
			if ( isset( $value->response[self::OLD_PLUGIN] ) ) {
				$package = $value->response[self::OLD_PLUGIN]->package;
				$package = add_query_arg( 'pid', self::NEW_PLUGIN_ID, $package );
				$value->response[self::OLD_PLUGIN]->package = $package;
			}
		}
		return $value;
	}

	/**
	 * This modifies the transient value that stores the *current* plugin
	 * versions. We manually set our plugin version to empty value so we can
	 * regress the version number during update to 1.0
	 *
	 * Filter: pre_set_site_transient_wpmudev_local_projects
	 *
	 * @param  array $value Plugin data list.
	 * @return array Plugin data list.
	 */
	static public function local_project_data( $value ) {
		$state = self::get_state();

		if ( $state->available && $state->confirmed ) {
			// The update is available and user confirmed to upgrade.

			if ( is_array( $value ) ) {
				if ( isset( $value[self::OLD_PLUGIN_ID] ) ) {
					$value[self::OLD_PLUGIN_ID]['version'] = '';
				}
			}
		}

		return $value;
	}

	/**
	 * Renames the plugin before it is upgraded.
	 *
	 * Filter: upgrader_pre_install
	 *
	 * @param bool|WP_Error  $return Upgrade offer return.
	 * @param array          $plugin Plugin package arguments.
	 * @return bool|WP_Error The passed in $return param or {@see WP_Error}.
	 */
	static public function prepare_update( $return, $plugin ) {
		if ( is_wp_error( $return ) ) {
			return $return;
		}

		$plugin_name = isset( $plugin['plugin'] ) ? $plugin['plugin'] : '';
		if ( self::OLD_PLUGIN == $plugin_name ) {
			deactivate_plugins( self::OLD_PLUGIN, true );
			activate_plugin( self::NEW_PLUGIN, null, false, true );
		}

		return $return;
	}

	/**
	 * This function checks the response from update server to determine if the
	 * new plugin is available. When the new plugin is available we display a
	 * message to the user to inform him of the manual update process.
	 */
	static protected function check_for_update( $plugin_info ) {
		if ( self::NEW_PLUGIN_ID != $plugin_info['id'] ) {
			return false;
		}

		// Update the update state to "available"
		$state = self::get_state();
		$state->available = true;
		$state->info = $plugin_info;
		update_site_option( self::TEMP_OPTION_KEY, $state );

		return true;
	}

	/**
	 * Returns the current update state.
	 *
	 * @return array Update-status information.
	 */
	static protected function get_state() {
		$state = get_site_option( self::TEMP_OPTION_KEY );
		$default_state = (object) array(
			'available' => false,
			'confirmed' => false,
			'remind_time' => 0,
			'info' => array(),
		);

		if ( ! is_object( $state ) ) {
			$state = $default_state;
			update_site_option( self::TEMP_OPTION_KEY, $state );
		}

		$state = (object) array_merge( (array) $default_state, (array) $state );

		return $state;
	}

	// ----- UPDATE NOTICE HANDLER ---------------------------------------------

	/**
	 * Add the admin-notice callback. We do this in the admin_head hook because
	 * the WPMUDEV Dashboard disables all admin_notices that are added earlier.
	 *
	 * Hook: admin_head
	 */
	static public function hook_confirm_message() {
		if ( ! current_user_can( 'update_plugins' ) ) { return; }

		$state = self::get_state();
		if ( ! $state->available ) { return false; }
		if ( $state->confirmed ) { return false; }

		if ( isset( $_GET['page'] ) && 0 === strpos( $_GET['page'], 'wpmudev' ) ) {
			// Always show a reminder in the WPMUDEV Dashboard.
			add_action( 'admin_footer', array( __CLASS__, 'confirm_inline' ) );
		} elseif ( time() > $state->remind_time ) {
			// If on other pages then WPMUDEV Dashboard we check the remind-time setting.
			add_action( 'all_admin_notices', array( __CLASS__, 'confirm_message' ) );
		}
	}

	/**
	 * Handles clicking the "Unlock Update" button in the update dialog.
	 * No response is made, but state is updated.
	 */
	static public function ajax_unlock() {
		$state = self::get_state();

		// When confirmed the update is available.
		$state->confirmed = true;
		update_site_option( self::TEMP_OPTION_KEY, $state );

		// Refresh the updates_available option to trigger our filter callback.
		$data = get_site_option( 'wdp_un_updates_available' );
		update_site_option( 'wdp_un_updates_available', $data );

		echo 'OK';
		exit;
	}

	/**
	 * Handles clicking the "Remind me later" button in the update dialog.
	 * No response is made, but state is updated.
	 */
	static public function ajax_remind() {
		$state = self::get_state();

		// Remind again in one week.
		$state->remind_time = time() + WEEK_IN_SECONDS;
		update_site_option( self::TEMP_OPTION_KEY, $state );

		echo 'OK';
		exit;
	}

	/**
	 * Displays a small Update notice on the WPMUDEV Dashboard page.
	 */
	static public function confirm_inline() {
		?>
		<div id="wpmudev-confirmation-<?php echo self::NEW_PLUGIN_ID; ?>" style="display:none;padding-top:20px;">
		<table cellpadding="3" cellspacing="3" width="100%" class="widefat">
			<thead>
			<tr>
				<th colspan="2"><?php _e( 'Protected Content becomes Membership2', MS_TEXT_DOMAIN ); ?></th>
			</tr>
			</thead>
			<tbody>
			<tr class="wdv-update">
				<td>
					<img src="https://premium.wpmudev.org/wp-content/uploads/2014/10/Product-Content-280x158.png" width="100" height="60" style="float:left; padding: 5px" />
					<strong><a href="http://premium.wpmudev.org/project/membership" target="_blank">Membership2</a></strong>
					<p>
					<?php _e( 'Protected Content is renamed to Membership2 during the next update.', MS_TEXT_DOMAIN ); ?>
					</p>
				</td>
				<td style="vertical-align:middle;text-align:right">
					<a href="#unlock-update" id="wpmudev-unlock-<?php echo self::NEW_PLUGIN_ID; ?>" class="button-secondary"><?php _e( 'Great, unlock the update', MS_TEXT_DOMAIN ); ?></a>
				</td>
			</tr>
			<tr class="wdv-changelog">
				<td colspan="2">
					<div class="wdv-view-link">
						<a href="#"><?php _e( 'Details', MS_TEXT_DOMAIN ); ?> <i class="wdvicon-chevron-down"></i></a>
					</div>
					<div class="wdv-changelog-drop">
					<p>
					<?php _e( 'Heads up: Protected Content is now called Membership2. During the next update the plugin is renamed.', MS_TEXT_DOMAIN ); ?>
					</p>
					<p>
					<?php _e( 'The next version holds a few new features in store for you.<br>We added <b>Network wide protection</b>, a <b>new Stripe Gateway</b> and much more improvements!', MS_TEXT_DOMAIN ); ?>
					</p>
					<p>
					<?php printf( __( '%sRead more about the new version%s on our website!', MS_TEXT_DOMAIN ), '<a href="http://premium.wpmudev.org/project/membership" target="_blank">', '</a>' ); ?>
					</p>
					<div class="wdv-close-link"><a href="#"><?php _e( 'close', MS_TEXT_DOMAIN ); ?> <i class="wdvicon-chevron-up"></i></a></div>
					</div>
				</td>
			</tr>
			</tbody>
		</table>
		</div>
		<script>
		(function() {
			var plugins = jQuery( '.upgrade[name=upgrade-plugins]' ),
				note = jQuery( '#wpmudev-confirmation-<?php echo self::NEW_PLUGIN_ID; ?>' );

			note.detach();
			if ( ! plugins.length ) { return; }

			plugins.after( note );
			note.show();
		})();
		</script>
		<?php
		self::confirm_scripts();
	}

	/**
	 * Display a message that must be confirmed before making the big update
	 * available to the user. The message is only displayed when the update is
	 * ready.
	 */
	static public function confirm_message() {
		// We output the base CSS here, in case the WPMUDEV Dashboard plugin is deactivated.
		?>
		<style>
		.wpmudev-new {
			padding: 10px;
			color: #5F5F5F;
			border-radius: 2px;
			text-shadow: 0 1px 0 #FFF;
			-webkit-box-sizing: border-box;
			-moz-box-sizing: border-box;
			box-sizing: border-box;
			background-color: #F0F1F2;
			border: 1px solid #CFDFE5;
			margin: 5px 15px 15px 0;
			position: relative;
		}
		.wpmudev-new h4 {
			margin: 0 0 10px 0;
			font-size: 1.3em;
			font-family: 'Helvetica Neue', helvetica, arial, sans-serif;
			font-weight: 600;
			color: #3C3C3C;
		}
		.wpmudev-new .dev-widget-content > h4 {
			padding-bottom: 10px;
			border-bottom: 1px solid #DCEAF5;
		}
		.wpmudev-new h4 strong {
			color: #053254;
			font-weight: 400;
			font-size: 1.3em;
			padding-left: 20px;
			line-height: 33px;
		}
		.dev-content-wrapper {
			margin-top: 10px;
		}
		.wpmudev-new img {
			float: left;
			margin-right: 20px;
		}
		.dev-cta-wrap {
			text-align: right;
		}
		.dev-cta-wrap .wpmu-button:focus,
		.dev-cta-wrap .wpmu-button:active {
			color: #FFF;
		}
		</style>
		<div class="wpmudev-new notice" id="message-<?php echo self::NEW_PLUGIN_ID; ?>">
			<div class="dev-widget-content">
				<h4>
					<strong><?php _e( 'Protected Content becomes Membership2', MS_TEXT_DOMAIN ); ?></strong>
				</h4>

				<div class="dev-content-wrapper">
					<img src="https://premium.wpmudev.org/wp-content/uploads/2014/10/Product-Content-280x158.png" width="186" height="105" style="margin-bottom:20px;"/>

					<h4><?php _e( 'The Best Membership Plugin Just Got Even Better!', MS_TEXT_DOMAIN ); ?></h4>

					<p>
					<?php _e( 'Heads up: Protected Content is now called Membership2. During the next update the plugin is renamed.', MS_TEXT_DOMAIN ); ?>
					</p>
					<p>
					<?php _e( 'The next version holds a few new features in store for you.<br>We added <b>Network wide protection</b>, a <b>new Stripe Gateway</b> and much more improvements!', MS_TEXT_DOMAIN ); ?>
					</p>
					<p>
					<?php printf( __( '%sRead more about the new version%s on our website!', MS_TEXT_DOMAIN ), '<a href="http://premium.wpmudev.org/project/membership" target="_blank">', '</a>' ); ?>
					</p>

					<div class="dev-cta-wrap">
						<a href="#remind-me-later" id="wpmudev-remind-<?php echo self::NEW_PLUGIN_ID; ?>"><?php
							_e( 'Remind me in a few days', MS_TEXT_DOMAIN );
						?></a>
						&nbsp;
						<a href="#unlock-update" id="wpmudev-unlock-<?php echo self::NEW_PLUGIN_ID; ?>" class="wpmu-button"><?php
							_e( 'Great, unlock the update', MS_TEXT_DOMAIN );
						?></a>
					</div>
				</div>

				<div class="clear"></div>
			</div>
		</div>
		<?php
		self::confirm_scripts();
	}

	/**
	 * Outputs javascript that handles the confirm/remind-me-later clicks.
	 */
	static protected function confirm_scripts() {
		global $wpmudev_un;
		?>
		<script>
		(function() {
			var unlock = jQuery( '#wpmudev-unlock-<?php echo self::NEW_PLUGIN_ID; ?>' ),
				remind = jQuery( '#wpmudev-remind-<?php echo self::NEW_PLUGIN_ID; ?>' ),
				message = jQuery( '#message-<?php echo self::NEW_PLUGIN_ID; ?>' );

			function confirmed() {
				message.detach();
				<?php if ( $wpmudev_un ) : ?>
				window.location.href = '<?php echo esc_url_raw( $wpmudev_un->updates_url ); ?>';
				<?php endif; ?>
			}
			function postponed() {
				message.detach();
			}

			unlock.click(function(){ jQuery.post(window.ajaxurl, {action:jQuery(this).attr( 'id' )}, confirmed )});
			remind.click(function(){ jQuery.post(window.ajaxurl, {action:jQuery(this).attr( 'id' )}, postponed )});
		})();
		</script>
		<?php
	}
};
MS_M2_Migration_Handler::setup();