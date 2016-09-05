<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Addon_BuddyPress_Rule_ListTable extends MS_Helper_ListTable_Rule {

	/**
	 * List-ID is only used to generate the list HTML code.
	 *
	 * @var string
	 */
	protected $id = 'rule_buddypress';

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
				'name' => __( 'Type', 'membership2' ),
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
		$html = sprintf(
			'<div>%1$s</div><div>%2$s</div>',
			esc_html( $item->name ),
			esc_html( $item->description )
		);

		return $html;
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
