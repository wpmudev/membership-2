<?php

// +----------------------------------------------------------------------+
// | Copyright Incsub (http://incsub.com/)                                |
// +----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License, version 2, as  |
// | published by the Free Software Foundation.                           |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to the Free Software          |
// | Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               |
// | MA 02110-1301 USA                                                    |
// +----------------------------------------------------------------------+

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Base class for all list tables.
 *
 * @category Membership
 * @package Table
 *
 * @since 3.5
 */
class Membership_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $args The array of arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array_merge( array(
			'search_box_label' => __( 'Search' ),
			'single'           => __( 'item' ),
			'plural'           => __( 'items' ),
			'ajax'             => false,
		), $args ) );
	}

	/**
	 * Displays table navigation section.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $which The section where table navigation will be displayed.
	 */
	public function display_tablenav( $which ) {
		echo '<div class="tablenav ', esc_attr( $which ), '">';
			echo '<div class="alignleft actions">';
				$this->bulk_actions( $which );
			echo '</div>';
			$this->extra_tablenav( $which );
			$this->pagination( $which );
			echo '<br class="clear">';
		echo '</div>';
	}

	/**
	 * Returns column value.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The table row to display.
	 * @param string $column_name The column id to render.
	 * @return string The value to display.
	 */
	public function column_default( $item, $column_name ) {
		$value = isset( $item[$column_name] ) ? $item[$column_name] : '';
		return is_numeric( $value ) ? number_format( $value ) : $value;
	}

	/**
	 * Returns checkbox column value.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param array $item The table row to display.
	 * @return string The value to display.
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" class="cb" name="%1$s[]" value="%2$s">', $this->_args['plural'], $item['id'] );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns()
		);
	}

	/**
	 * Displays the search box if it was enabled in table arguments.
	 *
	 * @since 3.5
	 *
	 * @access public
	 * @param string $text The search button text.
	 * @param string $input_id The search input id.
	 */
	public function search_box( $text, $input_id ) {
		if ( isset( $this->_args['search_box'] ) && $this->_args['search_box'] ) {
			parent::search_box( $text, $input_id );
		}
	}

	/**
	 * Auto escapes all values and displays the table.
	 *
	 * @since 3.5
	 *
	 * @access public
	 */
	public function display() {
		if ( is_array( $this->items ) ) {
			foreach ( $this->items as &$item ) {
				foreach ( $item as &$value ) {
					$value = esc_html( $value );
				}
			}
		}

		parent::display();
	}

	/**
	 * Returns associative array with the list of bulk actions available on this table.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 * @return array The associative array of bulk actions.
	 */
	public function get_bulk_actions() {
		return isset( $this->_args['actions'] )
			? $this->_args['actions']
			: array();
	}

}