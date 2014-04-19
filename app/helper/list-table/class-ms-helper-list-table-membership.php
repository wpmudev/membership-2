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
class MS_Helper_List_Table_Membership extends MS_Helper_List_Table {
		
	protected $id = 'membership';
		
	public function get_columns() {
		return apply_filters( 'membership_helper_list_table_membership_columns', array(
			'cb'     => '<input type="checkbox" />',
			'name_col' => __('Membership Name', MS_TEXT_DOMAIN ),
			'active_col' => __('Active', MS_TEXT_DOMAIN ),
			'public_col' => __('Public', MS_TEXT_DOMAIN ),
			'members_col' => __('Members', MS_TEXT_DOMAIN ),
		) );
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="post_id" value="%1$s" />', $item->ID );
	}
	
	public function get_hidden_columns() {
		return apply_filters( 'membership_helper_list_table_membership_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'membership_helper_list_table_membership_sortable_columns', array() );
	}
	
	public function prepare_items() {
	
		$this->items = apply_filters( 'membership_helper_list_table_membership_items', MS_Model_Membership::get_memberships() );
		
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
	}

	public function get_column_actions( $item ) {
		return apply_filters( 'membership_helper_list_table_membership_column_actions', array(
				'edit' => "<a href='/wp-admin/admin.php?page=all-memberships&action=edit&membership_id={$item->id}'>".__( "Edit", MS_TEXT_DOMAIN )."</a>",
				'toggle_activation' => "<a href='/wp-admin/admin.php?page=all-memberships&action=toggle_activation&membership_id={$item->id}'>".__( "Deactivate", MS_TEXT_DOMAIN )."</a>",
				'toggle_public' => "<a href='/wp-admin/admin.php?page=all-memberships&action=toggle_public&membership_id={$item->id}'>".__( "Make Public", MS_TEXT_DOMAIN )."</a>",
			), 
			$item 
		);
		
	}
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'name_col':
				$html = "<a href='/wp-admin/admin.php?page=membership-edit&membership_id={$item->id}'>$item->name</a>";
				$html .= $this->row_actions( $this->get_column_actions( $item ), false );
				break;
			case 'active_col':
				$html = ( $item->active ) ? __( 'Active', MS_TEXT_DOMAIN ) : __( 'Deactivated', MS_TEXT_DOMAIN );
				break;
			case 'public_col':
				$html = ( $item->public ) ? __( 'Public', MS_TEXT_DOMAIN ) : __( 'Private', MS_TEXT_DOMAIN );
				break;
			case 'members_col':
				$html = 0;
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
	public function get_bulk_actions() {
		return apply_filters( 'membership_helper_list_table_membership_bulk_actions', array(
			'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
			'toggle_activation' => __( 'Toggle Activation', MS_TEXT_DOMAIN ),
			'toggle_public' => __( 'Toggle Public Status', MS_TEXT_DOMAIN ),
		) );
	}
}
