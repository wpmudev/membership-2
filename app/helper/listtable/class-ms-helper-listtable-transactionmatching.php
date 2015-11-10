<?php
/**
 * List unmatched M1 sub_ids and external payment options with membership_ids.
 *
 * Currently supported Sources:
 *   m1       .. M1 subscription
 *   pay_btn  .. PayPal Payment Button
 *
 * Currently supported matching_keys
 *   pay_btn  .. PayPal Payment Button
 *
 * The M1 matching_key is saved in the memberships source_id and is referenced
 * in MS_Model_Import::find_subscription() as 'source' - and not as 'm1'!
 *
 * @since  1.0.1.2
 */
class MS_Helper_ListTable_TransactionMatching extends MS_Helper_ListTable {

	/**
	 * This ID is used as class-name for the list output and also in various
	 * filter names in MS_Helper_ListTable.
	 *
	 * @var string
	 */
	protected $id = 'transaction_matching';

	/**
	 * Currently selected matching source;
	 *
	 * Supported types are
	 *   m1       .. Imported from M1
	 *   pay_btn  .. Custom PayPal Payment Button
	 *
	 * @var string
	 */
	protected $source = false;

	/**
	 * Currently selected source_id that should be matched.
	 *
	 * @var mixed
	 */
	protected $source_id = false;

	/**
	 * Constructor, defines general list table attributes.
	 *
	 * @since  1.0.1.2
	 */
	public function __construct() {
		// 'singular' just added for fun...
		// 'plural' is used as class name for the list.
		parent::__construct(
			array(
				'singular' => 'transaction_match',
				'plural'   => 'transaction_matches',
			)
		);
	}

	/**
	 * Defines the columns of the list table
	 *
	 * @since  1.0.1.2
	 * @return array
	 */
	public function get_columns() {
		$currency = MS_Plugin::instance()->settings->currency;

		$columns = apply_filters(
			'ms_helper_listtable_transactionmatching_columns',
			array(
				'id' => __( 'ID', 'membership2' ),
				'date' => __( 'Time', 'membership2' ),
				'status' => '',
				'method' => '',
				'gateway' => __( 'Gateway', 'membership2' ),
				'amount' => __( 'Amount', 'membership2' ),
				'invoice' => __( 'Invoice', 'membership2' ),
				'note' => __( 'Details', 'membership2' ),
			)
		);

		$columns = apply_filters(
			'ms_helper_listtable_transactionmatching_get_columns',
			$columns,
			$currency
		);

		return $columns;
	}

	/**
	 * Defines, which columns should be output as hidden columns.
	 *
	 * @since  1.0.1.2
	 * @return array
	 */
	public function get_hidden_columns() {
		return apply_filters(
			'ms_helper_listtable_transactionmatching_hidden_columns',
			array()
		);
	}

	/**
	 * Defines, which columns can be sorted.
	 *
	 * @since  1.0.1.2
	 * @return array
	 */
	public function get_sortable_columns() {
		return apply_filters(
			'ms_helper_listtable_transactionmatching_sortable_columns',
			array()
		);
	}

	/**
	 * Defines available bulk actions.
	 *
	 * @since  1.0.1.2
	 * @return array
	 */
	public function get_bulk_actions() {
		return apply_filters(
			'ms_helper_listtable_transactionmatching_bulk_actions',
			array()
		);
	}

	/**
	 * Loads the items that are displayed on the current list page.
	 *
	 * @since  1.0.1.2
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => -1,
			'offset' => 0,
		);

		if ( ! empty( $_GET['source'] ) && ! empty( $_GET['source_id'] ) ) {
			$this->source = $_GET['source'];
			$this->source_id = $_GET['source_id'];

			$args['state'] = array( 'err', 'ignore' );
			$args['source'] = array( $this->source_id, $this->source );

			$total_items = MS_Model_Transactionlog::get_item_count( $args );

			$this->items = apply_filters(
				'ms_helper_listtable_transactionmatching_items',
				MS_Model_Transactionlog::get_items( $args )
			);

			$this->set_pagination_args(
				array(
					'total_items' => $total_items,
					'per_page' => $per_page,
				)
			);
		}
	}

	/**
	 * Displays a custom search box for this list.
	 *
	 * @since  1.0.1.2
	 */
	public function search_box( $text = null, $input_id = 'search' ) {
		// Do not display anything.
		// Transaction logs cannot be searched currently
	}

