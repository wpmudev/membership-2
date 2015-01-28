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
 * @since 4.0.0
 *
 */
class MS_Addon_BuddyPress_Rule_ListTable extends MS_Helper_ListTable_Rule {

	/**
	 * List-ID is only used to generate the list HTML code.
	 *
	 * @var string
	 */
	protected $id = 'rule_buddypress';

	/**
	 * Define available columns
	 *
	 * @since  1.1.0
	 * @return array
	 */
	public function get_columns() {
		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
			array(
				'cb' => true,
				'name' => __( 'Type', MS_TEXT_DOMAIN ),
				'access' => true,
			)
		);
	}

	/**
	 * Return list of sortable columns.
	 *
	 * @since  1.1.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array();
	}

	/**
	 * Render the contents of the "name" column.
	 *
	 * @since  1.1.0
	 * @param  object $item Item that is displayed, provided by the model.
	 * @return string The HTML code.
	 */
	public function column_name( $item ) {
		$html = sprintf(
			'<div>%1$s</div><div>%2$s</div>',
			esc_html( $item->name ),
			esc_html( $item->description )
		);

		return $html;
	}

	/**
	 * Do not display a Title above the list.
	 *
	 * @since  1.1.0
	 */
	public function list_head() {
	}

	/**
	 * Do not display a status-filter for this rule.
	 *
	 * @since  1.1.0
	 * @return array
	 */
	public function get_views() {
		return array();
	}

}
