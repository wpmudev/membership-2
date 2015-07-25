<?php
/**
 * List of transaction protocol entries.
 *
 * @since  1.0.0
 */
class MS_Helper_ListTable_TransactionLog extends MS_Helper_ListTable {

	/**
	 * This ID is used as class-name for the list output and also in various
	 * filter names in MS_Helper_ListTable.
	 *
	 * @var string
	 */
	protected $id = 'transactionlog';

	/**
	 * Constructor, defines general list table attributes.
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		// 'singular' just added for fun...
		// 'plural' is used as class name for the list.
		parent::__construct(
			array(
				'singular' => 'transaction',
				'plural'   => 'transactions',
			)
		);
	}

	/**
	 * Defines the columns of the list table
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_columns() {
		$currency = MS_Plugin::instance()->settings->currency;

		$columns = apply_filters(
			'ms_helper_listtable_transactionlog_columns',
			array(
				'id' => __( 'ID', MS_TEXT_DOMAIN ),
				'date' => __( 'Time', MS_TEXT_DOMAIN ),
				'status' => '',
				'method' => '',
				'gateway' => __( 'Gateway', MS_TEXT_DOMAIN ),
				'amount' => __( 'Amount', MS_TEXT_DOMAIN ),
				'invoice' => __( 'Invoice', MS_TEXT_DOMAIN ),
				'note' => __( 'Details', MS_TEXT_DOMAIN ),
			)
		);

		$columns = apply_filters(
			'ms_helper_listtable_transactionlog_get_columns',
			$columns,
			$currency
		);

		return $columns;
	}

	/**
	 * Defines, which columns should be output as hidden columns.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_hidden_columns() {
		return apply_filters(
			'ms_helper_listtable_transactionlog_hidden_columns',
			array()
		);
	}

	/**
	 * Defines, which columns can be sorted.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return apply_filters(
			'ms_helper_listtable_transactionlog_sortable_columns',
			array()
		);
	}

	/**
	 * Defines available bulk actions.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_bulk_actions() {
		return apply_filters(
			'ms_helper_listtable_transactionlog_bulk_actions',
			array()
		);
	}

	/**
	 * Loads the items that are displayed on the current list page.
	 *
	 * @since  1.0.0
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$per_page = $this->get_items_per_page(
			'transactionlog_per_page',
			self::DEFAULT_PAGE_SIZE
		);

		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		$total_items = MS_Model_Transactionlog::get_item_count( $args );

		$this->items = apply_filters(
			'ms_helper_listtable_transactionlog_items',
			MS_Model_Transactionlog::get_items( $args )
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	/**
	 * Displays a custom search box for this list.
	 *
	 * @since  1.0.0
	 */
	public function search_box( $text = null, $input_id = 'search' ) {
		// Do not display anything.
		// Transaction logs cannot be searched currently
	}

	/**
	 * Defines predefines filters for this list table.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_views() {
		$views = array();

		return apply_filters(
			'ms_helper_listtable_transactionlog_views',
			$views
		);
	}

	/**
	 * Returns the row-class to be used for the specified table item.
	 *
	 * @param  object $item The current item.
	 * @return string Class to be added to the table row.
	 */
	protected function single_row_class( $item ) {
		return 'log-' . $item->state;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_id( $item, $column_name ) {
		$html = $item->id;
		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_date( $item, $column_name ) {
		$html = MS_Helper_Period::format_date( $item->date, 'Y-m-d H:i' );

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_status( $item, $column_name ) {
		switch ( $item->state ) {
			case 'ok':
				$icon = 'wpmui-fa-check';
				$hint = __( 'Success', MS_TEXT_DOMAIN );
				break;

			case 'ignore':
				$icon = 'wpmui-fa-times';
				$hint = __( 'Intentionally ignored', MS_TEXT_DOMAIN );
				break;

			default:
				$icon = 'wpmui-fa-warning';
				$hint = __( 'Error', MS_TEXT_DOMAIN );
				break;
		}

		$html = sprintf(
			'<span class="log-status" title="%2$s"><i class="wpmui-fa %1$s"></i></span>',
			$icon,
			$hint
		);

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_method( $item, $column_name ) {
		$html = '<span class="log-method" data-info="%2$s"><i class="wpmui-fa wpmui-%1$s"></i></span>';
		$icon = '';
		$info = __( 'Unknown method', MS_TEXT_DOMAIN );

		switch ( $item->method ) {
			case 'handle':
				$icon = 'fa-cloud-download';
				$info = __( 'Gateway called the IPN URL', MS_TEXT_DOMAIN );
				break;

			case 'request':
				$icon = 'fa-refresh';
				$info = __( 'Plugin requested a recuring payment', MS_TEXT_DOMAIN );
				break;

			case 'process':
				$icon = 'fa-shopping-cart';
				$info = __( 'User entered payment details', MS_TEXT_DOMAIN );
				break;
		}

		$html = sprintf( $html, $icon, $info );

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_gateway( $item, $column_name ) {
		$html = $html = MS_Model_Gateway::get_name( $item->gateway_id, true );
		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_amount( $item, $column_name ) {
		$html = MS_Helper_Billing::format_price( $item->amount );

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_invoice( $item, $column_name ) {
		if ( $item->invoice_id ) {
			$invoice_url = MS_Controller_Plugin::get_admin_url(
				'billing',
				array( 'action' => 'edit', 'invoice_id' => $item->invoice_id )
			);

			$html = sprintf(
				'<a href="%1$s">%2$s</a>',
				$invoice_url,
				$item->invoice_id
			);
		} else {
			$html = '-';
		}

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_note( $item, $column_name ) {
		$item_post_info = $item->post;
		if ( ! empty( $item_post_info ) ) {
			$post_data = array( 'POST data:' );
			foreach ( $item_post_info as $key => $value ) {
				$post_data[] = "[$key] = \"$value\"";
			}
			$post_info = sprintf(
				'<div class="more-details">%2$s<div class="post-data">%1$s</div></div>',
				implode( '<br>', $post_data ),
				'<i class="wpmui-fa wpmui-fa-info-circle"></i>'
			);
		} else {
			$post_info = '';
		}

		$html = sprintf(
			'<div class="detail-block">%1$s</div>',
			$post_info . $item->description
		);

		return $html;
	}

}
