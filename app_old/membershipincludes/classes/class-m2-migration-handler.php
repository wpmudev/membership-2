<?php
/*
 * Prepare upgrade to Membership (free) to Membership2 (free)
 *
 * The Membership project is discontinued.
 * It should be automatically replaced by the Membership2 project (former
 * Protected Content).
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
class M2_Migration_Handler {
	const PLUGIN_ID = 'membership2';

	/**
	 * User has to confirm update before updating the plugin to this name.
	 * I.e. While the NEW plugin keeps its old name no confirmation is displayed
	 * but once the new plugin contains this phrase in the project name the
	 * migration process begins by displaying a confirmation message.
	 */
	const NEW_PLUGIN_NAME = 'Membership2';

	/**
	 * When the big update is ready a flag is stored in site-options table using
	 * this option key.
	 * After the update the new plugin should remove this site-option again.
	 */
	const TEMP_OPTION_KEY = '_wporg_update_to_m2';

	/**
	 * Add hooks to watch all upgrade requests
	 */
	static public function setup() {
		// Display a custom "Update available" message.
		add_action( 'admin_head', array( __CLASS__, 'hook_confirm_message' ) );

		// Ajax handlers.
		add_action( 'wp_ajax_wpmudev-unlock-' . self::PLUGIN_ID, array( __CLASS__, 'ajax_unlock' ) );
		add_action( 'wp_ajax_wpmudev-remind-' . self::PLUGIN_ID, array( __CLASS__, 'ajax_remind' ) );
	}

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

		if ( time() > $state->remind_time ) {
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

		// Switch to the new version.
		membership2_use_m2();

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
	 * Returns the current update state.
	 *
	 * @return array Update-status information.
	 */
	static protected function get_state() {
		$state = get_site_option( self::TEMP_OPTION_KEY );
		$default_state = (object) array(
			'available' => true,
			'remind_time' => 0,
		);

		if ( ! is_object( $state ) ) {
			$state = $default_state;
			update_site_option( self::TEMP_OPTION_KEY, $state );
		}

		$state = (object) array_merge( (array) $default_state, (array) $state );

		return $state;
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
		.dev-cta-wrap .wpmu-button {
			padding: 12px 20px;
			background-color: rgba(0, 0, 0, 0);
			text-decoration: none;
			display: inline-block;
			outline: none;
			font-weight: bold;
			font-family: Helvetica Neue, helvetica, sans-serif !important;
			text-rendering: optimizeLegibility;
			-moz-border-radius: 3px;
			-webkit-border-radius: 3px;
			border-radius: 3px;
			-moz-background-clip: padding;
			-webkit-background-clip: padding-box;
			background-clip: padding-box;
			-moz-box-shadow: 0 1px 0 #001c33, inset 0 1px 0 #004f8c;
			-webkit-box-shadow: 0 1px 0 #001C33, inset 0 1px 0 #004F8C;
			box-shadow: 0 1px 0 #001C33, inset 0 1px 0 #004F8C;
			background-image: -moz-linear-gradient(bottom, #003866 0%, #00487f 100%);
			background-image: -o-linear-gradient(bottom, #003866 0%, #00487f 100%);
			background-image: -webkit-linear-gradient(bottom, #003866 0%, #00487F 100%);
			background-image: linear-gradient(bottom, #003866 0%, #00487f 100%);
			color: #E2ECF4;
			font-size: 14px;
			text-shadow: 0 -1px 0 #002A4C;
			border: none;
			-webkit-font-smoothing: antialiased;
		}
		.dev-cta-wrap .wpmu-button:hover,
		.dev-cta-wrap .wpmu-button:focus,
		.dev-cta-wrap .wpmu-button:active {
			color: #FFF;
		}
		</style>
		<div class="wpmudev-new notice" id="message-<?php echo self::PLUGIN_ID; ?>">
			<div class="dev-widget-content">
				<h4>
					<strong><?php _e( 'Membership2 Is Available', 'membership' ); ?></strong>
				</h4>

				<div class="dev-content-wrapper">
					<img src="https://premium.wpmudev.org/wp-content/uploads/2014/10/Product-Content-280x158.png" width="186" height="105" style="margin-bottom:20px;"/>

					<h4><?php _e( 'The Best Membership Plugin You Ever Used!', 'membership' ); ?></h4>

					<p>
					<?php _e( 'Membership2 is a <strong>completely rewritten plugin</strong> with a brand new user interface and new features.<br>We improved workflows and data structure to make the plugin as simple and easy to use as possible for you!', 'membership' ); ?>
					</p>
					<p>
					<?php _e( 'When you update to the new version we will migrate your Subscription plans and Members to the new data structure automatically.<br>However, the protection rules cannot be migrated. So you have to manually <strong>set up all protection rules again</strong> after the update.', 'membership' ); ?><small><sup>*</sup></small><br />
					<?php printf( __( '%sRead more about the new version%s on our website!', 'membership' ), '<a href="http://premium.wpmudev.org/project/membership" target="_blank">', '</a>' ); ?>
					</p>

					<div class="dev-cta-wrap">
						<a href="#remind-me-later" id="wpmudev-remind-<?php echo self::PLUGIN_ID; ?>"><?php
							_e( 'Remind me in a few days', 'membership' );
						?></a>
						&nbsp;
						<a href="#update-now" id="wpmudev-unlock-<?php echo self::PLUGIN_ID; ?>" class="wpmu-button"><?php
							_e( 'I understand. <u>Switch to Membership2 now</u>!', 'membership' );
						?><small><sup>*</sup></small></a>
					</div>
					<p style="text-align:right">
						<small><sup>*</sup> <?php _e( 'When you switch to Membership2 your data is migrated and you need to set up your protection rules again. Until this is completed your content is unprotected.', 'membership' ); ?></small>
					</p>
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
			var unlock = jQuery( '#wpmudev-unlock-<?php echo self::PLUGIN_ID; ?>' ),
				remind = jQuery( '#wpmudev-remind-<?php echo self::PLUGIN_ID; ?>' ),
				message = jQuery( '#message-<?php echo self::PLUGIN_ID; ?>' );

			function confirmed() {
				message.detach();
				window.location.reload();
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
M2_Migration_Handler::setup();