<?php

class MS_Addon_BuddyPress_Rule extends MS_Controller {

	/**
	 * The rule ID.
	 *
	 * @type string
	 */
	const RULE_ID = 'buddypress';

	const PROTECT_ALL = 'buddypress_all';
	const PROTECT_FRIENDSHIP = 'buddypress_friendship';
	const PROTECT_GROUP_CREATION = 'buddypress_add_group';
	const PROTECT_PRIVATE_MSG = 'buddypress_priv_msg';
	const PROTECT_MEMBERS = 'buddypress_members';


	/**
	 * Setup the rule.
	 *
	 * @since  1.1.0
	 */
	public function prepare_obj() {
		MS_Model_Rule::register_rule(
			self::RULE_ID,
			__CLASS__,
			__( 'BuddyPress', MS_TEXT_DOMAIN ),
			40 // must be lower than 50 (pages-rule is 50)
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
		$view = MS_Factory::load( 'MS_Addon_BuddyPress_Rule_View' );

		$view->data = $data;
		$callback = array( $view, 'to_html' );

		return $callback;
	}

}
