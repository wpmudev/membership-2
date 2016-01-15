<?php
/**
 * Listtable helper.
 * @package Membership2
 */

/**
 * List of email-protocol entries.
 *
 * @since  1.0.2.7
 */
class MS_Helper_ListTable_CommunicationLog extends MS_Helper_ListTable {

	/**
	 * This ID is used as class-name for the list output and also in various
	 * filter names in MS_Helper_ListTable.
	 *
	 * @var string
	 */
	protected $id = 'communicationlog';

	/**
	 * Constructor, defines general list table attributes.
	 *
	 * @since  1.0.2.7
	 */
	public function __construct() {
		// 'singular' just added for fun...
		// 'plural' is used as class name for the list.
		parent::__construct(
			array(
				'singular' => 'communication',
				'plural'   => 'communications',
			)
		);
	}

	/**
	 * Defines the columns of the list table
	 *
	 * @since  1.0.2.7
	 * @return array
	 */
	public function get_columns() {
		$columns = apply_filters(
			'ms_helper_listtable_communicationlog_columns',
			array(
				'id' => __( 'ID', 'membership2' ),
				'date' => __( 'Time', 'membership2' ),
				'status' => '',
				'type' => __( 'Type', 'membership2' ),
				'details' => __( 'Details', 'membership2' ),
			)
		);

		$columns = apply_filters(
			'ms_helper_listtable_communicationlog_get_columns',
			$columns
		);

		return $columns;
	}

	/**
	 * Defines, which columns should be output as hidden columns.
	 *
	 * @since  1.0.2.7
	 * @return array
	 */
	public function get_hidden_columns() {
		return apply_filters(
			'ms_helper_listtable_communicationlog_hidden_columns',
			array()
		);
	}

	/**
	 * Defines, which columns can be sorted.
	 *
	 * @since  1.0.2.7
	 * @return array
	 */
	public function get_sortable_columns() {
		return apply_filters(
			'ms_helper_listtable_communicationlog_sortable_columns',
			array()
		);
	}

	/**
	 * Defines available bulk actions.
	 *
	 * @since  1.0.2.7
	 * @return array
	 */
	public function get_bulk_actions() {
		return apply_filters(
			'ms_helper_listtable_communicationlog_bulk_actions',
			array()
		);
	}

	/**
	 * Loads the items that are displayed on the current list page.
	 *
	 * @since  1.0.2.7
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$per_page = $this->get_items_per_page(
			'communicationlog_per_page',
			50
		);

		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
			'meta_query' => array(),
		);

		if ( ! empty( $_GET['id'] ) ) {
			$args['post__in'] = explode( ',', $_GET['id'] );
		}

		$total_items = MS_Model_Communicationlog::get_item_count( $args );

		$this->items = apply_filters(
			'ms_helper_listtable_communicationlog_items',
			MS_Model_Communicationlog::get_items( $args )
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
	 * @since  1.0.2.7
	 * @param string $text Label for search button.
	 * @param string $input_id ID for search-field.
	 */
	public function search_box( $text = null, $input_id = 'search' ) {
		// Do not display anything.
		// Communication logs cannot be searched currently.
	}

	/**
	 * Defines predefined filters for this list table.
	 *
	 * @since  1.0.2.7
	 * @return array
	 */
	public function get_views() {
		$views = array();

		return apply_filters(
			'ms_helper_listtable_communicationlog_views',
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
		$class = 'log-' . ($item->sent ? 'ok' : 'err');
		$class .= ' log-' . $item->name;

		return $class;
	}

	/**
	 * Output column content.
	 *
	 * @since  1.0.2.7
	 * @param  object $item The item that is displayed.
	 * @param  string $column_name Internal name of the column.
	 * @return string The HTML code to output.
	 */
	public function column_id( $item, $column_name ) {
		$html = $item->id;
		return $html;
	}

	/**
	 * Output column content.
	 *
	 * @since  1.0.2.7
	 * @param  object $item The item that is displayed.
	 * @param  string $column_name Internal name of the column.
	 * @return string The HTML code to output.
	 */
	public function column_date( $item, $column_name ) {
		$html = MS_Helper_Period::format_date( $item->post_modified, 'Y-m-d H:i' );

		return $html;
	}

	/**
	 * Output column content.
	 *
	 * @since  1.0.2.7
	 * @param  object $item The item that is displayed.
	 * @param  string $column_name Internal name of the column.
	 * @return string The HTML code to output.
	 */
	public function column_status( $item, $column_name ) {
		if ( $item->sent ) {
			$html = '<i class="wpmui-fa wpmui-fa-check"></i>';
		} else {
			$html = '<i class="wpmui-fa wpmui-fa-circle"></i>';
		}

		return $html;
	}

	/**
	 * Output column content.
	 *
	 * @since  1.0.2.7
	 * @param  object $item The item that is displayed.
	 * @param  string $column_name Internal name of the column.
	 * @return string The HTML code to output.
	 */
	public function column_type( $item, $column_name ) {
		$html = $item->name;

		return $html;
	}

	/**
	 * Output column content.
	 *
	 * @since  1.0.2.7
	 * @param  object $item The item that is displayed.
	 * @param  string $column_name Internal name of the column.
	 * @return string The HTML code to output.
	 */
	public function column_details( $item, $column_name ) {
		$subject = esc_html( $item->title );
		$recipient = esc_html( $item->recipient );

		$trace = json_decode( $item->trace );
		if ( $trace && is_array( $trace ) ) {
			$lines = array();
			foreach ( $trace as $num => $item ) {
				$lines[] = sprintf(
					'<div class="line"><span class="line-num">%s</span><span class="line-val">%s</span></div>',
					$num,
					$item
				);
			}
			$details = implode( '', $lines );
		} else {
			$details = '-';
		}

		$html = sprintf(
			'<div class="detail-block"><span class="more-details">%s %s</span> <span class="txt-quickinfo">%s</span></div>',
			'<i class="wpmui-fa wpmui-fa-info-circle"></i>',
			'<div class="the-details"><div class="inner">' . $details . '</div></div>',
			$recipient . ': <span class="txt-subject">' . $subject . '</span>'
		);

		return $html;
	}
}
