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
	 * The current active tab in the vertical navigation.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $active_tab
	 */
	private $active_tab;
	
	
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
	 * @since 4.0.0
	 *
	 */
	public function print_admin_message() {
		add_action( 'admin_notices', array( 'MS_Helper_Membership', 'print_admin_message' ) );
	}
	
	/**
	 * Manages membership actions.
	 * 
	 * Verifies GET and POST requests to manage memberships
	 *
	 * @since 4.0.0
	 */
	public function admin_membership_list_manager() {
		$this->print_admin_message();
		$msg = 0;
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['membership_id'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $_GET['action'] ) ) {
			$msg = $this->membership_list_do_action( $_GET['action'], array( $_GET['membership_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'membership_id', 'action', '_wpnonce' ) ) ) ) ;
		}
		elseif( ! empty( $_POST['membership_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-memberships' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->membership_list_do_action( $action, $_POST['membership_id'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) );
		}
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
			return MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		}
		
		$msg = 0;
		foreach( $membership_ids as $membership_id ) {
			$membership = MS_Model_Membership::load( $membership_id );
			switch( $action ) {
				case 'toggle_activation':
					$membership->active = ! $membership->active;
					$membership->save();
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_ACTIVATION_TOGGLED;
					break;
				case 'toggle_public':
					$membership->public = ! $membership->public;
					$membership->save();
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_STATUS_TOGGLED;
					break;
				case 'delete':
					try{
						$membership->delete();
						$msg = MS_Helper_Membership::MEMBERSHIP_MSG_DELETED;
					}
					catch( Exception $e ) {
						$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_DELETED;
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
		$this->print_admin_message();

		$this->active_tab = $this->get_active_tab();
		$msg = 0;
		/**
		 * Save membership general tab
		 */
		if ( ! empty( $_POST['submit'] ) && ! empty( $_POST['action'] ) && 
			! empty( $_POST[ '_wpnonce' ] ) && wp_verify_nonce( $_POST[ '_wpnonce' ], $_POST['action'] ) ) {
			$section = MS_View_Membership_Edit::MEMBERSHIP_SECTION;
			if( ! empty( $_POST[ $section ] ) ) {
				$msg = $this->save_membership( $_POST[ $section ] );
			}
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg, 'membership_id' => $this->model->id ) ) );
		}
		/**
		 * Copy membership dripped schedule
		 */
		elseif( ! empty( $_POST['copy_dripped'] ) && ! empty( $_POST['membership_copy'] ) && ! empty( $_POST['_wpnonce'] ) && 
					! empty( $_POST['action'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
			$msg = $this->copy_dripped_schedule( $_POST['membership_copy'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg, 'membership_id' => $this->model->id ) ) );
		}
		/**
		 * Save membership dripped schedule
		 */
		elseif( ! empty( $_POST['dripped_submit'] ) && ! empty( $_POST['membership_id'] ) &&
				! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-rules' ) ) {
			$items = ! empty( $_POST['item'] ) ?  $_POST['item'] : null; 
			$msg = $this->save_dripped_schedule( $items );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg, 'membership_id' => $this->model->id ) ) );
		}
		/**
		 * Rule single action 
		 */
		elseif( ! empty( $_GET['action'] ) && ! empty( $_GET['membership_id'] ) && 
				! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $_GET['action'] ) ) {
			$msg = $this->rule_list_do_action( $_GET['action'], array( $_GET['item'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'action', 'item', '_wpnonce' ) ) ) );
		}
		/**
		 * Rule bulk actions
		 */
		elseif( ! empty( $_POST['membership_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-rules' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			$msg = $this->rule_list_do_action( $action, $_POST['item'] );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg,'membership_id' => $this->model->id ) ) );
		}
		/**
		 * Save url group add/edit
		 */
		elseif ( ! empty( $_POST['url_group_submit'] ) && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['_wpnonce'] ) && 
				! empty( $_POST['action'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
			$msg = $this->save_url_group( $_POST );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg, 'membership_id' => $this->model->id ) ) );
		}
		
	}

	/**
	 * New/Edit membership.
	 * 
	 * @since 4.0.0
	 */
	public function membership_edit() {
		$this->views['membership_edit'] = apply_filters( 'ms_view_membership_edit', new MS_View_Membership_Edit() );
		
		$data['membership'] = $this->model;
		$data['tabs'] = $this->get_tabs();
		$data['action'] = 'save_membership';
		
		$this->views['membership_edit']->data = $data;
		
		$this->views['membership_edit']->model = $this->model;
		
		$this->views['membership_edit']->render();
	}
	
	/**
	 * Get available tabs for editing the membership.
	 *  
	 * @return array The tabs configuration.
	 */
	public function get_tabs() {
		$membership_id = $this->model->id;
		
		$tabs = array(
				'general' => array(
						'title' =>	__( 'General', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=general&membership_id=' . $membership_id,
				),
				'page' => array(
						'title' => __( 'Pages', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=page&membership_id=' . $membership_id,
				),
				'category' => array(
						'title' => __( 'Categories', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=category&membership_id=' . $membership_id,
				),
				'post' => array(
						'title' => __( 'Post by post', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=post&membership_id=' . $membership_id,
				),
				'comment' => array(
						'title' => __( 'Comments', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=comment&membership_id=' . $membership_id,
				),
				'media' => array(
						'title' => __( 'Media', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=media&membership_id=' . $membership_id,
				),
				'menu' => array(
						'title' => __( 'Menus', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=menu&membership_id=' . $membership_id,
				),
				'shortcode' => array(
						'title' => __( 'Shortcodes', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=shortcode&membership_id=' . $membership_id,
				),
				'cpt' => array(
						'title' => __( 'Post Types', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=cpt&membership_id=' . $membership_id,
				),
				'cpt_group' => array(
						'title' => __( 'Post Type Groups', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=cpt_group&membership_id=' . $membership_id,
				),
				'urlgroup' => array(
						'title' => __( 'URL Groups', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=urlgroup&membership_id=' . $membership_id,
				),
				'dripped' => array(
						'title' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
						'url' => 'admin.php?page=membership-edit&tab=dripped&membership_id=' . $membership_id,
				),
		);
		/**
		 * Just general tab in the first access.
		 */
		if( ! $membership_id ){
			$tabs = array( 'general' => $tabs['general'] );
		}
		/**
		 * Enable / Disable post by post tab.
		 */
		if( apply_filters( 'ms_addon_post_by_post', MS_Plugin::instance()->addon->post_by_post ) ) {
			unset( $tabs['category'] );
		}
		else {
			unset( $tabs['post'] );
		}
		/**
		 * Enable / Disable cpt post by post tab.
		 */
		if( apply_filters( 'ms_addon_cpt_post_by_post', MS_Plugin::instance()->addon->cpt_post_by_post ) ) {
			unset( $tabs['cpt_group'] );
		}
		else {
			unset( $tabs['cpt'] );
		}
		/**
		 * Disable media tab if media/download protection is disabled. 
		 */
		if( MS_Model_Rule_Media::PROTECTION_TYPE_DISABLED == apply_filters( 'ms_settings_media_download', MS_Plugin::instance()->settings->downloads['protection_type'] ) ) {
			unset( $tabs['media'] );
		}
		/**
		 * Disable urlgroup tab.
		 */
		if( ! apply_filters( 'ms_addon_url_groups', MS_Plugin::instance()->addon->url_groups ) ) {
			unset( $tabs['urlgroup'] );
		}

		return apply_filters( 'ms_controller_membership_get_tabs', $tabs );
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
		return $this->active_tab = apply_filters( 'ms_helper_membership_get_active_tab', $active_tab );
	}
	
	/**
	 * Save membership general tab fields
	 *
	 * @since 4.0.0 
	 *
	 * @param mixed[] $fields
	 */
	private function save_membership( $fields ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}
		
		$msg = 0;
		if( is_array( $fields ) ) {
			foreach( $fields as $field => $value ) {
				try {
					$this->model->$field = $value;
				} 
				catch (Exception $e) {
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_PARTIALLY_UPDATED;					  
				}
			}
			$this->model->trial_period_enabled = ! empty( $fields['trial_period_enabled'] );
			$this->model->save();
			if( empty( $msg ) ) {
				$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
			}
		}
		return $msg;
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
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}

		$rule_type = $this->active_tab;
		$rule = $this->model->get_rule( $rule_type );
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
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
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
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_DRIPPED_NOT_COPIED;
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}

		$src_membership = MS_Model_Membership::load( $copy_from_id );
		if( $src_membership->id > 0 ) {
				
			$rule_types = array( 'post', 'page' ); 
			foreach( $rule_types as $rule_type) {
				$this->model->set_rule( $rule_type, $src_membership->rules[ $rule_type ] );
			}
			$this->model->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_DRIPPED_COPIED;
		}		
		return $msg;
	}
	
	/**
	 * Save new 'dripped content' schedule(s).
	 *
	 * @since 4.0.0
	 *
	 * @param mixed[] $items The item ids which action will be taken.
	 */	
	private function save_dripped_schedule( $items ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}
		
		$dripped = array(
			'post' => array(),
			'page' => array(),
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
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		return $msg;
	}
	
	/**
	 * Save Url Groups tab.
	 *
	 * @since 4.0.0
	 *
	 * @param int $copy_from_id The Membership ID to copy from.
	 */
	private function save_url_group( $fields ) {
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}
		
		if( is_array( $fields ) ) {
			$rule_type = 'url_group';
			$rule = $this->model->get_rule( $rule_type );
 
			foreach( $fields as $field => $value ) {
				$rule->$field = $value;
			}
			$this->model->set_rule( $rule_type, $rule );
			$this->model->save();
			$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
		}
		return $msg;
		
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
		
		if( 'general' == $this->active_tab ) {
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_style( 'chosen-jquery' );
		}
		elseif( 'dripped' == $this->active_tab ) {
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
				
		if( 'general' == $this->active_tab ) {
			wp_enqueue_script( 'ms-view-membership-render-general', $plugin_url. 'app/assets/js/ms-view-membership-render-general.js', array( 'jquery' ), $version );
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'jquery-validate' );
		}
		elseif( 'dripped' == $this->active_tab ) {
			wp_enqueue_script( 'ms-view-membership-render-dripped', $plugin_url. 'app/assets/js/ms-view-membership-render-dripped.js', array( 'jquery' ), $version );
			wp_enqueue_script( 'jquery-tmpl' );
			wp_enqueue_script( 'jquery-chosen' );
			wp_enqueue_script( 'jquery-validate' );
		}	
		elseif( 'urlgroup' == $this->active_tab ) {
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