	/**
	 * Return true if the current list is a view except "all"
	 *
	 * In this list the function always returns true to force the filters to
	 * be displayed.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function is_view() {
		return true;
	}

	/**
	 * Returns a human readable description of the import source.
	 *
	 * @since  1.0.0
	 * @param  string $source The import source.
	 * @param  mixed $source_id The original ID.
	 * @return string The label.
	 */
	protected function get_source_label( $source, $source_id ) {
		$label = __( 'Unknown ID: %s', 'membership2' );

		switch ( $source ) {
			case 'pay_btn':
				$label = __( 'PayPal Payment Button #%s', 'membership2' );
				break;

			case 'm1':
			default:
				$label = __( 'M1 Subscription Level #%s', 'membership2' );
				break;
		}

		$label = sprintf( $label, $source_id );

		return $label;
	}

	/**
	 * Defines predefines filters for this list table.
	 *
	 * Filters display the unmatched source_id values.
	 *
	 * @since  1.0.1.2
	 * @return array
	 */
	public function get_views() {
		$views = array();

		if ( MS_Model_Import::can_match() ) {
			$settings = MS_Factory::load( 'MS_Model_Settings' );
			$lst = $settings->get_custom_setting( 'import_match' );
			if ( ! is_array( $lst ) ) {
				$lst = array();
			}

			$views['label'] = array(
				'label' => __( 'Undefined transactions:', 'membership2' ),
			);

			foreach ( $lst as $source => $ids ) {
				foreach ( $ids as $source_id ) {
					$key = $source . '_' . $source_id;
					$label = $this->get_source_label( $source, $source_id );

					$views[$key] = array(
						'label' => $label,
						'url' => add_query_arg(
							array( 'source' => $source, 'source_id' => $source_id )
						),
					);
				}
			}
		}

		return apply_filters(
			'ms_helper_listtable_transactionmatching_views',
			$views
		);
	}

	/**
	 * Display custom text after the view-links are rendered.
	 *
	 * @since  1.0.1.2
	 */
	public function views() {
		parent::views();

		if ( ! $this->source || ! $this->source_id ) {
			if ( ! MS_Model_Import::can_match() ) {
				$url = MS_Controller_Plugin::get_admin_url(
					'billing',
					array( 'show' => 'logs' )
				);

				echo '<p>';
				_e( 'No suitable transaction found.', 'membership2' );
				echo '</p><p>';
				printf(
					'<strong>%s</strong><br />',
					__( 'Nothing to do right now:', 'membership2' )
				);
				_e( 'Transactions that can be automatically matched will appear here when they are processed by a payment gateway.<br>So simply check again later after new payments were made.', 'membership2' );
				echo '</p><p>';
				printf(
					__( 'If you are impatient then "Retry" some error-state transactions in the %sTransaction Logs%s section and then see if they appear on this page.', 'membership2' ),
					'<a href="' . $url . '">',
					'</a>'
				);
				echo '</p>';
			}

			// Don't display anything if no matching source was selected.
			return;
		}

		if ( ! MS_Model_Import::can_match( $this->source_id, $this->source ) ) {
			// For this transaction details is no matching possible right now.
			return;
		}

		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$label = $this->get_source_label( $this->source, $this->source_id );
		$memberships = MS_Model_Membership::get_memberships();

		$options = array( '0' => '-----' );
		foreach ( $memberships as $item ) {
			if ( $item->is_system() ) { continue; }
			if ( 'm1' == $this->source ) {
				// Only one membership can be matched with a M1 sub_id.
				if ( $item->source_id ) { continue; }
			}

			if ( $item->is_free() ) {
				$options[$item->id] = sprintf(
					'%s &bull; %s',
					$item->name,
					__( 'Free', 'membership2' )
				);
			} else {
				$options[$item->id] = sprintf(
					'%s &bull; %s &bull; %s',
					$item->name,
					$settings->currency . ' ' . MS_Helper_Billing::format_price( $item->price ),
					$item->get_payment_type_desc()
				);
			}
		}

		asort( $options );

		$field_memberships = array(
			'id' => 'match_with',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'before' => sprintf(
				__( '2. Link %s with', 'membership2' ),
				'<b>' . $label . '</b>'
			),
			'field_options' => $options,
		);
		$field_action = array(
			'id' => 'action',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => MS_Controller_Import::AJAX_ACTION_MATCH,
		);
		$field_source = array(
			'id' => 'source',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $this->source,
		);
		$field_source_id = array(
			'id' => 'source_id',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $this->source_id,
		);
		$field_save = array(
			'class' => 'action-match',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Save', 'membership2' ),
		);
		$field_retry_action = array(
			'class' => 'retry_action',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => MS_Controller_Import::AJAX_ACTION_RETRY,
		);
		$field_retry_nonce = array(
			'class' => 'retry_nonce',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => wp_create_nonce( MS_Controller_Import::AJAX_ACTION_RETRY ),
		);

		?>
		<div class="cf"></div>
		<form class="transaction-matching">
			<?php
			wp_nonce_field( MS_Controller_Import::AJAX_ACTION_MATCH );
			MS_Helper_Html::html_element( $field_retry_action );
			MS_Helper_Html::html_element( $field_retry_nonce );
			MS_Helper_Html::html_element( $field_action );
			MS_Helper_Html::html_element( $field_source );
			MS_Helper_Html::html_element( $field_source_id );
			?>
			<div class="content">
				<p><?php
				printf(
					__( '1. Below is a list of past "%s" transactions. Examine these transactions to find out which Membership they refer to.', 'membership2' ),
					'<b>' . $label . '</b>'
				);
				?></p>
				<hr />
				<p><?php
				MS_Helper_Html::html_element( $field_memberships );
				?></p>
				<div style="margin-left:14px">
				<?php
				_e( 'Notes:', 'membership2' );
				?>
				<br />
				<?php
				_e( 'This choice is saved so new transactions are processed automatically from now on.', 'membership2' );
				?>
				<br />
				<?php
				_e( 'Upon saving all transactions below will be processed, this might take a while.', 'membership2' );
				?>
				</div>
			</div>
			<div class="buttons">
				<?php MS_Helper_Html::html_element( $field_save ); ?>
			</div>
		</form>
		<?php
	}

