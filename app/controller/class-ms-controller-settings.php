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
 * Controller for managing Membership Plugin settings.
 *
 * The primary entry point for managing Membership admin pages.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Settings extends MS_Controller {
	
	const AJAX_ACTION_TOGGLE_SETTINGS = 'toggle_settings';
	
	const AJAX_ACTION_UPDATE_SETTING = 'update_setting';
	
	/**
	 * The model to use for loading/saving Membership settings data.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */	
	private $model;

	/**
	 * View to use for rendering Membership Settings manager.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $views
	 */	
	private $views;

	/**
	 * The current active tab in the vertical navigation.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $active_tab
	 */
	private $active_tab;

	/**
	 * Prepare Membership settings manager.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		$hook = 'protected-content_page_protected-content-settings';
		$this->add_action( 'load-' . $hook, 'admin_settings_manager' );

		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_SETTINGS, 'ajax_action_toggle_settings' );
		
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_UPDATE_SETTING, 'ajax_action_update_setting' );
		
		$this->add_action( 'admin_print_scripts-' . $hook, 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-' . $hook, 'enqueue_styles' );
	}
	
	/**
	 * Handle Ajax toggle action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_toggle_settings
	 *
	 * @since 4.0.0
	 */
	public function ajax_action_toggle_settings() {
		
		$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
		
		$msg = 0;
		if( $this->verify_nonce() && ! empty( $_POST['setting'] ) && $this->is_admin_user() ) {
			$msg = $this->save_general( $_POST['action'], array( $_POST['setting'] => 1 ) );
		}
	
		echo $msg;
		exit;
	}
	
	public function ajax_action_update_setting() {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );

		$required = array( 'field', 'value' );
		if( $this->verify_nonce() && $this->validate_required( $required ) && $this->is_admin_user() ) {
			$msg = $this->save_general( $_POST['action'], array( $_POST['field'] => $_POST['value'] ) );
		}
		
		echo $msg;
		exit;
	}
	
	/**
	 * Show admin notices.
	 *
	 * @since 4.0.0
	 *
	 */
	public function print_admin_message() {
		add_action( 'admin_notices', array( 'MS_Helper_Settings', 'print_admin_message' ) );
	}
	
	/**
	 * Get available tabs for editing the membership.
	 *
	 * @return array The tabs configuration.
	 */
	public function get_tabs() {
		$tabs = array(
				'general' => array(
						'title' =>	__( 'General', MS_TEXT_DOMAIN ),
				),
				'pages' => array(
						'title' =>	__( 'Pages', MS_TEXT_DOMAIN ),
				),
				'payment' => array(
						'title' =>	__( 'Payment', MS_TEXT_DOMAIN ),
				),
				'gateway' => array(
						'title' =>	__( 'Payment Gateways', MS_TEXT_DOMAIN ),
				),
				'messages-protection' => array(
						'title' =>	__( 'Protection Messages', MS_TEXT_DOMAIN ),
				),
				'messages-automated' => array(
						'title' =>	__( 'Automated Messages', MS_TEXT_DOMAIN ),
				),
				'downloads' => array(
						'title' =>	__( 'Media / Downloads', MS_TEXT_DOMAIN ),
				),
		);
	
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEDIA ) ) {
			unset( $tabs['downloads'] );
		}
		
		$page = ! empty( $_GET['page'] ) ? $_GET['page'] : 'protected-content-settings';
		foreach( $tabs as $key => $tab ) {
			$tabs[ $key ]['url'] = "admin.php?page={$page}&tab={$key}";
		}
		
		return apply_filters( 'ms_controller_settings_get_tabs', $tabs );
	}
	/**
	 * Get the current active settings page/tab.
	 *
	 * @since 4.0.0
	 */
	public function get_active_tab() {
		$tabs = $this->get_tabs();
		
		reset( $tabs );
		$first_key = key( $tabs );
	
		/** Setup navigation tabs. */
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : $first_key;
		if ( ! array_key_exists( $active_tab, $tabs ) ) {
			$active_tab = $first_key;
			wp_safe_redirect( add_query_arg( array( 'tab' => $active_tab ) ) );
		}
		return $this->active_tab = apply_filters( 'ms_controller_settings_get_active_tab', $active_tab );
	}
	
	/**
	 * Manages settings actions.
	 *
	 * Verifies GET and POST requests to manage settings.
	 * @since 4.0.0	
	 */
	public function admin_settings_manager() {
		$this->print_admin_message();
		$this->get_active_tab();

		$msg = 0;
		switch( $this->active_tab ) {
			case 'general':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				/**
				 * Admin bar enable request.
				 */
				if( ! empty( $_GET['action'] ) && ! empty( $_GET['setting'] ) && $this->verify_nonce( $_GET['action'], 'GET' ) ) {
					$msg = $this->save_general( $_GET['action'], array( $_GET['setting'] => 1 ) );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'action', '_wpnonce', 'setting' ) ) ) ) ;
				}
				/**
				 * General tab submit request.
				 */
				elseif( ! empty( $_POST['submit_general'] ) && $this->verify_nonce() ) {
					$msg =  $this->save_general( $_POST['action'], $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) ) ;
				}
				break;
			case 'pages':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				if ( $this->verify_nonce() ) {
					if( ! empty( $_POST['submit_pages'] ) ) {		
						$msg = $this->special_pages_do_action( 'submit_pages', $_POST );
					}
					else {
						$msg = $this->special_pages_do_action( 'create_special_page', $_POST );
					}
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) ) ;
				}
				break;
			case 'payment':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				/**
				 * Save payment settings tab
				 */
				if ( ! empty( $_POST['submit_payment'] ) && $this->verify_nonce() ) {
					$msg = $this->save_general( 'submit_payment', $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
				}
				break;
			case 'messages-protection':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				if ( ! empty( $_POST['submit'] ) && $this->verify_nonce() ) {
					
					$msg = $this->save_protection_messages( $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
				}
				break;
			case 'messages-automated':
				$type = MS_Model_Communication::COMM_TYPE_REGISTRATION;
				if( ! empty( $_GET['comm_type'] ) && MS_Model_Communication::is_valid_communication_type( $_GET['comm_type'] ) ) {
					$type = $_GET['comm_type'];
				}
				if( ! empty( $_POST['comm_type'] ) && MS_Model_Communication::is_valid_communication_type( $_POST['comm_type'] ) ) {
					wp_safe_redirect( add_query_arg( array( 'comm_type' => $_POST['comm_type'] ), remove_query_arg( 'msg' ) ) ) ;
				}
				$this->model = apply_filters( 'membership_model_communication', MS_Model_Communication::get_communication( $type ) );
				
				if ( ! empty( $_POST['save_email'] ) && $this->verify_nonce() ) {
					$msg = $this->save_communication( $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg, 'comm_type' => $_POST['type'] ) ) ) ;
				}
				break;
			case 'downloads':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				/**
				 * Download tab submit request.
				 */
				if( ! empty( $_POST['submit_downloads'] ) && $this->verify_nonce() ) {
					$msg = $this->save_general( $_POST['action'], $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) ) ;
				}
				break;
			default:
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				/**
				 * Settings tab submit request.
				 */
				if( ! empty( $_POST['submit_settings'] ) && $this->verify_nonce() ) {
					$msg = $this->save_general( $_POST['action'], $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) ) ;
				}
				break;
		}
		do_action( 'ms_controller_settings_admin_settings_manager_' . $this->active_tab );
		
	}
	
	/**
	 * Callback function from 'Membership' navigation.
	 *
	 * Menu Item: Membership > Settings
	 *
	 * @since 4.0.0
	 */
	public function admin_settings() {
		$action = ! empty( $_GET['action'] ) ? $_GET['action'] : '';
		
		do_action( "ms_controller_settings_{$this->active_tab}_{$action}" );
		
		$view = apply_filters( "ms_controller_settings_{$this->active_tab}_{$action}_view", new MS_View_Settings_Edit() );
		$data['tabs'] = $this->get_tabs();
		$data['settings'] = $this->model;
		$view->data = apply_filters( "ms_controller_settings_{$this->active_tab}_{$action}_data", array_merge( $data, $view->data ) );
		$view->model = $this->model;
		$view->render();
	}

	/**
	 * Save general tab settings.
	 * 
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param string $settings Array of settings to which action will be taken.
	 */
	public function save_general( $action, $settings ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		if( is_array( $settings ) ) {
			foreach( $settings as $field => $value ) {
				switch( $action ) {
					case 'toggle_activation':
					case 'toggle_settings':
						$this->model->$field = ! $this->model->$field;
						break;
					case 'save_general':
					case 'submit_payment':
					case 'save_downloads':
					case 'save_payment_settings':
					default:
						$this->model->$field = $value;
						break;
				}
			}
			$this->model->save();
			
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}
		return $msg;
	}

	/**
	 * Handle Special pages tab actions.
	 *
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param mixed[] $fields The data to process.
	 */
	public function special_pages_do_action( $action, $fields = null ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		do_action( 'ms_controller_settings_special_pages_do_action', $action, $fields );
		
		switch( $action ) {
			case 'submit_pages':
				$special_pages_types = MS_Model_Settings::get_special_page_types();
				$pages = $this->model->pages;
				foreach( $special_pages_types as $type ) {
					if( ! empty( $fields[ $type ] ) ) {
						$pages[ $type ] = $fields[ $type ];
					}
				}
				$this->model->pages = $pages;
				$this->model->save();
				$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
				break;
			case 'create_special_page':
				$special_pages_types = MS_Model_Settings::get_special_page_types();
				foreach( $special_pages_types as $type ) {
					$submit = "create_page_{$type}";
					if( ! empty( $fields[ $submit ] ) ) {
						$this->model->create_special_page( $type, false );
						$this->model->save();
						$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
					}
				}
				break;
		}
	
		return $msg;
	}
	
	/**
	 * Handle saving of Protection messages settings.
	 * 
	 * @since 4.0.0
	 *
	 * @param mixed[] $fields The data to process.
	 */	
	public function save_protection_messages( $fields ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
	
		if( ! empty( $fields ) ) {
			$types = MS_Model_Settings::get_protection_msg_types();

			foreach( $types as $type ) {
				if( isset( $fields[ $type ] ) ) {
					$this->model->set_protection_message( $type, $fields[ $type ] );
				}
			}
			$this->model->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}
		
		return $msg;
	}
	
	/**
	 * Handle saving of Communication settings.
	 *
	 * @since 4.0.0
	 *
	 * @param mixed[] $fields The data to process.
	 */
	public function save_communication( $fields ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		if( ! empty( $fields ) ) {
			$period = array();
			$this->model->enabled = ! empty( $fields['enabled'] );
			$this->model->subject = ! empty( $fields['subject'] ) ? $fields['subject'] : '';
			$this->model->message = ! empty( $fields['message'] ) ? $fields['message'] : '';
			$period['period_unit'] = ! empty( $fields['period_unit'] ) ? $fields['period_unit'] : '';
			$period['period_type'] = ! empty( $fields['period_type'] ) ? $fields['period_type'] : '';
			$this->model->period = $period;
			$this->model->cc_enabled = ! empty( $fields['cc_enabled'] );
			$this->model->cc_email = ! empty( $fields['cc_email'] ) ? $fields['cc_email'] : '';
			$this->model->save();
			$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
		}
		return $msg;
	}
	
	/**
	 * Load Membership admin styles.
	 *
	 * @since 4.0.0
	 */	
	public function enqueue_styles() {
		
		if( 'messages-automated' == $this->active_tab ) {
			wp_enqueue_style( 'ms-view-settings-render-messages-automated', MS_Plugin::instance()->url. 'app/assets/css/ms-view-settings-render-messages-automated.css', null, MS_Plugin::instance()->version );
		}
	}
	
	/**
	 * Load Membership admin scripts.
	 *
	 * @since 4.0.0
	 */		
	public function enqueue_scripts() {
		wp_register_script( 'ms-view-settings', MS_Plugin::instance()->url. 'app/assets/js/ms-view-settings.js', array( 'jquery' ), MS_Plugin::instance()->version );
		wp_enqueue_script( 'ms-view-settings' );
		wp_enqueue_script( 'ms-radio-slider' );
	}
}