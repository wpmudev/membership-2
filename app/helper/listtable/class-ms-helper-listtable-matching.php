<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Membership Matching-List Table
 *
 *
 * @since 1.0.4.2
 *
 */
class MS_Helper_ListTable_Matching extends MS_Helper_ListTable {

	/**
	 * Model that contains the list items of the table.
	 * @var MS_Model_Model
	 */
	protected $model;

	/**
	 * Associated membership model.
	 * @var MS_Model_Membership
	 */
	protected $membership;

	/**
	 * The current membership-ID
	 * @var int
	 */
	protected $membership_id = 0;

	/**
	 * List of matching options that are available for each list item.
	 * @var array
	 */
	protected $matching_options = array();

	/**
	 * Constructor.
	 *
	 * @since  1.0.4.2
	 *
	 * @param MS_Model $model Model for the list data.
	 * @param MS_Model_Membership $membership The associated membership.
	 */
	public function __construct( $model, $membership = null ) {
		parent::__construct(
			array(
				'singular'  => 'rule_' . $this->id,
				'plural'    => 'rules',
				'ajax'      => false,
			)
		);

		$this->model = $model;
		$this->membership = $membership;
	}

	/**
	 * Defines available columns.
	 * Generally this list will not change...
	 *
	 * @since  1.0.4.2
	 * @return array
	 */
	public function get_columns() {
		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
			array(
				'item' => $this->get_column_label( 'item' ),
				'match' => $this->get_column_label( 'match' ),
			)
		);
	}

	/**
	 * Allows child classes to easily override the column captions.
	 *
	 * @since  1.0.4.2
	 * @param  string $col
	 * @return string
	 */
	protected function get_column_label( $col ) {
		$label = '';

		switch ( $col ) {
			case 'item': $label = __( 'Item', MS_TEXT_DOMAIN ); break;
			case 'match': $label = __( 'Matching', MS_TEXT_DOMAIN ); break;
		}

		return $label;
	}

	/**
	 * Define which columns are included in the list that are not displayed.
	 * Usually this is an empty array.
	 *
	 * @since  1.0.4.2
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
	 * @since  1.0.4.2
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
	 * @since  1.0.4.2
	 */
	public function prepare_items() {
		$args = null;

		if ( ! empty( $_GET['status'] ) ) {
			$args['rule_status'] = $_GET['status'];
		}

		// Load the item-list (the rows in the table).
		$this->items = apply_filters(
			'ms_helper_listtable_' . $this->id . '_items',
			$this->model->get_contents( $args )
		);

		// Load the matching-list that is displayed for each item.
		$this->matching_options = apply_filters(
			'ms_helper_listtable_matching_' . $this->id . ' _matching',
			$this->model->get_matching_options( $args )
		);

		// Define the columns of the table.
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Renders the contents of any undefined column.
	 *
	 * @since  1.0.4.2
	 * @param  mixed $item
	 * @param  string $column_name
	 * @return string HTML code
	 */
	public function column_default( $item, $column_name ) {
		$html = print_r( $item, true );

		return $html;
	}

	/**
	 * Renders the contents of the ITEM colum.
	 *
	 * @since  1.0.4.2
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
	 * @since  1.0.4.2
	 * @param  mixed $item
	 * @param  string $column_name
	 * @return string HTML code
	 */
	public function column_match( $item ) {
		$list = array(
			'id' => 'ms-list-' . $item->id,
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => $item->value,
			'field_options' => $this->matching_options,
			'data_ms' => array(
				'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_MATCHING,
				'membership_id' => $this->get_membership_id(),
				'rule_type' => $item->type,
				'item' => $item->id,
			),
		);
		$html = MS_Helper_Html::html_element( $list, true );
		$html .= MS_Helper_Html::save_text( null, false, true );

		return $html;
	}

	/**
	 * Render the menu
	 *
	 * @since  1.0.4.2
	 */
	public function display() {
		$membership_id = array(
			'id' => 'membership_id',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $this->get_membership_id(),
		);
		MS_Helper_Html::html_element( $membership_id );

		parent::display();
	}

	/**
	 * Returns the membership-ID that is currently edited.
	 *
	 * @since  1.0.4.2
	 * @return int
	 */
	protected function get_membership_id() {
		if ( empty( $this->membership_id ) ) {
			if ( ! empty( $this->membership ) && $this->membership->is_valid() ) {
				$this->membership_id = $this->membership->id;
			}
			elseif ( ! empty( $_REQUEST['membership_id'] ) ) {
				$this->membership_id = $_REQUEST['membership_id'];
			}

			$this->membership_id = apply_filters(
				'ms_helper_listtable_rule_get_membership_id',
				$this->membership_id
			);
		}

		return $this->membership_id;
	}
}
