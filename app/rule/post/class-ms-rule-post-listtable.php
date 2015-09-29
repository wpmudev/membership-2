<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Rule_Post_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_Post::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'Post', 'membership2' );
		$this->name['plural'] = __( 'Posts', 'membership2' );
	}

	public function get_columns() {
		$columns = array(
			'cb' => true,
			'name' => __( 'Post title', 'membership2' ),
			'access' => true,
			'post_date' => __( 'Post date', 'membership2' ),
			'dripped' => true,
		);

		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
			$columns
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'membership_helper_listtable_' . $this->id . '_sortable_columns',
			array(
				'name' => array( 'name', false ),
				'dripped' => array( 'dripped', false ),
			)
		);
	}

	public function column_name( $item ) {
		$actions = array(
			sprintf(
				'<a href="%s" target="_blank">%s</a>',
				get_edit_post_link( $item->id, true ),
				__( 'Edit', 'membership2' )
			),
			sprintf(
				'<a href="%s" target="_blank">%s</a>',
				get_permalink( $item->id ),
				__( 'View', 'membership2' )
			),
		);
		$actions = apply_filters(
			'ms_rule_' . $this->id . '_column_actions',
			$actions,
			$item
		);

		return sprintf(
			'%1$s %2$s',
			$item->post_title,
			$this->row_actions( $actions )
		);
	}

	public function column_post_date( $item, $column_name ) {
		return MS_Helper_Period::format_date( $item->post_date );
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param  string $which Either 'top' or 'bottom'
	 * @param  bool $echo Output or return the HTML code? Default is output.
	 */
	public function extra_tablenav( $which, $echo = true ) {
		if ( 'top' != $which ) {
			return;
		}

		$filter_button = array(
			'id' => 'filter_button',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Filter', 'membership2' ),
			'class' => 'button',
		);

		if ( ! $echo ) { ob_start(); }
		?>
		<div class="alignleft actions">
			<?php
			$this->months_dropdown( 'page' );
			MS_Helper_Html::html_element( $filter_button );
			?>
		</div>
		<?php
		if ( ! $echo ) { return ob_get_clean(); }
	}
}
