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
	
	public function __construct() {
		$this->post_type = MS_Model_Membership::$POST_TYPE;
		
		$this->add_filter( 'manage_edit-ms_membership_columns', 'add_manage_columns');
		
		$this->add_action( "add_meta_boxes_$this->post_type", 'cfg_meta_boxes', 50 );
				
		$this->add_action( "manage_{$this->post_type}_posts_custom_column", 'manage_columns', 10, 2);
		
		$this->add_filter('post_updated_messages', 'post_updated_messages' );
	}
	
	public function add_manage_columns( $columns ) {
		$columns = array();

		$columns['cb'] =  '<input type="checkbox" />';
		$columns['title'] = __('Membership Name', MS_TEXT_DOMAIN);
		$columns['active'] = __('Active', MS_TEXT_DOMAIN);
		$columns['members'] = __('Members', MS_TEXT_DOMAIN);
		
		return $columns;
	}
	
	public function manage_columns( $column_name, $id ) {
		$membership = MS_Model_Membership::load( $id );
		
		switch ($column_name) {
			case 'active':
				echo ( $membership->active ) ? 'Active' : 'Deactivated';
				break;
		
			case 'members':
				break;
			default:
				break;
		}
	}
	
	public function cfg_meta_boxes() {
		add_meta_box( 'ms_membership_general_metabox', __('Membership Definitions', MS_TEXT_DOMAIN ), 
			'MS_View_Membership::membership_general_metabox', $this->post_type, 'normal', 'high' );
	}
	
	public function post_updated_messages( $messages ) {
		global $post;
		$messages[$this->post_type] = array(
				0 => '', // Unused. Messages start at index 1.
				1 => __( 'Membership updated', MS_TEXT_DOMAIN ),
				2 => __( 'Field updated', MS_TEXT_DOMAIN ),
				3 => __( 'Field deleted', MS_TEXT_DOMAIN ),
				4 => __( 'Membership updated', MS_TEXT_DOMAIN ),
				5 => isset($_GET['revision']) ? __( 'Membership restaured', MS_TEXT_DOMAIN ) : false,
				6 => __( 'Membership published', MS_TEXT_DOMAIN ),
				7 => __( 'Membership saved', MS_TEXT_DOMAIN ),
				8 => __( 'Membership sent', MS_TEXT_DOMAIN ),
				9 => __( 'Membership agended', MS_TEXT_DOMAIN ),
				10 => __( 'Membership draft updated', MS_TEXT_DOMAIN ),
		);
		return $messages;
	}
	
}