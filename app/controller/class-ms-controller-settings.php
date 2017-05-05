<?php
/**
 * Controller for managing Plugin Settings.
 *
 * The primary entry point for managing Membership admin pages.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage Controller
 */
class MS_Controller_Settings extends MS_Controller {

	/**
	 * AJAX action constants.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	const AJAX_ACTION_TOGGLE_SETTINGS = 'toggle_settings';
	const AJAX_ACTION_UPDATE_SETTING = 'update_setting';
	const AJAX_ACTION_UPDATE_CUSTOM_SETTING = 'update_custom_setting';
	const AJAX_ACTION_UPDATE_PROTECTION_MSG = 'update_protection_msg';
	const AJAX_ACTION_TOGGLE_CRON = 'toggle_cron';

	/**
	 * Settings tabs.
	 *
	 * @since 1.0.1.0
	 *
	 * @var   string
	 */
	const TAB_GENERAL = 'general';
	const TAB_PAYMENT = 'payment';
	const TAB_MESSAGES = 'messages';
	const TAB_EMAILS = 'emails';
	const TAB_IMPORT = 'import';

	/**
	 * The current active tab in the vertical navigation.
	 *
	 * @since  1.0.0
	 *
	 * @var string
	 */
	private $active_tab = null;

	/**
	 * Construct Settings manager.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		parent::__construct();

		/*
		 * Check if the user wants to manually run the Cron services.
		 * This block calls the action 'ms_run_cron_services' which is defined
		 * in MS_Model_Plugin. It will run all cron jobs and re-schedule them.
		 *
		 * @since  1.0.0
		 */
		if ( isset( $_REQUEST['run_cron'] ) ) {
			$url = esc_url_raw( remove_query_arg( 'run_cron' ) );
			do_action( 'ms_run_cron_services', $_REQUEST['run_cron'] );
			wp_safe_redirect( $url );
			exit;
		}

