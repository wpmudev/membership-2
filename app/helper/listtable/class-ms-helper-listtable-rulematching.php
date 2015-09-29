<?php
/**
 * Membership Matching-List Table
 *
 * @since  1.0.0
 */
class MS_Helper_ListTable_RuleMatching extends MS_Helper_ListTable_Rule {

	/**
	 * List of matching options that are available for each list item.
	 *
	 * @var array
	 */
	protected $matching_options = array();

	/**
	 * True means that the matching can be changed.
	 * False will display the matching details in read-only mode
	 *
	 * @var bool
	 */
	protected $editable = true;

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
	}

	/**
	 * Defines available columns.
	 * Generally this list will not change...
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'item' => $this->get_column_label( 'item' ),
			'match' => $this->get_column_label( 'match' ),
		);

		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
			$columns
		);
	}

	/**
	 * Allows child classes to easily override the column captions.
	 *
	 * @since  1.0.0
	 * @param  string $col
	 * @return string
	 */
	protected function get_column_label( $col ) {
		$label = '';

		switch ( $col ) {
			case 'item': $label = __( 'Item', 'membership2' ); break;
			case 'match': $label = __( 'Matching', 'membership2' ); break;
		}

		return $label;
	}

	/**
	 * Define which columns are included in the list that are not displayed.
	 * Usually this is an empty array.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_hidden_columns() {
		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_hidden_columns',
			array()
		);
	}

	/**
	 * Define which columns can be sorted.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_sortable_columns() {
		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_sortable_columns',
			array()
		);
	}

	/**
	 * Prepare the table contents so they can be displayed later.
	 *
	 * @since  1.0.0
	 */
	public function prepare_items() {
		parent::prepare_items();

		// Load the matching-list that is displayed for each item.
		$this->matching_options = apply_filters(
			'ms_helper_listtable_matching_' . $this->id . ' _matching',
			$this->model->get_matching_options()
		);
	}

	/**
	 * Renders the contents of the ITEM colum.
	 *
	 * @since  1.0.0
	 * @param  mixed $item
	 * @param  string $column_name
	 * @return string HTML code
	 */
	public function column_item( $item ) {
		$html = $item->title;
		return $html;
	}

	/**
	 * Renders the contents of the MATCH/REPLACE column.
	 *
	 * @since  1.0.0
	 * @param  mixed $item
	 * @param  string $column_name
	 * @return string HTML code
	 */
	public function column_match( $item ) {
		if ( $this->editable ) {
			$list = array(
				'id' => 'ms-list-' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $item->value,
				'field_options' => $this->matching_options,
				'ajax_data' => array(
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_MATCHING,
					'rule_type' => $item->type,
					'item' => $item->id,
				),
			);
			$html = MS_Helper_Html::html_element( $list, true );
		} else {
			if ( isset( $this->matching_options[$item->value] ) ) {
				$html = esc_html( $this->matching_options[$item->value] );
			} else {
				$html = '-';
			}
		}

		return $html;
	}

}
