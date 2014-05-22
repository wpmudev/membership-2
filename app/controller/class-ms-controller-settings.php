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

class MS_Controller_Settings extends MS_Controller {
	
	private $capability = 'manage_options';
	
	private $model;
	
	private $views;
	
	private $active_tab;
		
	public function __construct() {
		$this->add_action( 'load-membership_page_membership-settings', 'admin_settings_manager' );

		$this->add_action( 'admin_print_scripts-membership_page_membership-settings', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-membership_page_membership-settings', 'enqueue_styles' );
	}
	
	public function get_active_tab() {
		$this->active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
	}
	
	/**
	 * Manages settings actions.
	 *
	 * Verifies GET and POST requests to manage settings.
	 */
	public function admin_settings_manager() {
		$this->get_active_tab();
		switch( $this->active_tab ) {
			case 'general':
			case 'pages':
				$this->model = apply_filters( 'membership_model_settings', MS_Plugin::instance()->settings );
				break;
			case 'payment':
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
				elseif( ! empty( $_POST['submit'] ) ) {
					$section = MS_View_Settings_Gateway::GATEWAY_SECTION;
					$nonce = MS_View_Settings_Gateway::GATEWAY_NONCE;
					if ( ! empty( $_POST[ $section ]['gateway_id'] ) && ! empty( $_POST[ $section ]['action'] )  &&
						! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
							
						$msg = $this->gateway_list_do_action( $_POST[ $section ]['action'], array( $_POST[ $section ]['gateway_id'] ), $_POST[ $section ] );
						wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
					}
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
				
				$nonce = MS_View_Settings::COMM_NONCE;
				if( ! empty( $_POST['save_email'] ) && MS_Model_Communication::is_valid_communication_type( $_POST['type'] )
						&& ! empty( $_POST[ $nonce ] )  && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
					$this->save_communication( $_POST );
					wp_safe_redirect( add_query_arg( array( 'comm_type' => $_POST['type']) ) ) ;
				}
				break;
		}
		$msg = 0;
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) && ! empty( $_GET['setting'] ) ) {
			$this->save_general( $_GET['action'], array( $_GET['setting'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'action', '_wpnonce', 'setting' ) ) ) ) ;
		}
		
	}
	/**
	 * Menu Settings.
	 */
	public function admin_settings() {
		if ( ! empty( $_GET['action'] ) ) {
			$this->prepare_action_view();
		}
		else {
			$view = apply_filters( 'ms_view_settings', new MS_View_Settings() );
			$view->model = $this->model;
			$view->render();
		}
	}
	/**
	 * Prepare and show action view.
	 *
	 */
	public function prepare_action_view() {
		if ( 'payment' == $this->active_tab && 'edit' == $_GET['action'] && ! empty( $_GET['gateway_id'] ) ) {
			$gateway_id = $_GET['gateway_id'];
			if( MS_Model_Gateway::is_valid_gateway( $gateway_id ) ) {
				switch( $gateway_id ) {
					case 'paypal_single_gateway':
					case 'paypal_standard_gateway':
						$view = apply_filters( 'ms_view_settings_gateway_paypal', new MS_View_Settings_Gateway_Paypal(), $gateway_id );
						break;
					default:
						$view = apply_filters( 'ms_view_settings_gateway', new MS_View_Settings_Gateway(), $gateway_id );
						break;
				}
				$data = array();
				$data['model'] = MS_Model_Gateway::factory( $gateway_id );
				$data['action'] = $_GET['action'];
				$view->data = $data;
				$view->render();
			}
		}
	}
	/**
	 * Save general tab settings.
	 * 
	 * @param string $action The action to execute.
	 * @param string $settings Array of settings to which action will be taken.
	 */
	public function save_general( $action, $settings ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
				
		if( is_array( $settings ) ) {
			foreach( $settings as $setting ) {
				switch( $action ) {
					case 'toggle_activation':
						$this->model->$setting = ! $this->model->$setting; 
						break;
				}
			}
			$this->model->save();
		}
	}
	
	public function gateway_list_do_action( $action, $gateways, $fields ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		$msg = 0;
		foreach( $gateways as $gateway_id ) {
			$gateway = MS_Model_Gateway::factory( $gateway_id );
			switch( $action ) {
				case 'toggle_activation':
					$gateway->active = ! $gateway->active;
					$gateway->save();
					$msg = 7;
					break;
				case 'edit':
					foreach( $fields as $field => $value ) {
						if( property_exists( $gateway, $field ) ) {
							$gateway->$field = $value;
						}
					}
					$gateway->save();
					break;
			}
		}
		
		return $msg;
	}
	public function save_communication( $fields ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		if( ! empty( $fields ) ) {
			$period = array();
			$this->model->enabled = ! empty( $fields['enabled'] );
			$this->model->cc_email = ! empty( $fields['enabled'] ) ? $fields['enabled'] : '';
			$this->model->subject = ! empty( $fields['subject'] ) ? $fields['subject'] : '';
			$this->model->message = ! empty( $fields['message'] ) ? $fields['message'] : '';
			$period['period_unit'] = ! empty( $fields['period_unit'] ) ? $fields['period_unit'] : '';
			$period['period_type'] = ! empty( $fields['period_type'] ) ? $fields['period_type'] : '';
			$this->model->period = $period;
			$this->model->cc_enabled = ! empty( $fields['cc_enabled'] );
			$this->model->save();
		}
	}
	
	public function enqueue_styles() {
		
		if( 'messages-automated' == $this->active_tab ) {
			wp_enqueue_style( 'ms-view-settings-render-messages-automated', MS_Plugin::instance()->url. 'app/assets/css/ms-view-settings-render-messages-automated.css', null, MS_Plugin::instance()->version );
		}
	}
	
	
	public function enqueue_scripts() {
		wp_register_script( 'ms_view_member_ui', MS_Plugin::instance()->url. 'app/assets/js/ms-view-member-ui.js', null, MS_Plugin::instance()->version );
		wp_enqueue_script( 'ms_view_member_ui' );				
	}
}