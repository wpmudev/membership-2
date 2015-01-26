<?php

class MS_Rule_Url extends MS_Controller {

	/**
	 * The rule ID.
	 *
	 * @type string
	 */
	const RULE_ID = 'url';

	// Form actions
	const ACTION_ADD = 'rule_url_add';
	const ACTION_DELETE = 'rule_url_delete';

	/**
	 * Setup the rule.
	 *
	 * @since  1.1.0
	 */
	public function prepare_obj() {
		if ( MS_Rule_Url_Model::is_active() ) {
			MS_Model_Rule::register_rule(
				self::RULE_ID,
				__CLASS__,
				__( 'URL Restrictions', MS_TEXT_DOMAIN ),
				0
			);
		}

		$this->add_filter(
			'ms_view_protectedcontent_define-' . self::RULE_ID,
			'handle_render_callback', 10, 2
		);

		$this->process_form();
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
		$view = MS_Factory::load( 'MS_Rule_Url_View' );

		$view->data = $data;
		$callback = array( $view, 'to_html' );

		return $callback;
	}

	/**
	 * Processes a form submit and changes the rule values, if valid form data
	 * is found.
	 *
	 * @since  1.1.0
	 */
	private function process_form() {
		$action = false;
		if ( isset( $_POST['rule_action'] ) ) {
			$action = $_POST['rule_action'];

			if ( ! $this->verify_nonce( $action ) ) {
				$action = false;
			}
		} elseif ( isset( $_GET['rule_action'] ) ) {
			$action = $_GET['rule_action'];

			if ( ! $this->verify_nonce( $action, 'GET' ) ) {
				$action = false;
			}
		}

		if ( empty( $action ) ) { return; }
		$redirect = false;

		switch ( $action ) {
			case self::ACTION_ADD:
				$url = $_POST['url_value'];
				$base = MS_Model_Membership::get_base();
				$rule = $base->get_rule( self::RULE_ID );
				$rule->add_url( $url );
				$base->set_rule( self::RULE_ID, $rule );
				$base->save();

				$redirect = true;
				break;

			case self::ACTION_DELETE:
				$id = $_REQUEST['item'];
				$base = MS_Model_Membership::get_base();
				$rule = $base->get_rule( self::RULE_ID );
				$rule->delete_url( $id );
				$base->set_rule( self::RULE_ID, $rule );
				$base->save();

				$redirect = true;
				break;
		}

		if ( $redirect ) {
			$target = remove_query_arg(
				array( '_wpnonce', 'item', 'rule_action' )
			);
			wp_safe_redirect( $target );
			exit;
		}
	}

}

