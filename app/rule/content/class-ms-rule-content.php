<?php

class MS_Rule_Content extends MS_Controller {

	/**
	 * The rule ID.
	 *
	 * @type string
	 */
	const RULE_ID = 'content';

	/**
	 * Setup the rule.
	 *
	 * @since  1.1.0
	 */
	public function prepare_obj() {
		if ( MS_Rule_Content_Model::is_active() ) {
			MS_Model_Rule::register_rule(
				self::RULE_ID,
				__CLASS__,
				__( 'Comments & More Tag', MS_TEXT_DOMAIN ),
				80
			);
		}

		$this->add_filter(
			'ms_view_protectedcontent_define-' . self::RULE_ID,
			'handle_render_callback', 10, 2
		);

		$this->add_filter(
			'ms_rule_listtable-' . self::RULE_ID,
			'return_listtable'
		);
	}

	/**
	 * Tells Protected Content Admin to display this form to manage this rule.
	 *
	 * @since 1.1.0
	 *
	 * @param array $callback (Invalid callback)
	 * @param array $data The data collection.
	 * @return array Correct callback.
	 */
	public function handle_render_callback( $callback, $data ) {
		$view = MS_Factory::load( 'MS_Rule_Content_View' );

		$view->data = $data;
		$callback = array( $view, 'to_html' );

		return $callback;
	}

	/**
	 * Returns the ListTable object for this rule.
	 *
	 * @since 1.1.0
	 *
	 * @return MS_Helper_ListTable
	 */
	public function return_listtable( $empty ) {
		$base = MS_Model_Membership::get_base();
		$rule = $base->get_rule( self::RULE_ID );
		return new MS_Rule_Content_ListTable( $rule );
	}

}

