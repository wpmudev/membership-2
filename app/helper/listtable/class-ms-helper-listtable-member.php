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
 * Members List Table.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage Helper
 */
class MS_Helper_ListTable_Member extends MS_Helper_ListTable {

	/**
	 * A list of all memberships is generated in __construct() for performance.
	 *
	 * @var array
	 */
	static $memberships = null;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct(){
		parent::__construct(
			array(
				'singular'  => 'member',
				'plural'    => 'members',
				'ajax'      => false,
			)
		);

		add_action(
			'ms_helper_listtable_searchbox_start',
			array( $this, 'searchbox_filters' )
		);

		$memberships = MS_Model_Membership::get_memberships(
			array( 'include_guest' => 0 )
		);
		self::$memberships = array();

		foreach ( $memberships as $item ) {
			self::$memberships[$item->id] = (object) array(
				'label' => $item->name,
				'attr' => sprintf( 'data-color="%1$s"', $item->get_color() ),
			);
		}
	}

	/**
	 * Get list table columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *		Returns array of $id => $title.
	 *
	 *		@type string $id The list table column id.
	 *		@type string $title The list table column title.
	 * }
	 */
	public function get_columns() {
		$columns = array(
			'cb' => '<input type="checkbox" />',
			'username' => __( 'Username', MS_TEXT_DOMAIN ),
			'email' => __( 'E-mail', MS_TEXT_DOMAIN ),
			'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
			'infos' => '&nbsp;',
		);

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) {
			unset( $columns['trial'] );
		}