		$this->add_action(
			'ms_controller_membership_setup_completed',
			'auto_setup_settings'
		);

		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_SETTINGS, 'ajax_action_toggle_settings' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_SETTING, 'ajax_action_update_setting' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_CUSTOM_SETTING, 'ajax_action_update_custom_setting' );
		$this->add_ajax_action( self::AJAX_ACTION_UPDATE_PROTECTION_MSG, 'ajax_action_update_protection_msg' );
		$this->add_ajax_action( self::AJAX_ACTION_TOGGLE_CRON, 'ajax_action_toggle_cron' );

	}

	/**
	 * Initialize the admin-side functions.
	 *
	 * @since  1.0.0
	 */
	public function admin_init() {
		$hook = MS_Controller_Plugin::admin_page_hook( 'settings' );

		$this->run_action( 'load-' . $hook, 'admin_settings_manager' );
		$this->run_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
	}

	/**
	 * Get settings model
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 */
	public function ajax_action_update_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			lib3()->array->strip_slashes( $_POST, 'value' );

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
	 * @since  1.0.0
	 */
	public function ajax_action_update_custom_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		$isset = array( 'group', 'field', 'value' );
		if ( $this->verify_nonce()
			&& self::validate_required( $isset, 'POST', false )
			&& $this->is_admin_user()
		) {
			$settings = $this->get_model();
			lib3()->array->strip_slashes( $_POST, 'value' );

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
	 * @since  1.0.0
	 */
	public function ajax_action_update_protection_msg() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;

		if ( ! $this->is_admin_user() ) {
			return $msg;
		}

		$isset_update = array( 'type', 'value' );
		$isset_toggle = array( 'field', 'value', 'membership_id' );

		// Update a message.
		if ( $this->verify_nonce() && $this->is_admin_user() ) {
			$settings = $this->get_model();

			if ( self::validate_required( $isset_update, 'POST', false ) ) {
				lib3()->array->strip_slashes( $_POST, 'value' );
				lib3()->array->equip_post( 'membership_id' );

				$settings->set_protection_message(
					$_POST['type'],
					$_POST['value'],
					$_POST['membership_id']
				);
				$settings->save();
				$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
			}

			// Toggle a override message flag.
			elseif ( self::validate_required( $isset_toggle, 'POST', false ) ) {
				$field = $_POST['field'];

				if ( 0 === strpos( $field, 'override_' ) ) {
					$type = substr( $field, 9 );
					if ( lib3()->is_true( $_POST['value'] ) ) {
						$settings->set_protection_message(
							$type,
							$settings->get_protection_message( $type ),
							$_POST['membership_id']
						);
					} else {
						$settings->set_protection_message(
							$type,
							null,
							$_POST['membership_id']
						);
					}

					$settings->save();
					$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
				}
			}
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
	 * @since  1.0.0
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
		// Tip: Only pages must be published that are added to the menu.
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
	 * Get available tabs for editing the membership.
	 *
	 * @since  1.0.0
	 *
	 * @return array The tabs configuration.
	 */
	public function get_tabs() {
		$tabs = array(
			self::TAB_GENERAL => array(
				'title' => __( 'General', 'membership2' ),
			),
			self::TAB_PAYMENT => array(
				'title' => __( 'Payment', 'membership2' ),
			),
			self::TAB_MESSAGES => array(
				'title' => __( 'Protection Messages', 'membership2' ),
			),
			self::TAB_EMAILS => array(
				'title' => __( 'Automated Email Responses', 'membership2' ),
			),
			self::TAB_IMPORT => array(
				'title' => __( 'Import Tool', 'membership2' ),
			),
		);

		$def_key = MS_Controller_Plugin::MENU_SLUG . '-settings';
		lib3()->array->equip_get( 'page' );
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
	 * @since  1.0.0
	 */
	public function get_active_tab() {
		if ( null === $this->active_tab ) {
			if ( ! MS_Controller_Plugin::is_page( 'settings' ) ) {
				$this->active_tab = '';
			} else {
				$tabs = $this->get_tabs();

				reset( $tabs );
				$first_key = key( $tabs );

				// Setup navigation tabs.
				lib3()->array->equip_get( 'tab' );
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
	 * @since  1.0.0
	 */
	public function admin_settings_manager() {
		MS_Helper_Settings::print_admin_message();
		$this->get_active_tab();
		$msg = 0;
		$redirect = false;

		if ( $this->is_admin_user() ) {
			if ( $this->verify_nonce() || $this->verify_nonce( null, 'GET' ) ) {
				/**
				 * After verifying permissions those filters can be used by Add-ons
				 * to process their own settings form.
				 *
				 * @since  1.0.1.0
				 */
				do_action(
					'ms_admin_settings_manager-' . $this->active_tab
				);
				do_action(
					'ms_admin_settings_manager',
					$this->active_tab
				);

				switch ( $this->active_tab ) {
					case self::TAB_GENERAL:
						lib3()->array->equip_request( 'action', 'network_site' );
						$action = $_REQUEST['action'];

						$redirect = esc_url_raw(
							remove_query_arg( array( 'msg' => $msg ) )
						);

						// See if we change settings for the network-wide mode.
						if ( MS_Plugin::is_network_wide() ) {
							$new_site_id = intval( $_REQUEST['network_site'] );

							if ( 'network_site' == $action && ! empty( $new_site_id ) ) {
								$old_site_id = MS_Model_Pages::get_setting( 'site_id' );
								if ( $old_site_id != $new_site_id ) {
									MS_Model_Pages::set_setting( 'site_id', $new_site_id );
									$msg = MS_Helper_Settings::SETTINGS_MSG_SITE_UPDATED;
									$redirect = esc_url_raw(
										add_query_arg( array( 'msg' => $msg ) )
									);
								}
							}
						}
						break;

					case self::TAB_IMPORT:
						$tool = MS_Factory::create( 'MS_Controller_Import' );

						// Output is passed to the view via self::_message()
						$tool->process();
						break;

					case self::TAB_PAYMENT:
					case self::TAB_MESSAGES:
						break;

					default:
						break;
				}
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
	 * @since  1.0.0
	 */
	public function admin_page() {
		$hook = 'ms_controller_settings-' . $this->active_tab;

		do_action( $hook );

		$view = MS_Factory::create( 'MS_View_Settings_Edit' );
		$view = apply_filters( $hook . '_view', $view );

		$data = array();
		$data['tabs'] = $this->get_tabs();
		$data['settings'] = $this->get_model();

		$data['message'] = self::_message();

		if ( isset( $data['message']['error'] ) ) {
			lib3()->ui->admin_message( $data['message']['error'], 'err' );
		}

		switch ( $this->get_active_tab() ) {
			case self::TAB_EMAILS:
				$type = MS_Model_Communication::COMM_TYPE_REGISTRATION;

				$temp_type = isset( $_GET['comm_type'] ) ? $_GET['comm_type'] : '';
				if ( MS_Model_Communication::is_valid_communication_type( $temp_type ) ) {
					$type = $temp_type;
				}

				$comm = MS_Model_Communication::get_communication( $type );

				$data['comm'] = $comm;
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
	 * @since  1.0.0
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
	 * Load Membership admin scripts.
	 *
	 * @since  1.0.0
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
			case self::TAB_PAYMENT:
				add_thickbox();
				$data['ms_init'][] = 'view_settings_payment';
				break;

			case self::TAB_MESSAGES:
				$data['ms_init'][] = 'view_settings_protection';
				break;

			case self::TAB_EMAILS:
				$data['ms_init'][] = 'view_settings_automated_msg';
				break;

			case self::TAB_GENERAL:
				$data['ms_init'][] = 'view_settings_setup';
				break;
		}

		lib3()->ui->data( 'ms_data', $data );
		wp_enqueue_script( 'ms-admin' );
	}
	
	/**
	 * Toggle Cron once enabled or disabled
	 *
	 * This actions is called once the switch in the settings is toggled
	 * It calls th action in MS_Model_Plugin
	 * 
	 * @since 1.0.3.6
	 */
	public function ajax_action_toggle_cron(){
		if ( $this->verify_nonce( 'toggle_settings' )
			&& $this->is_admin_user()
		) {
			do_action( 'ms_toggle_cron', null );
			wp_send_json_success();
		}
		wp_send_json_error();
		
	}
}
