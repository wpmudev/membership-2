<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Rule_Content_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_Content::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'Content Part', 'membership2' );
		$this->name['plural'] = __( 'Content Parts', 'membership2' );
	}

	public function get_columns() {
		$columns = array(
			'cb' => true,
			'name' => __( 'Content Part', 'membership2' ),
			'access' => true,
		);

		if ( MS_Model_Membership::TYPE_DRIPPED !== $this->membership->type ) {
			unset( $columns['dripped'] );
		}

		return apply_filters(
			"ms_helper_listtable_{$this->id}_columns",
			$columns
		);
	}

	public function column_name( $item ) {
		return $item->name;
	}

	/**
	 * This rule has only one view.
	 *
	 * @since  1.0.0
	 */
	public function get_views() {
		return array();
	}

}