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
	
	private $views;
		
	public function __construct() {

		$this->post_type = MS_Model_Membership::$POST_TYPE;
		
		$membership_id = ! empty( $_GET['membership_id'] ) ? $_GET['membership_id'] : 0;
		
		$this->membership = MS_Model_Membership::load( $membership_id );
		
		$this->views['membership'] = new MS_View_Membership( $this->membership );
		
		$this->views['rule'] = new MS_View_Rule( $this->membership );
		
		$this->add_action( 'admin_print_scripts-membership_page_membership-edit', 'enqueue_scripts' );
		
	}
	public function membership_dashboard() {
	
	}
	
	public function admin_membership_list() {
		
		$this->views['membership']->admin_membership_list();
	}
	
	public function membership_edit() {
		
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		
		if( ! empty( $_POST['submit'] ) )
		{
			if( 'general' == $active_tab ) {
				$this->save_membership();
				$this->views['membership']->set_membership( $this->membership );				
			}
			elseif ( 'rules' == $active_tab ) {
				$this->save_rules();
			}
		}
		$tabs = array(
			'general' => array(
					'title' =>	__( 'General', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-edit&tab=general&membership_id=' . $this->membership->id,
			),
			'rules' => array(
					'title' =>	__( 'Protection Rules', MS_TEXT_DOMAIN ),
					'url' => 'admin.php?page=membership-edit&tab=rules&membership_id=' . $this->membership->id,
			),
		);
		if( 'general' == $active_tab ) {
			$this->views['membership']->membership_edit( $tabs );
		}
		elseif ( 'rules' == $active_tab ) {
			$this->views['rule']->membership_rule_edit( $tabs );
		}
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
			$this->membership->$field['id'] = (! empty( $_POST[ $this->views['membership']->section ][ $field['id'] ] ) ) 
				? sanitize_text_field( $_POST[ $this->views['membership']->section ][ $field ['id'] ] )
				: '';
		}
		$this->membership->save();
	}
	public function save_rules() {
		if ( ! current_user_can( $this->capability ) ) return;
		if ( empty( $_POST[ MS_View_Rule::SAVE_NONCE ] ) ||
			! wp_verify_nonce( $_POST[ MS_View_Rule::SAVE_NONCE ], MS_View_Rule::SAVE_NONCE ) ) return;
		
		/**
		 * Membership protection rules fields
		 */
// 		$this->membership->save();
		
	}
	public function enqueue_scripts() {
	
		$plugin_url = MS_Plugin::get_plugin_url();
		$version = MS_Plugin::get_plugin_version();

		wp_register_script( 'render_rule', $plugin_url. 'app/assets/js/ms-view-rule-render-rule.js', null, $version );
		wp_enqueue_script( 'render_rule' );
	
	}
	
}