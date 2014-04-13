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
	
	private $membership;
	
	private $view;
	
	public function __construct() {

		$this->post_type = MS_Model_Membership::$POST_TYPE;
		
		$membership_id = ! empty( $_GET['membership_id'] ) ? $_GET['membership_id'] : 0;
		
		$this->membership = MS_Model_Membership::load( $membership_id );
		
		$this->view = new MS_View_Membership( $this->membership );
		
	}
	public function membership_dashboard() {
	
	}
	
	public function admin_membership_list() {
		
		$this->view->admin_membership_list();
	}
	
	public function membership_edit() {
		
		if( ! empty( $_POST['submit'] ) )
		{
			$membership_id = $this->save_membership();
		}
		$this->view->membership_edit( $this->membership );
	}
	
	/*
	 * Save membership details
	 * TODO better sanitize and validate fields in model
	 */
	public function save_membership() {
		
		if ( ! current_user_can( $this->capability ) ) return;
		if ( empty( $_POST[ MS_View_Membership::SAVE_NONCE ] ) || 
			! wp_verify_nonce( $_POST[ MS_View_Membership::SAVE_NONCE ], MS_View_Membership::SAVE_NONCE ) ) return;
		
		foreach( $this->view->fields as $field ) {
			$this->membership->$field['id'] = (! empty( $_POST[ $this->view->section ][ $field['id'] ] ) ) 
				? sanitize_text_field( $_POST[ $this->view->section ][ $field ['id'] ] )
				: '';
		}
		$this->membership->save();
		return $this->membership->id;
	}
}