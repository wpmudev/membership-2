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
class MS_Rule_MenuItem_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = 'rule_menu';

	/**
	 * A list of all available menus.
	 *
	 * @var array
	 */
	protected $menus;

	/**
	 * The currently selected menu-ID.
	 *
	 * @var int
	 */
	protected $menu_id;

	public function __construct( $model, $membership, $all_menus, $menu_id ) {
		parent::__construct( $model, $membership );
		$this->menus = $all_menus;
		$this->menu_id = $menu_id;
		$this->name['singular'] = __( 'Menu Item', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Menu Items', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		$columns = array(
			'cb' => true,
			'title' => __( 'Menu Title', MS_TEXT_DOMAIN ),
			'type' => __( 'Menu Type', MS_TEXT_DOMAIN ),
			'access' => true,
		);

		return apply_filters(
			'membership_helper_listtable_' . $this->id . '_columns',
			$columns
		);
	}

	public function prepare_items_args( $defaults ) {
		$args = apply_filters(
			'ms_rule_menuitem_listtable_prepare_items_args',
			array( 'menu_id' => $this->menu_id )
		);

		return wp_parse_args( $args, $defaults );
	}

	public function column_title( $item, $column_name ) {
		return $item->title;
	}

	public function column_type( $item, $column_name ) {
		return $item->type_label;
	}

	/**
	 * No pagination for this rule
	 *
	 * @since  1.1.0
	 * @return int
	 */
	protected function get_items_per_page() {
		return 0;
	}

	/**
	 * Return true if the current list is a view except "all"
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function is_view() {
		return true;
	}

	/**
	 * The rule uses the view-filter to select the menu to protect
	 *
	 * @since  1.1.0
	 */
	public function get_views() {
		$views = $this->menus;
		$views[$this->menu_id]['current'] = true;

		return $views;
	}

}