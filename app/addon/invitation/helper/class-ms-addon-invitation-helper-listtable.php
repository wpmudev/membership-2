<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Addon_Invitation_Helper_Listtable extends MS_Helper_ListTable {

	protected $id = 'invitation';

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'invitation',
				'plural'   => 'invitations',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return apply_filters(
			'ms_addon_invitation_helper_listtable_invitation_columns',
			array(
				'cb' => '<input type="checkbox" />',
				'icode' => __( 'Invitation Code', MS_TEXT_DOMAIN ),
				'start_date' => __( 'Start date', MS_TEXT_DOMAIN ),
				'expire_date' => __( 'Expire date', MS_TEXT_DOMAIN ),
				'membership' => __( 'Membership', MS_TEXT_DOMAIN ),
				'used' => __( 'Used', MS_TEXT_DOMAIN ),
				'remaining_uses' => __( 'Remaining uses', MS_TEXT_DOMAIN ),
			)
		);
	}

	public function get_hidden_columns() {
		return apply_filters(
			'ms_addon_invitation_helper_listtable_membership_hidden_columns',
			array()
		);
	}

	public function get_sortable_columns() {
		return apply_filters(
			'ms_addon_invitation_helper_listtable_membership_sortable_columns',
			array()
		);
	}

	public function prepare_items() {
		$this->_column_headers = array(
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		);

		$total_items = MS_Addon_Invitation_Model::get_invitation_count();
		$per_page = $this->get_items_per_page( 'invitation_per_page', 10 );
		$current_page = $this->get_pagenum();

		$args = array(
			'posts_per_page' => $per_page,
			'offset' => ( $current_page - 1 ) * $per_page,
		);

		$this->items = apply_filters(
			'ms_addon_invitation_helper_listtable_invitation_items',
			MS_Addon_Invitation_Model::get_invitations( $args )
		);

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page' => $per_page,
			)
		);
	}

	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="invitation_id[]" value="%1$s" />',
			esc_attr( $item->id )
		);
	}

	public function column_icode( $item ) {
		$actions = array();

		$actions['edit'] = sprintf(
			'<a href="?page=%s&action=%s&invitation_id=%s">%s</a>',
			esc_attr( $_REQUEST['page'] ),
			'edit',
			esc_attr( $item->id ),
			__( 'Edit', MS_TEXT_DOMAIN )
		);
		$actions['delete'] = sprintf(
			'<span class="delete"><a href="%s">%s</a></span>',
			wp_nonce_url(
				sprintf(
					'?page=%s&invitation_id=%s&action=%s',
					esc_attr( $_REQUEST['page'] ),
					esc_attr( $item->id ),
					'delete'
				),
				'delete'
			),
			__( 'Delete', MS_TEXT_DOMAIN )
		);

		return sprintf(
			'<code>%1$s</code> %2$s',
			$item->code,
			$this->row_actions( $actions )
		);
	}

	public function column_membership( $item ) {
		$html = '';
		$is_any = true;

		foreach ( $item->membership_id as $id ) {
			if ( MS_Model_Membership::is_valid_membership( $id ) ) {
				$is_any = false;

				$membership = MS_Factory::load( 'MS_Model_Membership', $id );
				$html .= sprintf(
					'<span class="ms-bold">%s</span><br />',
					$membership->name
				);
			}
		}

		if ( $is_any ) {
			$html = sprintf(
				'<span class="ms-low">%s</span>',
				__( 'Any', MS_TEXT_DOMAIN )
			);
		}

		return $html;
	}

	public function column_start_date( $item ) {
		$html = $item->start_date;

		return $html;
	}

	public function column_expire_date( $item ) {
		$html = '';

		if ( $item->expire_date ) {
			$html = $item->expire_date;
		} else {
			$html = __( 'No expire', MS_TEXT_DOMAIN );
		}

		return $html;
	}

	public function column_used( $item ) {
		$html = $item->used;

		return $html;
	}

	public function column_remaining_uses( $item ) {
		$html = $item->remaining_uses;

		return $html;
	}

	public function get_bulk_actions() {
		return apply_filters(
			'ms_addon_invitation_helper_listtable_bulk_actions',
			array(
				'delete' => __( 'Delete', MS_TEXT_DOMAIN ),
			)
		);
	}

}