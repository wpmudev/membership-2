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
 * @package Membership2
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

		/*
		 * Check if the user wants to manually run the Cron services.
		 * This block calls the action 'ms_run_cron_services' which is defined
		 * in MS_Model_Plugin. It will run all cron jobs and re-schedule them.
		 *
		 * @since 1.1.0
		 */
		if ( isset( $_REQUEST['run_cron'] ) ) {
			$url = esc_url_raw( remove_query_arg( 'run_cron' ) );
			do_action( 'ms_run_cron_services', $_REQUEST['run_cron'] );
			wp_safe_redirect( $url );
			exit();
		}

		$hook = MS_Controller_Plugin::admin_page_hook( 'settings' );
		$this->add_action( 'load-' . $hook, 'admin_settings_manager' );
		$this->add_action( 'ms_controller_membership_setup_completed', 'auto_setup_settings' );

		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_SETTINGS, 'ajax_action_toggle_settings' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_SETTING, 'ajax_action_update_setting' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_CUSTOM_SETTING, 'ajax_action_update_custom_setting' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_PROTECTION_MSG, 'ajax_action_update_protection_msg' );

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
	 * Related action hooks:
	 * * wp_ajax_toggle_settings
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_toggle_settings() {
		$msg = 0;

		$fields = array( 'setting' );
		if ( $this->verify_nonce()
			&& self::validate_required( $fields )
			&& $this->is_admin_user()
		) {
			$msg = $this->save_general(
				$_POST['action'],
				array( $_POST['setting'] => 1 )
			);
		}

		wp_die( $msg );
	}

	/**
	 * Handle Ajax update setting action.
	 *
	 * Related action hooks:
	 * * wp_ajax_update_setting
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			lib2()->array->strip_slashes( $_POST, 'value' );

			$msg = $this->save_general(
				$_POST['action'],
				array( $_POST['field'] => $_POST['value'] )
			);

			// Some settings require to flush WP rewrite rules.
			flush_rewrite_rules();
		}

		wp_die( $msg );
	}

	/**
	 * Handle Ajax update custom setting action.
	 *
	 * Related action hooks:
	 * * wp_ajax_update_custom_setting
	 *
	 * @since 1.0.0
	 */
	public function ajax_action_update_custom_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'group', 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$settings = $this->get_model();
			lib2()->array->strip_slashes( $_POST, 'value' );

			$settings->set_custom_setting(
				$_POST['group'],
				$_POST['field'],
				$_POST['value']
			);
			$settings->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		wp_die( $msg );
	}

	/**
	 * Handle Ajax update protection msg.
	 *
	 * Related action hooks:
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
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
			&& MS_Model_Settings::is_valid_protection_msg_type( $_POST['type'] )
		) {
			$settings = MS_Factory::load( 'MS_Model_Settings' );
			lib2()->array->strip_slashes( $_POST, 'value' );

			$settings->set_protection_message( $_POST['type'], $_POST['value'] );
			$settings->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}

		wp_die( $msg );
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

		// Create special pages.
		MS_Model_Pages::create_missing_pages();

		$pg_prot_cont = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_PROTECTED_CONTENT );
		$pg_acco = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_ACCOUNT );
		$pg_regi = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_REGISTER );
		$pg_regi_comp = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_REG_COMPLETE );
		$pg_memb = MS_Model_Pages::get_page( MS_Model_Pages::MS_PAGE_MEMBERSHIPS );

		// Publish special pages.
		// Tipp: Only pages must be published that are added to the menu.
		wp_publish_post( $pg_acco->ID );
		if ( ! $membership->private ) {
			wp_publish_post( $pg_memb->ID );
			wp_publish_post( $pg_regi->ID );
		}

		// Create new WordPress menu-items.
		MS_Model_Pages::create_menu( MS_Model_Pages::MS_PAGE_ACCOUNT );
		if ( ! $membership->private ) {
			MS_Model_Pages::create_menu( MS_Model_Pages::MS_PAGE_MEMBERSHIPS );
			MS_Model_Pages::create_menu( MS_Model_Pages::MS_PAGE_REGISTER );
		}

		// Enable Membership2.
		$settings->plugin_enabled = true;
		$settings->save();

		// Enable the "Allow user registration" setting of WordPress
		MS_Model_Member::allow_registration();
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
			'payment' => array(
				'title' => __( 'Payment', MS_TEXT_DOMAIN ),
			),
			'messages-protection' => array(
				'title' => __( 'Protection Messages', MS_TEXT_DOMAIN ),
			),
			'messages-automated' => array(
				'title' => __( 'Automated Email Responses', MS_TEXT_DOMAIN ),
			),
			'import' => array(
				'title' => __( 'Import Tool', MS_TEXT_DOMAIN ),
			),
		);

		$def_key = MS_Controller_Plugin::MENU_SLUG . '-settings';
		lib2()->array->equip_get( 'page' );
		$page = sanitize_html_class( $_GET['page'], $def_key );

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
			lib2()->array->equip_get( 'tab' );
			$active_tab = sanitize_html_class( $_GET['tab'], $first_key );

			if ( ! array_key_exists( $active_tab, $tabs ) ) {
				$new_url = esc_url_raw(
					add_query_arg( array( 'tab' => $first_key ) )
				);
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

					if ( self::validate_required( $fields, 'GET' ) ) {
						$msg = $this->save_general( $_GET['action'], array( $_GET['setting'] => 1 ) );

						$redirect = esc_url_raw(
							add_query_arg(
								'msg',
								$msg,
								remove_query_arg( array( 'action', '_wpnonce', 'setting' ) )
							)
						);
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
					if ( self::validate_required( array( 'comm_type' ) )
						&& MS_Model_Communication::is_valid_communication_type( $_POST['comm_type'] )
					) {
						$redirect = esc_url_raw(
							remove_query_arg(
								'msg',
								add_query_arg( 'comm_type', $_POST['comm_type'] )
							)
						);
						break;
					}

					$fields = array( 'type', 'subject', 'email_body' );
					if ( isset( $_POST['save_email'] )
						&& self::validate_required( $fields )
					) {
						$msg = $this->save_communication( $type, $_POST );
						$redirect = esc_url_raw(
							add_query_arg(
								array(
									'comm_type' => urlencode( $_POST['type'] ),
									'msg' => $msg,
								)
							)
						);
						break;
					}
					break;

				case 'import':
					$tool = MS_Factory::create( 'MS_Controller_Import' );

					// Output is passed to the view via self::_message()
					$tool->process();
					break;

				case 'payment':
				case 'messages-protection':
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
		lib2()->array->equip_get( 'action' );
		$action = $_GET['action'];
		$hook = 'ms_controller_settings_' . $this->active_tab . '_' . $action;

		do_action( $hook );

		$view = MS_Factory::create( 'MS_View_Settings_Edit' );
		$view = apply_filters( $hook . '_view', $view );

		$data = array();
		$data['tabs'] = $this->get_tabs();
		$data['settings'] = $this->get_model();

		$data['message'] = self::_message();

		if ( isset( $data['message']['error'] ) ) {
			lib2()->ui->admin_message( $data['message']['error'], 'err' );
		}

		switch ( $this->get_active_tab() ) {
			case 'messages-automated':
				$type = MS_Model_Communication::COMM_TYPE_REGISTRATION;

				$temp_type = isset( $_GET['comm_type'] ) ? $_GET['comm_type'] : '';
				if ( MS_Model_Communication::is_valid_communication_type( $temp_type ) ) {
					$type = $temp_type;
				}

				$comm = apply_filters(
					'membership_model_communication',
					MS_Model_Communication::get_communication( $type, true )
				);

				$data['comm'] = $comm;
				break;

			case 'messages-protection':
				$data['membership'] = MS_Model_Membership::get_base();
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

		$comm = apply_filters(
			'membership_model_communication',
			MS_Model_Communication::get_communication( $type )
		);

		if ( ! empty( $fields ) ) {
			$period = array();
			$comm->enabled = ! empty( $fields['enabled'] );
			$comm->subject = @$fields['subject'];
			$comm->message = @$fields['email_body'];
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
		$initial_url = MS_Controller_Plugin::get_admin_url();

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

			case 'general':
				$data['ms_init'][] = 'view_settings_setup';
				break;
		}

		lib2()->ui->data( 'ms_data', $data );
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
