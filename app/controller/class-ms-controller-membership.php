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
	
	private $capability = 'manage_options';
	
	private $model;
	
	private $views;
		
	public function __construct() {

		$membership_id = ! empty( $_GET['membership_id'] ) ? $_GET['membership_id'] : 0;
		
		$this->model = apply_filters( 'membership_membership_model', MS_Model_Membership::load( $membership_id ) );
		
		$this->add_action( 'load-membership_page_all-memberships', 'admin_membership_list_manager' );
		$this->add_action( 'load-admin_page_membership-edit', 'membership_edit_manager' );
		
		$this->add_action( 'admin_print_scripts-admin_page_membership-edit', 'enqueue_scripts' );
		$this->add_action( 'admin_print_scripts-membership_page_all-memberships', 'enqueue_scripts' );		
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
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['membership_id'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			$msg = $this->membership_list_do_action( $_GET['action'], array( $_GET['membership_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'membership_id', 'action', '_wpnonce' ) ) ) ) ;
		}
		elseif( ! empty( $_POST['membership_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-memberships' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->membership_list_do_action( $action, $_POST['membership_id'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) );
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
		$nonce = MS_View_Membership_Edit::MEMBERSHIP_SAVE_NONCE;
		if ( ! empty( $_POST['submit'] ) && ! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
			$section = MS_View_Membership_Edit::MEMBERSHIP_SECTION;
			if( ! empty( $_POST[ $section ] ) ) {
				$msg = $this->save_membership( $_POST[ $section ] );
			}
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
		elseif( ! empty( $_POST['dripped_submit'] ) && ! empty( $_POST['membership_id'] ) 
				&& ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-rules' ) ) {
			$items = ! empty( $_POST['item'] ) ?  $_POST['item'] : null; 
			$this->save_dripped_schedule( $items );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), add_query_arg( array( 'membership_id' => $this->model->id ) ) ) ) ;
		}
		/**
		 * Rule single action 
		 */
		// Changed the nonce for ajax toggles
		elseif( ! empty( $_GET['action'] ) && ! empty( $_GET['membership_id'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], MS_View_Membership_Edit::MEMBERSHIP_SAVE_NONCE ) ) {
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
	 * New/Edit membership.
	 * 
	 */
	public function membership_edit() {
		$msg = 0;
		$this->views['membership_edit'] = apply_filters( 'membership_membership_edit_view', new MS_View_Membership_Edit() );
		
		$this->views['membership_edit']->model = $this->model;
		
		$this->views['membership_edit']->post_by_post_option = apply_filters( 'membership_addon_post_by_post', MS_Plugin::instance()->addon->post_by_post );
		
		$this->print_admin_message( $msg );
		$this->views['membership_edit']->render();
	}
	
	/*
	 * Save membership general tab fields
	 * @param array $fields
	 */
	private function save_membership( $fields ) {
		
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}

		if( is_array( $fields ) ) {
			foreach( $fields as $field => $value ) {
				if( property_exists( $this->model, $field ) ) {
					$this->model->$field = $value;  
				}
			}
			$this->model->trial_period_enabled = ! empty( $fields['trial_period_enabled'] );
			$this->model->save();
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
		$dripped = array(
			'post' => array(),
			'page' => array(),
			'category' => array(),
		);
		
		if( is_array( $items ) ) {
			foreach( $items as $item ) {
				$dripped[ $item['type'] ][ $item['id'] ] = array(
						'period_unit' => $item['period_unit'],
						'period_type' => $item['period_type'],
				);
			}
				
		}
		
		foreach( $dripped as $rule_type => $drip ) {
			$rule = $this->model->rules[ $rule_type ];
			$rule->dripped = $drip;
			$this->model->set_rule( $rule_type, $rule );
		}
		
		$this->model->save();
	}
	
	public function enqueue_styles() {
		
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_register_style( 'chosen-jquery', $plugin_url. 'app/assets/css/chosen.css', null, $version );
		
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		
		if( 'general' == $active_tab ) {
			wp_enqueue_style( 'ms_membership_view_render_general', $plugin_url. 'app/assets/css/ms-view-membership-render-general.css', null, $version );
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style( 'chosen-jquery' );
		}
		elseif( 'dripped' == $active_tab ) {
			wp_enqueue_style( 'chosen-jquery' );
			wp_enqueue_style( 'ms-view-membership-render-dripped', $plugin_url. 'app/assets/css/ms-view-membership-render-dripped.css', null, $version );
		}
	}
	
		
	public function enqueue_scripts() {
	
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_register_script( 'jquery-validate', $plugin_url. 'app/assets/js/jquery.validate.js', array( 'jquery' ), $version );
		wp_register_script( 'jquery-chosen', $plugin_url. 'app/assets/js/chosen.jquery.js', array( 'jquery' ), $version );
		wp_register_script( 'jquery-tmpl', $plugin_url. 'app/assets/js/jquery.tmpl.js', array( 'jquery' ), $version );
		
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		
		if( 'general' == $active_tab ) {
			wp_enqueue_script( 'ms-view-membership-render-general', $plugin_url. 'app/assets/js/ms-view-membership-render-general.js', array( 'jquery' ), $version );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-validate' );
		}
		elseif( 'dripped' == $active_tab ) {
			wp_enqueue_script( 'ms-view-membership-render-dripped', $plugin_url. 'app/assets/js/ms-view-membership-render-dripped.js', array( 'jquery' ), $version );
			wp_enqueue_script( 'jquery-tmpl' );
			wp_enqueue_script( 'jquery-chosen' );
			wp_enqueue_script( 'jquery-validate' );
		}	

		/* Toggle Button Behaviour */
		wp_register_script( 'ms_view_member_ui', MS_Plugin::instance()->url. 'app/assets/js/ms-view-member-ui.js', null, MS_Plugin::instance()->version );
		wp_enqueue_script( 'ms_view_member_ui' );		
	}
	
}