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

	const NONCE_ACTION = 'membnonce';
	
	protected $nonce;
	
	public function __construct(){
		parent::__construct( array(
				'singular'  => 'membership',
				'plural'    => 'memberships',
				'ajax'      => false
		) );
		$this->nonce = wp_create_nonce( self::NONCE_ACTION );
	}
	
	public function get_columns() {
		return apply_filters( 'membership_helper_list_table_membership_columns', array(
			'cb'     => '<input type="checkbox" />',
			'name' => __('Membership Name', MS_TEXT_DOMAIN ),
			'active' => __('Active', MS_TEXT_DOMAIN ),
			'public' => __('Public', MS_TEXT_DOMAIN ),
			'members' => __('Members', MS_TEXT_DOMAIN ),
		) );
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="membership_id[]" value="%1$s" />', $item->ID );
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
				'edit' => "<a href='/wp-admin/admin.php?page=membership-edit&membership_id={$item->id}'>".__( "Edit", MS_TEXT_DOMAIN )."</a>",
				'toggle_activation' => "<a href='/wp-admin/admin.php?page=all-memberships&action=toggle_activation&membership_id={$item->id}'>".__( "Deactivate", MS_TEXT_DOMAIN )."</a>",
				'toggle_public' => "<a href='/wp-admin/admin.php?page=all-memberships&action=toggle_public&membership_id={$item->id}'>".__( "Make Public", MS_TEXT_DOMAIN )."</a>",
			), 
			$item 
		);
		
	}
	public function column_name( $item ) {
		$actions = array(
				sprintf( '<a href="?page=membership-edit&membership_id=%s">%s</a>',
						$item->id,
						'edit',
						__('Edit', MS_TEXT_DOMAIN )
				),
				sprintf( '<a href="?page=%s&membership_id=%s&action=%s&%s=%s">%s</a>',
						$_REQUEST['page'],
						$item->id,
						'toggle_activation',
						self::NONCE_ACTION,
						$this->nonce,
						__('Toggle Activation', MS_TEXT_DOMAIN )
				),
				sprintf( '<a href="?page=%s&membership_id=%s&action=%s&%s=%s">%s</a>',
						$_REQUEST['page'],
						$item->id,
						'toggle_public',
						self::NONCE_ACTION,
						$this->nonce,
						__('Toggle Public', MS_TEXT_DOMAIN )
				),
				sprintf( '<span class="delete"><a href="?page=%s&membership_id=%s&action=%s&%s=%s">%s</a></span>',
						$_REQUEST['page'],
						$item->id,
						'delete',
						self::NONCE_ACTION,
						$this->nonce,
						__('Delete', MS_TEXT_DOMAIN )
				),
		);
		$actions = apply_filters( "membership_helper_list_table_{$this->id}_column_name_actions", $actions, $item );
		return sprintf( '%1$s %2$s', $item->name, $this->row_actions( $actions ) );
		
	}
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'name':
				$html = "<a href='/wp-admin/admin.php?page=membership-edit&membership_id={$item->id}'>$item->name</a>";
				$html .= $this->row_actions( $this->get_column_actions( $item ), false );
				break;
			case 'active':
				$html = ( $item->active ) ? __( 'Active', MS_TEXT_DOMAIN ) : __( 'Deactivated', MS_TEXT_DOMAIN );
				break;
			case 'public':
				$html = ( $item->public ) ? __( 'Public', MS_TEXT_DOMAIN ) : __( 'Private', MS_TEXT_DOMAIN );
				break;
			case 'members':
				$html = $item->get_members_count();
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
	
	public function display() {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_ACTION );
	
		parent::display();
	}
	
}
