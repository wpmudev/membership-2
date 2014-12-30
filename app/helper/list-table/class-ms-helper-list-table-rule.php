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
class MS_Helper_List_Table_Rule extends MS_Helper_List_Table {

	/**
	 * ID of the rule. This is overwritten by each rule!
	 *
	 * @var string
	 */
	protected $id = 'rule';

	/**
	 * The rule model
	 *
	 * @var MS_Model_Rule
	 */
	protected $model;

	/**
	 * The membership object linked to the rule
	 *
	 * @var MS_Model_Membership
	 */
	protected $membership;


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

	public function get_columns() {
		return apply_filters(
			"ms_helper_list_table_{$this->id}_columns",
			array(
				'cb' => '<input type="checkbox" />',
				'content' => __( 'Content', MS_TEXT_DOMAIN ),
				'rule_type' => __( 'Rule type', MS_TEXT_DOMAIN ),
				'dripped' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
			)
		);
	}

	public function get_hidden_columns() {
		return apply_filters(
			"ms_helper_list_table_{$this->id}_hidden_columns",
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			"ms_helper_list_table_{$this->id}_sortable_columns",
			array(
				'content' => 'content',
				'access' => 'access',
				'dripped' => 'dripped',
			)
		);
	}

	public function get_bulk_actions() {
		$bulk_actions = array(
			'give_access' => __( 'Give access', MS_TEXT_DOMAIN ),
			'no_access' => __( 'Remove access', MS_TEXT_DOMAIN ),
		);

		if ( $this->membership->is_visitor_membership() ) {
			$bulk_actions = array(
				'give_access' => __( 'Protect content', MS_TEXT_DOMAIN ),
				'no_access' => __( 'Remove protection', MS_TEXT_DOMAIN ),
			);
		}

		return apply_filters(
			"ms_helper_list_table_{$this->id}_bulk_actions",
			$bulk_actions
		);
	}

	public function prepare_items() {
		$args = null;

		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		if ( MS_Model_Membership::TYPE_DRIPPED != $this->membership->type ) {
			unset( $this->_column_headers[0]['dripped'] );
		}

		// Some columns have a pre-defined title that cannot be changed.
		if ( isset( $this->_column_headers[0]['cb'] ) ) {
			$this->_column_headers[0]['cb'] = '<input type="checkbox" />';
		}

		if ( isset( $this->_column_headers[0]['dripped'] ) ) {
			$this->_column_headers[0]['dripped'] = __( 'When to Reveal Content', MS_TEXT_DOMAIN );
		}

		if ( isset( $this->_column_headers[0]['access'] ) ) {
			if ( $this->membership->is_visitor_membership() ) {
				$this->_column_headers[0]['access'] = __( 'Content Protection', MS_TEXT_DOMAIN );
			} else {
				$this->_column_headers[0]['access'] = __( 'Members Access', MS_TEXT_DOMAIN );
			}
		}

		// Initialize current pagination Page
		$per_page = $this->get_items_per_page(
			"{$this->id}_per_page",
			self::DEFAULT_PAGE_SIZE
		);

		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'number' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		// Add a status filter
		if ( ! empty( $_GET['status'] ) ) {
			$args['rule_status'] = $_GET['status'];
		}

		// Search string.
		if ( ! empty( $_REQUEST['s'] ) ) {
			$this->search_string = $_REQUEST['s'];
			$args['s'] = $_REQUEST['s'];
			$args['posts_per_page'] = false;
			$args['number'] = false;
			$args['offset'] = 0;
		}

		// Month filter.
		if ( ! empty( $_REQUEST['m'] ) && strlen( $_REQUEST['m'] ) == 6 ) {
			$args['year'] = substr( $_REQUEST['m'], 0 , 4 );
			$args['monthnum'] = substr( $_REQUEST['m'], 5 , 2 );
		}

		// show all content instead of protected only for dripped
		if ( MS_Model_Membership::TYPE_DRIPPED == $this->membership->type ) {
			$args['show_all'] = 1;
		}

		// Flag if we want to get the protected or the accessible content.
		$args['get_base_list'] = $this->membership->is_visitor_membership();

		// Allow other helper list tables to customize the args array.
		$args = $this->prepare_items_args( $args );

		// Count items
		$total_items = $this->model->get_content_count( $args );

		// List available items
		$this->items = apply_filters(
			"ms_helper_list_table_{$this->id}_items",
			$this->model->get_contents( $args )
		);

		// Prepare the table pagination
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	/**
	 * Can be overwritten to customize the args array for prepare_items()
	 *
	 * @since  1.1.0
	 * @param  array $defaults
	 * @return array
	 */
	public function prepare_items_args( $defaults ) {
		return $defaults;
	}

	public function column_default( $item, $column_name ) {
		$html = print_r( $item, true );

		return $html;
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="item[]" value="%1$s" />',
			$item->id
		);
	}

