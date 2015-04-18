<?php

class MS_Addon_Bbpress_Rule extends MS_Controller {

	/**
	 * The rule ID.
	 *
	 * @type string
	 */
	const RULE_ID = 'bbpress';

	/**
	 * Setup the rule.
	 *
	 * @since  1.1.0
	 */
	public function prepare_obj() {
		MS_Model_Rule::register_rule(
			self::RULE_ID,
			__CLASS__,
			__( 'bbPress', MS_TEXT_DOMAIN ),
			100
		);

		$this->add_filter(
			'ms_view_protectedcontent_define-' . self::RULE_ID,
			'handle_render_callback', 10, 2
		);
	}

	/**
	 * Tells Membership2 Admin to display this form to manage this rule.
	 *
	 * @since 1.1.0
	 *
	 * @param array $callback (Invalid callback)
	 * @param array $data The data collection.
	 * @return array Correct callback.
	 */
	public function handle_render_callback( $callback, $data ) {
		$view = MS_Factory::load( 'MS_Addon_Bbpress_Rule_View' );

		$view->data = $data;
		$callback = array( $view, 'to_html' );

		return $callback;
	}

}
