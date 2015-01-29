<?php

class MS_Gateway_Paypalstandard_View_Settings extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap">
			<form class="ms-gateway-settings-form ms-form wpmui-ajax-update" data-ajax="<?php echo esc_attr( $gateway->id ); ?>">
				<?php
				$description = sprintf(
					'%s <br />%s <strong>%s</strong> <br /><a href="%s">%s</a>',
					__( 'In order for Membership to function correctly you must setup an IPN listening URL with PayPal. Failure to do so will prevent your site from being notified when a member cancels their subscription.', MS_TEXT_DOMAIN ),
					__( 'Your IPN listening URL is:', MS_TEXT_DOMAIN ),
					$this->data['model']->get_return_url(),
					'https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/',
					__( 'Instructions &raquo;', MS_TEXT_DOMAIN )
				);

				MS_Helper_Html::settings_box_header( '', $description );
				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				MS_Helper_Html::settings_box_footer();
				?>
			</form>
			<div class="buttons">
				<?php
				MS_Helper_Html::html_element(
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
						'value' => __( 'Close', MS_TEXT_DOMAIN ),
						'class' => 'close',
					)
				);

				MS_Helper_Html::html_element(
					array(
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
						'class' => 'ms-submit-form',
						'data' => array(
							'form' => 'ms-gateway-settings-form',
						)
					)
				);
				?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	protected function prepare_fields() {
		$gateway = $this->data['model'];
		$action = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;
		$nonce = wp_create_nonce( $action );


		$fields = array(
			'merchant_id' => array(
				'id' => 'merchant_id',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'PayPal Merchant Account ID', MS_TEXT_DOMAIN ),
				'value' => $gateway->merchant_id,
				'class' => 'ms-text-large',
			),

			'paypal_site' => array(
				'id' => 'paypal_site',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'PayPal Site', MS_TEXT_DOMAIN ),
				'field_options' => $gateway->get_paypal_sites(),
				'value' => $gateway->paypal_site,
				'class' => 'ms-text-large',
			),

			'mode' => array(
				'id' => 'mode',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'PayPal Mode', MS_TEXT_DOMAIN ),
				'value' => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class' => 'ms-text-large',
			),

			'pay_button_url' => array(
				'id' => 'pay_button_url',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
				'value' => $gateway->pay_button_url,
				'class' => 'ms-text-large',
			),

			'dialog' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'dialog',
				'value' => 'Gateway_' . ucfirst( $gateway->id ) . '_View_Dialog',
			),

			'gateway_id' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'gateway_id',
				'value' => $gateway->id,
			),
		);

		return $fields;
	}

}