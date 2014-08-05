<?php

class MS_View_Gateway_Paypal_Single_Button extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		
		$action_url = apply_filters( 'ms_view_gateway_paypal_single_button_form_action_url', $this->data['action_url'] );
		
		ob_start();
		?>
			<tr>
				<td class='ms-buy-now-column' colspan='2' >
					<form action="<?php echo $action_url; ?>" method="post">
						<?php 
							foreach( $this->fields as $field ) {
								MS_Helper_Html::html_input( $field ); 
							}
						?>
						<img alt="" border="0" width="1" height="1" src="https://www.paypal.com/en_US/i/scr/pixel.gif" >
					</form>
				</td>
			</tr>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	
	private function prepare_fields() {
	
		$ms_relationship = $this->data['ms_relationship'];
		$membership = $ms_relationship->get_membership();
		if( 0 == $membership->price ) {
			return;
		}
		
		$gateway = $this->data['gateway'];
		$invoice = $ms_relationship->get_current_invoice();
		$this->fields = array(
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => wp_create_nonce( "{$this->data['gateway']->id}_{$this->data['ms_relationship']->id}" ),
				),
				'business' => array(
						'id' => 'business',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $gateway->paypal_email,
				),
				'cmd' => array(
						'id' => 'cmd',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => '_xclick',
				),
				'item_number' => array(
						'id' => 'item_number',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $ms_relationship->membership_id,
				),
				'item_name' => array(
						'id' => 'item_name',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->name,
				),
				'amount' => array(
						'id' => 'amount',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->total,
				),
				'currency_code' => array(
						'id' => 'currency_code',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->currency,
				),
				'return' => array(
						'id' => 'return',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_WELCOME ) ),
				),
				'cancel_return' => array(
						'id' => 'cancel_return',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => get_permalink( MS_Plugin::instance()->settings->get_special_page( MS_Model_Settings::SPECIAL_PAGE_SIGNUP ) ),
				),
				'notify_url' => array(
						'id' => 'notify_url',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $gateway->get_return_url(),
				),
				'lc' => array(
						'id' => 'lc',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $gateway->paypal_site,
				),
				'custom' => array(
						'id' => 'custom',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $invoice->id,
				),
		);
		
		/** Don't send to paypal if free */
		if( 0 == $invoice->total ) {
			$this->fields = array(
					'gateway' => array(
							'id' => 'gateway',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => $gateway->id,
					),
					'ms_relationship_id' => array(
							'id' => 'ms_relationship_id',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => $ms_relationship->id,
					),
					'step' => array(
							'id' => 'step',
							'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
							'value' => 'process_purchase',
					),
			);
			$this->data['action_url'] = null;
		}
		else {
			if( $gateway->is_live_mode() ) {
				$this->data['action_url'] = 'https://www.paypal.com/cgi-bin/webscr';
			}
			else {
				$this->data['action_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}
		}
		
		$this->fields['submit'] = array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
				'value' => 'https://www.paypalobjects.com/en_US/i/btn/x-click-but06.gif',
				'alt' => __( 'PayPal - The safer, easier way to pay online', MS_TEXT_DOMAIN ),
		);
		/** custom pay button defined in gateway settings */
		if( ! empty( $gateway->pay_button_url ) ) {
			if( strpos( $gateway->pay_button_url, 'http' ) !== 0 ) {
				$this->fields['submit']['value'] = $gateway->pay_button_url; 		
			}
			else {
				$this->fields['submit'] = array(
						'id' => 'submit',
						'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
						'value' => $gateway->pay_button_url ? $gateway->pay_button_url : __( 'Paypal', MS_TEXT_DOMAIN ),
				);
			}
		}
	}
}