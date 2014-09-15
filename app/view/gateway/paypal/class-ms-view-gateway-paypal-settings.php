<?php

class MS_View_Gateway_Paypal_Settings extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		$gateway = $this->data['model'];
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<div class='ms-settings'>
					<h2><?php echo sprintf( __( '%s settings', MS_TEXT_DOMAIN ), $gateway->name ); ?></h2>
					<form class="ms-gateway-setings-form ms-form" data-ms="<?php echo $gateway->id;?>">
						<?php
							$description = '';
							if( MS_Model_Gateway::GATEWAY_PAYPAL_STANDARD == $gateway->id ) {
								$description = sprintf( '%s <br />%s <strong>%s</strong> <br /><a href="%s">%s</a>',
									__( 'In order for Membership to function correctly you must setup an IPN listening URL with PayPal. Failure to do so will prevent your site from being notified when a member cancels their subscription.', MS_TEXT_DOMAIN ),
									__( 'Your IPN listening URL is:', MS_TEXT_DOMAIN ),
									$this->data['model']->get_return_url(),
									'https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/',
									__( 'Instructions »', MS_TEXT_DOMAIN )
								); 
							}
							MS_Helper_Html::settings_box( $this->fields, '', $description );
							MS_Helper_Html::settings_footer( null, null, true );
						?>
					</form>
					<div class="clear"></div>
				</div>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	function prepare_fields() {
		$gateway = $this->data['model'];
		$action = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;
		$nonce = wp_create_nonce( $action );
		
		if( $gateway->id == MS_Model_Gateway::GATEWAY_PAYPAL_SINGLE ) {
			$merchant_id_field = array(
					'id' => 'paypal_email',
					'title' => __( 'Paypal Email', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $gateway->paypal_email,
					'class' => 'required ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'paypal_email',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			);
		}
		else {
			$merchant_id_field = array(
					'id' => 'merchant_id',
					'title' => __( 'PayPal Merchant Account ID', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $gateway->merchant_id,
					'class' => 'required ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'merchant_id',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			);
		}
		$this->fields = array(
			'merchant_id' => $merchant_id_field,
			'paypal_site' => array(
					'id' => 'paypal_site_' . $gateway->id,
					'title' => __( 'PayPal Site', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $gateway->get_paypal_sites(),
					'value' => $gateway->paypal_site,
// 					'class' => 'chosen-select',
					'class' => 'required ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'paypal_site',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'mode' => array(
					'id' => 'mode_'. $gateway->id,
					'title' => __( 'PayPal Mode', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $gateway->mode,
					'field_options' => $gateway->get_mode_types(),
					'class' => 'required ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'mode',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'pay_button_url' => array(
					'id' => 'pay_button_url_'. $gateway->id,
					'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $gateway->pay_button_url,
					'class' => 'ms-ajax-update',
					'data_ms' => array(
							'gateway_id' => $gateway->id,
							'field' => 'pay_button_url',
							'action' => $action,
							'_wpnonce' => $nonce,
					),
			),
			'separator' => array(
					'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'close' => array(
					'id' => 'close',
					'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
					'value' => __( 'Close', MS_TEXT_DOMAIN ),
					'class' => 'ms-link-button ms-close-button',
			),
			'save' => array(
					'id' => 'save',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);
	}

}