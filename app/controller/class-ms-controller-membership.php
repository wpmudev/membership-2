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
	
	const AJAX_ACTION_TOGGLE_MEMBERSHIP = 'toggle_membership';
	
	const STEP_MS_LIST = 'ms_list';
	const STEP_OVERVIEW = 'overview';
	const STEP_SETUP_PROTECTED_CONTENT = 'setup_protected_content';
	const STEP_CHOOSE_MS_TYPE = 'choose_ms_type';
	const STEP_ACCESSIBLE_CONTENT = 'accessible_content';
	const STEP_SETUP_PAYMENT = 'setup_payment';
	
	protected $active_tab;
	
	/**
	 * Prepare the Membership manager.
	 *
	 * @since 4.0.0
	 */
	public function __construct() {

		$this->add_action( 'load-toplevel_page_protected-content', 'membership_admin_page_process' );
		
		$this->add_action( 'wp_ajax_' . self::AJAX_ACTION_TOGGLE_MEMBERSHIP, 'ajax_action_toggle_membership' );
		
		$this->add_action( 'admin_print_scripts-protected-content_page_protected-content-setup', 'enqueue_scripts' );
		$this->add_action( 'admin_print_scripts-toplevel_page_protected-content', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-protected-content_page_protected-content-setup', 'enqueue_styles' );
		$this->add_action( 'admin_print_styles-toplevel_page_protected-content', 'enqueue_styles' );
	}
	
	/**
	 * Handle Ajax toggle action.
	 *
	 * **Hooks Actions: **
	 *
	 * * wp_ajax_toggle_membership
	 *
	 * @since 4.0.0
	 */
	public function ajax_action_toggle_membership() {
		$msg = 0;
		if( $this->verify_nonce() && ! empty( $_POST['membership_id'] ) && ! empty( $_POST['field'] ) && $this->is_admin_user() ) {
			$msg = $this->membership_list_do_action( 'toggle_'. $_POST['field'], array( $_POST['membership_id'] ) );
		}
	
		echo $msg;
		exit;
	}
	
	public function load_membership() {
		$membership_id = ! empty( $_GET['membership_id'] ) ? $_GET['membership_id'] : 0;
	
		return apply_filters( 'ms_controller_membership_load_membership', MS_Factory::load( 'MS_Model_Membership', $membership_id ) );
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
	public function membership_admin_page_process() {
		$this->print_admin_message();
		$msg = 0;
// 		if( ! empty( $_GET['action'] ) && ! empty( $_GET['membership_id'] ) && ! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $_GET['action'] ) ) {
// 			$msg = $this->membership_list_do_action( $_GET['action'], array( $_GET['membership_id'] ) );
// 			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'membership_id', 'action', '_wpnonce' ) ) ) ) ;
// 		}
// 		elseif( ! empty( $_POST['membership_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-memberships' ) ) {
// 			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
// 			$msg = $this->membership_list_do_action( $action, $_POST['membership_id'] );
// 			wp_safe_redirect( add_query_arg( array( 'msg' => $msg) ) );
// 		}
	}
	
	public function membership_admin_page_manager() {
		$view = null;
		$data = array();
		$step = $this->get_step();
		switch( $step ) {
			case self::STEP_MS_LIST:
				$view  = new MS_View_Membership_List();
				break;
			case self::STEP_OVERVIEW:
				$view = new MS_View_Membership_Overview();
				break;
			case self::STEP_SETUP_PROTECTED_CONTENT:
				$this->setup_protected_content();
				break;
			case self::STEP_CHOOSE_MS_TYPE:
				$view = new MS_View_Membership_Choose_Type();
				break;
			case self::STEP_ACCESSIBLE_CONTENT:
				$view = new MS_View_Membership_Accessible_Content();
				break;
			case self::STEP_SETUP_PAYMENT:
				$view = new MS_View_Membership_Setup_Payment();
				break;
		}
// 		$data['membership'] = $this->load_membership();
// 		$data['action'] = $_GET['action'];
		$view->data = apply_filters( 'ms_view_membership_data', $data );
		$view = apply_filters( 'ms_view_membership', $view ); ;
		$view->render();
	}
	public function get_step() {
		/** Initial step */
		$step = self::STEP_MS_LIST;
		
		$steps = array(
				self::STEP_MS_LIST,
				self::STEP_OVERVIEW,
				self::STEP_SETUP_PROTECTED_CONTENT,
				self::STEP_CHOOSE_MS_TYPE,
				self::STEP_ACCESSIBLE_CONTENT,
				self::STEP_SETUP_PAYMENT,
		);
		
		$membership = $this->load_membership();
		if( $membership->is_valid() ) {
			/** Get current step */
			if( ! empty( $_POST['step'] ) && in_array( $_POST['step'], $steps ) ) {
				$step = $_POST['step'];
			}
		}
		
		return apply_filters( 'ms_controller_membership_get_next_step', $step );
	}
	
	public function setup_protected_content() {

		$data = array();
		$data['tabs'] = $this->get_tabs();
		$data['step'] = $this->get_step();
		$data['action'] = MS_Controller_Rule::AJAX_ACTION_UPDATE_RULE;
		$data['membership'] = MS_Model_Membership::get_visitor_membership();
		$view = apply_filters( 'ms_view_membership_setup_protected_content', new MS_View_Membership_Setup_Protected_Content() ); ;
		$view->data = apply_filters( 'ms_view_membership_setup_protected_content_data', $data );
		$view->render();
	}
	
	/**
	 * Get available tabs for editing the membership.
	 *
	 * @return array The tabs configuration.
	 */
	public function get_tabs() {
		$membership_id = $this->load_membership()->id;
	
		$tabs = array(
			'category' => array(
					'title' => __( 'Categories, Custom Post Types', MS_TEXT_DOMAIN ),
			),
			'post' => array(
					'title' => __( 'Post by post, Custom Post Types', MS_TEXT_DOMAIN ),
			),
			'page' => array(
					'title' => __( 'Pages', MS_TEXT_DOMAIN ),
			),
			'comment' => array(
					'title' => __( 'Comments, More Tag, Menus', MS_TEXT_DOMAIN ),
			),
			'shortcode' => array(
					'title' => __( 'Shortcodes', MS_TEXT_DOMAIN ),
			),
			'urlgroup' => array(
					'title' => __( 'URL Groups', MS_TEXT_DOMAIN ),
			),
		);
		/**
		 * Enable / Disable post by post tab.
		 */
		if( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			unset( $tabs['category'] );
		}
		else {
			unset( $tabs['post'] );
		}
		/**
		 * Disable urlgroup tab.
		 */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_URL_GROUPS ) ) {
			unset( $tabs['urlgroup'] );
		}
		/**
		 * Disable shortcode tab.
		 */
		if( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_SHORTCODE ) ) {
			unset( $tabs['shortcode'] );
		}

		foreach( $tabs as $key => $tab ) {
			$tabs[ $key ]['url'] = "admin.php?page=membership&action=edit&tab={$key}&membership_id={$membership_id}";
		}
		
		return apply_filters( 'ms_controller_membership_get_tabs', $tabs, $membership_id );
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
		return $this->active_tab = apply_filters( 'ms_controller_membership_get_active_tab', $active_tab );
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
		$msg = MS_Helper_Membership::MEMBERSHIP_MSG_NOT_UPDATED;
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		
		$msg = 0;
		foreach( $membership_ids as $membership_id ) {
			$membership = MS_Factory::load( 'MS_Model_Membership', $membership_id );
			switch( $action ) {
				case 'toggle_active':
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
		$view = apply_filters( 'membership_membership_list_view', new MS_View_Membership_List() );
	
		$view->render();
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
		if( ! $this->is_admin_user() ) {
			return $msg;
		}
		$membership = $this->load_membership();
		
		$msg = 0;
		if( is_array( $fields ) ) {
			foreach( $fields as $field => $value ) {
				try {
					$membership->$field = $value;
				} 
				catch (Exception $e) {
					$msg = MS_Helper_Membership::MEMBERSHIP_MSG_PARTIALLY_UPDATED;					  
				}
			}
			$membership->trial_period_enabled = ! empty( $fields['trial_period_enabled'] );
			$this->model->save();
			if( empty( $msg ) ) {
				$msg = MS_Helper_Membership::MEMBERSHIP_MSG_UPDATED;
			}
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
		
		switch( $this->get_active_tab() ) {
			case 'category':
				wp_enqueue_style( 'jquery-chosen' );
				break;
			default:
				wp_enqueue_style( 'jquery-ui' );
				break;
		}
		wp_enqueue_style( 'ms_view_membership', $plugin_url. 'app/assets/css/ms-view-membership.css', null, $version );
	}
	
	/**
	 * Load Membership manager specific scripts.
	 *
	 * @since 4.0.0
	 */				
	public function enqueue_scripts() {

		$plugin_url = MS_Plugin::instance()->url;
		$version = MS_Plugin::instance()->version;
		
		wp_enqueue_script( 'ms-radio-slider' );
		
		switch( $this->get_active_tab() ) {
			case 'category':
				wp_enqueue_style( 'jquery-chosen' );
				wp_enqueue_script( 'ms-view-membership-setup-protected-content', $plugin_url. 'app/assets/js/ms-view-membership-setup-protected-content.js', array( 'jquery', 'jquery-chosen' ), $version );
				break;
			case 'url_group':
				wp_register_script( 'ms-view-membership-render-url-group', $plugin_url. 'app/assets/js/ms-view-membership-render-url-group.js', array( 'jquery' ), $version );
				wp_localize_script( 'ms-view-membership-render-url-group', 'ms', array(
				'valid_rule_msg' => __( 'Valid', MS_TEXT_DOMAIN ),
				'invalid_rule_msg' => __( 'Invalid', MS_TEXT_DOMAIN ),
				'empty_msg'	=> __( 'Add Page URLs to the group in case you want to test it against', MS_TEXT_DOMAIN ),
				'nothing_msg' => __( 'Enter an URL above to test against rules in the group', MS_TEXT_DOMAIN ),
				));
				wp_enqueue_script( 'ms-view-membership-render-url-group' );
				
				break;
			default:
				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script( 'jquery-validate' );
				break;
		}
	}
	
}