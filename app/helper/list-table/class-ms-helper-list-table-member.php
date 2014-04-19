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

// RK: Nothing changed yet. Just a copy of MS_Helper_List_Table_Membership


/**
 * Membership List Table 
 *
 *
 * @since 4.0.0
 *
 */
class MS_Helper_List_Table_Member extends MS_Helper_List_Table {
		
	private $data = array(
		array( 'ID' => 1, 'username' => 'user1', 'name' => 'John One', 'email' => 'my1@email.com', 'membership' => 'Visitor' ),
		array( 'ID' => 2, 'username' => 'user2', 'name' => 'Tim Two', 'email' => 'my2@email.com', 'membership' => 'Visitor' ),
		array( 'ID' => 3, 'username' => 'user2', 'name' => 'Tom Three', 'email' => 'my3@email.com', 'membership' => 'Visitor' ),				
	);
		
		
	public function __construct() {
		parent::__construct();
	}
	
	public function get_columns() {
		$columns = array(
			'username' => __('Username', MS_TEXT_DOMAIN ),
			'name' => __('Name', MS_TEXT_DOMAIN ),
			'email' => __('E-mail', MS_TEXT_DOMAIN ),
			'membership' => __('Membership(s)', MS_TEXT_DOMAIN ),			
		);
		return $columns;
	}
	
	public function get_hidden_columns() {
		return array();
	}
	
	public function get_sortable_columns() {
		return array();
	}
	public function prepare_items() {
		
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		$this->items = $this->get_items();
	}
	public function get_items() {
		
		$items = $this->data;
		// $args = array(
		// 			'post_type' => MS_Model_Membership::$POST_TYPE,
		// 			'posts_per_page' => 10, //TODO 
		// 			'order' => 'DESC',
		// 		);
		// 		$query = new WP_Query($args);
		// 		$items = $query->get_posts();
		
		return $items;
	}
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'username':
			case 'name':
			case 'email':
			case 'membership':
				return $item[ $column_name ];
			default:
				print_r( $item, true );
		}
		// switch( $column_name ) {
		// 	case 'name':
		// 		$html = "<a href='/wp-admin/admin.php?page=membership-edit&membership_id={$item->id}'>$item->name</a>";
		// 		break;
		// 	case 'active':
		// 		$html = ( $item->active ) ? __( 'Active', MS_TEXT_DOMAIN ) : __( 'Deactivated', MS_TEXT_DOMAIN );
		// 		break;
		// 	case 'members':
		// 		$html = 0;
		// 		break;
		// 	default:
		// 		$html = print_r( $item, true ) ;
		// 		break;
		// }
		return $html;
	}
}
