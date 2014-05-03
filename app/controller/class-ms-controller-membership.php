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

class MS_Controller_Membership extends MS_Controller {
	
	private $post_type;
	
	private $capability = 'manage_options';
	
	private $model;
	
	private $views;
		
	public function __construct() {

		$this->post_type = MS_Model_Membership::$POST_TYPE;
		
		$membership_id = ! empty( $_GET['membership_id'] ) ? $_GET['membership_id'] : 0;
		
		$this->model = apply_filters( 'membership_membership_model', MS_Model_Membership::load( $membership_id ) );
		
		$this->add_action( 'load-membership_page_all-memberships', 'admin_membership_list_manager' );
		$this->add_action( 'load-admin_page_membership-edit', 'membership_edit_manager' );
		
		$this->add_action( 'admin_print_scripts-admin_page_membership-edit', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-admin_page_membership-edit', 'enqueue_styles' );
		
	}
	/**
	 * Show admin notices.
	 * 
	 * @todo Improve messaging, hooking into admin_notices or create a html_helper method
	 * @param number $msg
	 */
	public function print_admin_message( $msg = 0 ) {
		
		if( empty( $msg ) ) {
			$msg = ! empty( $_GET['msg'] ) ? (int) $_GET['msg'] : 0;
		} 
		
		$messages = array(
				1 => __( 'Membership added.', MS_TEXT_DOMAIN ),
				2 => __( 'Membership deleted.', MS_TEXT_DOMAIN ),
				3 => __( 'Membership updated.', MS_TEXT_DOMAIN ),
				4 => __( 'Membership not added.', MS_TEXT_DOMAIN ),
				5 => __( 'Membership not updated.', MS_TEXT_DOMAIN ),
				6 => __( 'Membership not deleted.', MS_TEXT_DOMAIN ),
				7 => __( 'Membership activation toggled.', MS_TEXT_DOMAIN ),
				8 => __( 'Membership activation not toggled.', MS_TEXT_DOMAIN ),
				9 => __( 'Membership status toggled.', MS_TEXT_DOMAIN ),
				10 => __( 'Membership status not toggled.', MS_TEXT_DOMAIN ),
				11 => __( 'Memberships updated.', MS_TEXT_DOMAIN ),
		);
		
		if ( array_key_exists( $msg, $messages ) ) {
			echo '<div id="message" class="updated fade"><p>' . $messages[ $msg ] . '</p></div>';
		}
	}
	
	/**
	 * Manages membership actions.
	 * 
	 * Verifies GET and POST requests to manage memberships
	 */
	public function admin_membership_list_manager() {
		$msg = 0;
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['membership_id'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {
			$msg = $this->membership_list_do_action( $_GET['action'], array( $_GET['membership_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'membership_id', 'action', '_wpnonce' ) ) ) ) ;
		}
		elseif( ! empty( $_POST['membership_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-memberships' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->membership_list_do_action( $action, $_POST['membership_id'] );
		}
		
		$this->print_admin_message( $msg );
	}
	/**
	 * Execute action in Membership model.
	 * 
	 * @param string $action The action to execute.
	 * @param array $membership_ids The membership ids which action will be taken.
	 * @return number Resulting message id.
	 */	
	private function membership_list_do_action( $action, $membership_ids ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		
		$msg = 0;
		foreach( $membership_ids as $membership_id ) {
			$membership = MS_Model_Membership::load( $membership_id );
			switch( $action ) {
				case 'toggle_activation':
					$membership->active = ! $membership->active;
					$membership->save();
					$msg = 7;
					break;
				case 'toggle_public':
					$membership->public = ! $membership->public;
					$membership->save();
					$msg = 9;
					break;
				case 'delete':
					try{
						$membership->delete();
						$msg = 2;
					}
					catch( Exception $e ) {
						$msg = 6;
					}
					break;
			}
		}
		
		return $msg;
	}
	/**
	 * Show admin membership list.
	 * 
	 * Show all memberships available.
	 */
	public function admin_membership_list() {
	
		/** Menu: Memberships */
		$this->views['membership_list'] = apply_filters( 'membership_membership_list_view', new MS_View_Membership_List() );
	
		$this->views['membership_list']->render();
	}
	
	public function membership_edit_manager() {
		$msg = 0;
		/**
		 * Save membership general tab
		 */
		if( ! empty( $_POST['submit'] ) ) {
			$msg = $this->save_membership();
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), add_query_arg( array( 'membership_id' => $this->model->id ) ) ) ) ;
		}
		/**
		 * Copy membership dripped schedule
		 */
		elseif( ! empty( $_POST['copy_dripped'] ) ) {
			$nonce = MS_View_Membership_Edit::DRIPPED_NONCE;
			if ( ! empty( $_POST['membership_copy'] ) && ! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
				$msg = $this->copy_dripped_schedule( $_POST['membership_copy'] );
				wp_safe_redirect( add_query_arg( array( 'msg' => $msg), add_query_arg( array( 'membership_id' => $this->model->id ) ) ) ) ;
			}
		}
		/**
		 * Save membership dripped schedule
		 */
		elseif( ! empty( $_POST['dripped_submit'] ) && ! empty( $_POST['item'] ) && ! empty( $_POST['membership_id'] ) 
				&& ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-rules' ) ) {
			$this->save_dripped_schedule( $_POST['item'] );
		}
		/**
		 * Rule single action 
		 */
		elseif( ! empty( $_GET['action'] ) && ! empty( $_GET['membership_id'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {
			$msg = $this->rule_list_do_action( $_GET['action'], array( $_GET['item'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'action', 'item', '_wpnonce' ) ) ) ) ;
		}
		/**
		 * Rule bulk actions
		 */
		elseif( ! empty( $_POST['membership_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-rules' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->rule_list_do_action( $action, $_POST['item'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), add_query_arg( array( 'membership_id' => $this->model->id ) ) ) ) ;
		}

		$this->print_admin_message( $msg );
		
	}
	/**
	 * Edit membership.
	 * 
	 */
	public function membership_edit() {
		$msg = 0;
		/** New/Edit: Membership */
		$this->views['membership_edit'] = apply_filters( 'membership_membership_edit_view', new MS_View_Membership_Edit() );
		
		$this->views['membership_edit']->model = $this->model;
		
		$this->print_admin_message( $msg );
		$this->views['membership_edit']->render();
	}
	
	/*
	 * Save membership details
	 * TODO better sanitize and validate fields in model
	 */
	private function save_membership() {
		
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		
		/**
		 * Save membership general fields. 
		 */	
		$nonce = MS_View_Membership_Edit::MEMBERSHIP_SAVE_NONCE;
		if ( ! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
			
			$section = MS_View_Membership_Edit::MEMBERSHIP_SECTION;
			if( ! empty( $_POST[ $section ] ) ) {
				foreach( $_POST[ $section ] as $field => $value ) {
					if( property_exists( $this->model, $field ) ) {
						$this->model->$field = sanitize_text_field( $value );  
					}
				}
				$this->model->save();
				
// 				return 3;
			}
		}
	}
		
	/**
	 * Execute action in Rule model.
	 *
	 * @param string $action The action to execute.
	 * @param array $item_ids The item ids which action will be taken.
	 * @return number Resulting message id.
	 */
	
	private function rule_list_do_action( $action, $items ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		$msg = 0;
		$rule_type = ! empty( $_GET['tab'] ) ? $_GET['tab'] : '';
		if( array_key_exists( $rule_type, $this->model->rules ) ) {
			$rule = $this->model->rules[ $rule_type ];
			$rule_value = $rule->rule_value;
				
			foreach( $items as $item ) {
				switch( $action ) {
					case 'give_access':
						$rule_value[ $item ] = $item;
						break;
					case 'no_access':
						unset( $rule_value[ $item ] );
						break;
				}
			}
			$rule->rule_value = $rule_value;
			$this->model->set_rule( $rule_type, $rule );
			$this->model->save();
		}
		return $msg;
	}
	
	private function copy_dripped_schedule( $copy_from_id ) {
		$src_membership = MS_Model_Membership::load( $copy_from_id );
		
		$rule_types = array( 'post', 'page', 'category' ); 
		foreach( $rule_types as $rule_type) {
			$this->model->set_rule( $rule_type, $src_membership->rules[ $rule_type ] );
		}
		$this->model->save();
	}
	
	private function save_dripped_schedule( $items ) {
		if( is_array( $items ) ) {
			$dripped = array();
		
			foreach( $items as $item ) {
				$dripped[ $item['type'] ][ $item['id'] ] = array(
						'period_unit' => $item['period_unit'],
						'period_type' => $item['period_type'],
				);
			}
				
			foreach( $dripped as $rule_type => $drip ) {
				$rule = $this->model->rules[ $rule_type ];
				$rule->dripped = $drip;
				$rule->validate();
				$this->model->set_rule( $rule_type, $rule );
			}
			$this->model->save();
		}
		
	}
	public function enqueue_styles() {
		
		$plugin_url = MS_Plugin::get_plugin_url();
		$version = MS_Plugin::get_plugin_version();

		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		
		if( 'general' == $active_tab ) {
			wp_register_style( 'ms_membership_view_render_general', $plugin_url. 'app/assets/css/ms-view-membership-render-general.css', null, $version );
			wp_enqueue_style( 'ms_membership_view_render_general' );
			wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
		}
		elseif( 'dripped' == $active_tab ) {
			wp_register_style( 'chosen-jquery', $plugin_url. 'app/assets/css/chosen.css', null, $version );
			wp_enqueue_style( 'chosen-jquery' );
			wp_enqueue_style( 'ms-view-membership-render-dripped', $plugin_url. 'app/assets/css/ms-view-membership-render-dripped.css', null, $version );
		}
// 		else if( 'rules' == $active_tab ) {		
// 			wp_register_style( 'ms_rule_view_render_rule', $plugin_url. 'app/assets/css/ms-view-rule-render-rule.css', null, $version );
// 			wp_enqueue_style( 'ms_rule_view_render_rule' );
// 		}
	}
	
		
	public function enqueue_scripts() {
	
		$plugin_url = MS_Plugin::get_plugin_url();
		$version = MS_Plugin::get_plugin_version();
		
		wp_register_script( 'jquery-validate', $plugin_url. 'app/assets/js/jquery.validate.js', array( 'jquery' ), $version );
		wp_register_script( 'jquery-chosen', $plugin_url. 'app/assets/js/chosen.jquery.js', array( 'jquery' ), $version );
		
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		
		if( 'general' == $active_tab ) {
			wp_register_script( 'ms-view-membership-render-general', $plugin_url. 'app/assets/js/ms-view-membership-render-general.js', null, $version );
			wp_enqueue_script( 'ms-view-membership-render-general' );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-validate' );
		}
		elseif( 'dripped' == $active_tab ) {
			wp_register_script( 'ms-view-membership-render-dripped', $plugin_url. 'app/assets/js/ms-view-membership-render-dripped.js', null, $version );
			wp_enqueue_script( 'ms-view-membership-render-dripped' );
			wp_register_script( 'jquery-tmpl', $plugin_url. 'app/assets/js/jquery.tmpl.js', array( 'jquery' ), $version );
			wp_enqueue_script( 'jquery-tmpl' );
			wp_enqueue_script( 'jquery-chosen' );
			wp_enqueue_script( 'jquery-validate' );
		}
		// 		else if( 'rules' == $active_tab ) {		
// 			wp_register_script( 'ms_rule_view_render_rule', $plugin_url. 'app/assets/js/ms-view-rule-render-rule.js', null, $version );
// 			wp_enqueue_script( 'ms_rule_view_render_rule' );
// 		}
	
	}
	
}