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
class MS_Helper_ListTable_Rule extends MS_Helper_ListTable {

	/**
	 * ID of the rule. This is overwritten by each rule!
	 *
	 * @var   string
	 */
	protected $id = 'rule';

	/**
	 * Holds the human readable name of the rule tyle
	 *
	 * @since 1.1.0
	 * @var array
	 */
	protected $name = array(
		'singular' => 'Item',
		'plural' => 'Items',
		'default_access' => 'Everyone',
	);

	/**
	 * The rule model
	 *
	 * @var MS_Rule
	 */
	protected $model;

	/**
	 * The membership object linked to the rule
	 *
	 * @var   MS_Model_Membership
	 */
	protected $membership;

	/**
	 * The `prepare_items()` function stores the prepared filter args in this
	 * member variable for later usage.
	 *
	 * @var   array
	 * @since 1.1.0
	 */
	protected $prepared_args = array();

	/**
	 * A list of all active memberships
	 *
	 * @var array
	 * @since 1.1.0
	 */
	static protected $memberships = array();

	/**
	 * Initialize the list table
	 *
	 * @since 1.0.0
	 * @param MS_Rule $model Rule-Model
	 */
	public function __construct( $model ) {
		parent::__construct(
			array(
				'singular'  => 'rule_' . $this->id,
				'plural'    => 'rules_' . $this->id,
				'ajax'      => false,
			)
		);

		$this->name['singular'] = __( 'Item', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'Items', MS_TEXT_DOMAIN );
		$this->name['default_access'] = __( 'Everyone', MS_TEXT_DOMAIN );

		$this->model = $model;
		$this->membership = MS_Model_Membership::get_base();

		$memberships = MS_Model_Membership::get_memberships();
		self::$memberships = array();

		foreach ( $memberships as $item ) {
			self::$memberships[$item->id] = (object) array(
				'label' => $item->name,
				'attr' => sprintf( 'data-color="%1$s"', $item->get_color() ),
			);
		}
	}

	public function get_columns() {
		return apply_filters(
			'ms_helper_listtable_' . $this->id . '_columns',
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
			"ms_helper_listtable_{$this->id}_hidden_columns",
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			"ms_helper_listtable_{$this->id}_sortable_columns",
			array(
				'content' => 'content',
				'dripped' => 'dripped',
			)
		);
	}

