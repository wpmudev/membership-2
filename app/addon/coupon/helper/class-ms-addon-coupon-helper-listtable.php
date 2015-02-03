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
class MS_Addon_Coupon_Helper_Listtable extends MS_Helper_ListTable {

	protected $id = 'coupon';

	public function __construct(){
		parent::__construct(
			array(
				'singular' => 'coupon',
				'plural'   => 'coupons',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return apply_filters(
			'ms_addon_coupon_helper_listtyble_columns',
			array(
				'cb' => '<input type="checkbox" />',
				'ccode' => __( 'Coupon Code', MS_TEXT_DOMAIN ),
				'discount' => __( 'Discount', MS_TEXT_DOMAIN ),
				'start_date' => __( 'Start date', MS_TEXT_DOMAIN ),
				'expire_date' => __( 'Expire date', MS_TEXT_DOMAIN ),
				'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
				'used' => __( 'Used', MS_TEXT_DOMAIN ),
				'remaining_uses' => __( 'Remaining uses', MS_TEXT_DOMAIN ),
			)
		);
	}

	public function get_hidden_columns() {
		return apply_filters(
			'ms_addon_coupon_helper_listtable_hidden_columns',
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'ms_addon_coupon_helper_listtable_sortable_columns',
			array()
		);
	}

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$total_items = MS_Addon_Coupon_Model::get_coupon_count();
		$per_page = $this->get_items_per_page( 'coupon_per_page', 10 );
		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		$this->items = apply_filters(
			'ms_addon_coupon_helper_listtyble_items',
			MS_Addon_Coupon_Model::get_coupons( $args )
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="coupon_id[]" value="%1$s" />',
			esc_attr( $item->id )
		);
	}

	public function column_ccode( $item ) {
		$actions = array();

		$actions['edit'] = sprintf(
			'<a href="?page=%s&action=%s&coupon_id=%s">%s</a>',
			esc_attr( $_REQUEST['page'] ),
			'edit',
			esc_attr( $item->id ),
			__( 'Edit', MS_TEXT_DOMAIN )
		);
		$actions['delete'] = sprintf(
			'<span class="delete"><a href="%s">%s</a></span>',
			wp_nonce_url(
				sprintf(
					'?page=%s&coupon_id=%s&action=%s',
					esc_attr( $_REQUEST['page'] ),
					esc_attr( $item->id ),
					'delete'
				),
				'delete'
			),
			__( 'Delete', MS_TEXT_DOMAIN )
		);

		return sprintf(
			'<code>%1$s</code> %2$s',
			$item->name,
			$this->row_actions( $actions )
		);
	}

	public function column_membership( $item ) {
		$html = '';
		$is_any = true;

		foreach ( $item->membership_id as $id ) {
			if ( MS_Model_Membership::is_valid_membership( $id ) ) {
				$is_any = false;

				$membership = MS_Factory::load( 'MS_Model_Membership', $id );
				$html .= sprintf(
					'<span class="ms-bold">%s</span><br />',
					$membership->name
				);
			}
		}

		if ( $is_any ) {
			$html = sprintf(
				'<span class="ms-low">%s</span>',
				__( 'Any', MS_TEXT_DOMAIN )
			);
		}

		return $html;
	}

	public function column_discount( $item ) {
		$html = '';

		if ( MS_Addon_Coupon_Model::TYPE_VALUE == $item->discount_type ) {
			$html = sprintf(
				'%s %s',
				MS_Plugin::instance()->settings->currency,
				MS_Helper_Billing::format_price( $item->discount )
			);
		} elseif ( MS_Addon_Coupon_Model::TYPE_PERCENT == $item->discount_type ) {
			$html = $item->discount . ' %';
		} else {
			$html = apply_filters( 'ms_addon_coupon_helper_listtable_column_discount', $item->discount );
		}

		return $html;
	}

	public function column_start_date( $item ) {
		$html = $item->start_date;

		return $html;
	}

	public function column_expire_date( $item ) {
		$html = '';

		if ( $item->expire_date ) {
			$html = $item->expire_date;
		} else {
			$html = __( 'No expire', MS_TEXT_DOMAIN );
		}

		return $html;
	}

	public function column_used( $item ) {
		$html = $item->used;

		return $html;
	}

	public function column_remaining_uses( $item ) {
		$html = $item->remaining_uses;

		return $html;
	}

	public function get_bulk_actions() {
		return apply_filters(
			'ms_addon_coupon_helper_listtable_bulk_actions',
			array(
				'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
			)
		);
	}

}
