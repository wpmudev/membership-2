<?php
/**
 * Controller for Automated Communications.
 *
 * @since  1.0.0
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Communication extends MS_Controller {

	/**
	 * Ajax action name.
	 *
	 * @since  1.0.0
	 * @var string The ajax action name.
	 */
	const AJAX_ACTION_UPDATE_COMM = 'update_comm';

	/**
	 * Prepare Membership settings manager.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		do_action( 'ms_controller_communication_before', $this );

		$this->add_ajax_action(
			self::AJAX_ACTION_UPDATE_COMM,
			'ajax_action_update_communication'
		);

		$this->add_action(
			'ms_controller_membership_setup_completed',
			'auto_setup_communications'
		);

		do_action( 'ms_controller_communication_after', $this );
	}

	/**
	 * Handle Ajax update comm field action.
	 *
	 * Related Action Hooks:
	 * - wp_ajax_update_comm
	 *
	 * @since  1.0.0
	 */
	public function ajax_action_update_communication() {
		do_action(
			'ms_controller_communication_ajax_action_update_communication_before',
			$this
		);

		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'type', 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			lib2()->array->strip_slashes( $_POST, 'value' );

			$comm = MS_Model_Communication::get_communication( $_POST['type'] );
			$field = $_POST['field'];
			$value = $_POST['value'];
			$comm->$field = $value;
			$comm->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		do_action(
			'ms_controller_communication_ajax_action_update_communication_after',
			$this
		);

		echo apply_filters(
			'ms_controller_commnucation_ajax_action_update_communication_msg',
			$msg,
			$this
		);
		exit;
	}

	/**
	 * Auto setup communications.
	 *
	 * Fires after a membership setup is completed.
	 *
	 * Related Action Hooks:
	 * - ms_controller_membership_setup_completed
	 *
	 * @since  1.0.0
	 * @param MS_Model_Membership $membership
	 */
	public function auto_setup_communications( $membership ) {
		/*
		 * Note: We intentionally set the parameter to null. This
		 * function should set up default messages when first membership is
		 * created. It should not override default messages with membership-
		 * specific ones.
		 */
		$comms = MS_Model_Communication::load_communications( null );

		// Private memberships don't have communications enabled
		if ( ! $membership->is_private ) {
			foreach ( $comms as $comm ) {
				$comm->enabled = true;
				$comm->save();
			}
		}

		do_action(
			'ms_controller_communication_auto_setup_communications_after',
			$membership,
			$this
		);
	}

	/**
	 * Prepare WordPress to add our custom TinyMCE button to the WYSIWYG editor.
	 *
	 * @since  1.0.1.0
	 *
	 * @see class-ms-view-settings-edit.php (function render_tab_messages_automated)
	 * @see ms-view-settings-automated-msg.js
	 */
	static public function add_mce_buttons() {
		// Check user permissions.
		if ( ! current_user_can( 'edit_posts' )
			&& ! current_user_can( 'edit_pages' )
		) {
			return;
		}

		// Check if WYSIWYG is enabled.
		if ( 'true' != get_user_option( 'rich_editing' ) ) {
			return;
		}

		add_filter(
			'mce_external_plugins',
			array( __CLASS__, 'add_variables_plugin' )
		);
		add_filter(
			'mce_buttons',
			array( __CLASS__, 'register_variables_button' )
		);
	}

	/**
	 * Associate a javascript file with the new TinyMCE button.
	 *
	 * Hooks Filters:
	 * - mce_external_plugins
	 *
	 * @since  1.0.0
	 *
	 * @param  array $plugin_array List of default TinyMCE plugin scripts.
	 * @return array Updated list of TinyMCE plugin scripts.
	 */
	public static function add_variables_plugin( $plugin_array ) {
		$plugin_url = MS_Plugin::instance()->url;

		// This is a dummy reference (ms-admin.js is always loaded)!
		// Actually this line would not be needed, but WordPress will not show
		// our button when this is missing...
		$plugin_array['ms_variable'] = $plugin_url . 'app/assets/js/ms-admin.js';

		return $plugin_array;
	}

	/**
	 * Register new "Insert variables" button in the editor.
	 *
	 * Hooks Filters:
	 * - mce_buttons
	 *
	 * @since  1.0.0
	 *
	 * @param  array $buttons List of default TinyMCE buttons.
	 * @return array Updated list of TinyMCE buttons.
	 */
	public static function register_variables_button( $buttons ) {
		array_push( $buttons, 'ms_variable' );
		return $buttons;
	}

}