	public function get_bulk_actions() {
		$bulk_actions = array(
			'give_access' => __( 'Protect content', MS_TEXT_DOMAIN ),
			'no_access' => __( 'Remove protection', MS_TEXT_DOMAIN ),
		);

		return apply_filters(
			"ms_helper_listtable_{$this->id}_bulk_actions",
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

		// Some columns have a pre-defined title that cannot be changed.
		if ( isset( $this->_column_headers[0]['cb'] ) ) {
			$this->_column_headers[0]['cb'] = '<input type="checkbox" />';
		}

		if ( isset( $this->_column_headers[0]['dripped'] ) ) {
			$this->_column_headers[0]['dripped'] = __( 'When to Reveal Content', MS_TEXT_DOMAIN );
		}

		if ( isset( $this->_column_headers[0]['access'] ) ) {
			$this->_column_headers[0]['access'] = __( 'Who Has Access', MS_TEXT_DOMAIN );
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

		// If a membership is filtered then only show protected items
		if ( ! empty( $_REQUEST['membership_id'] ) ) {
			$args['membership_id'] = $_REQUEST['membership_id'];
		}

		// Allow other helper list tables to customize the args array.
		$args = $this->prepare_items_args( $args );

		// Count items
		$total_items = $this->model->get_content_count( $args );

		// List available items
		$this->items = apply_filters(
			"ms_rule_{$this->id}_items",
			$this->model->get_contents( $args )
		);

		// Save the args for use in later functions
		$this->prepared_args = $args;

		// Prepare the table pagination
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	/**
	 * Returns true, if the list displays items of the base membership.
	 * i.e. true means that the Membership filter is set to "All"
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function list_shows_base_items() {
		static $Is_Base = null;

		if ( null === $Is_Base ) {
			// When no membership_id is specified the list will display base items.
			$Is_Base = true;

			if ( ! empty( $_REQUEST['membership_id'] ) ) {
				$membership = MS_Factory::load( 'MS_Model_Membership', $_REQUEST['membership_id'] );
				$Is_Base = $membership->is_base();
			}
		}

		return $Is_Base;
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

	public function column_cb( $item, $column_name ) {
		return sprintf(
			'<input type="checkbox" name="item[]" value="%1$s" />',
			$item->id
		);
	}

	public function column_access( $item, $column_name ) {
		$rule = $this->model;
		$memberships = $rule->get_memberships( $item->id );

		$public = array(
			'id' => 'ms-empty-' . $item->id,
			'type' => MS_Helper_Html::TYPE_HTML_TEXT,
			'value' => $this->name['default_access'],
			'after' => 'Modify Access',
			'class' => 'ms-empty-note',
		);

		$list = array(
			'id' => 'ms-memberships-' . $item->id,
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'value' => array_keys( $memberships ),
			'field_options' => self::$memberships,
			'multiple' => true,
			'class' => 'ms-memberships',
			'ajax_data' => array(
				'action' => MS_Controller_Rule::AJAX_ACTION_CHANGE_MEMBERSHIPS,
				'rule' => $item->type,
				'item' => $item->id,
			),
		);

		$html = sprintf(
			'<div class="no-auto-init">%1$s%2$s</div>',
			MS_Helper_Html::html_element( $public, true ),
			MS_Helper_Html::html_element( $list, true )
		);

		return $html;
	}

	public function column_dripped( $item, $column_name ) {
		ob_start();
		?>
		<a href="#" class="editinline"><?php _e( 'Set date...', MS_TEXT_DOMAIN ); ?></a>
		<div class="inline_data hidden">
			<span class="name"><?php echo esc_html( $item->name ); ?></span>
			<span class="dripped">...</span>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_helper_listtable_rule_column_dripped',
			$html
		);
	}

	public function column_content( $item, $column_name ) {
		$html = $item->content;

		return $html;
	}

	/**
	 * Adds a class to the <tr> element
	 *
	 * @since  1.1.0
	 * @param  object $item
	 */
	protected function single_row_class( $item ) {
		$rule = $this->model;
		$memberships = $rule->get_memberships( $item->id );

		$class = empty( $memberships ) ? 'ms-empty' : 'ms-assigned';
		return $class;
	}

	/**
	 * Displays the inline-edit form used to edit the dripped content details.
	 *
	 * @since 1.1.0
	 */
	protected function inline_edit() {
		$rule = $this->model;
		$membership = $this->membership;

		$field_id = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'membership_id',
		);

		$field_type = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'name' => 'dripped_type',
			'class' => 'dripped_type',
			'field_options' => MS_Model_Rule::get_dripped_types(),
		);

		$field_date = array(
			'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
			'name' => 'date',
		);

		$field_delay_unit = array(
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			'name' => 'delay_unit',
			'class' => 'ms-text-small',
		);

		$field_delay_type = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'name' => 'delay_type',
			'field_options' => MS_Helper_Period::get_periods(),
		);

		?>
		<div>
			<h4 class="lbl-name"></h4>
		</div>
		<fieldset>
			<div class="inline-edit-col">
				<div class="dynamic-form"></div>
			</div>
		</fieldset>
		<div class="dripped-form cf no-auto-init hidden">
			<div class="drip-col col-1">
				<span class="the-name"></span>
				<?php MS_Helper_Html::html_element( $field_id ); ?>
			</div>
			<div class="drip-col col-2">
				<?php MS_Helper_Html::html_element( $field_type ); ?>
			</div>
			<div class="drip-col col-3">
				<div class="drip-option <?php echo esc_attr( MS_Model_Rule::DRIPPED_TYPE_INSTANTLY ); ?>">
					<?php _e( 'Instantly', MS_TEXT_DOMAIN ); ?>
				</div>
				<div class="drip-option <?php echo esc_attr( MS_Model_Rule::DRIPPED_TYPE_SPEC_DATE ); ?>">
					<?php MS_Helper_Html::html_element( $field_date ); ?>
				</div>
				<div class="drip-option <?php echo esc_attr( MS_Model_Rule::DRIPPED_TYPE_FROM_REGISTRATION ); ?>">
					<?php
					MS_Helper_Html::html_element( $field_delay_unit );
					MS_Helper_Html::html_element( $field_delay_type );
					?>
				</div>
			</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Displayed above the views.
	 *
	 * In the rule list-tables the list-head is used to display a filter for
	 * membership-ID. Combined with the views (below) users can filter all rules
	 * by membership + protection status independantly
	 *
	 * @since  1.1.0
	 */
	public function list_head() {
		$type_name = $this->name['plural'];
		$membership_name = '';
		$membership_color = '';

		/*
		 * We don't build the title dynamically to make sure translations are
		 * possible and meaningful in the context.
		 *
		 * E.g. "Showing All Pages" in german would typically translate as
		 * "All pages are shown"; also "All" has several translations, depending
		 * on context.
		 */
		if ( empty( $_GET['membership_id'] ) ) {
			if ( empty( $_GET['status'] ) ) {
				$title = __( 'Showing <b>All</b> %1$s', MS_TEXT_DOMAIN );
			} elseif ( MS_Model_Rule::FILTER_NOT_PROTECTED == $_GET['status'] ) {
				$title = __( 'Showing All <b>Unprotected</b> %1$s', MS_TEXT_DOMAIN );
			} elseif ( MS_Model_Rule::FILTER_PROTECTED == $_GET['status'] ) {
				$title = __( 'Showing All <b>Protected</b> %1$s', MS_TEXT_DOMAIN );
			}
		} else {
			$membership = MS_Factory::load( 'MS_Model_Membership', $_GET['membership_id'] );

			if ( empty( $_GET['status'] ) ) {
				$title = __( 'Showing <b>All</b> %1$s for %2$s', MS_TEXT_DOMAIN );
			} elseif ( MS_Model_Rule::FILTER_NOT_PROTECTED == $_GET['status'] ) {
				$title = __( 'Showing All %1$s that are <b>not protected</b> by %2$s', MS_TEXT_DOMAIN );
			} elseif ( MS_Model_Rule::FILTER_PROTECTED == $_GET['status'] ) {
				$title = __( 'Showing All %1$s that are <b>protected</b> by %2$s', MS_TEXT_DOMAIN );
			}

			$membership_name = $membership->name;
			$membership_color = $membership->get_color();
		}

		$title = sprintf(
			$title,
			'<b>' . esc_html( $type_name ) . '</b>',
			sprintf(
				'<span class="the-title" style="background-color:%2$s">%1$s</span>',
				esc_html( $membership_name ),
				$membership_color
			)
		);

		printf( '<h3 class="ms-list-title">%1$s</h3>', $title );
	}

	/**
	 * Returns an array that defines possible views.
	 *
	 * In the rule list-tables the views are used to filter by protection status
	 * and not by membership-ID or other factors.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_views() {
		$count_args = $this->prepared_args;
		unset( $count_args['rule_status'] );

		// Count is not working, so we remove it for now
		//$count = $this->model->count_item_access( $count_args );

		$url = apply_filters(
			'ms_helper_listtable_' . $this->id . '_url',
			remove_query_arg( array( 'status', 'paged' ) )
		);

		$views = array();

		$views['all'] = array(
			'url' => $url,
			'label' => __( 'All', MS_TEXT_DOMAIN ),
			//'count' => $count['total'],
		);

		$views['public'] = array(
			'url' => add_query_arg( array( 'status' => MS_Model_Rule::FILTER_NOT_PROTECTED ), $url ),
			'label' => __( 'Unprotected', MS_TEXT_DOMAIN ),
			//'count' => $count['restricted'],
		);

		$views['protected'] = array(
			'url' => add_query_arg( array( 'status' => MS_Model_Rule::FILTER_PROTECTED ), $url ),
			'label' => __( 'Protected', MS_TEXT_DOMAIN ),
			//'count' => $count['accessible'],
		);

		return apply_filters(
			"ms_helper_listtable_{$this->id}_views",
			$views
		);
	}

	/**
	 * Return true if the current list is a view except "all"
	 *
	 * @since  1.1.0
	 * @return bool
	 */
	public function is_view() {
		return ! empty( $_GET['status'] );
	}
}
