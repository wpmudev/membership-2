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
class MS_Helper_ListTable_Billing extends MS_Helper_ListTable {

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
			'membership_helper_listtable_membership_columns',
			array(
				'cb' => '<input type="checkbox" />',
				'invoice' => __( 'Invoice #', MS_TEXT_DOMAIN ),
				'user' => __( 'User', MS_TEXT_DOMAIN ),
				'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
				'status' => __( 'Status', MS_TEXT_DOMAIN ),
				'total' => sprintf( '%1$s (%2$s)', __( 'Total', MS_TEXT_DOMAIN ), $currency ),
				'due_date' => __( 'Due date', MS_TEXT_DOMAIN ),
				'gateway_id' => __( 'Gateway', MS_TEXT_DOMAIN ),
			)
		);

		$columns = apply_filters(
			'ms_helper_listtable_billing_get_columns',
			$columns,
			$currency
		);

		return $columns;
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="invoice_id[]" value="%1$s" />',
			esc_attr( $item->id )
		);
	}

	public function get_hidden_columns() {
		return apply_filters(
			'membership_helper_listtable_membership_hidden_columns',
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'membership_helper_listtable_membership_sortable_columns',
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
			'membership_helper_listtable_invoice_items',
			MS_Model_Invoice::get_invoices( $args )
		);

		$per_page = $this->get_items_per_page( 'invoice_per_page', self::DEFAULT_PAGE_SIZE );
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	private function get_query_args() {
		$defaults = MS_Model_Invoice::get_query_args();

		$per_page = $this->get_items_per_page( 'invoice_per_page', self::DEFAULT_PAGE_SIZE );
		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		$args = wp_parse_args( $args, $defaults );

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

		return sprintf(
			'%1$s %2$s',
			$item->id,
			$this->row_actions( $actions )
		);
	}

	public function column_total( $item, $column_name ) {
		$html = MS_Helper_Billing::format_price( $item->total );

		return $html;
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
			'membership_helper_listtable_invoice_bulk_actions',
			array(
				'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
			)
		);
	}

	public function get_views() {
		$all_status = MS_Model_Invoice::get_status_types();
		$views = array();

		$args = $this->get_query_args();
		$url = remove_query_arg( array( 'status', 'msg' ) );
		$count = MS_Model_Invoice::get_invoice_count( $args );
		$views['all'] = array(
			'url' => $url,
			'label' => __( 'All', MS_TEXT_DOMAIN ),
			'count' => $count,
		);

		$url = remove_query_arg( array( 'status', 'msg' ) );
		$url = add_query_arg( 'status', 'open', $url );
		$args = $this->get_query_args();
		$args['meta_query']['status']['value'] = array(
			MS_Model_Invoice::STATUS_BILLED,
			MS_Model_Invoice::STATUS_PENDING,
		);
		$args['meta_query']['status']['compare'] = 'IN';
		$count = MS_Model_Invoice::get_invoice_count( $args );
		$views['open'] = array(
			'url' => $url,
			'label' => __( 'Billed or Pending', MS_TEXT_DOMAIN ),
			'count' => $count,
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

		return apply_filters( 'ms_helper_listtable_billing_views', $views );
	}

}
