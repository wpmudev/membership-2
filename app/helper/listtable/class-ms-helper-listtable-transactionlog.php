<?php
/**
 * List of transaction protocol entries.
 *
 * @since 1.0.0.6
 */
class MS_Helper_ListTable_TransactionLog extends MS_Helper_ListTable {

	/**
	 * The post type that contains the transaction log items.
	 */
	const POST_TYPE = 'ms_transaction_log';

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
	 * @since 1.0.0.6
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
	 * @since  1.0.0.6
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
	 * @since  1.0.0.6
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
	 * @since  1.0.0.6
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
	 * @since  1.0.0.6
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
	 * @since  1.0.0.6
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$args = $this->get_query_args();
		$this->count_items();

		$this->items = apply_filters(
			'ms_helper_listtable_transactionlog_items',
			$this->get_items( $args )
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $args['posts_per_page'],
			)
		);
	}

	/**
	 * Prepares the collection of query arguments used to filter list items.
	 * These arguments are later passed to a WP_Query constructor.
	 *
	 * @since  1.0.0.6
	 * @return array
	 */
	protected function get_query_args() {
		$defaults = MS_Model_Invoice::get_query_args();

		$per_page = $this->get_items_per_page(
			'transactionlog_per_page',
			self::DEFAULT_PAGE_SIZE
		);
		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		$args = wp_parse_args( $args, $defaults );

		return $args;
	}

	/**
	 * Defines predefines filters for this list table.
	 *
	 * @since  1.0.0.6
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
		return 'log-' . $item->status;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0.6
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
	 * @since  1.0.0.6
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
	 * @since  1.0.0.6
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_status( $item, $column_name ) {
		if ( $item->success ) {
			$html = '<span class="log-status"><i class="wpmui-fa wpmui-fa-check"></i></span>';
		} else {
			$html = '<span class="log-status"><i class="wpmui-fa wpmui-fa-warning"></i></span>';
		}

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0.6
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_gateway( $item, $column_name ) {
		$html = $html = MS_Model_Gateway::get_name( $item->gateway_id );
		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0.6
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
	 * @since  1.0.0.6
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_invoice( $item, $column_name ) {
		$invoice_url = MS_Controller_Plugin::get_admin_url(
			'billing',
			array( 'action' => 'edit', 'invoice_id' => $item->invoice_id )
		);

		$html = sprintf(
			'<a href="%1$s">%2$s</a>',
			$invoice_url,
			$item->invoice_id
		);

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.0.6
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_note( $item, $column_name ) {
		$html = sprintf(
			'<div class="detail-block">%1$s</div>',
			$item->note
		);

		return $html;
	}

	/**
	 * Returns the total number of transaction logs in the database.
	 *
	 * @since  1.0.0.6
	 * @return int
	 */
	protected function count_items() {
		$count = wp_count_posts( self::POST_TYPE );

		$log_count = 0;
		foreach ( $count as $value ) {
			$log_count += $value;
		}

		return $log_count;
	}

	/**
	 * Returns a list of transaction log items that will be displayed in the
	 * listview.
	 *
	 * @since  1.0.0.6
	 * @param  array $args Filter options.
	 * @return array List of matching transaction log entries.
	 */
	protected function get_items( $args ) {
		$args['post_type'] = self::POST_TYPE;

		$query = new WP_Query( $args );
		$item = array();

		foreach ( $query->posts as $post ) {
			$item = (object) array(
				'id' => $post->ID,
				'date' => $post->post_date,
				'note' => $post->post_content,
				'gateway_id' => get_post_meta( $post->ID, '_gateway_id', true ),
				'method' => get_post_meta( $post->ID, '_method', true ),
				'success' => get_post_meta( $post->ID, '_success', true ),
				'subscription_id' => get_post_meta( $post->ID, '_subscription_id', true ),
				'invoice_id' => get_post_meta( $post->ID, '_invoice_id', true ),
				'amount' => get_post_meta( $post->ID, '_amount', true ),
			);

			if ( $item->success ) {
				$item->status = 'ok';
			} else {
				$item->status = 'err';
			}

			$items[] = $item;
		}

		return $items;
	}

}
