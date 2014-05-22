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
class MS_Helper_List_Table_Billing extends MS_Helper_List_Table {
		
	protected $id = 'billing';
	
	public function __construct(){
		parent::__construct( array(
				'singular'  => 'billing',
				'plural'    => 'billings',
				'ajax'      => false
		) );
	}
	
	public function get_columns() {
		return apply_filters( 'membership_helper_list_table_membership_columns', array(
			'cb' => '<input type="checkbox" />',
			'invoice' => __( 'Invoice #', MS_TEXT_DOMAIN ),
			'user' => __( 'User', MS_TEXT_DOMAIN ),
			'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
			'status' => __( 'Status', MS_TEXT_DOMAIN ),
			'amount' => __( 'Amount', MS_TEXT_DOMAIN ),
			'tax_description' => __( 'Tax description', MS_TEXT_DOMAIN ),
			'tax_rate' => __( 'Tax rate', MS_TEXT_DOMAIN ),
			'total' => __( 'Total', MS_TEXT_DOMAIN ),
			'due_date' => __( 'Due date', MS_TEXT_DOMAIN ),
			'gateway' => __( 'Gateway', MS_TEXT_DOMAIN ),
		) );
	}
	
	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="transaction_id[]" value="%1$s" />', $item->id );
	}
	
	public function get_hidden_columns() {
		return apply_filters( 'membership_helper_list_table_membership_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'membership_helper_list_table_membership_sortable_columns', array() );
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		$total_items =  MS_Model_Transaction::get_transaction_count();
		$per_page = $this->get_items_per_page( 'transaction_per_page', 10 );
		$current_page = $this->get_pagenum();
		
		$args = array(
				'posts_per_page' => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
			);
		
		$this->items = apply_filters( 'membership_helper_list_table_transaction_items', MS_Model_Transaction::get_transactions( $args ) );
		
		$this->set_pagination_args( array(
					'total_items' => $total_items,
					'per_page' => $per_page,
				)
			);
	}

	function column_invoice( $item ) {
	
		$actions = array(
				'edit' => sprintf( '<a href="?page=%s&action=%s&transaction_id=%s">%s</a>', $_REQUEST['page'], 'edit', $item->id, __('Edit', MS_TEXT_DOMAIN ) ),
		);
	
		echo sprintf( '%1$s %2$s', $item->invoice, $this->row_actions( $actions ) );
	
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			case 'user':
				$html = MS_Model_Member::load( $item->user_id )->username;
				break;
			case 'membership':
				$membership = MS_Model_Membership::load( $item->membership_id );
				$html = $membership->name;
				break;
			case 'status':
				$html = $item->status;
				break;
			case 'amount':
				$html = $item->amount;
				break;
			case 'tax_description':
				$html = $item->tax_description;
				break;
			case 'tax_rate':
				$html = $item->tax_rate;
				break;
			case 'total':
				$html = $item->total;
				break;
			case 'due_date':
				$html = $item->due_date;
				break;
			case 'gateway':
				$html = $item->gateway_id;
				break;
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}
	
	public function get_bulk_actions() {
		return apply_filters( 'membership_helper_list_table_transaction_bulk_actions', array(
			'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
		) );
	}
	
}
