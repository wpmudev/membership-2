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
class MS_Helper_List_Table_Coupon extends MS_Helper_List_Table {
		
	protected $id = 'coupon';
	
	public function __construct(){
		parent::__construct( array(
				'singular'  => 'coupon',
				'plural'    => 'coupons',
				'ajax'      => false
		) );
	}
	
	public function get_columns() {
		return apply_filters( 'membership_helper_list_table_coupon_columns', array(
			'cb'     => '<input type="checkbox" />',
			'code' => __( 'Coupon Code', MS_TEXT_DOMAIN ),
			'discount' => __( 'Discount', MS_TEXT_DOMAIN ),
			'start_date' => __( 'Start date', MS_TEXT_DOMAIN ),
			'expire_date' => __( 'Expired date', MS_TEXT_DOMAIN ),
			'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
			'used' => __( 'Used', MS_TEXT_DOMAIN ),
			'remaining' => __( 'Remaining uses', MS_TEXT_DOMAIN ),
		) );
	}
	
	public function get_hidden_columns() {
		return apply_filters( 'membership_helper_list_table_membership_hidden_columns', array() );
	}
	
	public function get_sortable_columns() {
		return apply_filters( 'membership_helper_list_table_membership_sortable_columns', array() );
	}
	
	public function prepare_items() {
	
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
		
		$total_items =  MS_Model_Coupon::get_coupon_count();
		$per_page = $this->get_items_per_page( 'coupon_per_page', 10 );
		$current_page = $this->get_pagenum();
		
		$args = array(
				'posts_per_page' => $per_page,
				'offset' => ( $current_page - 1 ) * $per_page,
			);
		
		$this->items = apply_filters( 'membership_helper_list_table_coupon_items', MS_Model_Coupon::get_coupons( $args ) );
		
		$this->set_pagination_args( array(
					'total_items' => $total_items,
					'per_page' => $per_page,
				)
			);
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="coupon_id[]" value="%1$s" />', $item->id );
	}
	
	function column_code( $item ) {
		$actions = array(
				'edit' => sprintf( '<a href="?page=%s&action=%s&coupon_id=%s">%s</a>', $_REQUEST['page'], 'edit', $item->id, __( 'Edit', MS_TEXT_DOMAIN ) ),
				'delete' => sprintf( '<a href="?page=%s&action=%s&coupon_id=%s">%s</a>', $_REQUEST['page'], 'delete', $item->id, __( 'Delete', MS_TEXT_DOMAIN ) ),
		);
	
		echo sprintf( '%1$s %2$s', $item->name, $this->row_actions( $actions ) );
			
	}
	
	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			default:
				if( property_exists( $item, $column_name) ) {
					$html = $item->column_name;
				}
				else {
					$html = print_r( $item, true );
				}
				break;
		}
		return $html;
	}
	
	public function get_bulk_actions() {
		return apply_filters( 'membership_helper_list_table_membership_bulk_actions', array(
			'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
		) );
	}
	
}
