<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Rule_Special_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_Special::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'Special Page', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Special Pages', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		$columns = array(
			'cb' => true,
			'name' => __( 'Page title', MS_TEXT_DOMAIN ),
			'url' => __( 'Example', MS_TEXT_DOMAIN ),
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
		return $item->post_title;
	}

	public function column_url( $item ) {
		return $item->url;
	}

}