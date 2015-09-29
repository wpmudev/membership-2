<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
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
			'post_modified' => __( 'Date', 'membership2' ),
			'user_id' => __( 'Member', 'membership2' ),
			'membership_id' => __( 'Membership', 'membership2' ),
			'description' => __( 'Event', 'membership2' ),
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
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		if ( isset( $_REQUEST['membership_id'] ) ) {
			$args['membership_id'] = $_REQUEST['membership_id'];
		}

		if ( ! empty( $_REQUEST['s'] ) ) {
			$args['s'] = $_REQUEST['s'];
			$this->search_string = $args['s'];
			$args['posts_per_page'] = -1;
			$args['number'] = false;
			$args['offset'] = 0;
		}

		// Prepare order by statement.
		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
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
