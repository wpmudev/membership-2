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
 * @since 1.0.0
 */
class MS_Helper_ListTable_Event extends MS_Helper_ListTable {

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
			'user_id' => __( 'Member', MS_TEXT_DOMAIN ),
			'membership_id' => __( 'Membership', MS_TEXT_DOMAIN ),
			'description' => __( 'Event', MS_TEXT_DOMAIN ),
		);

		if ( isset( $_REQUEST['membership_id'] ) ) {
			unset( $columns['membership_id'] );
		}

		return apply_filters(
			'membership_helper_listtable_event_columns',
			$columns
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'membership_helper_listtable_event_sortable_columns',
			array(
				'post_modified' => array( 'post_modified', false ),
				'user_id' => array( 'user_id', false ),
				'membership_id' => array( 'membership_id', false ),
			)
		);
	}

	/**
	 * Prepare list items.
	 *
	 * @since 1.1.0
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

		$per_page = apply_filters(
			'ms_helper_listtable_member_items_per_page',
			self::DEFAULT_PAGE_SIZE
		);
		$current_page = $this->get_pagenum();

		$args = array(
			'number' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		if ( isset( $_REQUEST['membership_id'] ) ) {
			$args['membership_id'] = $_REQUEST['membership_id'];
		}

		$total_items = MS_Model_Event::get_event_count( $args );
		$this->items = MS_Model_Event::get_events( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);

		do_action(
			'ms_helper_listtable_event_prepare_items',
			$args,
			$this
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

	public function column_post_modified( $item, $column_name ) {
		$html = MS_Helper_Period::format_date( $item->post_modified );

		return $html;
	}

	public function column_description( $item, $column_name ) {
		$html = $item->description;

		return $html;
	}

}
