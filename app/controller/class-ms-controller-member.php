<?php
/**
 * This file defines the MS_Controller_Member class.
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
 * Controller for managing Members and Membership relationships.
 *
 * Focuses on the Member and the member's Memberships.
 * Handles Membership movements (add, cancel, move, etc.) and is responsible for the rendering of Member lists and management.
 *
 * @since 4.0.0
 * @package Membership
 * @subpackage Controller
 */
class MS_Controller_Member extends MS_Controller {

	/**
	 * The custom post type used with Members.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $post_type
	 */
	private $post_type;
	
	/**
	 * Capability required to manage Members.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $capability
	 */	
	private $capability = 'manage_options';

	/**
	 * The model to use for loading/saving Member data.
	 *
	 * @since 4.0.0
	 * @access private
	 * @var $model
	 */	
	private $model;

	/**
	 * Prepare the Member manager.
	 *
	 * @since 4.0.0
	 */		
	public function __construct() {
		
		$this->add_action( 'load-membership_page_membership-members', 'admin_member_list_manager' );
		
		$this->add_action( 'admin_print_scripts-membership_page_membership-members', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-membership_page_membership-members', 'enqueue_styles' );
		
	}

	/**
	 * Show admin notices.
	 *
	 * @since 4.0.0
	 *
	 */
	public function print_admin_message() {
		add_action( 'admin_notices', array( 'MS_Helper_Member', 'print_admin_message' ) );
	}
	
	/**
	 * Manages membership actions.
	 *
	 * Verifies GET and POST requests to manage members
	 *
	 * @todo It got complex, maybe consider using ajax editing or create a new edit page with all member 
	 * 	membership fields (active, memberships, start, end, gateway)
	 *
	 * @since 4.0.0
	 */
	public function admin_member_list_manager() {
		$this->print_admin_message();
		
		$msg = 0;
		/**
		 * Execute list table single action.
		 */
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['member_id'] ) && 
			! empty( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $_GET['action'] ) ) {
			
			$msg = $this->member_list_do_action( $_GET['action'], array( $_GET['member_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg ), remove_query_arg( array( 'member_id', 'action', '_wpnonce' ) ) ) );
		}
		/**
		 * Execute list table bulk actions.
		 */
		elseif( ! empty( $_POST['member_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-members' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			if( $action == 'toggle_activation') {
				$msg = $this->member_list_do_action( $action, $_POST['member_id'] );
				wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
			}
		}
		/**
		 * Execute edit view page action submit.
		 */
		elseif( ! empty( $_POST['submit'] ) ) {
			if ( ! empty( $_POST['member_id'] ) && ! empty( $_POST['action'] ) && ! empty( $_POST['membership_id'] ) &&
				! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $_POST['action'] ) ) {
				
				$member_ids = is_array( $_POST['member_id'] ) ? $_POST['member_id'] : explode( ',', $_POST['member_id'] );
				$msg = $this->member_list_do_action( $_POST['action'], $member_ids, $_POST['membership_id'] );
				wp_safe_redirect( add_query_arg( array( 'msg' => $msg ) ) );
			}
		}
		
	}

	/**
	 * Show member list.
	 * 
	 * Menu Members, show all users available.
	 * @since 4.0.0
	 */	
	public function admin_member_list() {
		
		// Don't render the view if it was a toggle button AJAX call.
		if( isset( $_GET['toggle_action'] ) ) {
			exit;
		}
		
		/**
		 * Action view edit page request
		 */
		if( ! empty( $_REQUEST['action'] ) && ! empty( $_REQUEST['member_id'] ) ) {
			$this->prepare_action_view( $_REQUEST['action'], $_REQUEST['member_id'] );
		}
		else {
			$view = apply_filters( 'ms_view_member_list', new MS_View_Member_List() );
			$view->multiple_membership_option = apply_filters( 'ms_model_addon_multiple_membership', MS_Model_Addon::load()->multiple_membership );
			$view->render();
		}
	}

	/**
	 * Prepare and show action view.
	 * 
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param int $member_id User ID of the member.
	 */
	public function prepare_action_view( $action, $member_id ) {
		$view = null;
		$data = array();
		
		/** Bulk actions */
		if( is_array( $member_id ) ) {
			$memberships = MS_Model_Membership::get_membership_names();
			$data['member_id'] = $member_id;
			switch( $action ) {
				case 'add':
					$memberships[0] = __( 'Select Membership to add', MS_TEXT_DOMAIN );
					break;
				case 'cancel':
					$memberships[0] = __( 'Select Membership to cancel', MS_TEXT_DOMAIN );
					break;
				case 'drop':
					$memberships[0] = __( 'Select Membership to drop', MS_TEXT_DOMAIN );
					break;
				case 'move':
					$memberships_move = $memberships;
					$memberships_move[0] = __( 'Select Membership to move from', MS_TEXT_DOMAIN );
			
					$memberships = MS_Model_Membership::get_membership_names();
					$memberships[0] = __( 'Select Membership to move to', MS_TEXT_DOMAIN );
					break;
			}
		}
		/** Single action */
		else {
			/** Member Model */
			$member = apply_filters( 'membership_member_model', MS_Model_Member::load( $member_id ) );
			$data['member_id'] = array( $member_id );
			switch( $action ) {
				case 'add':
					$memberships = MS_Model_Membership::get_membership_names();
					$memberships = array_diff_key( $memberships, $member->membership_relationships );
					$memberships[0] = __( 'Select Membership to add', MS_TEXT_DOMAIN );
					break;
				case 'cancel':
					$args = array( 'post__in' => array_keys( $member->membership_relationships ) );
					$memberships = MS_Model_Membership::get_membership_names( $args );
					$memberships[0] = __( 'Select Membership to cancel', MS_TEXT_DOMAIN );
					break;
				case 'drop':
					$args = array( 'post__in' => array_keys( $member->membership_relationships ) );
					$memberships = MS_Model_Membership::get_membership_names( $args );
					$memberships[0] = __( 'Select Membership to drop', MS_TEXT_DOMAIN );
					break;
				case 'move':
					$args = array( 'post__in' => array_keys( $member->membership_relationships ) );
					$memberships_move = MS_Model_Membership::get_membership_names( $args );
					$memberships_move[0] = __( 'Select Membership to move from', MS_TEXT_DOMAIN );
						
					$memberships = MS_Model_Membership::get_membership_names();
					$memberships = array_diff_key( $memberships, $member->membership_relationships );
					$memberships[0] = __( 'Select Membership to move to', MS_TEXT_DOMAIN );
					break;
				case 'edit_date':
					$view = apply_filters( 'membership_view_member_date', new MS_View_Member_Date() );
					$data['member_id'] = $member_id;
					$data['membership_relationships'] = MS_Model_Membership_Relationship::get_membership_relationships( array( 'user_id' => $member->id ) );
					break;
			}
		}
		
		if( in_array( $action, array( 'add', 'move', 'drop', 'cancel' ) ) ) {
			$view = apply_filters( 'ms_view_member_membership', new MS_View_Member_Membership() );
			$data['memberships'] = $memberships;
			if( 'move' == $action ){
				$data['memberships_move'] = $memberships_move;
			}
		}
		
		$data['action'] = $action;
		$view->data = $data;
		$view->render();
	}
	
	/**
	 * Handles Member list actions.
	 * 
	 * @since 4.0.0
	 *
	 * @param string $action The action to execute.
	 * @param object[] $members Array of members.	
	 * @param int $membership_id The Membership to apply action to.
	 */	
	public function member_list_do_action( $action, $members, $membership_id = null ) {
		$msg = MS_Helper_Member::MSG_MEMBER_NOT_UPDATED;
		if ( ! current_user_can( $this->capability ) ) {
			return $msg;
		}
		
		foreach( $members as $member_id ){
			/** Member Model */
			$member = apply_filters( 'membership_member_model', MS_Model_Member::load( $member_id ) );
			switch( $action ) {
				case 'add':
					$member->add_membership( $membership_id );
					$msg = MS_Helper_Member::MSG_MEMBER_ADDED;
					break;
				case 'cancel':
					$member->cancel_membership( $membership_id );
					$msg = MS_Helper_Member::MSG_MEMBER_UPDATED;
					break;
				case 'drop':
					$member->drop_membership( $membership_id );
					$msg = MS_Helper_Member::MSG_MEMBER_DELETED;
					break;
				case 'move':
					if( ! empty( $_POST['membership_move_from_id'] ) ) {
						$member->move_membership( $_POST['membership_move_from_id'], $_POST['membership_id'] );
						$msg = MS_Helper_Member::MSG_MEMBER_UPDATED;
					}
					break;
				case 'toggle_activation':
					$member->active = ! $member->active;
					break;
				case 'edit_date':
					if( is_array( $membership_id ) ) {
						foreach ( $membership_id as $id ) {
							$ms_relationship = $member->membership_relationships[ $id ];
							if( ! empty( $_POST[ "start_date_$id" ] ) ){
								$ms_relationship->start_date = $_POST[ "start_date_$id" ];
								$ms_relationship->set_trial_expire_date();
							}
							if( ! empty( $_POST[ "expire_date_$id" ] ) ){
								$ms_relationship->expire_date = $_POST[ "expire_date_$id" ];
							}
							$ms_relationship->save();
						}
						$msg = MS_Helper_Member::MSG_MEMBER_UPDATED;
					}
					break;
			}
			$member->save();
		}
		return $msg;
	}

	/**
	 * Load Member manager specific styles.
	 *
	 * @since 4.0.0
	 */		
	public function enqueue_styles() {
		wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
	}

	/**
	 * Load Member manager specific scripts.
	 *
	 * @since 4.0.0
	 */	
	public function enqueue_scripts() {
		/** Start and expire date edit */
		if( ! empty( $_GET['action'] ) && 'edit_date' == $_GET['action'] ) {
			wp_enqueue_script( 'jquery-ui-datepicker' );
			wp_enqueue_script( 'ms-view-member-date', MS_Plugin::instance()->url. 'app/assets/js/ms-view-member-date.js', null, MS_Plugin::instance()->version );
		}
		/** Members list */
		else {
			wp_enqueue_script( 'ms_view_member_ui' );
			wp_enqueue_script( 'ms-view-members-list', MS_Plugin::instance()->url. 'app/assets/js/ms-view-member-list.js', null, MS_Plugin::instance()->version );
		}
				
	}
	
}