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
class MS_Helper_ListTable_Rule_Url_Group extends MS_Helper_ListTable_Rule {

	protected $id = 'rule_url_group';

	public function __construct( $model, $membership = null ) {
		parent::__construct( $model, $membership );
		$this->name['singular'] = __( 'URL', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'URLs', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		return apply_filters(
			'membership_helper_ListTable_' . $this->id . '_columns',
			array(
				'cb' => true,
				'url' => __( 'Page URL', MS_TEXT_DOMAIN ),
				'access' => true,
			)
		);
	}

	public function column_url( $item ) {
		return $item->url;
	}

}