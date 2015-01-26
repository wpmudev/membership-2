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
class MS_Rule_ReplaceLocation_ListTable extends MS_Helper_ListTable_RuleMatching {

	protected $id = MS_Rule_ReplaceLocation::RULE_ID;

	/**
	 * Constructor.
	 *
	 * @since  1.0.4.2
	 *
	 * @param MS_Model $model Model for the list data.
	 * @param MS_Model_Membership $membership The associated membership.
	 */
	public function __construct( $model, $membership ) {
		parent::__construct( $model, $membership );
		$this->name['singular'] = __( 'Menu Location', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Menu Locations', MS_TEXT_DOMAIN );

		add_filter(
			'ms_helper_listtable_' . $this->id . '_columns',
			array( $this, 'customize_columns' )
		);

		$this->editable = self::list_shows_base_items();
	}

	/**
	 * Add the Access-column to the list table
	 *
	 * @since  1.1.0
	 */
	public function customize_columns( $columns ) {
		$columns['access'] = true;
		return $columns;
	}

	/**
	 * Override the column captions.
	 *
	 * @since  1.0.4.2
	 * @param  string $col
	 * @return string
	 */
	protected function get_column_label( $col ) {
		$label = '';

		switch ( $col ) {
			case 'item': $label = __( 'Menu Location', MS_TEXT_DOMAIN ); break;
			case 'match': $label = __( 'Show this menu to members', MS_TEXT_DOMAIN ); break;
		}

		return $label;
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
	 * This rule has no views
	 *
	 * @since  1.1.0
	 * @return array
	 */
	public function get_views() {
		return array();
	}

}