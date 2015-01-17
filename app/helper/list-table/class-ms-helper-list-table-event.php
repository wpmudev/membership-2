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
class MS_Helper_List_Table_Event extends MS_Helper_List_Table {

	protected $id = 'event';

	public function __construct(){
		parent::__construct(
			array(
				'singular'  => 'event',
				'plural'    => 'events',
				'ajax'      => false,
			)
		);
	}

	public function get_columns() {
		$columns = array(
			'post_modified' => __( 'Date', MS_TEXT_DOMAIN ),
			'user_id' => __( 'User', MS_TEXT_DOMAIN ),
			'membership_id' => __( 'Membership', MS_TEXT_DOMAIN ),
			'description' => __( 'Event', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			'membership_helper_list_table_event_columns',
			$columns
		);
	}

	public function get_hidden_columns() {
		return apply_filters(
			'membership_helper_list_table_event_hidden_columns',
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'membership_helper_list_table_event_sortable_columns',
			array(
				'post_modified' => array( 'post_modified', false ),
				'user_id' => array( 'user_id', false ),
				'membership_id' => array( 'membership_id', false ),
			)
		);
	}

	public function column_user_id( $item, $column_name ) {
		$member = MS_Factory::load(
			'MS_Model_Member',
			$item->user_id
		);
		$html = $member->username;

		return $html;
	}

	public function column_membership_id( $item, $column_name ) {
		$membership = MS_Factory::load(
			'MS_Model_Membership',
			$item->membership_id
		);
		$html = $membership->name;

		return $html;
	}

	public function column_default( $item, $column_name ) {
		return $item->$column_name;
	}

	public function get_bulk_actions() {
		return apply_filters(
			'ms_helper_list_table_membership_bulk_actions',
			array()
		);
	}
}
