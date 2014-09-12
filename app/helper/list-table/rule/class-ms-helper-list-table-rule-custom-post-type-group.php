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
class MS_Helper_List_Table_Rule_Custom_Post_Type_Group extends MS_Helper_List_Table_Rule {

	protected $id = 'cpt_group';
			
	public function get_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_columns", array(
			'id' => __( 'Custom Post Type', MS_TEXT_DOMAIN ),
			'access' => __( 'Access', MS_TEXT_DOMAIN ),
		) );
	}
		
	public function get_sortable_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_sortable_columns", array(
				'name' => 'name',
				'access' => 'access',
		) );
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

	public function prepare_items() {
	
		$args = null;
		if( ! empty( $_GET['status'] ) ) {
			$args['rule_status'] = $_GET['status'];
		}
	
		if( ! $this->model->visitor_membership ) {
			$args['protected_content'] = 1;
		}
		$this->items = apply_filters( "ms_helper_list_table_{$this->id}_items", $this->model->get_contents( $args ) );
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
	}
}