		return apply_filters(
			'ms_helper_listtable_member_get_columns',
			$columns
		);
	}

	/**
	 * Get list table sortable columns.
	 *
	 * @since 1.0.0
	 *
	 * @return array {
	 *		Returns array of $id => $title.
	 *
	 *		@type string $id The list table column id.
	 *		@type array $orderby The field id to use order.
	 * }
	 */
	public function get_sortable_columns() {
		return apply_filters(
			'ms_helper_listtable_member_get_sortable_columns',
			array(
				'username' => 'login',
				'email' => 'email',
			)
		);
	}

	/**
	 * Prepare list items.
	 *
	 * @since 1.0.0
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);

		$per_page = apply_filters(
			'ms_helper_listtable_member_items_per_page',
			10 //self::DEFAULT_PAGE_SIZE
		);
		$current_page = $this->get_pagenum();

		$args = array(
			'number' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		// Prepare the filter details.
		$args = $this->prepare_query_args( $args );

		$total_items = MS_Model_Member::get_members_count( $args );
		$this->items = MS_Model_Member::get_members( $args );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);

		do_action(
			'ms_helper_listtable_member_prepare_items',
			$args,
			$this
		);
	}

	/**
	 * Returns a query arg structure tailored to give the defined results
	 *
	 * @since  1.0.4.5
	 * @return array Query args
	 */
	protected function prepare_query_args( $args ) {
		WDev()->array->equip_request(
			's',
			'membership_id',
			'search_options',
			'status'
		);

		// Prepare order by statement.
		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}

		// Filter by search-term
		$search_filter = $_REQUEST['s'];
		if ( ! empty( $search_filter ) ) {
			$this->search_string = $search_filter;
			$search_option = $_REQUEST['search_options'];

			switch ( $search_option ) {
				case 'email':
				case 'username':
					$args['search'] = sprintf( '*%s*', $search_filter );
					break;

				default:
					$args['meta_query'][ $option ] = array(
						'key' => $option,
						'value' => $search_filter,
						'compare' => 'LIKE',
					);
					break;
			}
		}

		// Filter by membership_id and membership status
		$membership_id = $_REQUEST['membership_id'];
		$members = array();
		$filter = array();

		if ( ! empty( $membership_id ) ) {
			$filter['membership_id'] = $membership_id;
		}

		if ( ! empty( $status ) ) {
			$filter['status'] = $status;
		}

		if ( ! empty( $filter ) ) {
			$subscriptions = MS_Model_Relationship::get_subscriptions(
				$filter
			);

			foreach ( $subscriptions as $ms_relationship ) {
				$members[ $ms_relationship->user_id ] = $ms_relationship->user_id;
			}

			// Workaround to invalidate query
			if ( empty( $members ) ) {
				$members[0] = 0;
			}

			$args['include'] = $members;
		}

		return $args;
	}

	/**
	 * Display checkbox column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item The table item to display.
	 */
	public function column_cb( $item ) {
		$html = sprintf(
			'<input type="checkbox" name="member_id[]" value="%s" />',
			esc_attr( $item->id )
		);

		return $html;
	}

	/**
	 * Infos-Column
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item The table item to display.
	 */
	public function column_infos( $item ) {
		$dialog_data = array(
			'member_id' => $item->id,
		);

		$html = sprintf(
			'<a href="#" data-ms-dialog="View_Member_Dialog" data-ms-data="%s"><i class="dashicons dashicons-id"></i></a>',
			esc_attr( json_encode( $dialog_data ) )
		);

		return $html;
	}

	/**
	 * Display Username column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item The table item to display.
	 */
	public function column_username( $item ) {
		$actions = array();
		$actions['edit'] = sprintf(
			'<a href="user-edit.php?user_id=%s">%s</a>',
			esc_attr( $item->id ),
			__( 'Edit', MS_TEXT_DOMAIN )
		);

		$html = sprintf(
			'%1$s %2$s',
			$item->username,
			$this->row_actions( $actions )
		);

		return $html;
	}

	/**
	 * Display Email column.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $item The table item to display.
	 */
	public function column_email( $item ) {
		$html = $item->email;
		return $html;
	}

	/**
	 * Create membership column.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Member $member The member object.
	 */
	public function column_membership( $member ) {
		if ( MS_Model_Member::is_admin_user( $member->id ) ) {
			$html = '<b>' . __( 'Admin User', MS_TEXT_DOMAIN ) . '</b>';
		} else {
			$subscriptions = $member->get_membership_ids();

			$visitor = array(
				'id' => 'ms-empty-' . $member->id,
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => __( '(Visitor)' ),
				'after' => 'Edit',
				'class' => 'ms-empty-note',
			);

			$list = array(
				'id' => 'ms-memberships-' . $member->id,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $subscriptions,
				'field_options' => self::$memberships,
				'multiple' => true,
				'class' => 'ms-memberships',
				'ajax_data' => array(
					'action' => MS_Controller_Member::AJAX_ACTION_CHANGE_MEMBERSHIPS,
					'member' => $member->id,
				),
			);

			$html = sprintf(
				'<div class="no-auto-init">%1$s%2$s</div>',
				MS_Helper_Html::html_element( $visitor, true ),
				MS_Helper_Html::html_element( $list, true )
			);
		}

		return apply_filters(
			'ms_helper_listtable_member_column_membership',
			$html,
			$member,
			$this
		);
	}

	/**
	 * Adds a class to the <tr> element
	 *
	 * @since  1.1.0
	 * @param  object $item
	 */
	protected function single_row_class( $member ) {
		$subscriptions = $member->get_membership_ids();
		$class = empty( $subscriptions ) ? 'ms-empty' : 'ms-assigned';

		return $class;
	}

	/**
	 * Bulk actions options.
	 *
	 * @since 1.0.0
	 *
	 * @param array {
	 *     @type string $action The action name.
	 *     @type mixed $desciption The action description.
	 * }
	 */
	public function get_bulk_actions() {
		$actions = array(
			'toggle_activation' => __( 'Toggle Activation', MS_TEXT_DOMAIN ),
			'Memberships' => array(
				'add'     => __( 'Add membership', MS_TEXT_DOMAIN ),
				'cancel'  => __( 'Cancel membership', MS_TEXT_DOMAIN ),
				'move'    => __( 'Move membership', MS_TEXT_DOMAIN ),
				'drop'    => __( 'Drop membership', MS_TEXT_DOMAIN ),
			),
		);

		return apply_filters(
			'ms_helper_listtable_member_get_bulk_actions',
			$actions,
			$this
		);
	}

	/**
	 * Add custom filters to the searchbox
	 *
	 * @since 1.1.0
	 */
	public function searchbox_filters() {
		WDev()->array->equip_request( 'search_options' );

		$search_options = array(
			'id' => 'search_options',
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => $_REQUEST['search_options'],
			'field_options' => array(
				'username'   => __( 'Username / E-mail', MS_TEXT_DOMAIN ),
				'nickname'   => __( 'Nickname', MS_TEXT_DOMAIN ),
				'first_name' => __( 'First Name', MS_TEXT_DOMAIN ),
				'last_name'  => __( 'Last Name', MS_TEXT_DOMAIN ),
			),
		);

		// Display the extra search options
		MS_Helper_Html::html_element( $search_options );
	}

	/**
	 * This list has no views.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_views() {
		return array();
	}
}
