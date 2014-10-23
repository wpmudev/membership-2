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
		parent::__construct(
			array(
				'singular' => 'billing',
				'plural'   => 'billings',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		$currency = MS_Plugin::instance()->settings->currency;

		$columns = apply_filters(
			'membership_helper_list_table_membership_columns',
			array(
				'cb' => '<input type="checkbox" />',
				'invoice' => __( 'Invoice #', MS_TEXT_DOMAIN ),
				'user' => __( 'User', MS_TEXT_DOMAIN ),
				'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
				'status' => __( 'Status', MS_TEXT_DOMAIN ),
				'amount' => sprintf( '%1$s (%2$s)', __( 'Amount', MS_TEXT_DOMAIN ), $currency ),
				'discount' => sprintf( '%1$s (%2$s)', __( 'Discount', MS_TEXT_DOMAIN ), $currency ),
				'total' => sprintf( '%1$s (%2$s)', __( 'Total', MS_TEXT_DOMAIN ), $currency ),
				'due_date' => __( 'Due date', MS_TEXT_DOMAIN ),
				'gateway_id' => __( 'Gateway', MS_TEXT_DOMAIN ),
			)
		);

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_COUPON ) ) {
			unset( $columns['discount'] );
			unset( $columns['amount'] );
		}

		return apply_filters( 'ms_helper_list_table_billing_get_columns', $columns );
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="invoice_id[]" value="%1$s" />',
			esc_attr( $item->id )
		);
	}

	public function get_hidden_columns() {
		return apply_filters(
			'membership_helper_list_table_membership_hidden_columns',
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'membership_helper_list_table_membership_sortable_columns',
			array(
				'invoice' => array( 'ID', false ),
				'user' => array( 'author', false ),
				'membership' => array( 'ms_membership_ids', false ),
				'status' => array( 'status', false ),
				'amount' => array( 'amount', false ),
				'total' => array( 'total', false ),
				'due_date' => array( 'due_date', false ),
				'gateway_id' => array( 'gateway_id', false ),
			)
		);
	}

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$args = $this->get_query_args();

		$total_items = MS_Model_Invoice::get_invoice_count( $args );

		$this->items = apply_filters(
			'membership_helper_list_table_invoice_items',
			MS_Model_Invoice::get_invoices( $args )
		);

		$per_page = $this->get_items_per_page( 'invoice_per_page', 10 );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	private function get_query_args() {
		$per_page = $this->get_items_per_page( 'invoice_per_page', 10 );
		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}

		/**
		 * Prepare order by statement.
		 */
		$orderby = @$args['orderby'];
		if ( ! empty( $orderby )
			&& ! in_array( $orderby, array( 'ID', 'author' ) )
			&& property_exists( 'MS_Model_Invoice', $orderby )
		) {
			$args['meta_key'] = $orderby;
			if ( in_array( $orderby, array( 'amount', 'total', 'tax_rate' ) ) ) {
				$args['orderby'] = 'meta_value_num';
			}
			else {
				$args['orderby'] = 'meta_value';
			}
		}

		/**
		 * Search string.
		 */
		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['author_name'] = $_REQUEST['s'];
		}

		$args['meta_query'] = array();

		/**
		 * Gateway filter.
		*/
		if ( ! empty( $_REQUEST['gateway_id'] ) ) {
			$args['meta_query']['gateway_id'] = array(
				'key' => 'gateway_id',
				'value' => $_REQUEST['gateway_id'],
			);
		}

		/**
		 * Payment status filter.
		 */
		if ( ! empty( $_REQUEST['status'] ) ) {
			$args['meta_query']['status'] = array(
				'key' => 'status',
				'value' => $_REQUEST['status'],
			);
		}

		return $args;
	}

	public function column_invoice( $item ) {
		$actions = array();
		$actions['edit'] = sprintf(
			'<a href="?page=%s&action=%s&invoice_id=%s">%s</a>',
			esc_attr( $_REQUEST['page'] ),
			'edit',
			esc_attr( $item->id ),
			__( 'Edit', MS_TEXT_DOMAIN )
		);
		$actions['view'] = sprintf(
			'<a href="%s">%s</a>',
			get_permalink(  $item->id ),
			__( 'View', MS_TEXT_DOMAIN )
		);

		printf( '%1$s %2$s', $item->id, $this->row_actions( $actions ) );
	}

	public function column_default( $item, $column_name ) {
		$html = '';

		switch ( $column_name ) {
			case 'user':
				$member = MS_Factory::load( 'MS_Model_Member', $item->user_id );
				$html = $member->username;
				break;

			case 'membership':
				$membership = MS_Factory::load( 'MS_Model_Membership', $item->membership_id );
				$html = $membership->name;
				break;

			default:
				$html = $item->$column_name;
				break;
		}

		return $html;
	}

	public function get_bulk_actions() {
		return apply_filters(
			'membership_helper_list_table_invoice_bulk_actions',
			array(
				'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
			)
		);
	}

	public function get_views() {
		$all_status = MS_Model_Invoice::get_status_types();
		$views = array();

		$views['all'] = array(
			'url' => remove_query_arg( array( 'status', 'msg' ) ),
			'label' => __( 'All', MS_TEXT_DOMAIN ),
		);

		foreach ( $all_status as $status => $desc ) {
			$args = $this->get_query_args();
			$args['meta_query']['status']['value'] = $status;
			$count = MS_Model_Invoice::get_invoice_count( $args );
			$status_url = add_query_arg(
				array( 'status' => $status ),
				remove_query_arg( array( 'msg' ) )
			);

			$views[ $status ] =	array(
				'url' => $status_url,
				'label' => $desc,
				'count' => $count,
			);
		}

		return apply_filters( 'ms_helper_list_table_billing_views', $views );
	}

}
