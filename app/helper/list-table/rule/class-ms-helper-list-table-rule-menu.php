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
class MS_Helper_List_Table_Rule_Menu extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_menu';

	protected $menu_id;

	public function __construct( $model, $membership, $menu_id ) {
		parent::__construct( $model, $membership );
		$this->menu_id = $menu_id;
		$this->name['singular'] = __( 'Menu Item', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Menu Items', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		$menus = $this->model->get_menu_array();
		return apply_filters(
			'membership_helper_list_table_' . $this->id . '_columns',
			array(
				'cb' => true,
				'title' => __( 'Menu title', MS_TEXT_DOMAIN ),
				'access' => true,
			)
		);
	}

	public function prepare_items_args( $defaults ) {
		$args = apply_filters(
			'ms_helper_list_table_rule_menu_prepare_items_args',
			array( 'menu_id' => $this->menu_id )
		);

		return wp_parse_args( $args, $defaults );
	}

	public function column_title( $item, $column_name ) {
		return $item->title;
	}

	protected function get_items_per_page() {
		return 0;
	}

}