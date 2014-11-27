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
class MS_Helper_List_Table_Rule_Buddypress extends MS_Helper_List_Table_Rule {

	protected $id = 'rule_buddypress';

	/**
	 * Flag, if the list is the base list (protected content/TRUE) or a
	 * membership list (accessible content/FALSE)
	 *
	 * @since 1.0.4.4
	 *
	 * @var bool
	 */
	protected $base_list = false;

	/**
	 * Sets the base_list flag
	 *
	 * @since  1.0.4.4
	 * @param  bool $state
	 */
	public function is_base_list( $state ) {
		$this->base_list = (bool) $state;
	}

	public function get_columns() {
		if ( $this->base_list ) {
			$access_label = __( 'Protect Content', MS_TEXT_DOMAIN );
		} else {
			$access_label = __( 'Access', MS_TEXT_DOMAIN );
		}

		return apply_filters(
			"ms_helper_list_table_{$this->id}_columns",
			array(
				'cb'     => '<input type="checkbox" />',
				'name' => __( 'Type', MS_TEXT_DOMAIN ),
				'access' => $access_label,
			)
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			"ms_helper_list_table_{$this->id}_sortable_columns",
			array()
		);
	}

	public function column_name( $item ) {
		$html = sprintf(
			'<div>%1$s</div><div>%2$s</div>',
			$item->name,
			$item->description
		);

		return $html;
	}

	public function column_default( $item, $column_name ) {
		$html = print_r( $item, true );
		return $html;
	}

	public function get_views() {
		return apply_filters(
			"ms_helper_list_table_{$this->id}_views",
			array()
		);
	}

}
