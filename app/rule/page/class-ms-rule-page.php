<?php

class MS_Rule_Page extends MS_Controller {

	/**
	 * The rule ID.
	 *
	 * @type string
	 */
	const RULE_ID = 'page';

	/**
	 * Setup the rule.
	 *
	 * @since  1.1.0
	 */
	public function prepare_obj() {
		if ( MS_Rule_Page_Model::is_active() ) {
			MS_Model_Rule::register_rule(
				self::RULE_ID,
				__CLASS__,
				__( 'Pages', MS_TEXT_DOMAIN ),
				50,
				true // can be dripped
			);
		}

		$this->add_filter(
			'ms_view_protectedcontent_define-' . self::RULE_ID,
			'handle_render_callback', 10, 2
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
		$view = MS_Factory::load( 'MS_Rule_Page_View' );

		$view->data = $data;
		$callback = array( $view, 'to_html' );

		return $callback;
	}

}

