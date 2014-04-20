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
	
	private $models;
	
	private $views;
		
	public function __construct() {
		
		$member_id = ! empty( $_GET['member_id'] ) ? $_GET['member_id'] : 0;
		
		/** Member Model */
		$this->model = apply_filters( 'membership_member_model', MS_Model_Member::load( $member_id ) );

		/** Menu: Members */
		$this->views['member_list'] = apply_filters( 'membership_member_list_view', new MS_View_Member_List() );
		
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
		$this->views['member_list']->render();
	}
		
	public function membership_edit() {		
		$this->views['membership_edit']->model = $this->model;

		if( ! empty( $_POST['submit'] ) )
		{
			$this->save_membership();
			$this->views['membership_edit']->model = $this->model;
		}
		$this->views['membership_edit']->render();
	}
	
	
	
}