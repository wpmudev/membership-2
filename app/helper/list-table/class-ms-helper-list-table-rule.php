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

	protected $id = 'rule';

	protected $model;

	protected $membership;

	public function __construct( $model, $membership = null ) {
		parent::__construct( array(
			'singular'  => "rule_$this->id",
			'plural'    => "rules",
			'ajax'      => false
		) );

		$this->model = $model;
		$this->membership = $membership;
	}

	public function get_columns() {
		return apply_filters( "ms_helper_list_table_{$this->id}_columns", array(
			'cb'     => '<input type="checkbox" />',
			'content' => __( 'Content', MS_TEXT_DOMAIN ),
			'rule_type' => __( 'Rule type', MS_TEXT_DOMAIN ),
			'dripped' => __( 'Dripped Content', MS_TEXT_DOMAIN ),
		) );
	}

	public function get_hidden_columns() {
		return apply_filters( "ms_helper_list_table_{$this->id}_hidden_columns", array() );
	}

	public function get_sortable_columns() {
		return apply_filters( "ms_helper_list_table_{$this->id}_sortable_columns", array(
				'content' => 'content',
				'access' => 'access',
				'dripped' => 'dripped',
		) );
	}

	public function get_bulk_actions() {
		$bulk_actions = array(
				'give_access' => __( 'Give access', MS_TEXT_DOMAIN ),
				'no_access' => __( 'Remove access', MS_TEXT_DOMAIN ),
		);
		if( $this->membership->protected_content ) {
			$bulk_actions = array(
					'give_access' => __( 'Protect content', MS_TEXT_DOMAIN ),
					'no_access' => __( 'Remove protection', MS_TEXT_DOMAIN ),
			);
		}

		return apply_filters( "ms_helper_list_table_{$this->id}_bulk_actions", $bulk_actions );
	}

	public function prepare_items() {

		$args = null;
		if( ! empty( $_GET['status'] ) ) {
			$args['rule_status'] = $_GET['status'];
		}

		$this->items = apply_filters( "ms_helper_list_table_{$this->id}_items", $this->model->get_contents( $args ) );

		$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );
	}

	public function column_default( $item, $column_name ) {
		$html = '';
		switch( $column_name ) {
			default:
				$html = print_r( $item, true ) ;
				break;
		}
		return $html;
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="item[]" value="%1$s" />', $item->id );
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
				'period_unit' => $rule->get_dripped_value( MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION, $item->id, 'period_unit' ),
				'period_type' => $rule->get_dripped_value( MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION, $item->id, 'period_type' ),
		);

		$period_from_today = array(
				'period_unit' => $rule->get_dripped_value( MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION, $item->id, 'period_unit' ),
				'period_type' => $rule->get_dripped_value( MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION, $item->id, 'period_type' ),
		);
		$fields = array(
				'spec_date' => array(
						'id' => 'spec_date_' . $item->id,
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'value' => $rule->get_dripped_value( MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE, $item->id, 'spec_date' ),
						'class' => 'ms-dripped-value ms-dripped-spec-date ms-ajax-update',
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
						'class' => 'ms-dripped-value ms-dripped-from-registration ms-field-input-period-unit ms-ajax-update',
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
						'class' => 'ms-field-input-period-type ms-ajax-update',
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
						'class' => 'ms-dripped-value ms-dripped-from-registration ms-field-input-period-unit ms-ajax-update',
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
						'class' => 'ms-field-input-period-type ms-ajax-update',
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
			<div class="ms-dripped-edit-wrapper ms-dripped-type-<?php echo MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE; ?>">
				<?php _e( 'on', MS_TEXT_DOMAIN );?><span class="ms-dripped-desc"></span>
				<?php MS_Helper_Html::html_element( $fields['spec_date'] );?>
				<span class="ms-dripped-calendar"></span>
			</div>
			<div class="ms-dripped-edit-wrapper ms-period-edit-wrapper ms-period-wrapper ms-dripped-type-<?php echo MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION; ?>">
				<div class="ms-period-desc-wrapper">
					<?php echo MS_Helper_Html::period_desc( $period_from_reg, 'ms-dripped-period' ); ?><?php _e( ' after registration', MS_TEXT_DOMAIN );?>
					<span class="ms-dripped-pen"></span>
				</div>
				<div class="ms-period-editor-wrapper">
					<?php MS_Helper_Html::html_element( $fields['period_unit_from_reg'] );?>
					<?php MS_Helper_Html::html_element( $fields['period_type_from_reg'] );?>
					<?php MS_Helper_Html::html_element( $fields['ok'] );?>
				</div>
			</div>
			<div class="ms-dripped-edit-wrapper ms-period-edit-wrapper ms-period-wrapper ms-dripped-type-<?php echo MS_Model_Rule::DRIPPED_TYPE_FROM_TODAY; ?>">
				<div class="ms-period-desc-wrapper">
					<?php _e( 'in', MS_TEXT_DOMAIN );?><?php echo MS_Helper_Html::period_desc( $period_from_today, 'ms-dripped-period' ); ?>
					<span class="ms-dripped-pen"></span>
				</div>
				<div class="ms-period-editor-wrapper">
					<?php MS_Helper_Html::html_element( $fields['period_unit_from_today'] );?>
					<?php MS_Helper_Html::html_element( $fields['period_type_from_today'] );?>
					<?php MS_Helper_Html::html_element( $fields['ok'] );?>
				</div>
			</div>
		<?php
		$html = ob_get_clean();
		return apply_filters( 'ms_helper_list_table_rule_column_dripped', $html );
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
		if( ! empty( $this->membership ) && $this->membership->is_valid() ) {
			$membership_id = $this->membership->id;
		}
		elseif( ! empty( $_REQUEST['membership_id'] ) ) {
			$membership_id = $_REQUEST['membership_id'];
		}
		return apply_filters( 'ms_helper_list_table_rule_get_membership_id', $membership_id );
	}

	public function get_views() {
		$count = $this->model->count_item_access();
		$has_access_desc = __( 'Has Access', MS_TEXT_DOMAIN );
		$no_access_desc = __( 'Access Restricted', MS_TEXT_DOMAIN );
		$has_access_status = MS_Model_Rule::FILTER_HAS_ACCESS;
		$no_access_status = MS_Model_Rule::FILTER_NO_ACCESS;

		if ( $this->membership->protected_content ) {
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
