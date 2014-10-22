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
class MS_Helper_List_Table_Membership extends MS_Helper_List_Table {

	protected $id = 'membership';

	public function __construct(){
		parent::__construct(
			array(
				'singular'  => 'membership',
				'plural'    => 'memberships',
				'ajax'      => false,
			)
		);
	}

	public function get_columns() {
		$columns = array(
				'name' => __( 'Membership Name', MS_TEXT_DOMAIN ),
				'type_description' => __( 'Type of Membership', MS_TEXT_DOMAIN ),
				'active' => __( 'Active', MS_TEXT_DOMAIN ),
				'members' => __( 'Members', MS_TEXT_DOMAIN ),
				'price' => __( 'Cost', MS_TEXT_DOMAIN ),
				'payment_structure' => __( 'Payment Structure', MS_TEXT_DOMAIN ),
				'shortcode' => __( 'Membership Shortcode', MS_TEXT_DOMAIN ),
		);

		return apply_filters( 'membership_helper_list_table_membership_columns', $columns );
	}

	public function get_hidden_columns() {
		return apply_filters( 'membership_helper_list_table_membership_hidden_columns', array() );
	}

	public function get_sortable_columns() {
		return apply_filters(
			'membership_helper_list_table_membership_sortable_columns',
			array(
				'name' => array( 'name', true ),
				'type_description' => array( 'type', true ),
				'active' => array( 'active', true ),
			)
		);
	}

	public function column_active( $item ) {
		$toggle = array(
				'id' => 'ms-toggle-' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'value' => $item->active,
				'class' => '',
				'data_ms' => array(
						'action' => MS_Controller_Membership::AJAX_ACTION_TOGGLE_MEMBERSHIP,
						'field' => 'active',
						'membership_id' => $item->id,
				),
		);
		$html = MS_Helper_Html::html_element( $toggle, true );

		return $html;
	}

	public function prepare_items() {
		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );

		$args = array();

		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}
		/**
		 * Prepare order by statement.
		 */
		if ( ! empty( $args['orderby'] ) ) {
			if ( property_exists( 'MS_Model_Membership', $args['orderby'] ) ) {
				$args['meta_key'] = $args['orderby'];
				$args['orderby'] = 'meta_value';
			}
		}

		$this->items = apply_filters(
			'membership_helper_list_table_membership_items',
			MS_Model_Membership::get_grouped_memberships( $args )
		);
	}

	public function column_name( $item ) {
		$actions = array();

		if ( ! $item->has_parent() ) {
			$actions['overview'] = sprintf(
				'<a href="?page=%s&step=%s&membership_id=%s">%s</a>',
				$_REQUEST['page'],//XSS!!!!
				MS_Controller_Membership::STEP_OVERVIEW,
				$item->id,
				__( 'Overview', MS_TEXT_DOMAIN )
			);
		} else {
			$actions['content'] = sprintf(
				'<a href="?page=%1$s&step=%2$s&membership_id=%3$s&tab=page&edit=1">%4$s</a>',
				$_REQUEST['page'],//XSS!!!!
				MS_Controller_Membership::STEP_ACCESSIBLE_CONTENT,
				$item->id,
				__( 'Edit Content', MS_TEXT_DOMAIN )
			);
		}

		$actions['payment'] = sprintf(
			'<a href="?page=%1$s&step=%2$s&membership_id=%3$s&tab=page&edit=1">%4$s</a>',
			$_REQUEST['page'],//XSS!!!!
			MS_Controller_Membership::STEP_SETUP_PAYMENT,
			$item->id,
			__( 'Payment options', MS_TEXT_DOMAIN )
		);

		$actions['delete'] = sprintf(
			'<span class="delete"><a href="%s">%s</a></span>',
			wp_nonce_url(
				sprintf(
					'?page=%s&membership_id=%s&action=%s',
					$_REQUEST['page'],//XSS!!!!
					$item->id,
					'delete'
					),
				'delete'
				),
			__( 'Delete', MS_TEXT_DOMAIN )
		);

		$actions = apply_filters( "ms_helper_list_table_{$this->id}_column_name_actions", $actions, $item );
		return sprintf( '%1$s %2$s', $item->name, $this->row_actions( $actions ) );

	}

	public function column_default( $item, $column_name ) {
		$html = '';
		switch ( $column_name ) {
			case 'members':
				$html = $item->get_members_count();
				break;

			case 'type_description':
				if ( ! $item->parent_id ) {
					$html .= sprintf(
						'<span class="ms-img-type-%1$s small"></span> ',
						esc_attr( $item->type )
					);
				}
				$html .= sprintf(
					'<span class="ms-type-desc ms-%1$s"><span>%2$s<span></span>',
					esc_attr( $item->type ),
					esc_html( $item->type_description )
				);
				if ( $item->private ) {
					$html .= sprintf(
						'<span class="ms-is-private">, %s</span>',
						__( 'Private', MS_TEXT_DOMAIN )
					);
				}
				break;

			case 'payment_structure':
				if ( $item->has_payment() ) {
					$class = 'ms-bold';
				} else {
					$class = 'ms-low';
				}
				$html = sprintf(
					'<span class="%1$s">%2$s</span>',
					$class,
					$item->get_payment_type_desc()
				);
				break;

			case 'price':
				if ( $item->can_have_children() ) {
					$html = sprintf(
						'<span class="ms-low">%1$s</span>',
						__( 'Varied', MS_TEXT_DOMAIN )
					);
				}
				elseif ( $item->price > 0 ) {
					$html = sprintf(
						__( '<span class="ms-bold"><span class="ms-currency">%1$s</span> <span class="ms-price">%2$s</span></span>', MS_TEXT_DOMAIN ),
						MS_Plugin::instance()->settings->currency_symbol,
						number_format_i18n( $item->price, 2 )
					);
				}
				else {
					$html = sprintf(
						'<span class="ms-low">%1$s</span>',
						__( 'Free', MS_TEXT_DOMAIN )
					);
				}
				break;

			case 'shortcode':
				$html = '<code>[' . MS_Model_Rule_Shortcode::PROTECT_CONTENT_SHORTCODE . ' id="' . $item->id . '"]</code>';
				break;

			default:
				$html = $item->$column_name;
				break;
		}
		return $html;
	}

	public function get_bulk_actions() {
		return apply_filters( 'ms_helper_list_table_membership_bulk_actions', array() );
	}

	/**
	 * Generates content for a single row of the table
	 *
	 * @since 1.0
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		static $row_class = '';
		$class = '';

		// Only alternate the background color on top-level (children have same background as the parent).
		if ( $item->parent_id == 0 ) {
			$row_class = ( $row_class == '' ? 'alternate' : '' );
		} else {
			$class = 'ms-child-row';
		}

		?>
		<tr class="<?php echo esc_attr( $row_class . ' ' . $class ); ?>">
			<?php $this->single_row_columns( $item ); ?>
		</tr>
		<?php
	}
}