	public function column_access( $item ) {
		$toggle = array(
			'id' => 'ms-toggle-' . $item->id,
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'value' => $item->access,
			'class' => '',
			'data_ms' => array(
				'action' => MS_Controller_Rule::AJAX_ACTION_TOGGLE_RULE,
				'membership_id' => $this->get_membership_id(),
				'rule' => $item->type,
				'item' => $item->id,
			),
		);
		$html = MS_Helper_Html::html_element( $toggle, true );

		return $html;
	}

	public function column_dripped( $item ) {
		$action = MS_Controller_Rule::AJAX_ACTION_UPDATE_DRIPPED;
		$nonce = wp_create_nonce( $action );
		$rule = $this->model;
		$membership = $this->membership;

		$period_from_reg = array(
			'period_unit' => $rule->get_dripped_value(
				MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION,
				$item->id,
				'period_unit'
			),
			'period_type' => $rule->get_dripped_value(
				MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION,
				$item->id,
				'period_type'
			),
		);

		$period_from_today = array(
			'period_unit' => $rule->get_dripped_value(
				MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION,
				$item->id,
				'period_unit'
			),
			'period_type' => $rule->get_dripped_value(
				MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION,
				$item->id,
				'period_type'
			),
		);

		$fields = array(
			'spec_date' => array(
				'id' => 'spec_date_' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $rule->get_dripped_value(
					MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE,
					$item->id,
					'spec_date'
				),
				'class' => 'ms-dripped-value ms-dripped-spec-date',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'dripped_type' => MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE,
					'id' => $item->id,
					'field' => 'spec_date',
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'period_unit_from_reg' => array(
				'id' => 'period_unit_' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $period_from_reg['period_unit'],
				'class' => 'ms-dripped-value ms-dripped-from-registration',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'dripped_type' => MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION,
					'field' => 'period_unit',
					'id' => $item->id,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'period_type_from_reg' => array(
				'id' => 'period_type_' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $period_from_reg['period_type'],
				'field_options' => MS_Helper_Period::get_periods(),
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'dripped_type' => MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION,
					'field' => 'period_type',
					'id' => $item->id,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'period_unit_from_today' => array(
				'id' => 'period_unit_' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $period_from_today['period_unit'],
				'class' => 'ms-dripped-value ms-dripped-from-registration',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'dripped_type' => MS_Model_Rule::DRIPPED_TYPE_FROM_TODAY,
					'field' => 'period_unit',
					'id' => $item->id,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'period_type_from_today' => array(
				'id' => 'period_type_' . $item->id,
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $period_from_today['period_type'],
				'field_options' => MS_Helper_Period::get_periods(),
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'dripped_type' => MS_Model_Rule::DRIPPED_TYPE_FROM_TODAY,
					'field' => 'period_type',
					'id' => $item->id,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'ok' => array(
				'id' => 'ok_' . $membership->id,
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Ok', MS_TEXT_DOMAIN ),
				'class' => 'ms-dripped-edit-ok',
			),
		);

		ob_start();
		?>
		<div class="ms-dripped-edit-wrapper <?php echo 'ms-dripped-type-' . MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE; ?>">
			<?php _e( 'on', MS_TEXT_DOMAIN ); ?><span class="ms-dripped-desc"></span>
			<?php MS_Helper_Html::html_element( $fields['spec_date'] ); ?>
			<span class="ms-dripped-calendar"></span>
		</div>
		<div class="ms-dripped-edit-wrapper ms-period-edit-wrapper ms-period-wrapper <?php echo 'ms-dripped-type-' . MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION; ?>">
			<div class="ms-period-desc-wrapper">
				<?php
				printf(
					__( '%1$s after registration', MS_TEXT_DOMAIN ),
					MS_Helper_Html::period_desc( $period_from_reg, 'ms-dripped-period' )
				);
				?>
				<span class="ms-dripped-pen"></span>
			</div>
			<div class="ms-period-editor-wrapper">
				<?php
				MS_Helper_Html::html_element( $fields['period_unit_from_reg'] );
				MS_Helper_Html::html_element( $fields['period_type_from_reg'] );
				MS_Helper_Html::html_element( $fields['ok'] );
				?>
			</div>
		</div>
		<div class="ms-dripped-edit-wrapper ms-period-edit-wrapper ms-period-wrapper <?php echo 'ms-dripped-type-' . MS_Model_Rule::DRIPPED_TYPE_FROM_TODAY; ?>">
			<div class="ms-period-desc-wrapper">
				<?php
				printf(
					__( 'in %1$s', MS_TEXT_DOMAIN ),
					MS_Helper_Html::period_desc( $period_from_today, 'ms-dripped-period' )
				);
				?>
				<span class="ms-dripped-pen"></span>
			</div>
			<div class="ms-period-editor-wrapper">
				<?php
				MS_Helper_Html::html_element( $fields['period_unit_from_today'] );
				MS_Helper_Html::html_element( $fields['period_type_from_today'] );
				MS_Helper_Html::html_element( $fields['ok'] );
				?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_helper_list_table_rule_column_dripped',
			$html
		);
	}

	public function display() {
		$membership_id = array(
			'id' => 'membership_id',
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'value' => $this->get_membership_id(),
		);
		MS_Helper_Html::html_element( $membership_id );

		parent::display();
	}

	protected function get_membership_id() {
		$membership_id = 0;

		if ( ! empty( $this->membership ) && $this->membership->is_valid() ) {
			$membership_id = $this->membership->id;
		} elseif ( ! empty( $_REQUEST['membership_id'] ) ) {
			$membership_id = $_REQUEST['membership_id'];
		}

		return apply_filters(
			'ms_helper_list_table_rule_get_membership_id',
			$membership_id
		);
	}

	public function get_views() {
		$count = $this->model->count_item_access();
		$has_access_desc = __( 'Has Access', MS_TEXT_DOMAIN );
		$no_access_desc = __( 'Access Restricted', MS_TEXT_DOMAIN );
		$has_access_status = MS_Model_Rule::FILTER_HAS_ACCESS;
		$no_access_status = MS_Model_Rule::FILTER_NO_ACCESS;

		if ( $this->membership->is_visitor_membership() ) {
			$has_access_desc = __( 'Protected content', MS_TEXT_DOMAIN );
			$no_access_desc = __( 'Not protected', MS_TEXT_DOMAIN );
			$has_access_status = MS_Model_Rule::FILTER_PROTECTED;
			$no_access_status = MS_Model_Rule::FILTER_NOT_PROTECTED;
		}

		$url = apply_filters(
			"ms_helper_list_table_{$this->id}_url",
			remove_query_arg( array( 'status', 'paged' ) )
		);

		$views = array(
			'all' => array(
				'url' => $url,
				'label' => __( 'All', MS_TEXT_DOMAIN ),
				'count' => $count['total'],
			),

			'has_access' => array(
				'url' => add_query_arg( array( 'status' => $has_access_status ), $url ),
				'label' => $has_access_desc,
				'count' => $count['accessible'],
			),

			'no_access' => array(
				'url' => add_query_arg( array( 'status' => $no_access_status ), $url ),
				'label' => $no_access_desc,
				'count' => $count['restricted'],
			),
		);

		return apply_filters(
			"ms_helper_list_table_{$this->id}_views",
			$views
		);
	}
}
