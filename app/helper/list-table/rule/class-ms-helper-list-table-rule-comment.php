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
class MS_Helper_List_Table_Rule_Comment extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_comment';
		
	public function get_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_columns", array(
			'cb'     => '<input type="checkbox" />',
			'name' => __( 'Comments', MS_TEXT_DOMAIN ),
			'access' => __( 'Access', MS_TEXT_DOMAIN ),
			'dripped' => __( 'Dripped', MS_TEXT_DOMAIN ),
		) );
	}
		
	public function get_sortable_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_sortable_columns", array() );
	}
			
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'name':
				$html = $item->name;
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
		
}
