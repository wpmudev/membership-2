<?php
/**
 * Members List Table.
 *
 * @since  1.0.0
 *
 * @package Membership2
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @return array Query args
	 */
	protected function prepare_query_args( $args ) {
		lib2()->array->equip_request(
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
					$args['meta_query'][ $search_option ] = array(
						'key' => $search_option,
						'value' => $search_filter,
						'compare' => 'LIKE',
					);
					break;
			}

			$args['posts_per_page'] = -1;
			$args['number'] = false;
			$args['offset'] = 0;
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

			foreach ( $subscriptions as $subscription ) {
				$members[ $subscription->user_id ] = $subscription->user_id;
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
	 * @since  1.0.0
	 *
	 * @param mixed $member The table item to display.
	 */
	public function column_cb( $member ) {
		if ( MS_Model_Member::is_admin_user( $member->id ) ) {
			$html = '';
		} else {
			$html = sprintf(
				'<input type="checkbox" name="member_id[]" value="%s" />',
				esc_attr( $member->id )
			);
		}

		return $html;
	}

	/**
	 * Infos-Column
	 *
	 * @since  1.0.0
	 *
	 * @param mixed $member The table item to display.
	 */
	public function column_infos( $member ) {
		$dialog_data = array(
			'member_id' => $member->id,
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
	 * @since  1.0.0
	 *
	 * @param mixed $member The table item to display.
	 */
	public function column_username( $member ) {
		$actions = array();
		$actions['edit'] = sprintf(
			'<a href="%s">%s</a>',
			MS_Controller_Plugin::get_admin_url(
				'add-member',
				array( 'user_id' => $member->id )
			),
			__( 'Subscription Details', MS_TEXT_DOMAIN )
		);
		$actions['profile'] = sprintf(
			'<a href="%s">%s</a>',
			admin_url( 'user-edit.php?user_id=' . $member->id ),
			__( 'Edit Profile', MS_TEXT_DOMAIN )
		);

		$html = sprintf(
			'%1$s %2$s',
			$member->username,
			$this->row_actions( $actions )
		);

		return $html;
	}

	/**
	 * Display Email column.
	 *
	 * @since  1.0.0
	 *
	 * @param mixed $member The table item to display.
	 */
	public function column_email( $member ) {
		$html = $member->email;
		return $html;
	}

	/**
	 * Create membership column.
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 * @param  object $member
	 */
	protected function single_row_class( $member ) {
		$subscriptions = $member->get_membership_ids();
		$class = empty( $subscriptions ) ? 'ms-empty' : 'ms-assigned';

		return $class;
	}

	/**
	 * Bulk actions options.
	 *
	 * @since  1.0.0
	 *
	 * @param array {
	 *     @type string $action The action name.
	 *     @type mixed $desciption The action description.
	 * }
	 */
	public function get_bulk_actions() {
		$protect_key = __( 'Add Membership', MS_TEXT_DOMAIN );
		$unprotect_key = __( 'Drop Membership', MS_TEXT_DOMAIN );
		$bulk_actions = array(
			'drop-all' => __( 'Drop all Memberships', MS_TEXT_DOMAIN ),
			$protect_key => array(),
			$unprotect_key => array(),
		);

		$args = array(
			'include_guest' => 0,
		);
		$memberships = MS_Model_Membership::get_membership_names( $args );
		$txt_add = __( 'Add: %s', MS_TEXT_DOMAIN );
		$txt_rem = __( 'Drop: %s', MS_TEXT_DOMAIN );
		foreach ( $memberships as $id => $name ) {
			$bulk_actions[$protect_key]['add-' . $id] = sprintf( $txt_add, $name );
			$bulk_actions[$unprotect_key]['drop-' . $id] = sprintf( $txt_rem, $name );
		}

		return apply_filters(
			'ms_helper_listtable_member_get_bulk_actions',
			$bulk_actions,
			$this
		);
	}

	/**
	 * Add custom filters to the searchbox
	 *
	 * @since  1.0.0
	 */
	public function searchbox_filters() {
		lib2()->array->equip_request( 'search_options' );

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
	 * @since  1.0.0
	 *
	 * @return array
	 */
	public function get_views() {
		return array();
	}
}
