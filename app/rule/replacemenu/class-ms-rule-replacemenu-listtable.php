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
class MS_Rule_ReplaceMenu_ListTable extends MS_Helper_ListTable_Matching {

	protected $id = MS_Model_Rule::RULE_TYPE_REPLACE_MENUS;

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
		$this->name['singular'] = __( 'Menu', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Menus', MS_TEXT_DOMAIN );
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
			case 'item': $label = __( 'Menu', MS_TEXT_DOMAIN ); break;
			case 'match': $label = __( 'Show this menu to members instead', MS_TEXT_DOMAIN ); break;
		}

		return $label;
	}

}