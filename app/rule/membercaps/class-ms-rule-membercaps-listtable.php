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
 * @since 1.1
 *
 */
class MS_Rule_MemberCaps_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = 'rule_membercaps';

	public function __construct( $model, $membership = null ) {
		parent::__construct( $model, $membership );
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
			$this->name['singular'] = __( 'Capability', MS_TEXT_DOMAIN );
			$this->name['plural'] = __( 'Capabilities', MS_TEXT_DOMAIN );
		} else {
			$this->name['singular'] = __( 'Role', MS_TEXT_DOMAIN );
			$this->name['plural'] = __( 'Roles', MS_TEXT_DOMAIN );
		}
	}

	public function get_columns() {
		if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
			$name_label = __( 'Capability', MS_TEXT_DOMAIN );
		} else {
			$name_label = __( 'Role', MS_TEXT_DOMAIN );
		}

		$columns = array(
			'cb' => true,
			'name' => $name_label,
			'access' => true,
		);

		return apply_filters(
			"ms_helper_ListTable_{$this->id}_columns",
			$columns
		);
	}

	public function column_name( $item ) {
		return $item->post_title;
	}

}