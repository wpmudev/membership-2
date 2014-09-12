<?php

class MS_View_Gateway_Paypal_Settings extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<div class='ms-settings'>
					<h2><?php echo $this->data['model']->name;?> settings</h2>
					<form action="<?php echo remove_query_arg( array( 'action', 'gateway_id' ) ); ?>" method="post" class="ms-form">
						<?php
							$description = '';
							if( MS_Model_Gateway::GATEWAY_PAYPAL_STANDARD == $this->data['model']->id ) {
								$description = sprintf( '%s <br />%s <strong>%s<strong> <br /><a href="%s">%s</a>',
									__( 'In order for Membership to function correctly you must setup an IPN listening URL with PayPal. Failure to do so will prevent your site from being notified when a member cancels their subscription.', MS_TEXT_DOMAIN ),
									__( 'Your IPN listening URL is:', MS_TEXT_DOMAIN ),
									$this->data['model']->get_return_url(),
									'https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/',
									__( 'Instructions »', MS_TEXT_DOMAIN )
								); 
							}
							MS_Helper_Html::settings_box(
								$this->fields, 
								'', 
								$description,
								array( 'label_element' => 'h3' ) 
							);
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
		$model = $this->data['model'];
		if( $model->id == MS_Model_Gateway::GATEWAY_PAYPAL_SINGLE ) {
			$merchant_id_field = array(
					'id' => 'paypal_email',
					'title' => __( 'Paypal Email', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->paypal_email,
			);
		}
		else {
			$merchant_id_field = array(
					'id' => 'merchant_id',
					'title' => __( 'PayPal Merchant Account ID', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->merchant_id,
			);
		}
		$this->fields = array(
			'merchant_id' => $merchant_id_field,
			'paypal_site' => array(
					'id' => 'paypal_site',
					'title' => __( 'PayPal Site', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $model->get_paypal_sites(),
					'value' => $model->paypal_site,
// 					'class' => 'chosen-select',
			),
			'mode' => array(
					'id' => 'mode',
					'title' => __( 'PayPal Mode', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $model->mode,
					'field_options' => $model->get_mode_types(),
			),
			'pay_button_url' => array(
					'id' => 'pay_button_url',
					'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $model->pay_button_url,
			),
			'_wpnonce' => array(
					'id' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce( $this->data['action'] ),
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
			'gateway_id' => array(
					'id' => 'gateway_id',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $model->id,
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
			'activate' => array(
					'id' => 'activate',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Activate', MS_TEXT_DOMAIN ),
			),
		);
	}

}