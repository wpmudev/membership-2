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
		
		/** Menu: Memberships */
		$this->views['membership_list'] = apply_filters( 'membership_membership_list_view', new MS_View_Membership_List() );
		
		/** New/Edit: Membership */ 
		$this->views['membership_edit'] = apply_filters( 'membership_membership_edit_view', new MS_View_Membership_Edit() );
		
		$this->add_action( 'admin_print_scripts-admin_page_membership-edit', 'enqueue_scripts' );
		$this->add_action( 'admin_print_styles-admin_page_membership-edit', 'enqueue_styles' );
		
	}
	
	public function admin_membership_list() {
		$this->views['membership_list']->render();
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
	
	/*
	 * Save membership details
	 * TODO better sanitize and validate fields in model
	 */
	public function save_membership() {
		if ( ! current_user_can( $this->capability ) ) {
			return;
		}
		
		/**
		 * Save membership general fields. 
		 */	
		$nonce = MS_View_Membership_Edit::MEMBERSHIP_SAVE_NONCE;
		if ( ! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
			
			$this->views['membership_edit']->prepare_general();
			
			$section = MS_View_Membership_Edit::MEMBERSHIP_SECTION;
			foreach( $this->views['membership_edit']->fields as $field ) {
				$this->model->$field['id'] = ( ! empty( $_POST[ $section ][ $field['id'] ] ) ) 
					? sanitize_text_field( $_POST[ $section ][ $field ['id'] ] )
					: '';
			}
			$this->model->save();
		}
		/*
		 * Save protection rules fields.
		 */
		$nonce = MS_View_Membership_Edit::RULE_SAVE_NONCE;
		if ( ! empty( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], $nonce ) ) {
			
			$section = MS_View_Membership_Edit::RULE_SECTION;
// 			$this->model->save();
		}
		
	}
	
	
	public function enqueue_styles() {
		
		$plugin_url = MS_Plugin::get_plugin_url();
		$version = MS_Plugin::get_plugin_version();

		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		
		if( 'general' == $active_tab ) {
			wp_register_style( 'ms_membership_view_render_general', $plugin_url. 'app/assets/css/ms-view-membership-render-general.css', null, $version );
			wp_enqueue_style( 'ms_membership_view_render_general' );
		}
		else if( 'rules' == $active_tab ) {		
			wp_register_style( 'ms_rule_view_render_rule', $plugin_url. 'app/assets/css/ms-view-rule-render-rule.css', null, $version );
			wp_enqueue_style( 'ms_rule_view_render_rule' );
		}
	}
	
		
	public function enqueue_scripts() {
	
		$plugin_url = MS_Plugin::get_plugin_url();
		$version = MS_Plugin::get_plugin_version();
		
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		
		if( 'general' == $active_tab ) {
			wp_register_script( 'ms_view_membership_render_general', $plugin_url. 'app/assets/js/ms-view-membership-render-general.js', null, $version );
			wp_enqueue_script( 'ms_view_membership_render_general' );
		}
		else if( 'rules' == $active_tab ) {		
			wp_register_script( 'ms_rule_view_render_rule', $plugin_url. 'app/assets/js/ms-view-rule-render-rule.js', null, $version );
			wp_enqueue_script( 'ms_rule_view_render_rule' );
		}
	
	}
	
}