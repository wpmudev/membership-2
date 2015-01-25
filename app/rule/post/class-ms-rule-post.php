<?php

class MS_Rule_Post extends MS_Controller {

	/**
	 * The rule ID.
	 *
	 * @type string
	 */
	const RULE_ID = 'post';

	/**
	 * Setup the rule.
	 *
	 * @since  1.1.0
	 */
	public function prepare_obj() {
		MS_Model_Rule::register_rule(
			self::RULE_ID,
			__CLASS__,
			__( 'Posts', MS_TEXT_DOMAIN ),
			11,
			true // can be dripped
		);

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
		$view = MS_Factory::load( 'MS_Rule_Post_View' );

		$view->data = $data;
		$callback = array( $view, 'to_html' );

		return $callback;
	}

}

