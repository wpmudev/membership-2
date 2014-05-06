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

class MS_Controller_Member extends MS_Controller {

	private $post_type;
	
	private $capability = 'manage_options';
	
	private $model;
	
	private $views;
		
	public function __construct() {
		
		/** New/Edit: Member */ 
		$this->views['member_edit'] = apply_filters( 'membership_member_edit_view', new MS_View_Member_Edit() );
		
		/** Hook to save screen options for Members screen. */
		$this->add_filter('set-screen-option', 'table_set_option', 10, 3 );
		
		$this->add_action( 'load-membership_page_membership-members', 'admin_member_list_manager' );
		
		$this->add_action( 'admin_print_scripts-membership_page_membership-members', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-membership_page_membership-members', 'enqueue_styles' );
		
	}

	public function table_set_option($status, $option, $value) {
	  if ( 'members_per_page' == $option ) return $value;
	}
	
	
	/** Prepare pagination for member list. */
	public function table_options() {
		$option = 'per_page';
		$args = array(
			'label' => __('Members per Page', MS_TEXT_DOMAIN ),
			'default' => 10,
			'option' => 'members_per_page',
		);
		add_screen_option( $option, $args );
	}
	
	
	// * Save 'Screen Option' selection. 
	// public function member_table_set_option($status, $option, $value) {
	  // return $value;
	// }

	/**
	 * Manages membership actions.
	 *
	 * Verifies GET and POST requests to manage members
	 * @todo It got complex, maybe consider using ajax editing or create a new edit page with all member 
	 * 	membership fields (active, memberships, start, end, gateway)
	 */
	public function admin_member_list_manager() {
		/**
		 * Execute table single action.
		 */
		$msg = 0;
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['member_id'] ) && ! empty( $_GET['_wpnonce'] ) && check_admin_referer( $_GET['action'] ) ) {
			$msg = $this->member_list_do_action( $_GET['action'], array( $_GET['member_id'] ) );
			wp_safe_redirect( add_query_arg( array( 'msg' => $msg), remove_query_arg( array( 'member_id', 'action', '_wpnonce' ) ) ) );
			die();
		}
		/**
		 * Execute bulk actions.
		 */
		elseif( ! empty( $_POST['member_id'] ) && ! empty( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'bulk-members' ) ) {
			$action = $_POST['action'] != -1 ? $_POST['action'] : $_POST['action2'];
			if( $action == 'toggle_activation') {
				$msg = $this->member_list_do_action( $action, $_POST['member_id'] );
			}
			else {
// 				$this->prepare_action_view( $action, $_POST['member_id'] );
			}
		}
		/**
		 * Execute view page action submit.
		 */
		elseif( ! empty( $_POST['submit'] ) ) {
			$section = MS_View_Member_Membership::MEMBERSHIP_SECTION;
			$nonce = MS_View_Member_Membership::MEMBERSHIP_NONCE;
			if ( ! empty( $_POST[ $section ]['member_id'] ) && ! empty( $_POST[ $section ]['action'] ) && ! empty( $_POST[ $section ]['membership_id'] ) &&
				! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
					
				$this->member_list_do_action( $_POST[ $section ]['action'], array( $_POST[ $section ]['member_id'] ), $_POST[ $section ]['membership_id'] );
			}
		}
		
	}
	/**
	 * Show member list.
	 * 
	 * Menu Members, show all users available.
	 */	
	public function admin_member_list() {
		/**
		 * Action view page request
		 */
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['member_id'] ) ) {
			$this->prepare_action_view( $_GET['action'], $_GET['member_id'] );
		}
		else {
			$this->views['member_list'] = apply_filters( 'membership_view_member_list', new MS_View_Member_List() );
			$this->views['member_list']->multiple_membership_option = apply_filters( 'membership_addon_multiple_membership', MS_Model_Addon::load()->multiple_membership );
			$this->views['member_list']->render();
		}
	}

	/**
	 * Prepare and show action view.
	 * 
	 * @param string $action The action to execute.
	 */
	public function prepare_action_view( $action, $member_id ) {
		$view = null;
		/** Member Model */
		$this->model = apply_filters( 'membership_member_model', MS_Model_Member::load( $member_id ) );
		
		switch( $action ) {
			case 'add':
				$memberships = MS_Model_Membership::get_membership_names();
				$memberships = array_diff_key( $memberships, $this->model->membership_relationships );
				$memberships[0] = __( 'Select Membership to add', MS_TEXT_DOMAIN );
				break;
			case 'drop':
				$args = array( 'post__in' => array_keys( $this->model->membership_relationships ) );
				$memberships = MS_Model_Membership::get_membership_names( $args );
				$memberships[0] = __( 'Select Membership to drop', MS_TEXT_DOMAIN );
				break;
			case 'move':
				$args = array( 'post__in' => array_keys( $this->model->membership_relationships ) );
				$memberships_move = MS_Model_Membership::get_membership_names( $args );
				$memberships_move[0] = __( 'Select Membership to move from', MS_TEXT_DOMAIN );
					
				$memberships = MS_Model_Membership::get_membership_names();
				$memberships = array_diff_key( $memberships, $this->model->membership_relationships );
				$memberships[0] = __( 'Select Membership to move to', MS_TEXT_DOMAIN );
				break;
			case 'edit_date':
				$view = apply_filters( 'membership_view_member_date', new MS_View_Member_Date() );
				$view->membership_relationships = $this->model->membership_relationships;
				$view->membership_ids = $this->model->membership_ids;
				break;
		}
		
		if( in_array( $action, array( 'add', 'move', 'drop' ) ) ) {
			$view = apply_filters( 'membership_view_member_membership', new MS_View_Member_Membership() );
			$view->memberships = $memberships;
			if( 'move' == $action ){
				$view->memberships_move = $memberships_move;
			}
		}
		$view->action = $action;
		$view->member_id = $member_id;
		$view->render();
	}
	
	public function member_list_do_action( $action, $members, $membership_id = null ) {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		$section = MS_View_Member_Membership::MEMBERSHIP_SECTION;
		foreach( $members as $member_id ){
			/** Member Model */
			$member = apply_filters( 'membership_member_model', MS_Model_Member::load( $member_id ) );
			switch( $action ) {
				case 'add':
					$member->add_membership( $membership_id );
					break;
				case 'drop':
					$member->drop_membership( $membership_id );
					break;
				case 'move':
					if( ! empty( $_POST[ $section ]['membership_move_from_id'] ) ) {
						$member->move_membership( $_POST[ $section ]['membership_move_from_id'], $_POST[ $section ]['membership_id'] );
					}
					break;
				case 'toggle_activation':
					$member->active = ! $member->active;
					break;
				case 'edit_date':
					if( is_array( $membership_id ) ) {
						$membership_relationships = $member->membership_relationships;
						foreach ( $membership_id as $id ) {
							if( ! empty( $_POST[ $section ][ "start_date_$id" ] ) ){
								$membership_relationships[ $id ]->start_date = $_POST[ $section ][ "start_date_$id" ];
							}
							if( ! empty( $_POST[ $section ][ "expire_date_$id" ] ) ){
								$membership_relationships[ $id ]->expire_date = $_POST[ $section ][ "expire_date_$id" ];
							}
						}
						$member->membership_relationships = $membership_relationships;
					}
					break;
			}
			$member->save();
		}			
	}
		
	public function enqueue_styles() {
		wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
	}
	
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_register_script( 'ms_view_member_date', MS_Plugin::get_plugin_url(). 'app/assets/js/ms-view-member-date.js', null, MS_Plugin::get_plugin_version() );
		wp_enqueue_script( 'ms_view_member_date' );
	}
	
}