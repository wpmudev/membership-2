<?php

class MS_View_Gateway_Paypal_Settings extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		/** Render tabbed interface. */
		?>
		<div class="ms-wrap">
			<form class="ms-gateway-setings-form ms-form wpmui-ajax-update" data-ajax="<?php echo esc_attr( $gateway->id ); ?>">
				<?php
				$description = '';
				if ( MS_Model_Gateway::GATEWAY_PAYPAL_STANDARD == $gateway->id ) {
					$description = sprintf(
						'%s <br />%s <strong>%s</strong> <br /><a href="%s">%s</a>',
						__( 'In order for Membership to function correctly you must setup an IPN listening URL with PayPal. Failure to do so will prevent your site from being notified when a member cancels their subscription.', MS_TEXT_DOMAIN ),
						__( 'Your IPN listening URL is:', MS_TEXT_DOMAIN ),
						$this->data['model']->get_return_url(),
						'https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/',
						__( 'Instructions &raquo;', MS_TEXT_DOMAIN )
					);
				}

				MS_Helper_Html::settings_box_header( '', $description );
				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				?>
			</form>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	protected function prepare_fields() {
		$gateway = $this->data['model'];
		$action = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;
		$nonce = wp_create_nonce( $action );

		if ( $gateway->id == MS_Model_Gateway::GATEWAY_PAYPAL_SINGLE ) {
			$merchant_id_field = array(
				'id' => 'paypal_email',
				'title' => __( 'PayPal Email', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->paypal_email,
				'class' => 'required',
			);
		} else {
			$merchant_id_field = array(
				'id' => 'merchant_id',
				'title' => __( 'PayPal Merchant Account ID', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->merchant_id,
				'class' => 'required',
			);
		}

		$fields = array(
			'merchant_id' => $merchant_id_field,
			'paypal_site' => array(
				'id' => 'paypal_site',
				'title' => __( 'PayPal Site', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $gateway->get_paypal_sites(),
				'value' => $gateway->paypal_site,
				'class' => 'required',
			),

			'mode' => array(
				'id' => 'mode',
				'title' => __( 'PayPal Mode', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class' => 'required',
			),

			'pay_button_url' => array(
				'id' => 'pay_button_url',
				'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->pay_button_url,
			),

			'dialog' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'dialog',
				'value' => 'Gateway_' . $gateway->id . '_Dialog',
			),

			'gateway_id' => array(
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'name' => 'gateway_id',
				'value' => $gateway->id,
			),

			'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'close' => array(
				'id' => 'close',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Close', MS_TEXT_DOMAIN ),
				'class' => 'close',
			),

			'save' => array(
				'id' => 'save',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);

		return $fields;
	}

}