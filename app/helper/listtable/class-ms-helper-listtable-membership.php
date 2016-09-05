<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Helper_ListTable_Membership extends MS_Helper_ListTable {

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
                        'cb'        => '<input type="checkbox" />',
			'priority' => sprintf(
				'<span title="%s">#</span>',
				__( 'Membership Order', 'membership2' )
			),
			'name' => __( 'Membership Name', 'membership2' ),
			'active' => __( 'Active', 'membership2' ),
			'type_description' => __( 'Type of Membership', 'membership2' ),
			'members' => __( 'Members', 'membership2' ),
			'price' => __( 'Payment', 'membership2' ),
			'shortcode' => __( 'Shortcodes', 'membership2' ),
		);

		return apply_filters(
			'membership_helper_listtable_membership_columns',
			$columns
		);
	}
        
        public function column_cb( $item ) {
            return sprintf(
                '<input class="del_membership_ids" type="checkbox" name="memberships[]" value="%s" />', $item->id
            );    
        }

	public function get_hidden_columns() {
		return apply_filters(
			'membership_helper_listtable_membership_hidden_columns',
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'membership_helper_listtable_membership_sortable_columns',
			array(
				'priority' => array( 'menu_order', true ),
				'name' => array( 'name', true ),
				'type_description' => array( 'type', true ),
				'active' => array( 'active', true ),
			)
		);
	}

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$args = array();

		if ( ! empty( $_REQUEST['orderby'] ) && ! empty( $_REQUEST['order'] ) ) {
			$args['orderby'] = $_REQUEST['orderby'];
			$args['order'] = $_REQUEST['order'];
		}

		// Prepare order by statement.
		if ( ! empty( $args['orderby'] )
			&& property_exists( 'MS_Model_Membership', $args['orderby'] )
		) {
			$args['meta_key'] = $args['orderby'];
			$args['orderby'] = 'meta_value';
		}

		$this->items = apply_filters(
			'membership_helper_listtable_membership_items',
			MS_Model_Membership::get_memberships( $args )
		);
	}

	public function column_priority( $item ) {
		$result = '-';

		if ( ! $item->is_system() ) {
			$result = $item->priority;
		}

		return $result;
	}

	public function column_name( $item ) {
		$actions = array();

		// Prepare the Membership actions.
		$actions['edit'] = sprintf(
			'<a href="?page=%1$s&step=%2$s&tab=%3$s&membership_id=%4$s">%5$s</a>',
			esc_attr( $_REQUEST['page'] ),
			MS_Controller_Membership::STEP_EDIT,
			MS_Controller_Membership::TAB_DETAILS,
			esc_attr( $item->id ),
			__( 'Edit', 'membership2' )
		);

		if ( ! $item->is_system() ) {
			$actions['payment'] = sprintf(
				'<a href="?page=%1$s&step=%2$s&tab=%3$s&membership_id=%4$s">%5$s</a>',
				esc_attr( $_REQUEST['page'] ),
				MS_Controller_Membership::STEP_EDIT,
				MS_Controller_Membership::TAB_PAYMENT,
				esc_attr( $item->id ),
				$item->is_free ? __( 'Access options', 'membership2' ) : __( 'Payment options', 'membership2' )
			);
		}

		$actions['delete'] = sprintf(
			'<span class="delete"><a href="%s">%s</a></span>',
			wp_nonce_url(
				sprintf(
					'?page=%1$s&membership_id=%2$s&action=%3$s',
					esc_attr( $_REQUEST['page'] ),
					esc_attr( $item->id ),
					'delete'
				),
				'delete'
			),
			__( 'Delete', 'membership2' )
		);

		$actions = apply_filters(
			'ms_helper_listtable_membership_column_name_actions',
			$actions,
			$item
		);

		// Add the badge to special memberships.
		if ( $item->is_guest() ) {
			$badge = sprintf(
				'<span class="ms-badge ms-guest-badge" data-wpmui-tooltip="%2$s" data-width="180">%1$s</span>',
				__( 'Guest', 'membership2' ),
				__( 'All Logged-Out users are considered guests', 'membership2' )
			);
		} elseif ( $item->is_user() ) {
			$badge = sprintf(
				'<span class="ms-badge ms-user-badge" data-wpmui-tooltip="%2$s" data-width="180">%1$s</span>',
				__( 'Default', 'membership2' ),
				__( 'All logged-in users that have not signed up for any membership', 'membership2' )
			);
		} else {
			$badge = apply_filters(
				'ms_helper_listtable_memberships_name_badge',
				'',
				$item,
				$this
			);
		}

		return sprintf(
			'<span class="ms-color" style="background-color:%4$s">&nbsp;</span> ' .
			'<a href="?page=%5$s&step=%6$s&membership_id=%7$s" class="the-name">%1$s</a> ' .
			'%3$s%2$s',
			esc_html( $item->name ),                 // 1
			$this->row_actions( $actions ),          // 2
			$badge,                                  // 3
			$item->get_color(),                      // 4
			esc_attr( $_REQUEST['page'] ),           // 5
			MS_Controller_Membership::STEP_OVERVIEW, // 6
			esc_attr( $item->id )                    // 7
		);
	}

	public function column_active( $item ) {
		$toggle = array(
			'id' => 'ms-toggle-' . $item->id,
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
			'value' => $item->active,
			'data_ms' => array(
				'action' => MS_Controller_Membership::AJAX_ACTION_TOGGLE_MEMBERSHIP,
				'field' => 'active',
				'membership_id' => $item->id,
			),
		);

		$html = MS_Helper_Html::html_element( $toggle, true );

		return $html;
	}

	public function column_members( $item, $column_name ) {
		$html = '';

		if ( ! $item->is_system() ) {
			$count = $item->get_members_count();

			$url = MS_Controller_Plugin::get_admin_url(
				'members',
				array( 'membership_id' => $item->id )
			);

			$html = sprintf(
				'<a href="%2$s">%1$s</a>',
				intval( $count ),
				$url
			);
		}

		return $html;
	}

	public function column_type_description( $item, $column_name ) {
		$html = '';

		$html .= sprintf(
			'<span class="ms-img-type ms-img-type-%1$s small"></span> ',
			esc_attr( $item->type )
		);

		$desc = $item->type_description;
		if ( ! empty( $desc ) ) {
			$html .= sprintf(
				'<span class="ms-type-desc ms-%1$s">%2$s</span>',
				esc_attr( $item->type ),
				$desc
			);
		}

		if ( ! $item->is_system() && $item->private ) {
			$html .= sprintf(
				'<span class="ms-is-private">, <span>%1$s</span></span>',
				__( 'Private', 'membership2' )
			);
		}

		return $html;
	}

	public function column_price( $item, $column_name ) {
		$html = '';

		if ( ! $item->is_system() ) {
			if ( ! $item->is_free() ) {
				$html = sprintf(
					'<span class="ms-currency">%1$s</span> <span class="ms-price">%2$s</span> (<span class="ms-payment">%3$s</span>)',
					MS_Plugin::instance()->settings->currency_symbol,
					MS_Helper_Billing::format_price( $item->price ),
					$item->get_payment_type_desc()
				);
				$html = '<span class="ms-bold">' . $html . '</span>';
			} else {
				$html = sprintf(
					'<span class="ms-bold">%1$s</span> (<span class="ms-payment">%2$s</span>)',
					__( 'Free', 'membership2' ),
					$item->get_payment_type_desc()
				);
			}

			$followup = MS_Factory::load(
				'MS_Model_Membership',
				$item->on_end_membership_id
			);

			if ( $followup->is_valid() ) {
				$html .= '<div class="ms-followup">' . sprintf(
					__( 'Follow with: %1$s', 'membership2' ),
					'<span class="ms-color" style="background:' . $followup->get_color() . '">&nbsp;</span>' . $followup->name
				) . '</div>';
			}
		}

		return $html;
	}

	public function column_shortcode( $item, $column_name ) {
		$shortcodes = array(
			MS_Rule_Shortcode_Model::PROTECT_CONTENT_SHORTCODE => array(
				'tag' => '[%1$s id="%2$s"][/%1$s]',
				'label' => __( 'Protect content', 'membership2' ),
			),
			MS_Helper_Shortcode::SCODE_MS_TITLE => array(
				'tag' => '[%1$s id="%2$s"]',
				'label' => __( 'Membership Title', 'membership2' ),
			),
			MS_Helper_Shortcode::SCODE_MS_DETAILS => array(
				'tag' => '[%1$s id="%2$s"]',
				'label' => __( 'Membership Description', 'membership2' ),
			),
			MS_Helper_Shortcode::SCODE_MS_PRICE => array(
				'tag' => '[%1$s id="%2$s"]',
				'label' => __( 'Membership Price', 'membership2' ),
			),
			MS_Helper_Shortcode::SCODE_MS_BUY => array(
				'tag' => '[%1$s id="%2$s"]',
				'label' => __( 'Subscribe Button', 'membership2' ),
			),
			MS_Helper_Shortcode::SCODE_REGISTER_USER => array(
				'tag' => '[%1$s membership_id="%2$s"]',
				'label' => __( 'Registration form', 'membership2' ),
			),
		);
		if ( $item->is_system() ) {
			unset( $shortcodes[MS_Helper_Shortcode::SCODE_MS_TITLE] );
			unset( $shortcodes[MS_Helper_Shortcode::SCODE_MS_DETAILS] );
			unset( $shortcodes[MS_Helper_Shortcode::SCODE_MS_PRICE] );
			unset( $shortcodes[MS_Helper_Shortcode::SCODE_MS_BUY] );
			unset( $shortcodes[MS_Helper_Shortcode::SCODE_REGISTER_USER] );
		}

		$shortcodes = apply_filters(
			'ms_helper_listtable_membership_shortcodes',
			$shortcodes,
			$item
		);

		$lines = array();
		foreach ( $shortcodes as $code => $details ) {
			$lines[] = sprintf(
				'<div>%s: <code>%s</code></div>',
				$details['label'],
				sprintf(
					$details['tag'],
					$code,
					esc_attr( $item->id )
				)
			);
		}

		$html = sprintf(
			'<div class="ms-shortcodes"><span class="ms-trigger"><span class="label">%s</span><div class="inner">%s</div></span></div>',
			__( 'Show', 'membership2' ) . ' <i class="wpmui-fa wpmui-fa-caret-down"></i>',
			implode( '', $lines )
		);

		return $html;
	}

	public function get_bulk_actions() {
		return apply_filters(
			'ms_helper_listtable_membership_bulk_actions',
			array()
		);
	}
}
