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
		add_filter('set-screen-option', array( $this, 'table_set_option' ), 10, 3);
		
		// $this->add_action( 'admin_print_scripts-admin_page_membership-edit', 'enqueue_scripts' );
		// $this->add_action( 'admin_print_styles-admin_page_membership-edit', 'enqueue_styles' );
		
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

	
	
	public function admin_member_list() {
		
		if( ! empty( $_GET['action'] ) && ! empty( $_GET['member_id']) ) {
			/** Menu: Members */
			$this->views['membership'] = apply_filters( 'membership_view_member_membership', new MS_View_Member_Membership() );

			$member_id = $_GET['member_id'];
			$action =  $_GET['action'];
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
					//TODO
					break;
			}	
			$this->views['membership']->action = $action;
			$this->views['membership']->member_id = $member_id;
			$this->views['membership']->memberships = $memberships;
			$this->views['membership']->render();
		}
		else {
			if( ! empty( $_POST['submit'] ) ) {
				$this->manage_member_membership();
			}
			/** Menu: Members */
			$this->views['member_list'] = apply_filters( 'membership_view_member_list', new MS_View_Member_List() );
			
			$this->views['member_list']->render();
		}
	}
		
	public function manage_member_membership() {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		$section = MS_View_Member_Membership::MEMBERSHIP_SECTION;
		$nonce = MS_View_Member_Membership::MEMBERSHIP_NONCE;
		if ( ! empty( $_POST[ $section ]['member_id'] ) && ! empty( $_POST[ $section ]['action'] ) && ! empty( $_POST[ $section ]['membership_id'] ) && 
				! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {

			$member_id = $_POST[ $section ]['member_id'];
			$action = $_POST[ $section ]['action'];
			$membership_id = $_POST[ $section ]['membership_id'];
			
			/** Member Model */
			$this->model = apply_filters( 'membership_member_model', MS_Model_Member::load( $member_id ) );

			switch( $action ) {
				case 'add':
					$this->model->add_membership( $membership_id );
					break;
				case 'drop':
					$this->model->drop_membership( $membership_id );
					break;
				case 'move':
					//TODO
					break;
			}
			$this->model->save();
		}
	}
	
	
	
}