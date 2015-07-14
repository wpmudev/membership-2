<?php
/**
 * Membership List Table
 *
 * @since  1.0.0
 */
class MS_Rule_Url_ListTable extends MS_Helper_ListTable_Rule {

	protected $id = MS_Rule_Url::RULE_ID;

	public function __construct( $model ) {
		parent::__construct( $model );
		$this->name['singular'] = __( 'URL', MS_TEXT_DOMAIN );
		$this->name['plural'] = __( 'URLs', MS_TEXT_DOMAIN );
		$this->name['default_access'] = __( 'Nobody', MS_TEXT_DOMAIN );
	}

	public function get_columns() {
		return apply_filters(
			'membership_helper_listtable_' . $this->id . '_columns',
			array(
				'cb' => true,
				'url' => __( 'Page URL', MS_TEXT_DOMAIN ),
				'access' => true,
			)
		);
	}

	public function column_url( $item ) {
		$rule_url = esc_url_raw(
			add_query_arg( array( 'item' => $item->id ) )
		);

		$actions = array();

		if ( $this->list_shows_base_items() ) {
			$trash_url = esc_url_raw(
				add_query_arg(
					array(
						'rule_action' => MS_Rule_Url::ACTION_DELETE,
						'_wpnonce' => wp_create_nonce( MS_Rule_Url::ACTION_DELETE ),
					),
					$rule_url
				)
			);

			$actions['trash'] = sprintf(
				'<a href="%s">%s</a>',
				$trash_url,
				__( 'Delete', MS_TEXT_DOMAIN )
			);
		}

		$actions = apply_filters(
			'ms_rule_' . $this->id . '_column_actions',
			$actions,
			$item
		);

		return sprintf(
			'%1$s %2$s',
			$item->url,
			$this->row_actions( $actions )
		);
	}

	/**
	 * Remove the view-filters for this rule.
	 *
	 * @since  1.0.0
	 */
	public function get_views() {
		return array();
	}

	/**
	 * Remove the list-header (with the rule title) for this rule
	 *
	 * @since  1.0.0
	 */
	public function list_head() {
	}

}