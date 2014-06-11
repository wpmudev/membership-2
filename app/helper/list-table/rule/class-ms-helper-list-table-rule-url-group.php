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
class MS_Helper_List_Table_Rule_Url_Group extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_url_group';
		
	public function get_columns() {
		return apply_filters( "membership_helper_list_table_{$this->id}_columns", array(
				'cb'     => '<input type="checkbox" />',
				'url' => __( 'Page URL', MS_TEXT_DOMAIN ),
				'access' => __( 'Access', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function column_url( $item ) {
	
		$actions = array(
				'edit' => sprintf( '<a href="?page=%s&tab=urlgroup&action=%s&url_id=%s">%s</a>', $_REQUEST['page'], 'url_group_edit', $item->id, __( 'Edit', MS_TEXT_DOMAIN ) ),
				'delete' => sprintf( '<span class="delete"><a href="%s">%s</a></span>',
						wp_nonce_url(
						sprintf( '?page=%s&url_group_id=%s&action=%s',
							$_REQUEST['page'],
							$item->id,
							'delete'
						),
						'delete'
					),
					__('Delete', MS_TEXT_DOMAIN )
				),
				
		);
		$actions = apply_filters( "membership_helper_list_table_{$this->id}_column_name_actions", $actions, $item );
	
		return sprintf( '%1$s %2$s', $item->url, $this->row_actions( $actions ) );
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
	
	public function get_views(){
		$views = parent::get_views();
		unset( $views['dripped'] );
		return $views;
	}
}