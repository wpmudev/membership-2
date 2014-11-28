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
class MS_Helper_List_Table_Rule_Special extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_special';

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		// Set items-per-page to 50: Never use pagination!
		$per_page = $this->get_items_per_page( "{$this->id}_per_page", 50 );
		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		if ( ! empty( $_GET['status'] ) ) {
			$args['rule_status'] = $_GET['status'];
		}

		$total_items = $this->model->get_content_count( $args );
		$this->items = apply_filters(
			"ms_helper_list_table_{$this->id}_items",
			$this->model->get_contents( $args )
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'name' => __( 'Page title', MS_TEXT_DOMAIN ),
			'url' => __( 'Example', MS_TEXT_DOMAIN ),
			'access' => __( 'Members Access', MS_TEXT_DOMAIN ),
		);

		if ( MS_Model_Membership::TYPE_DRIPPED !== $this->membership->type ) {
			unset( $columns['dripped'] );
		}

		return apply_filters(
			"ms_helper_list_table_{$this->id}_columns",
			$columns
		);
	}

	public function column_name( $item ) {
		return $item->post_title;
	}

	public function column_url( $item ) {
		return $item->url;
	}

	public function column_default( $item, $column_name ) {
		$html = '';

		switch ( $column_name ) {
			default:
				$html = $item->$column_name;
				break;
		}

		return $html;
	}

}