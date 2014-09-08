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
class MS_Helper_List_Table_Membership_Group extends MS_Helper_List_Table {
		
	protected $id = 'membership_group';
	
	protected $membership;
	
	public function __construct( $membership ){
		parent::__construct( array(
				'singular'  => 'membership_group',
				'plural'    => 'membership_groups',
				'ajax'      => false
		) );
		$this->membership = $membership;
	}
		
	public function get_columns() {
		
		if( MS_Model_Membership::TYPE_CONTENT_TYPE == $this->membership->type ) {
			$name =  __( 'Content Types', MS_TEXT_DOMAIN );
		}
		elseif( MS_Model_Membership::TYPE_TIER == $this->membership->type ) {
			$name =  __( 'Tier Levels', MS_TEXT_DOMAIN );
		}
		$columns = array(
				'name' =>$name,
				'active' => __( 'Active', MS_TEXT_DOMAIN ),
				'edit' => '',
		);
		
		return apply_filters( 'ms_helper_list_table_membership_group_columns', $columns );
	}
	
	public function get_hidden_columns() {
		return apply_filters( 'ms_helper_list_table_membership_group_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'ms_helper_list_table_membership_group_sortable_columns', array() );
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		$args = array();
		
		/**
		 * Get children memberships.
		 */
		$this->items = apply_filters( 'membership_helper_list_table_membership_items', $this->membership->get_children() );
		
	}

	function column_active( $item ) {
	
		$toggle = array(
				'id' => 'ms-toggle-' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $item->active,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Membership::AJAX_ACTION_TOGGLE_MEMBERSHIP,
						'field' => 'active',
						'membership_id' => $item->id,
				),
		);
		$html = MS_Helper_Html::html_input( $toggle, true );
	
		return $html;
	}
	
	public function column_edit( $item ) {
		$html = sprintf( '<a href="%s">%s</a>',
				add_query_arg( array( 'step' => MS_Controller_Membership::STEP_ACCESSIBLE_CONTENT, 'membership_id' => $item->id ) ),
				__( 'Edit Accessible Content', MS_TEXT_DOMAIN )
		);
		return $html;
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
	
	public function get_bulk_actions() {
		return apply_filters( 'ms_helper_list_table_membership_bulk_actions', array() );
	}
	
}
