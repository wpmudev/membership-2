<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Rule_MemberCaps_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_MemberCaps::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'Capability', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Capabilities', MS_TEXT_DOMAIN );
		$this->name['default_access'] = __( 'Default WordPress Logic', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		$name_label = __( 'Capability', MS_TEXT_DOMAIN );

		$columns = array(
			'cb' => true,
			'name' => $name_label,
			'access' => true,
		);

		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
			$columns
		);
	}

	public function column_name( $item ) {
		return $item->post_title;
	}

}