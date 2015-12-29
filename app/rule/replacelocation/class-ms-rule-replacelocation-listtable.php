<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Rule_ReplaceLocation_ListTable extends MS_Helper_ListTable_RuleMatching {

	protected $id = MS_Rule_ReplaceLocation::RULE_ID;

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param MS_Model $model Model for the list data.
	 * @param MS_Model_Membership $membership The associated membership.
	 */
	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'Menu Location', 'membership2' );
		$this->name['plural'] = __( 'Menu Locations', 'membership2' );

		add_filter(
			'ms_helper_listtable_' . $this->id . '_columns',
			array( $this, 'customize_columns' )
		);
                
                add_filter(
                    'bulk_actions-membership-2_page_membership2-protection',
                    array( $this, 'replace_location_disable_bulk_action' )
                );

		$this->editable = self::list_shows_base_items();
	}

	/**
	 * Add the Access-column to the list table
	 *
	 * @since  1.0.0
	 */
	public function customize_columns( $columns ) {
		$columns['access'] = true;
		return $columns;
	}
        
        public function replace_location_disable_bulk_action( $actions ) {
            if( isset( $_REQUEST['tab'] ) && $_REQUEST['tab'] == 'replace_location' ) {
                return '';
            }
            return $actions;
        }

	/**
	 * Override the column captions.
	 *
	 * @since  1.0.0
	 * @param  string $col
	 * @return string
	 */
	protected function get_column_label( $col ) {
		$label = '';

		switch ( $col ) {
			case 'item': $label = __( 'Menu Location', 'membership2' ); break;
			case 'match': $label = __( 'Show this menu to members', 'membership2' ); break;
		}

		return $label;
	}

	/**
	 * No pagination for this rule
	 *
	 * @since  1.0.0
	 * @return int
	 */
	protected function get_items_per_page( $option, $default_value = null ) {
		return 0;
	}

	/**
	 * This rule has no views
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_views() {
		return array();
	}

}