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

class MS_Controller_Rule extends MS_Controller {
	
	private $post_type;
	
	private $capability = 'manage_options';
	
	private $model;
	
	private $view;
		
	public function __construct() {
		
		$membership_id = ! empty( $_GET['membership_id'] ) ? $_GET['membership_id'] : 0;
		
		$this->model = apply_filters( 'membership_membership_model', MS_Model_Membership::load( $membership_id ) );
				
		// $this->view = apply_filters( 'membership_rule_view', MS_Helper_Membership_List_Table( $this->model ) );
		
		$this->add_action( 'admin_print_styles-admin_page_membership-edit-rules', 'enqueue_styles' );
		
		$this->add_action( 'admin_print_scripts-admin_page_membership-edit-rules', 'enqueue_scripts' );
		
	}
	
	public function membership_edit_rules() {
				
		// $tabs = array(
		// 	'general' => array(
		// 			'title' =>	__( 'General', MS_TEXT_DOMAIN ),
		// 			'url' => 'admin.php?page=membership-edit&tab=general&membership_id=' . $this->model->id,
		// 	),
		// 	'rules' => array(
		// 			'title' =>	__( 'Protection Rules', MS_TEXT_DOMAIN ),
		// 			'url' => 'admin.php?page=membership-edit-rules&tab=rules&membership_id=' . $this->model->id,
		// 	),
		// );
		// 
		// if( ! empty( $_POST['submit'] ) )
		// {
		// 	$this->save_rules();
		// }
		// 
		// $this->view->membership_rule_edit( $tabs );
	}
	
	
	
	
	
	/*
	 * Save membership rule details
	 * TODO better sanitize and validate fields in model
	 */
	public function save_rules() {
		if ( ! current_user_can( $this->capability ) ) return;
		if ( empty( $_POST[ MS_View_Rule::SAVE_NONCE ] ) ||
			! wp_verify_nonce( $_POST[ MS_View_Rule::SAVE_NONCE ], MS_View_Rule::SAVE_NONCE ) ) return;
		
		/**
		 * Membership protection rules fields
		 */
// 		$this->model->save();
		
	}
	public function enqueue_scripts() {
	
		wp_register_script( 'ms_rule_view_render_rule_js', MS_Plugin::get_plugin_url(). 'app/assets/js/ms-view-rule-render-rule.js', null, MS_Plugin::get_plugin_version() );
		wp_enqueue_script( 'ms_rule_view_render_rule_js' );
	
	}

	public function enqueue_styles() {
		wp_register_style( 'ms_rule_view_render_rule_css', MS_Plugin::get_plugin_url(). 'app/assets/css/ms-view-rule-render-rule.css', null, MS_Plugin::get_plugin_version() );
		wp_enqueue_style( 'ms_rule_view_render_rule_css' );
	}
}
