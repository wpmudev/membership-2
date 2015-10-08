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
			50
		);

		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
			'meta_query' => array(),
		);

		if ( ! empty( $_GET['state'] ) ) {
			$args['state'] = $_GET['state'];
		}

		if ( ! empty( $_GET['id'] ) ) {
			$args['post__in'] = explode( ',', $_GET['id'] );
		}

		if ( ! empty( $_GET['invoice'] ) ) {
			$args['meta_query']['invoice_id'] = array(
				'key' => 'invoice_id',
				'value' => explode( ',', $_GET['invoice'] ),
				'compare' => 'IN',
			);
		}

		if ( ! empty( $_GET['gateway_id'] ) ) {
			$args['meta_query']['gateway_id'] = array(
				'key' => 'gateway_id',
				'value' => $_GET['gateway_id'],
			);
		}

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
		$base_url = remove_query_arg( array( 'state', 'id', 'invoice' ) );

		$views['all'] = array(
			'label' => __( 'All', 'membership2' ),
			'url' => $base_url,
			'count' => MS_Model_Transactionlog::get_item_count(),
		);

		$views['ok'] = array(
			'label' => __( 'Successful', 'membership2' ),
			'url' => add_query_arg( 'state', 'ok', $base_url ),
			'count' => MS_Model_Transactionlog::get_item_count(
				array( 'state' => 'ok' )
			),
		);

		$views['err'] = array(
			'label' => __( 'Failed', 'membership2' ),
			'url' => add_query_arg( 'state', 'err', $base_url ),
			'count' => MS_Model_Transactionlog::get_item_count(
				array( 'state' => 'err' )
			),
		);

		$views['ignore'] = array(
			'label' => __( 'Ignored', 'membership2' ),
			'url' => add_query_arg( 'state', 'ignore', $base_url ),
			'count' => MS_Model_Transactionlog::get_item_count(
				array( 'state' => 'ignore' )
			),
		);

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
		$class = 'log-' . $item->state;

		if ( $item->is_manual ) {
			$class .= ' is-manual';
		}

		return $class;
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
		$html = '<span class="log-status"><i class="wpmui-fa log-status-icon"></i></span>';

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
	 * @since  1.0.0
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
		$extra_infos = '';
		$row_actions = '';
		$nonce_action = '';
		$detail_lines = array();
		$actions = array();
		$ind = 0;

		// 1. Prepare the "Additional Details" popup.
		$detail_lines = self::get_details( $item );

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

		// 2. Prepare the row actions.
		if ( 'err' == $item->state ) {
			$actions = array(
				'action-link' => __( 'Link', 'membership2' ),
				'action-ignore' => __( 'Ignore', 'membership2' ),
			);

			// We can only re-process the transaction if we have POST data.
			$postdata = $item->post;
			if ( is_array( $postdata ) && ! empty( $postdata ) ) {
				$actions['action-retry'] = __( 'Retry', 'membership2' );
			}
		} elseif ( 'ignore' == $item->state && $item->is_manual ) {
			$actions = array(
				'action-clear' => __( 'Reset', 'membership2' ),
			);
			$nonce_action = MS_Controller_Billing::AJAX_ACTION_TRANSACTION_LINK;
		}

		if ( count( $actions ) ) {
			$nonces = array();
			$nonces[] = wp_nonce_field(
				MS_Controller_Billing::AJAX_ACTION_TRANSACTION_UPDATE,
				'nonce_update',
				false,
				false
			);
			$nonces[] = wp_nonce_field(
				MS_Controller_Billing::AJAX_ACTION_TRANSACTION_LINK,
				'nonce_link',
				false,
				false
			);
			$nonces[] = wp_nonce_field(
				MS_Controller_Import::AJAX_ACTION_RETRY,
				'nonce_retry',
				false,
				false
			);
			$action_tags = array();
			foreach ( $actions as $class => $label ) {
				$action_tags[] = sprintf(
					'<a href="#" class="%s">%s</a>',
					$class,
					$label
				);
			}

			$row_actions = sprintf(
				'<div class="actions %1$s-actions">%2$s %3$s</div>',
				$item->state,
				implode( '', $nonces ),
				implode( ' | ', $action_tags )
			);
		}

		// 3. Combine the prepared parts.
		$html = sprintf(
			'<div class="detail-block">%s <span class="txt">%s</span> %s</div>',
			$extra_infos,
			$item->description,
			$row_actions
		);

		return $html;
	}

	/**
	 * Returns an array with additional details about the transaction.
	 *
	 * This function is used in the note-column and is shared in the objects for
	 * TransactionLog and TransactionMatching.
	 *
	 * @since  1.0.1.2
	 * @param  MS_Model_Transaction $item Transaction object.
	 * @return array The transaction details.
	 */
	static public function get_details( $item ) {
		$detail_lines = array();

		if ( $item->is_manual ) {
			$detail_lines[] = __( 'Transaction state manually changed', 'membership2' );
			$detail_lines[] = sprintf(
				__( 'Modified on: %s', 'membership2' ),
				$item->manual_date
			);
			$detail_lines[] = sprintf(
				__( 'Modified by: %s', 'membership2' ),
				$item->get_manual_user()->display_name
			);
		}

		$postdata = $item->post;
		$group = array();
		if ( ! empty( $postdata ) && is_array( $postdata ) ) {
			$id_fields = array();
			switch ( $item->gateway_id ) {
				case MS_Gateway_Paypalstandard::ID:
					if ( isset( $postdata['invoice'] ) ) {
						$id_fields[] = 'invoice';
					} elseif ( isset( $postdata['custom'] ) ) {
						$id_fields[] = 'custom';
						$detail_lines[] = __( 'Imported subscription from old Membership plugin.', 'membership2' );
					} elseif ( isset( $postdata['btn_id'] ) ) {
						$id_fields[] = 'btn_id';
						$id_fields[] = 'payer_email';
						$detail_lines[] = __( 'Payment via a PayPal Payment button.', 'membership2' );
					} elseif ( isset( $postdata['txn_type'] ) ) {
						// Highlight invalid transactions.
						if ( 'send_money' == $postdata['txn_type'] ) {
							$id_fields[] = 'txn_type';
							$detail_lines[] = __( 'Someone sent you money inside PayPal or PayPal re-sent a previous payment.<br>Plugin did not attempt to match payment to a subscription.', 'membership2' );
						}
					}
					break;
			}

			if ( count( $detail_lines ) ) {
				$detail_lines[] = '<hr />';
			}
			ksort( $postdata );
			$ind = 0;
			foreach ( $postdata as $key => $value ) {
				if ( strpos( $key, ':' ) > 0 ) {
					$parts = explode( ':', $key );
					if ( ! isset( $groups[ $parts[0] ] ) ) {
						$groups[ $parts[0] ] = array();
					}
					$groups[ $parts[0] ][ $parts[1] ] = $value;
					continue;
				}

				if ( 0 === $ind ) {
					$detail_lines[] = __( 'POST data:', 'membership2' );
				}

				$ind += 1;

				$line_class = '';
				if ( in_array( $key, $id_fields ) ) {
					$line_class = 'is-id';
				}

				$detail_lines[] = sprintf(
					'<span class="line %s"><small class="line-num">%s</small> <span class="line-key">%s</span> <span class="line-val">%s</span></span>',
					$line_class,
					$ind,
					$key,
					htmlspecialchars( $value )
				);
			}

			foreach ( $groups as $group => $values ) {
				$ind = 0;
				foreach ( $values as $key => $value ) {
					if ( 0 === $ind ) {
						$detail_lines[] = '<hr />';
						$detail_lines[] = $group . ':';
					}

					$ind += 1;

					$detail_lines[] = sprintf(
						'<span class="line"><small class="line-num">%s</small> <span class="line-key">%s</span> <span class="line-val">%s</span></span>',
						$ind,
						$key,
						htmlspecialchars( $value )
					);
				}
			}
		}

		$headers = $item->headers;
		$cookies = false;
		if ( ! empty( $headers ) && is_array( $headers ) ) {
			if ( count( $detail_lines ) ) {
				$detail_lines[] = '<hr />';
			}
			ksort( $headers );
			$ind = 0;
			$detail_lines[] = __( 'HTTP Headers:', 'membership2' );
			foreach ( $headers as $key => $value ) {
				if ( 'Cookie' == $key ) {
					$cookies = explode( ';', $value );
					continue;
				}
				$ind += 1;

				$detail_lines[] = sprintf(
					'<span class="line"><small class="line-num">%s</small> <span class="line-key">%s</span> <span class="line-val">%s</span></span>',
					$ind,
					$key,
					htmlspecialchars( $value )
				);
			}
		}

		if ( ! empty( $cookies ) && is_array( $cookies ) ) {
			if ( count( $detail_lines ) ) {
				$detail_lines[] = '<hr />';
			}
			ksort( $cookies );
			$ind = 0;
			$detail_lines[] = __( 'Cookies:', 'membership2' );
			foreach ( $cookies as $key => $value ) {
				$ind += 1;
				$parts = explode( '=', $value );
				if ( count( $parts ) < 2 ) { continue; }

				$detail_lines[] = sprintf(
					'<span class="line"><small class="line-num">%s</small> <span class="line-key">%s</span> <span class="line-val">%s</span></span>',
					$ind,
					array_shift( $parts ),
					htmlspecialchars( implode( '=', $parts ) )
				);
			}
		}

		if ( count( $detail_lines ) ) {
			$detail_lines[] = '<hr />';
		}

		$detail_lines[] = __( 'Logged in user:', 'membership2' );
		$user_id = $item->user_id;
		if ( $user_id ) {
			$user = get_user_by( 'id', $user_id );
			$detail_lines[] = sprintf(
				'<span class="line"><small class="line-num">%s</small><span class="line-key">%s</span> <span class="line-val">%s</span></span>',
				1,
				__( 'User ID', 'membership2' ),
				$user_id
			);
			$detail_lines[] = sprintf(
				'<span class="line"><small class="line-num">%s</small><span class="line-key">%s</span> <span class="line-val">%s</span></span>',
				2,
				__( 'Username', 'membership2' ),
				$user->user_login
			);
			$detail_lines[] = sprintf(
				'<span class="line"><small class="line-num">%s</small><span class="line-key">%s</span> <span class="line-val">%s</span></span>',
				3,
				__( 'Email', 'membership2' ),
				$user->user_email
			);
		} else {
			$detail_lines[] = sprintf(
				'<span class="line"><small class="line-num">%s</small><span class="line-key">%s</span> <span class="line-val">%s</span></span>',
				1,
				__( 'Guest', 'membership2' ),
				__( 'Could not determine a logged in user', 'membership2' )
			);
		}

		$req_url = $item->url;
		if ( ! empty( $req_url ) ) {
			if ( count( $detail_lines ) ) {
				$detail_lines[] = '<hr />';
			}
			$detail_lines[] = sprintf(
				'<span class="line"><span class="line-key">%s</span> <span class="line-val">%s</span></span>',
				__( 'Request URL', 'membership2' ),
				$req_url
			);
		}

		return $detail_lines;
	}

}
