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
		
		$this->views['membership_list'] = apply_filters( 'membership_membership_list_view', new MS_View_Membership_List() );
		$this->views['membership_edit'] = apply_filters( 'membership_membership_edit_view', new MS_View_Membership_Edit() );
		
		$this->add_action( 'admin_print_scripts-admin_page_membership-edit', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-admin_page_membership-edit', 'enqueue_styles' );
		
	}
	public function membership_dashboard() {
	
	}
	
	public function admin_membership_list() {
		$this->views['membership_list']->data['model'] = $this->model;
		$this->views['membership_list']->render();
		// $this->views['membership']->admin_membership_list();
	}
	
	public function membership_edit() {
		$this->views['membership_edit']->data['id'] = $this->model->id;
		$this->views['membership_list']->data['model'] = $this->model;		
		$this->views['membership_edit']->render();

		if( ! empty( $_POST['submit'] ) )
		{
			$this->save_membership();
			$this->view->set_membership( $this->model );
		}
		$this->view->membership_edit( $tabs );
	}
	
	/*
	 * Save membership details
	 * TODO better sanitize and validate fields in model
	 */
	public function save_membership() {
		if ( ! current_user_can( $this->capability ) ) return;
		if ( empty( $_POST[ MS_View_Membership::SAVE_NONCE ] ) || 
			! wp_verify_nonce( $_POST[ MS_View_Membership::SAVE_NONCE ], MS_View_Membership::SAVE_NONCE ) ) return;
		
		/**
		 * Membership general fields. 
		 */	
		foreach( $this->views['membership']->fields as $field ) {
			$this->model->$field['id'] = (! empty( $_POST[ $this->views['membership']->section ][ $field['id'] ] ) ) 
				? sanitize_text_field( $_POST[ $this->views['membership']->section ][ $field ['id'] ] )
				: '';
		}
		$this->model->save();
	}
	
	
	public function enqueue_styles() {
		wp_register_style( 'ms_rule_view_render_rule_css', MS_Plugin::get_plugin_url(). 'app/assets/css/ms-view-rule-render-rule.css', null, MS_Plugin::get_plugin_version() );
		wp_enqueue_style( 'ms_rule_view_render_rule_css' );
	}
	
		
	public function enqueue_scripts() {
	
		$plugin_url = MS_Plugin::get_plugin_url();
		$version = MS_Plugin::get_plugin_version();

		wp_register_script( 'ms_view_membership_render_general', $plugin_url. 'app/assets/js/ms-view-membership-render-general.js', null, $version );
		wp_enqueue_script( 'view_membership_render_general' );
		
		wp_register_script( 'ms_rule_view_render_rule_js', MS_Plugin::get_plugin_url(). 'app/assets/js/ms-view-rule-render-rule.js', null, MS_Plugin::get_plugin_version() );
		wp_enqueue_script( 'ms_rule_view_render_rule_js' );
		
	
	}
	
}