<?php
/**
 * This file defines the MS_Controller_Membership class.
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
 * Controller for managing Memberships and Membership Rules.
 *
 * Focuses on Memberships and specifying access to content.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Membership extends MS_Controller {
	
	/**
	 * Capability required to manage Memberships.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $capability
	 */	
	private $capability = 'manage_options';

	/**
	 * The model to use for loading/saving Membership data.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */	
	private $model;

	/**
	 * View to use for rendering Membership settings.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $views
	 */	
	private $views;
	
	/**
	 * Prepare the Membership manager.
	 *
	 * @since 4.0.0
	 */
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
	 *
	 * @since 4.0.0
	 *
	 * @param int $msg
	 */
	public function print_admin_message( $msg = 0 ) {
		
		if( empty( $msg ) ) {
			$msg = ! empty( $_GET['msg'] ) ? (int) $_GET['msg'] : 0;
		} 
		
		// TODO: We could always create an /app/ level class that only specifies contants (almost like enums)
		//       E.g. MS_Constant::MEMBERSHIP_MSG_ADDED, MS_Constant::MEMBERSHIP_MSG_DELETED
		//       Then below,  
		//       MS_Contant::MEMBERSHIP_MSG_ADDED => __( 'Membership added.', MS_TEXT_DOMAIN ),
		//       MS_Constant::MEMBERSHIP_MSG_DELETED => __( 'Membership deleted.', MS_TEXT_DOMAIN ),
		//      
		//       Then we can reuse these constants elsewhere.
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
	 *
	 * @since 4.0.0
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
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int[] $membership_ids The membership ids which action will be taken.
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
	 *
	 * @since 4.0.0
	 */
	public function admin_membership_list() {
	
		/** Menu: Memberships */
		$this->views['membership_list'] = apply_filters( 'membership_membership_list_view', new MS_View_Membership_List() );
	
		$this->views['membership_list']->render();
	}
	
	/**
	 * Handles Membership form/AJAX submissions.
	 * 
	 * @since 4.0.0
	 */
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
		/**
		 * Save url group add/edit
		 */
		elseif ( ! empty( $_POST['url_group_submit'] ) && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], MS_View_Membership_Edit::URL_GROUP_NONCE ) ) {
			$msg = $this->save_url_group( $_POST );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), add_query_arg( array( 'membership_id' => $this->model->id ) ) ) ) ;
		}
		$this->print_admin_message( $msg );
		
	}

	/**
	 * New/Edit membership.
	 * 
	 * @since 4.0.0
	 */
	public function membership_edit() {
		$msg = 0;
		$this->views['membership_edit'] = apply_filters( 'membership_membership_edit_view', new MS_View_Membership_Edit() );
		
		$this->views['membership_edit']->model = $this->model;
		
		$this->views['membership_edit']->post_by_post_option = apply_filters( 'membership_addon_post_by_post', MS_Plugin::instance()->addon->post_by_post );
		
		$this->print_admin_message( $msg );
		$this->views['membership_edit']->render();
	}
	
	/**
	 * Save membership general tab fields
	 *
	 * @since 4.0.0 
	 *
	 * @param mixed[] $fields
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
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int[] $items The item ids which action will be taken.
	 * @return int Resulting message id.
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
	
	/**
	 * Coppy 'dripped content' schedule from one Membership to another.
	 *
	 * @since 4.0.0
	 *
	 * @param int $copy_from_id The Membership ID to copy from.
	 */	
	private function copy_dripped_schedule( $copy_from_id ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		
		$src_membership = MS_Model_Membership::load( $copy_from_id );
		
		$rule_types = array( 'post', 'page', 'category' ); 
		foreach( $rule_types as $rule_type) {
			$this->model->set_rule( $rule_type, $src_membership->rules[ $rule_type ] );
		}
		$this->model->save();
	}
	
	/**
	 * Save new 'dripped content' schedule(s).
	 *
	 * @since 4.0.0
	 *
	 * @param mixed[] $items The item ids which action will be taken.
	 */	
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
	
	private function save_url_group( $fields ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		if( is_array( $fields ) ) {
			$rule_type = 'url_group';
			$rule = $this->model->rules[ $rule_type ];
 
			foreach( $fields as $field => $value ) {
				$rule->$field = $value;
			}
			$this->model->set_rule( $rule_type, $rule );
			$this->model->save();
		}
		
	}
	/**
	 * Load Membership manager specific styles.
	 *
	 * @since 4.0.0
	 */			
	public function enqueue_styles() {
		
		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_register_style( 'chosen-jquery', $plugin_url. 'app/assets/css/chosen.css', null, $version );
		
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		
		if( 'general' == $active_tab ) {
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style( 'chosen-jquery' );
		}
		elseif( 'dripped' == $active_tab ) {
			wp_enqueue_style( 'chosen-jquery' );
		}
		wp_enqueue_style( 'ms_membership_view_edit', $plugin_url. 'app/assets/css/ms-view-membership-edit.css', null, $version );
	}
	
	/**
	 * Load Membership manager specific scripts.
	 *
	 * @since 4.0.0
	 */				
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
		elseif( 'urlgroup' == $active_tab ) {
			wp_register_script( 'ms-view-membership-render-url-group', $plugin_url. 'app/assets/js/ms-view-membership-render-url-group.js', array( 'jquery' ), $version );
			wp_localize_script( 'ms-view-membership-render-url-group', 'ms', array( 
				'valid_rule_msg' => __( 'Valid', MS_TEXT_DOMAIN ),
				'invalid_rule_msg' => __( 'Invalid', MS_TEXT_DOMAIN ),
				'empty_msg'	=> __( 'Add Page URLs to the group in case you want to test it against', MS_TEXT_DOMAIN ),
				'nothing_msg' => __( 'Enter an URL above to test against rules in the group', MS_TEXT_DOMAIN ),
			));
			wp_enqueue_script( 'ms-view-membership-render-url-group' );
		}
		else {
			/* Toggle Button Behaviour */
			wp_enqueue_script( 'ms_view_member_ui' );				
		}
	}
	
}