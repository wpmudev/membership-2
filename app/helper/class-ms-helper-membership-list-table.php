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

/**
 * Membership List Table 
 *
 *
 * @since 4.0.0
 *
 */
class MS_Helper_Membership_List_Table extends WP_List_Table {
		
	public function __construct() {
		parent::__construct();
	}
	
	public function get_columns() {
		$columns = array(
			'name' => __('Membership Name', MS_TEXT_DOMAIN ),
			'active' => __('Active', MS_TEXT_DOMAIN ),
			'members' => __('Members', MS_TEXT_DOMAIN ),
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
		
		$args = array(
			'post_type' => MS_Model_Membership::$POST_TYPE,
			'posts_per_page' => 10, //TODO 
			'order' => 'DESC',
		);
		$query = new WP_Query($args);
		$items = $query->get_posts();

		return $items;
	}
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'name':
				$html = "<a href='/wp-admin/admin.php?page=membership-edit&membership_id={$item->id}'>$item->name</a>";
				break;
			case 'active':
				$html = ( $item->active ) ? __( 'Active', MS_TEXT_DOMAIN ) : __( 'Deactivated', MS_TEXT_DOMAIN );
				break;
			case 'members':
				$html = 0;
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
}
