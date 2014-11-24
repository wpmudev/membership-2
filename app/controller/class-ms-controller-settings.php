<?php
/**
 * This file defines the MS_Controller_Settings class.
 *
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
 * Controller for managing Plugin Settings.
 *
 * The primary entry point for managing Membership admin pages.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Settings extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_SETTINGS = 'toggle_settings';
	const AJAX_ACTION_UPDATE_SETTING = 'update_setting';
	const AJAX_ACTION_UPDATE_CUSTOM_SETTING = 'update_custom_setting';
	const AJAX_ACTION_UPDATE_PROTECTION_MSG = 'update_protection_msg';

	/**
	 * The current active tab in the vertical navigation.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $active_tab = null;

	/**
	 * Construct Settings manager.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$hook = 'protect-content_page_protected-content-settings';
		$this->add_action( 'load-' . $hook, 'admin_settings_manager' );
		$this->add_action( 'ms_controller_membership_setup_completed', 'auto_setup_settings' );

		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_SETTINGS, 'ajax_action_toggle_settings' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_SETTING, 'ajax_action_update_setting' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_CUSTOM_SETTING, 'ajax_action_update_custom_setting' );
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_PROTECTION_MSG, 'ajax_action_update_protection_msg' );

		$this->add_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );

		// Add custom buttons to the MCE editor (insert variable).
		$this->add_action( 'admin_head-' . $hook, 'add_mce_buttons' );
	}

	/**
	 * Get settings model
	 *
	 * @since 1.0.0
	 *
	 * @return MS_Model_Settings
	 */
	public function get_model() {
		return MS_Factory::load( 'MS_Model_Settings' );
	}

	/**
	 * Handle Ajax toggle action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_toggle_settings
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_toggle_settings() {
		$msg = 0;

		$fields = array( 'setting' );
		if ( $this->verify_nonce()
			&& $this->validate_required( $fields )
			&& $this->is_admin_user()
		) {
			$msg = $this->save_general( $_POST['action'], array( $_POST['setting'] => 1 ) );
		}

		echo $msg;
		exit;
	}

	/**
	 * Handle Ajax update setting action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_update_setting
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& $this->validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$msg = $this->save_general(
				$_POST['action'],
				array( $_POST['field'] => $_POST['value'] )
			);
		}

		echo $msg;
		exit;
	}

	/**
	 * Handle Ajax update custom setting action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_update_custom_setting
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_custom_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'group', 'field', 'value' );
		if ( $this->verify_nonce()
			&& $this->validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$settings = $this->get_model();
			$settings->set_custom_setting( $_POST['group'], $_POST['field'], $_POST['value'] );
			$settings->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		echo $msg;
		exit;
	}

	/**
	 * Handle Ajax update protection msg.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_update_protection_msg
	 *
	 * @since 1.0
	 */
	public function ajax_action_update_protection_msg() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		if ( ! $this->is_admin_user() ) {
			return $msg;
		}
		$settings = $this->get_model();

		$isset = array( 'type', 'value' );
		if ( $this->verify_nonce()
			&& $this->validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
			&& MS_Model_Settings::is_valid_protection_msg_type( $_POST['type'] )
		) {
			$settings = MS_Factory::load( 'MS_Model_Settings' );
			$settings->set_protection_message( $_POST['type'], $_POST['value'] );
			$settings->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		echo $msg;
		exit;
	}

	/**
	 * Auto setup settings.
	 *
	 * Fires after a membership setup is completed.
	 * This hook is executed every time a new membership is created.
	 *
	 * Related Action Hooks:
	 * - ms_controller_membership_setup_completed
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Membership $membership
	 */
	public function auto_setup_settings( $membership ) {
		$settings = $this->get_model();
		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );

		// Create special pages.
		$pg_prot_cont = $ms_pages->get_ms_page( MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT, true );
		$pg_acco = $ms_pages->get_ms_page( MS_Model_Pages::MS_PAGE_ACCOUNT, true );
		$pg_regi = $ms_pages->get_ms_page( MS_Model_Pages::MS_PAGE_REGISTER, true );
		$pg_regi_comp = $ms_pages->get_ms_page( MS_Model_Pages::MS_PAGE_REG_COMPLETE, true );
		$pg_memb = $ms_pages->get_ms_page( MS_Model_Pages::MS_PAGE_MEMBERSHIPS, true );

		// Publish special pages.
		// Tipp: Only pages must be published that are added to the menu.
		$pg_acco->set_page_status( 'publish' );
		if ( ! $membership->private ) {
			$pg_memb->set_page_status( 'publish' );
			$pg_regi->set_page_status( 'publish' );
		}

		// Create new WordPress menu-items.
		$ms_pages->create_menu( MS_Model_Pages::MS_PAGE_ACCOUNT );
		if ( ! $membership->private ) {
			$ms_pages->create_menu( MS_Model_Pages::MS_PAGE_MEMBERSHIPS );
			$ms_pages->create_menu( MS_Model_Pages::MS_PAGE_REGISTER );
		}

		// Enable Protected Content.
		$settings->plugin_enabled = true;
		$settings->save();

		MS_Plugin::flush_rewrite_rules();
	}

	/**
	 * Show admin notices.
	 *
	 * @since 1.0.0
	 *
	 */
	public function print_admin_message() {
		add_action(
			'admin_notices',
			array( 'MS_Helper_Settings', 'print_admin_message' )
		);
	}

	/**
	 * Get available tabs for editing the membership.
	 *
	 * @since 1.0.0
	 *
	 * @return array The tabs configuration.
	 */
	public function get_tabs() {
		$tabs = array(
			'general' => array(
				'title' => __( 'General', MS_TEXT_DOMAIN ),
			),
			'pages' => array(
				'title' => __( 'Membership Site Pages', MS_TEXT_DOMAIN ),
			),
			'payment' => array(
				'title' => __( 'Payment', MS_TEXT_DOMAIN ),
			),
			'messages-protection' => array(
				'title' => __( 'Protection Messages', MS_TEXT_DOMAIN ),
			),
			'messages-automated' => array(
				'title' => __( 'Automated Email Responses', MS_TEXT_DOMAIN ),
			),
			'downloads' => array(
				'title' => __( 'Media / Downloads', MS_TEXT_DOMAIN ),
			),
			'import' => array(
				'title' => __( 'Import Tool', MS_TEXT_DOMAIN ),
			),
		);

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
			unset( $tabs['downloads'] );
		}

		$def_key = MS_Controller_Plugin::MENU_SLUG . '-settings';
		$page = sanitize_html_class( @$_GET['page'], $def_key );

		foreach ( $tabs as $key => $tab ) {
			$tabs[ $key ]['url'] = sprintf(
				'admin.php?page=%1$s&tab=%2$s',
				esc_attr( $page ),
				esc_attr( $key )
			);
		}

		return apply_filters( 'ms_controller_settings_get_tabs', $tabs, $this );
	}

	/**
	 * Get the current active settings page/tab.
	 *
	 * @since 1.0.0
	 */
	public function get_active_tab() {
		if ( null === $this->active_tab ) {
			$tabs = $this->get_tabs();

			reset( $tabs );
			$first_key = key( $tabs );

			// Setup navigation tabs.
			$active_tab = sanitize_html_class( @$_GET['tab'], $first_key );
			if ( ! array_key_exists( $active_tab, $tabs ) ) {
				$new_url = add_query_arg( array( 'tab' => $first_key ) );
				wp_safe_redirect( $new_url );
				exit;
			} else {
				$this->active_tab = apply_filters(
					'ms_controller_settings_get_active_tab',
					$active_tab
				);
			}
		}

		return apply_filters(
			'ms_controller_settings_get_active_tab',
			$this->active_tab,
			$this
		);
	}

	/**
	 * Manages settings actions.
	 *
	 * Verifies GET and POST requests to manage settings.
	 *
	 * @since 1.0.0
	 */
	public function admin_settings_manager() {
		$this->print_admin_message();
		$this->get_active_tab();

		$msg = 0;
		$redirect = false;
		do_action( 'ms_controller_settings_admin_settings_manager_' . $this->active_tab );

		if ( $this->is_admin_user()
			&& ( $this->verify_nonce() || $this->verify_nonce( null, 'GET' ) )
		) {
			switch ( $this->active_tab ) {
				case 'general':
					// Admin bar enable request.
					$fields = array( 'action', 'setting' );

					if ( $this->validate_required( $fields, 'GET' ) ) {
						$msg = $this->save_general( $_GET['action'], array( $_GET['setting'] => 1 ) );

						$redirect = remove_query_arg( array( 'action', '_wpnonce', 'setting' ) );
						$redirect = add_query_arg( 'msg', $msg, $redirect );
						break;
					}
					break;

				case 'messages-automated':
					$type = MS_Model_Communication::COMM_TYPE_REGISTRATION;
					if ( ! empty( $_GET['comm_type'] )
						&& MS_Model_Communication::is_valid_communication_type( $_GET['comm_type'] )
					) {
						$type = $_GET['comm_type'];
					}

					// Load comm type from user select
					if ( $this->validate_required( array( 'comm_type' ) )
						&& MS_Model_Communication::is_valid_communication_type( $_POST['comm_type'] )
					) {
						$redirect = add_query_arg( 'comm_type', $_POST['comm_type'] );
						$redirect = remove_query_arg( 'msg', $redirect );
						break;
					}

					$fields = array( 'type', 'subject', 'message' );
					if ( isset( $_POST['save_email'] )
						&& $this->validate_required( $fields )
					) {
						$msg = $this->save_communication( $type, $_POST );
						$redirect = add_query_arg( 'comm_type', $_POST['type'] );
						$redirect = add_query_arg( 'msg', $msg, $redirect );
						break;
					}
					break;

				case 'import':
					$tool = MS_Factory::create( 'MS_Controller_Import' );
					$tool->process();
					break;

				case 'pages':
				case 'payment':
				case 'messages-protection':
				case 'downloads':
				default:
					break;
			}
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect );
			exit();
		}
	}

	/**
	 * Callback function from 'Membership' navigation.
	 *
	 * Menu Item: Membership > Settings
	 *
	 * @since 1.0.0
	 */
	public function admin_settings() {
		$action = @$_GET['action'];
		$hook = 'ms_controller_settings_' . $this->active_tab . '_' . $action;

		do_action( $hook );

		$view = MS_Factory::create( 'MS_View_Settings_Edit' );
		$view = apply_filters( $hook . '_view', $view );

		$data = array();
		$data['tabs'] = $this->get_tabs();
		$data['settings'] = $this->get_model();
		$data['message'] = self::_message();

		if ( isset( $data['message']['error'] ) ) {
			WDev()->message( $data['message']['error'], 'err' );
		}

		switch ( $this->get_active_tab() ) {
			case 'messages-automated':
				$type = MS_Model_Communication::COMM_TYPE_REGISTRATION;

				if ( MS_Model_Communication::is_valid_communication_type( @$_GET['comm_type'] ) ) {
					$type = $_GET['comm_type'];
				}

				$comm = apply_filters(
					'membership_model_communication',
					MS_Model_Communication::get_communication( $type, true )
				);

				$data['comm'] = $comm;
				break;

			case 'pages':
				$data['ms_pages'] = MS_Factory::load( 'MS_Model_Pages' )->get_ms_pages( true );
				$data['page_types'] = MS_Model_Pages::get_ms_page_types();
				break;

			case 'messages-protection':
				$data['membership'] = MS_Model_Membership::get_protected_content();
				break;
		}

		$data = array_merge( $data, $view->data );
		$view->data = apply_filters( $hook . '_data', $data );
		$view->model = $this->get_model();
		$view->render();
	}

	/**
	 * Save general tab settings.
	 *
	 * @since 1.0.0
	 *
	 * @param string $action The action to execute.
	 * @param string $settings Array of settings to which action will be taken.
	 */
	public function save_general( $action, $fields ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$settings = $this->get_model();

		if ( is_array( $fields ) ) {
			foreach ( $fields as $field => $value ) {
				switch ( $action ) {
					case 'toggle_activation':
					case 'toggle_settings':
						$settings->$field = ! $settings->$field;
						break;

					case 'save_general':
					case 'submit_payment':
					case 'save_downloads':
					case 'save_payment_settings':
					case 'update_setting':
					default:
						$settings->$field = $value;
						break;
				}
			}
			$settings->save();

			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		return apply_filters(
			'ms_controller_settings_save_general',
			$msg,
			$action,
			$fields,
			$this
		);
	}

	/**
	 * Handle saving of Communication settings.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $fields The data to process.
	 */
	public function save_communication( $type, $fields ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$comm = apply_filters( 'membership_model_communication', MS_Model_Communication::get_communication( $type ) );

		if ( ! empty( $fields ) ) {
			$period = array();
			$comm->enabled = ! empty( $fields['enabled'] );
			$comm->subject = @$fields['subject'];
			$comm->message = @$fields['message'];
			$period['period_unit'] = @$fields['period_unit'];
			$period['period_type'] = @$fields['period_type'];
			$comm->period = $period;
			$comm->cc_enabled = ! empty( $fields['cc_enabled'] );
			$comm->cc_email = @$fields['cc_email'];
			$comm->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		return apply_filters(
			'ms_controller_settings_save_communication',
			$type,
			$fields,
			$this
		);
	}

	/**
	 * Load Membership admin scripts.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		$active_tab = $this->get_active_tab();
		do_action( 'ms_controller_settings_enqueue_scripts_' . $active_tab );

		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		$initial_url = add_query_arg( array( 'page' => MS_Controller_Plugin::MENU_SLUG ), admin_url( 'admin.php' ) );

		$data = array(
			'ms_init' => array(),
			'initial_url' => $initial_url,
		);

		$data['ms_init'][] = 'view_settings';

		switch ( $active_tab ) {
			case 'payment':
				add_thickbox();
				$data['ms_init'][] = 'view_settings_payment';
				break;

			case 'messages-protection':
				$data['ms_init'][] = 'view_settings_protection';
				break;

			case 'messages-automated':
				$data['ms_init'][] = 'view_settings_automated_msg';
				break;
		}

		wp_localize_script( 'ms-admin', 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}

	/**
	 * Prepare WordPress to add our custom TinyMCE button to the WYSIWYG editor.
	 *
	 * @since 1.0.0
	 *
	 * @see class-ms-view-settings-edit.php (function render_tab_messages_automated)
	 * @see ms-view-settings-automated-msg.js
	 */
	public function add_mce_buttons() {
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

		// Check the current tab.
		switch ( $this->get_active_tab() ) {
			case 'messages-automated':
				$this->add_filter( 'mce_external_plugins', 'add_variables_plugin' );
				$this->add_filter( 'mce_buttons', 'register_variables_button' );
				break;
		}
	}

	/**
	 * Associate a javascript file with the new TinyMCE button.
	 *
	 * **Hooks Filters: **
	 * * mce_external_plugins
	 *
	 * @since 1.0.0
	 *
	 * @param  array $plugin_array List of default TinyMCE plugin scripts.
	 * @return array Updated list of TinyMCE plugin scripts.
	 */
	public function add_variables_plugin( $plugin_array ) {
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
	 * **Hooks Filters: **
	 * * mce_buttons
	 *
	 * @since 1.0.0
	 *
	 * @param  array $buttons List of default TinyMCE buttons.
	 * @return array Updated list of TinyMCE buttons.
	 */
	public function register_variables_button( $buttons ) {
		array_push( $buttons, 'ms_variable' );
		return $buttons;
	}
}
