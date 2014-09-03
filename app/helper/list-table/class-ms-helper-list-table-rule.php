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
class MS_Helper_List_Table_Rule extends MS_Helper_List_Table {

	protected $id = 'rule';
		
	protected $model;
	
	protected $membership;
	
	public function __construct( $model, $membership = null ) {
		parent::__construct( array(
			'singular'  => "rule_$this->id",
			'plural'    => "rules",
			'ajax'      => false
		) );
		
		$this->model = $model;
		$this->membership = $membership;
	}
		
	public function get_columns() {
		return apply_filters( "ms_helper_list_table_{$this->id}_columns", array(
			'cb'     => '<input type="checkbox" />',
			'content' => __( 'Content', MS_TEXT_DOMAIN ),
			'rule_type' => __( 'Rule type', MS_TEXT_DOMAIN ),
			'dripped' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function get_hidden_columns() {
		return apply_filters( "ms_helper_list_table_{$this->id}_hidden_columns", array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( "ms_helper_list_table_{$this->id}_sortable_columns", array(
				'content' => 'content',
				'access' => 'access',
				'dripped' => 'dripped',
		) );
	}
	
	public function get_bulk_actions() {
		return apply_filters( "ms_helper_list_table_{$this->id}_bulk_actions", array(
				'give_access' => __( 'Give access', MS_TEXT_DOMAIN ),
				'no_access' => __( 'Remove access', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function prepare_items() {
	
		$args = null;
		if( ! empty( $_GET['status'] ) ) {
			$args['rule_status'] = $_GET['status']; 
		}
		
		$this->items = apply_filters( "ms_helper_list_table_{$this->id}_items", $this->model->get_content( $args ) );
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
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
	
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="item[]" value="%1$s" />', $item->id );
	}
	
	public function column_access( $item ) {
		$toggle = array(
				'id' => 'ms-toggle-' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $item->access,
				'class' => '',
				'field_options' => array(
						'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE,
						'membership_id' => $this->get_membership_id(),
						'rule' => $item->type,
						'item' => $item->id,
				),
		);
		$html = MS_Helper_Html::html_input( $toggle, true );
		
		return $html;
	}
	
	public function column_dripped( $item ) {
		$actions = array( 
				sprintf( 
					'<a href="?page=%s&tab=dripped&membership_id=%s">%s</a>',
					$_REQUEST['page'],
					$this->get_membership_id(),
					__('Edit', MS_TEXT_DOMAIN )
				),
		);
		$actions = apply_filters( "ms_helper_list_table_{$this->id}_column_dripped_actions", $actions, $item );
		return sprintf( '%1$s %2$s', $item->delayed_period, $this->row_actions( $actions ) );
	}
	
	public function display() {
		$membership_id = array(
				'id' => 'membership_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->get_membership_id(),
		);
		MS_Helper_Html::html_input( $membership_id );
		
		parent::display();
	}
	
	protected function get_membership_id() {
		$membership_id = 0;
		if( ! empty( $this->membership ) && $this->membership->is_valid() ) {
			$membership_id = $this->membership->id;
		}
		elseif( ! empty( $_REQUEST['membership_id'] ) ) {
			$membership_id = $_REQUEST['membership_id'];
		}
		return apply_filters( 'ms_helper_list_table_rule_get_membership_id', $membership_id );
	}
	
	public function get_views(){
		$count = $this->model->count_item_access();
		
		$url = apply_filters( "ms_helper_list_table_{$this->id}_url", remove_query_arg( array ( 'status', 'paged' ) ) );
		
		return apply_filters( "ms_helper_list_table_{$this->id}_views", array(
				'all' => sprintf( '<a href="%s">%s</a> (%s)', $url, __( 'All', MS_TEXT_DOMAIN ), $count['total'] ),
				'has_access' => sprintf( '<a href="%s">%s</a> (%s)', 
						add_query_arg( array ( 'status' => MS_Model_Rule::FILTER_HAS_ACCESS ), $url ), 
						__( 'Has Access', MS_TEXT_DOMAIN ), 
						$count['accessible'] 
				),
				'no_access' => sprintf( '<a href="%s">%s</a> (%s)', 
						add_query_arg( array ( 'status' => MS_Model_Rule::FILTER_NO_ACCESS ), $url ),
						 __( 'Access Restricted', MS_TEXT_DOMAIN ), 
						$count['restricted'] 
				),
		) );
	}
}
