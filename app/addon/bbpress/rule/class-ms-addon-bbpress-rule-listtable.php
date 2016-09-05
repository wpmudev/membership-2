<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Addon_Bbpress_Rule_Listtable extends MS_Helper_ListTable_Rule {

	/**
	 * List-ID is only used to generate the list HTML code.
	 *
	 * @var string
	 */
	protected $id = 'rule_bbpress';

	/**
	 * Define available columns
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_columns() {
		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
			array(
				'cb' => true,
				'name' => __( 'Name', 'membership2' ),
				'access' => true,
			)
		);
	}

	/**
	 * Return list of sortable columns.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return array();
	}

	/**
	 * Render the contents of the "name" column.
	 *
	 * @since  1.0.0
	 * @param  object $item Item that is displayed, provided by the model.
	 * @return string The HTML code.
	 */
	public function column_name( $item ) {
		$actions = array(
			sprintf(
				'<a href="%s">%s</a>',
				get_edit_post_link( $item->id, true ),
				__( 'Edit', 'membership2' )
			),
			sprintf(
				'<a href="%s">%s</a>',
				get_permalink( $item->id ),
				__( 'View', 'membership2' )
			),
		);

		$actions = apply_filters(
			'membership_helper_listtable_' . $this->id . '_column_name_actions',
			$actions,
			$item
		);

		return sprintf(
			'%1$s %2$s',
			$item->post_title,
			$this->row_actions( $actions )
		);
	}

	/**
	 * Do not display a Title above the list.
	 *
	 * @since  1.0.0
	 */
	public function list_head() {
	}

	/**
	 * Do not display a status-filter for this rule.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_views() {
		return array();
	}

}
