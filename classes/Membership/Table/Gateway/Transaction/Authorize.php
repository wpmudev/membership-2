<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

/**
 * Authorize.net transactions table.
 *
 * @category Membership
 * @package Table
 * @subpackage Gateway
 *
 * @since 3.5
 */
class Membership_Table_Gateway_Transaction_Authorize extends Membership_Table {

	/**
	 * Returns subscription column.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The array of current item information.
	 * @return string The column value
	 */
	public function column_subscription( $item ) {
		$subscription = new M_Subscription( $item['transaction_subscription_ID'] );
		return sprintf( '<b>%s</b>', $subscription->sub_name() );
	}

	/**
	 * Returns user column.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The array of current item information.
	 * @return string The column value
	 */
	public function column_user( $item ) {
		$user = new WP_User( $item['transaction_user_ID'] );
		return $user->user_login;
	}

	/**
	 * Returns date column.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The array of current item information.
	 * @return string The column value
	 */
	public function column_date( $item ) {
		return date( 'l, d M y', $item['transaction_stamp'] );
	}

	/**
	 * Returns time column.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The array of current item information.
	 * @return string The column value
	 */
	public function column_time( $item ) {
		return date( 'H:i:s T', $item['transaction_stamp'] );
	}

	/**
	 * Returns amount column.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The array of current item information.
	 * @return string The column value
	 */
	public function column_amount( $item ) {
		return sprintf( '%s %s', $item['transaction_currency'], number_format( $item['transaction_total_amount'] / 100, 2, '.', ',' ) );
	}

	/**
	 * Returns transaction column.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The array of current item information.
	 * @return string The column value
	 */
	public function column_transaction( $item ) {
		return '<b>' . $item['transaction_paypal_ID'] . '</b>';
	}

	/**
	 * Returns status column.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The array of current item information.
	 * @return string The column value
	 */
	public function column_status( $item ) {
		return isset( $this->_args['statuses'][$item['transaction_status']] )
			? sprintf( '<b>%s</b>', $this->_args['statuses'][$item['transaction_status']] )
			: sprintf( '<i>%s</i>', esc_html__( 'unkonwn', 'membership' ) );
	}

	/**
	 * Returns notes column.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The array of current item information.
	 * @return string The column value
	 */
	public function column_notes( $item ) {
		return $item['transaction_note'];
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	public function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'] );
		}

		?><div class="tablenav <?php echo esc_attr( $which ) ?>">
			<?php $this->extra_tablenav( $which ) ?>
			<?php $this->pagination( $which ) ?>
			<br class="clear">
		</div><?php
	}

	/**
	 * Returns the associative array with the list of views available on this table.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @global wpdb $wpdb The database connection.
	 * @return array The array of views.
	 */
	public function get_views() {
		global $wpdb;

		$status = $this->_get_status_filter();
		$sub_id = filter_input( INPUT_GET, 'subscription', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );
		if ( !empty( $sub_id ) ) {
			$sub_id = ' AND transaction_subscription_ID = ' . $sub_id;
		}

		$views = array(
			'all' => sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				add_query_arg( array( 'status' => false, 'paged' => false ) ),
				!isset( $_GET['status'] ) ? ' class="current"' : '',
				__( 'All', 'membership' ),
				intval( $wpdb->get_var( sprintf(
					"SELECT COUNT(*) FROM %s WHERE transaction_gateway = '%s'%s",
					MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION,
					esc_sql( $this->_args['gateway'] ),
					$sub_id
				) ) )
			)
		);

		foreach ( $this->_args['statuses'] as $key => $label ) {
			$views[$key] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				add_query_arg( array( 'status' => $key, 'paged' => false ) ),
				$status == $key ? ' class="current"' : '',
				$label,
				intval( $wpdb->get_var( sprintf(
					"SELECT COUNT(*) FROM %s WHERE transaction_gateway = '%s' AND transaction_status = '%s'%s",
					MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION,
					esc_sql( $this->_args['gateway'] ),
					$key,
					$sub_id
				) ) )
			);
		}

		return $views;
	}

	/**
	 * Returns status filter value.
	 *
	 * @since 3.5
	 *
	 * @access private
	 * @return int Request status filter value.
	 */
	private function _get_status_filter() {
		$statuses = array_keys( $this->_args['statuses'] );
		return filter_input( INPUT_GET, 'status', FILTER_VALIDATE_INT, array(
			'options' => array(
				'min_range' => min( $statuses ),
				'max_range' => max( $statuses ),
				'default'   => false,
			),
		) );
	}

	/**
	 * Returns an array of actual columns for the table.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @return array The array of columns to render.
	 */
	public function get_columns() {
		$columns = array(
			'subscription' => esc_html__( 'Subscription', 'membership' ),
			'user'         => esc_html__( 'User', 'membership' ),
			'amount'       => esc_html__( 'Amount', 'membership' ),
			'transaction'  => esc_html__( 'Transaction', 'membership' ),
			'status'       => esc_html__( 'Status', 'membership' ),
			'date'         => esc_html__( 'Date', 'membership' ),
			'time'         => esc_html__( 'Time', 'membership' ),
			'notes'        => esc_html__( 'Notes', 'membership' ),
		);

		return $columns;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @since 3.5
	 * @access protected
	 */
	public function extra_tablenav( $which ) {
		$sub_id = filter_input( INPUT_GET, 'subscription', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );

		?><div class="alignleft actions">
			<?php if ( 'top' == $which ) : ?>
				<select name="subscription">
					<option value=""><?php _e( 'Show all subscriptions', 'membership' ) ?></option>
					<?php
						foreach ( $this->_args['subscriptions'] as $subscription ) :
							printf( '<option%s value="%s">%s</option>',
								selected( $subscription['id'], $sub_id, false ),
								esc_attr( $subscription['id'] ),
								esc_html( $subscription['sub_name'] )
							);
						endforeach;
					?>
				</select>
				<?php submit_button( __( 'Filter' ), 'button', false, false, array( 'id' => 'post-query-submit' ) ); ?>
			<?php endif; ?>
		</div><?php
	}

	/**
	 * Fetches transactions from database.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @global wpdb $wpdb The database connection.
	 */
	public function prepare_items() {
		global $wpdb;

		parent::prepare_items();

		$per_page = 20;
		$offset = ( $this->get_pagenum() - 1 ) * $per_page;

		$status = $this->_get_status_filter();
		$sub_id = filter_input( INPUT_GET, 'subscription', FILTER_VALIDATE_INT, array( 'options' => array( 'min_range' => 1 ) ) );

		$this->items = $wpdb->get_results( sprintf( "
			SELECT SQL_CALC_FOUND_ROWS *
			  FROM %s AS st
			 WHERE transaction_gateway = '%s'%s%s
			 ORDER BY st.transaction_ID DESC
			 LIMIT %d
			OFFSET %d",
			MEMBERSHIP_TABLE_SUBSCRIPTION_TRANSACTION,
			esc_sql( $this->_args['gateway'] ),
			!empty( $status ) ? " AND transaction_status = '{$status}'" : '',
			!empty( $sub_id ) ? ' AND transaction_subscription_ID = ' . $sub_id : '',
			$per_page,
			$offset
		), ARRAY_A );

		$total_items = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
	}

}