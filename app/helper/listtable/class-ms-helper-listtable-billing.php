<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Helper_ListTable_Billing extends MS_Helper_ListTable {

	protected $id = 'billing';

	public function __construct() {
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
			'ms_helper_listtable_billing_columns',
			array(
				'cb' => '<input type="checkbox" />',
				'invoice' => __( 'Invoice #', 'membership2' ),
				'user' => __( 'User', 'membership2' ),
				'membership' => __( 'Membership', 'membership2' ),
				'status' => __( 'Status', 'membership2' ),
				'total' => __( 'Total', 'membership2' ),
				'due_date' => __( 'Due date', 'membership2' ),
				'gateway_id' => __( 'Gateway', 'membership2' ),
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
			'ms_helper_listtable_billing_hidden_columns',
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'ms_helper_listtable_billing_sortable_columns',
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

		if ( ! isset( $_REQUEST['status'] ) ) {
			$_GET['status'] = 'default';
			$_REQUEST['status'] = 'default';
		}

		$args = $this->get_query_args();

		$total_items = MS_Model_Invoice::get_invoice_count( $args );

		$this->items = apply_filters(
			'ms_helper_listtable_billing_items',
			MS_Model_Invoice::get_invoices( $args )
		);

		$per_page = $this->get_items_per_page(
			'invoice_per_page',
			self::DEFAULT_PAGE_SIZE
		);
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	private function get_query_args() {
		$defaults = MS_Model_Invoice::get_query_args();
		lib3()->array->equip_request( 's' );

		$per_page = $this->get_items_per_page(
			'invoice_per_page',
			self::DEFAULT_PAGE_SIZE
		);
		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		// Filter by search-term
		$search_filter = $_REQUEST['s'];
		if ( ! empty( $search_filter ) ) {
			$this->search_string = $search_filter;
		}

		$args = wp_parse_args( $args, $defaults );

		return $args;
	}

	/**
	 * Returns the row-class to be used for the specified table item.
	 *
	 * @param  object $item The current item.
	 * @return string Class to be added to the table row.
	 */
	protected function single_row_class( $item ) {
		return 'invoice-' . $item->status;
	}

	public function column_invoice( $item ) {
		$actions = array();

		// Prepare the item actions.
		$actions['view'] = sprintf(
			'<a href="%s">%s</a>',
			get_permalink( $item->id ),
			__( 'View', 'membership2' )
		);

		if ( MS_Gateway_Manual::ID == $item->gateway_id && ! $item->is_paid() ) {
			$action_url = MS_Controller_Plugin::get_admin_url(
				'billing',
				array(
					'action' => MS_Controller_Billing::ACTION_PAY_IT,
					'_wpnonce' => wp_create_nonce( MS_Controller_Billing::ACTION_PAY_IT ),
					'invoice_id' => $item->id,
				)
			);

			$actions['pay_it'] = sprintf(
				'<a href="%s">%s</a>',
				$action_url,
				__( 'Mark as paid', 'membership2' )
			);
		}

		$edit_url = MS_Controller_Plugin::get_admin_url(
			'billing',
			array(
				'action' => MS_Controller_Billing::ACTION_EDIT,
				'invoice_id' => $item->id,
			)
		);

		return sprintf(
			'<a href="%3$s"><b>%1$s</b></a> %2$s',
			$item->get_invoice_number(),
			$this->row_actions( $actions ),
			$edit_url
		);
	}

	public function column_user( $item, $column_name ) {
		$member = MS_Factory::load( 'MS_Model_Member', $item->user_id );

		$html = sprintf(
			'<a href="%s">%s</a>',
			MS_Controller_Plugin::get_admin_url(
				'add-member',
				array( 'user_id' => $item->user_id )
			),
			$member->username
		);

		return $html;
	}

	public function column_membership( $item, $column_name ) {
		$membership = MS_Factory::load( 'MS_Model_Membership', $item->membership_id );

		return $membership->get_name_tag();
	}

	public function column_status( $item, $column_name ) {
		$icon = '';

		switch ( $item->status ) {
			case MS_Model_Invoice::STATUS_NEW:
				$icon = '<i class="wpmui-fa wpmui-fa-circle-o"></i>';
				break;

			case MS_Model_Invoice::STATUS_PAID:
				$icon = '<i class="wpmui-fa wpmui-fa-check-circle"></i>';
				break;

			case MS_Model_Invoice::STATUS_PENDING:
			case MS_Model_Invoice::STATUS_BILLED:
				$icon = '<i class="wpmui-fa wpmui-fa-clock-o"></i>';
				break;

			case MS_Model_Invoice::STATUS_DENIED:
				$icon = '<i class="wpmui-fa wpmui-fa-times-circle"></i>';
				break;

			case MS_Model_Invoice::STATUS_ARCHIVED:
				$icon = '<i class="wpmui-fa wpmui-fa-times-circle-o"></i>';
				break;

			default:
				$icon = $item->status_text();
				break;
		}

		return sprintf(
			'<span class="payment-status payment-status-%1$s" title="%3$s">%2$s</span>',
			$item->status,
			$icon,
			$item->status_text()
		);
	}

	public function column_amount( $item, $column_name ) {
		$html = MS_Helper_Billing::format_price( $item->amount );
		return $html;
	}

	public function column_total( $item, $column_name ) {
		if ( $item->total ) {
			$currency = $item->currency;
			$value = MS_Helper_Billing::format_price( $item->total );

			$html = sprintf(
				'<b>%1$s</b> <small>%2$s</small>',
				$value,
				$currency
			);
		} else {
			$html = __( 'Free', 'membership2' );
		}

		return $html;
	}

	public function column_due_date( $item, $column_name ) {
		$due_now = false;
		$is_paid = $item->is_paid();

		if ( ! $is_paid ) {
			$diff = MS_Helper_Period::subtract_dates(
				$item->due_date,
				MS_Helper_Period::current_date(),
				DAY_IN_SECONDS, // return value in DAYS.
				true // return negative value if first date is before second date.
			);
			$due_now = ($diff < 0);
		}

		$due_date = MS_Helper_Period::format_date( $item->due_date );

		if ( $due_now ) {
			$html = sprintf(
				'<span class="due-now" title="%2$s">%1$s</span>',
				$due_date,
				__( 'Payment is overdue', 'membership2' )
			);
		} elseif ( $item->pay_date ) {
			$pay_date = MS_Helper_Period::format_date( $item->pay_date, 'M j, Y' );
			$html = sprintf(
				'<span class="is-paid" title="%2$s">%1$s</span>',
				$due_date,
				sprintf(
					__( 'Paid: %s', 'membership2' ),
					$pay_date
				)
			);
		} else {
			$html = sprintf(
				'<span>%1$s</span>',
				$due_date
			);
		}

		return $html;
	}

	public function column_gateway_id( $item, $column_name ) {
		$html = MS_Model_Gateway::get_name( $item->gateway_id );
		return $html;
	}

	public function column_default( $item, $column_name ) {
		$html = '';

		if ( property_exists( $item, $column_name ) ) {
			$html = $item->column_name;
		}

		return apply_filters(
                            'ms_helper_listtable_billing_default_column',
                            $html,
                            $this
                        );
	}

	public function get_bulk_actions() {
		$bulk_actions = array(
			'archive' => __( 'Remove', 'membership2' ),
		);

		return apply_filters(
			'ms_helper_listtable_billing_bulk_actions',
			$bulk_actions,
			$this
		);
	}

	public function get_views() {
		$all_status = array(
			MS_Model_Invoice::STATUS_PAID => __( 'Paid', 'membership2' ),
			MS_Model_Invoice::STATUS_NEW => __( 'Draft', 'membership2' ),
			MS_Model_Invoice::STATUS_DENIED => __( 'Denied', 'membership2' ),
		);

		$views = array();
		$orig_status = '';

		if ( isset( $_REQUEST['status'] ) ) {
			$orig_status = $_REQUEST['status'];
		}
		$_REQUEST['status'] = 'default';
		$args = $this->get_query_args();
		$_REQUEST['status'] = $orig_status;
		$base_url = esc_url_raw( remove_query_arg( array( 'status', 'msg' ) ) );
		$count = MS_Model_Invoice::get_invoice_count( $args );
		$views['all'] = array(
			'url' => $base_url,
			'label' => __( 'Default', 'membership2' ),
			'count' => $count,
		);

		$url = esc_url_raw( add_query_arg( 'status', 'open', $base_url ) );
		$args = $this->get_query_args();
		$args['meta_query']['status']['value'] = array(
			MS_Model_Invoice::STATUS_BILLED,
			MS_Model_Invoice::STATUS_PENDING,
		);
		$args['meta_query']['status']['compare'] = 'IN';
		$count = MS_Model_Invoice::get_invoice_count( $args );
		$views['open'] = array(
			'url' => $url,
			'label' => __( 'Billed or Pending', 'membership2' ),
			'count' => $count,
		);

		foreach ( $all_status as $status => $desc ) {
			$args = $this->get_query_args();
			$args['meta_query']['status']['value'] = $status;
			$count = MS_Model_Invoice::get_invoice_count( $args );

			if ( $count ) {
				$status_url = esc_url_raw(
					add_query_arg(
						array( 'status' => $status ),
						remove_query_arg( array( 'msg' ) )
					)
				);
			} else {
				$status_url = false;
				$desc .= ' (0)';
				if ( MS_Model_Invoice::STATUS_DENIED != $status ) {
					$desc .= ' |';
				}
				$count = false;
			}

			$views[ $status ] =	array(
				'url' => $status_url,
				'label' => $desc,
				'count' => $count,
			);
		}

		return apply_filters( 'ms_helper_listtable_billing_views', $views );
	}

}
