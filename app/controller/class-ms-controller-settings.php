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
	
	
	/**
	 * Capability required to manage Membership settings.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $capability
	 */	
	private $capability = 'manage_options';

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
		$this->add_action( 'load-membership_page_membership-settings', 'admin_settings_manager' );

		$this->add_action( 'admin_print_scripts-membership_page_membership-settings', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-membership_page_membership-settings', 'enqueue_styles' );
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
						'url' => 'admin.php?page=membership-settings&tab=general',
				),
				'pages' => array(
						'title' =>	__( 'Pages', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-settings&tab=pages',
				),
				'payment' => array(
						'title' =>	__( 'Payment', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-settings&tab=payment',
				),
				'gateway' => array(
						'title' =>	__( 'Gateway', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-settings&tab=gateway',
				),
				'messages-protection' => array(
						'title' =>	__( 'Protection Messages', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-settings&tab=messages-protection',
				),
				'messages-automated' => array(
						'title' =>	__( 'Automated Messages', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-settings&tab=messages-automated',
				),
				'downloads' => array(
						'title' =>	__( 'Media / Downloads', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-settings&tab=downloads',
				),
		);
	
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
				if( ! empty( $_GET['action'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) && ! empty( $_GET['setting'] ) ) {
					$msg = $this->save_general( $_GET['action'], array( $_GET['setting'] => 1 ) );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'action', '_wpnonce', 'setting' ) ) ) ) ;
				}
				/**
				 * General tab submit request.
				 */
				elseif( ! empty( $_POST['submit_general'] ) && ! empty( $_POST['action'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
					$msg =  $this->save_general( $_POST['action'], $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) ) ;
				}
				break;
			case 'pages':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				if ( ! empty( $_POST['action'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
					if( ! empty( $_POST['submit_pages'] ) ) {		
						$msg = $this->special_pages_do_action( 'submit_pages', $_POST );
					}
					else {
						$msg = $this->special_pages_do_action( 'create_special_page', $_POST );
					}
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) ) ;
				}
				break;
			case 'gateway':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				/**
				 * Execute table single action.
				 */
				if( ! empty( $_GET['action'] ) && ! empty( $_GET['gateway_id'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {					
					$msg = $this->gateway_list_do_action( $_GET['action'], array( $_GET['gateway_id'] ) );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'gateway_id', 'action', '_wpnonce' ) ) ) ) ;
				}
				/**
				 * Execute bulk actions.
				 */
				elseif( ! empty( $_POST['gateway_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-gateways' ) ) {
					$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
					$msg = $this->gateway_list_do_action( $action, $_POST['gateway_id'] );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) );
				}
				/**
				 * Execute view page action submit.
				 */
				elseif( ! empty( $_POST['submit_gateway'] ) && ! empty( $_POST['gateway_id'] ) && ! empty( $_POST['action'] )  &&
						! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
							
						$msg = $this->gateway_list_do_action( $_POST['action'], array( $_POST['gateway_id'] ), $_POST );
						wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
				}
			break;
			case 'payment':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				/**
				 * Save payment settings tab
				 */
				if ( ! empty( $_POST['submit_payment'] ) && ! empty( $_POST['action'] ) &&
					! empty( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( $_POST[ '_wpnonce' ], $_POST['action'] ) ) {
					$msg = $this->save_general( 'submit_payment', $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
				}
				break;
			case 'messages-protection':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				if ( ! empty( $_POST['submit'] ) && ! empty( $_POST['action'] ) && 
						! empty( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( $_POST[ '_wpnonce' ], $_POST['action'] ) ) {
					
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
					wp_safe_redirect( add_query_arg( array( 'comm_type' => $_POST['comm_type']) ) ) ;
				}
				$this->model = apply_filters( 'membership_model_communication', MS_Model_Communication::get_communication( $type ) );
				
				if ( ! empty( $_POST['save_email'] ) && ! empty( $_POST['action'] ) &&
						! empty( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( $_POST[ '_wpnonce' ], $_POST['action'] ) ) {
					$msg = $this->save_communication( $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg, 'comm_type' => $_POST['type'] ) ) ) ;
				}
				break;
			case 'downloads':
				$this->model = apply_filters( 'ms_model_settings', MS_Plugin::instance()->settings );
				/**
				 * Download tab submit request.
				 */
				if( ! empty( $_POST['submit_downloads'] ) && ! empty( $_POST['action'] ) 
					&& ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
					$msg = $this->save_general( $_POST['action'], $_POST );
					wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) ) ;
				}
				break;	
		}
		
	}
	
	/**
	 * Callback function from 'Membership' navigation.
	 *
	 * Menu Item: Membership > Settings
	 *
	 * @since 4.0.0
	 */
	public function admin_settings() {
		if ( ! empty( $_GET['action'] ) ) {
			$this->prepare_gateway_view();
		}
		else {
			$view = apply_filters( 'ms_view_settings', new MS_View_Settings_Edit() );
			$data['tabs'] = $this->get_tabs();
			$view->data = $data;
			$view->model = $this->model;
			$view->render();
		}
	}

	/**
	 * Prepare and show action view.
	 *
	 * @since 4.0.0
	 */
	public function prepare_gateway_view() {
		if ( 'gateway' == $this->active_tab && 'edit' == $_GET['action'] && ! empty( $_GET['gateway_id'] ) ) {
			$gateway_id = $_GET['gateway_id'];
			if( MS_Model_Gateway::is_valid_gateway( $gateway_id ) ) {
				switch( $gateway_id ) {
					case MS_Model_Gateway::GATEWAY_MANUAL:
						$view = apply_filters( 'ms_view_settings_gateway_manual', new MS_View_Settings_Gateway_Manual(), $gateway_id );
						break;
					case MS_Model_Gateway::GATEWAY_PAYPAL_SINGLE:
					case MS_Model_Gateway::GATEWAY_PAYPAL_STANDARD:
						$view = apply_filters( 'ms_view_settings_gateway_paypal', new MS_View_Settings_Gateway_Paypal(), $gateway_id );
						break;
					case MS_Model_Gateway::GATEWAY_AUTHORIZE:
						$view = apply_filters( 'ms_view_settings_gateway_authorize', new MS_View_Settings_Gateway_Authorize(), $gateway_id );
						break;
					default:
						$view = apply_filters( 'ms_view_settings_gateway', new MS_View_Settings_Gateway(), $gateway_id );
						break;
				}
				$data = array();
				$data['model'] = MS_Model_Gateway::factory( $gateway_id );
				$data['action'] = $_GET['action'];
				$view->data = apply_filters( 'ms_view_settings_gateway_data', $data );
				$view->render();
			}
		}
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
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}

		if( is_array( $settings ) ) {
			foreach( $settings as $field => $value ) {
				switch( $action ) {
					case 'toggle_activation':
						$this->model->$field = ! $this->model->$field;
						break;
					case 'save_general':
					case 'submit_payment':
					case 'save_downloads':
						$this->model->$field = $value;
						break;
				}
			}
			$this->model->save();
			
			/**
			 * Initialise default membership if it is enabled.
			 */
			if( $this->model->default_membership_enabled ) {
				MS_Model_Membership::get_default_membership();
			}
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
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}
		
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
			/**Create special pages */	
			default:
				$special_pages_types = MS_Model_Settings::get_special_page_types();
				foreach( $special_pages_types as $type ) {
					$submit = "create_page_{$type}";
					if( ! empty( $fields[ $submit ] ) ) {
						$this->model->create_special_page( $type );
						$this->model->save();
						$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
					}
				}
			break;
		}
	
		return $msg;
	}
	
	/**
	 * Handle Payment Gateway list actions.
	 * 
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int[] $gateways The gateways IDs to process.
	 * @param mixed[] $fields The data to process.
	 */	
	public function gateway_list_do_action( $action, $gateways, $fields = null ) {
		$msg = MS_Helper_Settings::SETTINGS_MSG_NOT_UPDATED;
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}

		foreach( $gateways as $gateway_id ) {
			$gateway = MS_Model_Gateway::factory( $gateway_id );
			switch( $action ) {
				case 'toggle_activation':
					$gateway->active = ! $gateway->active;
					$gateway->save();
					$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
					break;
				case 'edit':
					foreach( $fields as $field => $value ) {
						if( property_exists( $gateway, $field ) ) {
							$gateway->$field = $value;
						}
					}
					$gateway->save();
					$msg = MS_Helper_Settings::SETTINGS_MSG_UPDATED;
					break;
			}
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
		
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}
		
		if( ! empty( $fields ) ) {
			$protection_message['content'] = isset( $fields['content'] ) ? $fields['content']: '';
			$protection_message['shortcode'] = isset( $fields['shortcode'] ) ? $fields['shortcode']: '';
			$protection_message['more_tag'] = isset( $fields['more_tag'] ) ? $fields['more_tag']: '';
			$this->model->protection_message = $protection_message;
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
		if ( ! current_user_can( $this->capability ) ) {
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
		if( 'gateway' == $this->active_tab ) {
			wp_enqueue_script( 'ms_view_member_ui' );
		}
		if( 'general' == $this->active_tab ) {
			wp_register_script( 'ms-view-settings', MS_Plugin::instance()->url. 'app/assets/js/ms-view-settings.js', array( 'jquery' ), MS_Plugin::instance()->version );
			wp_enqueue_script( 'ms-view-settings' );
		}		
	}
}