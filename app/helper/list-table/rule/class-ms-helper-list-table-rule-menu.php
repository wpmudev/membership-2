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
class MS_Helper_List_Table_Rule_Menu extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_menu';
	
	protected $menu_id;
	
	public function __construct( $model, $membership, $menu_id ) {
		parent::__construct( $model, $membership );
	
		$this->menu_id = $menu_id;
	}
		
	public function get_columns() {
		$menus = $this->model->get_menu_array();
		return apply_filters( "membership_helper_list_table_{$this->id}_columns", array(
				'title' => sprintf( '<span class="ms-menu-name">%s</span> - %s', $menus[ $this->menu_id ], __( 'Menu title', MS_TEXT_DOMAIN ) ),
				'access' => __( 'Members Access', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function get_bulk_actions() {
		return apply_filters( "membership_helper_list_table_{$this->id}_bulk_actions", array() );
	}
	
	public function prepare_items() {
		
		$args = apply_filters( 'ms_helper_list_table_rule_menu_prepare_items_args', array( 'menu_id' => $this->menu_id ) );
		
		$this->items = apply_filters( "membership_helper_list_table_{$this->id}_items", $this->model->get_contents( $args ) );
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			default:
				$html = $item->$column_name;
				break;
		}
		return $html;
	}
	
	public function column_cb( $item ) {
		
		$html = '';
		if( $item->parent_id ) {
			$html = sprintf( '<input type="checkbox" name="item[]" value="%1$s" />', $item->id );
		}
		return $html;
	}

	public function column_access( $item ) {
	
		$html = '';
		if( $item->parent_id ) {
				
			$html = parent::column_access( $item );
		}
		return $html;
	}
	
	public function get_views(){
		$views = parent::get_views();
		unset( $views['dripped'] );
		return $views;
	}
	
}