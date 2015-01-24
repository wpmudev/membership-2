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
 * Membership List Table
 *
 *
 * @since 4.0.0
 *
 */
class MS_Rule_Page_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = 'rule_page';

	public function __construct( $model, $membership = null ) {
		parent::__construct( $model, $membership );
		$this->name['singular'] = __( 'Page', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Pages', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		$columns = array(
			'cb' => true,
			'name' => __( 'Page title', MS_TEXT_DOMAIN ),
			'access' => true,
			'post_date' => __( 'Date', MS_TEXT_DOMAIN ),
			'dripped' => true,
		);

		return apply_filters(
			"ms_helper_listtable_{$this->id}_columns",
			$columns
		);
	}

	public function column_name( $item ) {
		$actions = array(
			sprintf(
				'<a href="%s">%s</a>',
				get_edit_post_link( $item->id, true ),
				__( 'Edit', MS_TEXT_DOMAIN )
			),
			sprintf(
				'<a href="%s">%s</a>',
				get_permalink( $item->id ),
				__( 'View', MS_TEXT_DOMAIN )
			),
		);

		$actions = apply_filters(
			"ms_helper_listtable_{$this->id}_column_name_actions",
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
		return $item->post_date;
	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param  string $which Either 'top' or 'bottom'
	 * @param  bool $echo Output or return the HTML code? Default is output.
	 */
	public function extra_tablenav( $which, $echo = true ) {
		if ( 'top' != $which ) {
			return '';
		}

		$filter_button = array(
			'id' => 'filter_button',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Filter', MS_TEXT_DOMAIN ),
			'button_type' => 'button',
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