<?php

/**
 * Dialog: Payment Gateway "PayPal Standard".
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage View
 */
class MS_Gateway_Paypalstandard_View_Dialog extends MS_Dialog {

	protected $gateway_id = MS_Gateway_Paypalstandard::ID;

	/**
	 * Generate/Prepare the dialog attributes.
	 *
	 * @since 1.0
	 */
	public function prepare() {
		$view = MS_Factory::create( 'MS_Gateway_Paypalstandard_View_Settings' );

		$data = array(
			'model' => MS_Model_Gateway::factory( $this->gateway_id ),
			'action' => 'edit',
		);

		$view->data = apply_filters( 'ms_gateway_view_settings_edit_data', $data );
		$view = apply_filters( 'ms_gateway_view_settings_edit', $view, $this->gateway_id );
		$gateway = $view->data['model'];

		// Dialog Title
		$this->title = sprintf( __( '%s settings', MS_TEXT_DOMAIN ), $gateway->name );

		// Dialog Size
		$this->height = 490;

		// Contents
		$this->content = $view->to_html();
	}

	/**
	 * Save the gateway details.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function submit() {
		$data = $_POST;

		unset( $data['action'] );
		unset( $data['dialog'] );

		$cont = MS_Plugin::instance()->controller->controllers['gateway'];
		$res = $cont->gateway_list_do_action( 'edit', array( $this->gateway_id ), $data );

		return $res;
	}

};