	/**
	 * Custom display function to hide item table for invalid filter options.
	 *
	 * @since  1.0.1.2
	 */
	public function display() {
		if ( MS_Model_Import::can_match( $this->source_id, $this->source ) ) {
			parent::display();
		}
	}

	/**
	 * Returns the row-class to be used for the specified table item.
	 *
	 * @param  object $item The current item.
	 * @return string Class to be added to the table row.
	 */
	protected function single_row_class( $item ) {
		$class = 'log-' . $item->state;

		if ( $item->is_manual ) {
			$class .= ' is-manual';
		}

		return $class;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.1.2
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
	 * @since  1.0.1.2
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
	 * @since  1.0.1.2
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_status( $item, $column_name ) {
		$html = '<span class="log-status"><i class="wpmui-fa log-status-icon"></i></span>';

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.1.2
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_method( $item, $column_name ) {
		$html = '<span class="log-method" data-info="%2$s"><i class="wpmui-fa wpmui-%1$s"></i></span>';
		$icon = '';
		$info = __( 'Unknown method', 'membership2' );

		switch ( $item->method ) {
			case 'handle':
				$icon = 'fa-cloud-download';
				$info = __( 'Gateway called the IPN URL', 'membership2' );
				break;

			case 'request':
				$icon = 'fa-refresh';
				$info = __( 'Plugin requested a recuring payment', 'membership2' );
				break;

			case 'process':
				$icon = 'fa-shopping-cart';
				$info = __( 'User entered payment details', 'membership2' );
				break;
		}

		$html = sprintf( $html, $icon, $info );

		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.1.2
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_gateway( $item, $column_name ) {
		$html = MS_Model_Gateway::get_name( $item->gateway_id, true );
		return $html;
	}

	/**
	 * Output column content
	 *
	 * @since  1.0.1.2
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
	 * @since  1.0.1.2
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
	 * @since  1.0.1.2
	 * @param  object $item The item that is displayed.
	 * @return string The HTML code to output.
	 */
	public function column_note( $item, $column_name ) {
		$extra_infos = '';
		$detail_lines = array();

		// 1. Prepare the "Additional Details" popup.
		$detail_lines = MS_Helper_ListTable_TransactionLog::get_details( $item );

		if ( count( $detail_lines ) ) {
			$icon_class = '';
			$post_data = $item->post;
			if ( ! $post_data ) {
				$icon_class = 'no-post';
			}

			$extra_infos = sprintf(
				'<div class="more-details %3$s">%2$s<div class="post-data"><div class="inner">%1$s</div></div></div>',
				implode( '<br>', $detail_lines ),
				'<i class="wpmui-fa wpmui-fa-info-circle"></i>',
				$icon_class
			);
		}

		// 2. Combine the prepared parts.
		$html = sprintf(
			'<div class="detail-block">%s <span class="txt">%s</span></div>',
			$extra_infos,
			$item->description
		);

		return $html;
	}

}
