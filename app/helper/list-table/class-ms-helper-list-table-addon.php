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
class MS_Helper_List_Table_Addon extends MS_Helper_List_Table {
		
	protected $id = 'addon';
	
	protected $model;
	
	public function __construct( $model ){
		$this->model = $model;
		
		parent::__construct( array(
				'singular'  => 'addon',
				'plural'    => 'addons',
				'ajax'      => false
		) );
	}
	
	public function get_columns() {
		return apply_filters( 'membership_helper_list_table_addon_columns', array(
			'cb'     => '<input type="checkbox" />',
			'addon_description' => __('Add-on description', MS_TEXT_DOMAIN ),
			'active' => __('Active', MS_TEXT_DOMAIN ),
		) );
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="addon[]" value="%1$s" />', $item->id );
	}
	
	public function get_hidden_columns() {
		return apply_filters( 'membership_helper_list_table_addon_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'membership_helper_list_table_addon_sortable_columns', array() );
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		$this->items = apply_filters( 'membership_helper_list_table_addon_items', $this->model->get_addon_list() );
	}

	public function column_addon_description( $item ) {
		$html = "<div class='title'>{$item->name}</div>";//these should be escaped if not meant to contain html
		$html .= "<span class='description'>{$item->description}</span>";
		return $html;
	}
	
	public function column_active( $item ) {
		
		$toggle = array(
				'id' => 'ms-toggle-' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $item->active,
				'class' => '',
				'data_ms' => array(
						'action' => MS_Controller_Addon::AJAX_ACTION_TOGGLE_ADDON,
						'field' => 'active',
						'addon' => $item->id,
				),
		);
		$html = MS_Helper_Html::html_element( $toggle, true );
		
		return $html;
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
	
	public function get_bulk_actions() {
		return apply_filters( 'membership_helper_list_table_addon_bulk_actions', array(
			'enable' => __( 'Enable', MS_TEXT_DOMAIN ),
			'disable' => __( 'Disable', MS_TEXT_DOMAIN ),
		) );
	}
	
}
