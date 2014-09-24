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
class MS_Helper_List_Table_Gateway extends MS_Helper_List_Table {
		
	protected $id = 'gateway';
	
	public function __construct(){
		parent::__construct( array(
				'singular'  => 'gateway',
				'plural'    => 'gateways',
				'ajax'      => false
		) );
	}
	
	public function get_columns() {
		return apply_filters( 'membership_helper_list_table_gateway_columns', array(
			'name' => __( 'Gateway Name', MS_TEXT_DOMAIN ),
			'active' => __( 'Active', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function get_hidden_columns() {
		return apply_filters( 'ms_helper_list_table_gateway_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'ms_helper_list_table_gateway_sortable_columns', array() );
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		$this->items = apply_filters( 'ms_helper_list_table_gateway_items', MS_Model_Gateway::get_gateways() );
		
		unset( $this->items[ MS_Model_Gateway::GATEWAY_FREE ] );
	}

	public function column_name( $item ) {
		$html = sprintf( '<div>%s %s</div>', $item->name, $item->description );
		$actions = array(
				sprintf( '<a class="thickbox" href="?admin.php#TB_inline&width=%s&height=%s&inlineId=ms-gateway-settings-%s">%s</a>',
						'500',
						'700',
						$item->id,
						__( 'Configure', MS_TEXT_DOMAIN )
				),
				sprintf( '<a href="?page=%s&gateway_id=%s">%s</a>',
						MS_Controller_Plugin::MENU_SLUG . '-billings',
						$item->id,
						__('View Transactions', MS_TEXT_DOMAIN )
				),
		);
		$actions = apply_filters( "gateway_helper_list_table_{$this->id}_column_name_actions", $actions, $item );
		return sprintf( '%1$s %2$s', $html, $this->row_actions( $actions ) );
	}
	
	public function column_active( $item ) {

		$class = $item->is_configured() ? 'ms-gateway-configured' : 'ms-gateway-not-configured';
		$html = "<div class='$class ms-active-wrapper-{$item->id}'>";
		$toggle = array(
			'id' => 'ms-toggle-' . $item->id,
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'value' => $item->active,
			'class' => '',
			'data_ms' => array(
				'action' => MS_Controller_Gateway::AJAX_ACTION_TOGGLE_GATEWAY,
				'gateway_id' => $item->id,
			),
		);
		$html .= MS_Helper_Html::html_input( $toggle, true );
		
		$html .= sprintf( '<div class="ms-gateway-setup-wrapper"><a class="button thickbox" href="#TB_inline?width=%s&height=%s&inlineId=ms-gateway-settings-%s">%s</a></div>',
				'500',
				'700',
				$item->id,
				__( 'Configure', MS_TEXT_DOMAIN )
		);
		$html .= "</div>";
		
		return apply_filters( 'ms_helper_list_table_gateway_column_active', $html );		
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
		return apply_filters( 'gateway_helper_list_table_gateway_bulk_actions', array(
			'toggle_activation' => __( 'Toggle Activation', MS_TEXT_DOMAIN ),
		) );
	}
	
}
