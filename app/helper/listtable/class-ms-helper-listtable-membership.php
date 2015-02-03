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
class MS_Helper_ListTable_Membership extends MS_Helper_ListTable {

	protected $id = 'membership';

	public function __construct(){
		parent::__construct(
			array(
				'singular'  => 'membership',
				'plural'    => 'memberships',
				'ajax'      => false,
			)
		);
	}

	public function get_columns() {
		$columns = array(
			'name' => __( 'Membership Name', MS_TEXT_DOMAIN ),
			'type_description' => __( 'Type of Membership', MS_TEXT_DOMAIN ),
			'active' => __( 'Active', MS_TEXT_DOMAIN ),
			'members' => __( 'Members', MS_TEXT_DOMAIN ),
			'price' => __( 'Cost', MS_TEXT_DOMAIN ),
			'shortcode' => __( 'Membership Shortcode', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'membership_helper_listtable_membership_columns',
			$columns
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
				'name' => array( 'name', true ),
				'type_description' => array( 'type', true ),
				'active' => array( 'active', true ),
			)
		);
	}

	public function column_active( $item ) {
		$toggle = array(
			'id' => 'ms-toggle-' . $item->id,
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'value' => $item->active,
			'data_ms' => array(
				'action' => MS_Controller_Membership::AJAX_ACTION_TOGGLE_MEMBERSHIP,
				'field' => 'active',
				'membership_id' => $item->id,
			),
		);

		$html = MS_Helper_Html::html_element( $toggle, true );

		return $html;
	}

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$args = array();

		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}

		// Prepare order by statement.
		if ( ! empty( $args['orderby'] )
			&& property_exists( 'MS_Model_Membership', $args['orderby'] )
		) {
			$args['meta_key'] = $args['orderby'];
			$args['orderby'] = 'meta_value';
		}

		$this->items = apply_filters(
			'membership_helper_listtable_membership_items',
			MS_Model_Membership::get_memberships( $args )
		);
	}

	public function column_name( $item ) {
		$actions = array();
		$badge = '';

		$actions['overview'] = sprintf(
			'<a href="?page=%1$s&step=%2$s&membership_id=%3$s">%4$s</a>',
			esc_attr( $_REQUEST['page'] ),
			MS_Controller_Membership::STEP_OVERVIEW,
			esc_attr( $item->id ),
			__( 'Overview', MS_TEXT_DOMAIN )
		);

		$edit_args = array(
			'membership_id' => $item->id,
		);

		$actions['edit'] = sprintf(
			'<span class="edit"><a href="#" data-ms-dialog="%s" data-ms-data="%s">%s</a></span>',
			'View_Membership_Edit_Dialog',
			esc_attr( json_encode( $edit_args ) ),
			__( 'Edit', MS_TEXT_DOMAIN )
		);

		if ( ! $item->is_free ) {
			$actions['payment'] = sprintf(
				'<a href="?page=%1$s&step=%2$s&membership_id=%3$s&tab=page&edit=1">%4$s</a>',
				esc_attr( $_REQUEST['page'] ),
				MS_Controller_Membership::STEP_PAYMENT,
				esc_attr( $item->id ),
				__( 'Payment options', MS_TEXT_DOMAIN )
			);
		}

		$actions['delete'] = sprintf(
			'<span class="delete"><a href="%s">%s</a></span>',
			wp_nonce_url(
				sprintf(
					'?page=%1$s&membership_id=%2$s&action=%3$s',
					esc_attr( $_REQUEST['page'] ),
					esc_attr( $item->id ),
					'delete'
				),
				'delete'
			),
			__( 'Delete', MS_TEXT_DOMAIN )
		);

		$actions = apply_filters(
			'ms_helper_listtable_' . $this->id . '_column_name_actions',
			$actions,
			$item
		);

		if ( $item->is_guest() ) {
			$badge = sprintf(
				'<span class="ms-guest-badge" data-wpmui-tooltip="%2$s" data-width="180">%1$s</span>',
				__( 'Guest', MS_TEXT_DOMAIN ),
				__( 'All Logged-Out users are considered guests', MS_TEXT_DOMAIN )
			);
		}

		return sprintf(
			'<span class="the-color" style="background-color:%4$s">&nbsp;</span> ' .
			'<span class="the-name">%1$s</span> ' .
			'%3$s%2$s',
			esc_html( $item->name ),
			$this->row_actions( $actions ),
			$badge,
			$item->get_color()
		);
	}

	public function column_members( $item, $column_name ) {
		$html = '';

		if ( ! $item->is_system() ) {
			$html = $item->get_members_count();
		}

		return $html;
	}

	public function column_type_description( $item, $column_name ) {
		$html = '';

		$html .= sprintf(
			'<span class="ms-img-type-%1$s small"></span> ',
			esc_attr( $item->type )
		);

		$desc = $item->type_description;
		if ( ! empty( $desc ) ) {
			$html .= sprintf(
				'<span class="ms-type-desc ms-%1$s">%2$s</span>',
				esc_attr( $item->type ),
				$desc
			);
		}

		if ( $item->private ) {
			$html .= sprintf(
				'<span class="ms-is-private">, <span>%1$s</span></span>',
				__( 'Private', MS_TEXT_DOMAIN )
			);
		}

		return $html;
	}

	public function column_price( $item, $column_name ) {
		$html = '';

		if ( ! $item->is_system() ) {
			if ( ! $item->is_free() ) {
				$html = sprintf(
					'<span class="ms-currency">%1$s</span> <span class="ms-price">%2$s</span> (<span class="ms-payment">%3$s</span>)',
					MS_Plugin::instance()->settings->currency_symbol,
					MS_Helper_Billing::format_price( $item->price ),
					$item->get_payment_type_desc()
				);
				$html = '<span class="ms-bold">' . $html . '</span>';
			} else {
				$html = sprintf(
					'<span class="ms-low">%1$s</span>',
					__( 'Free', MS_TEXT_DOMAIN )
				);
			}
		}

		return $html;
	}

	public function column_shortcode( $item, $column_name ) {
		return sprintf(
			'<code>[%1$s id="%2$s"]</code>',
			MS_Rule_Shortcode_Model::PROTECT_CONTENT_SHORTCODE,
			esc_attr( $item->id )
		);
	}

	public function get_bulk_actions() {
		return apply_filters(
			'ms_helper_listtable_membership_bulk_actions',
			array()
		);
	}
}
