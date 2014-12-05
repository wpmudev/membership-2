<?php

class MS_View_Gateway_Paypal_Single_Button extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();

		$action_url = apply_filters(
			'ms_view_gateway_paypal_single_button_form_action_url',
			$this->data['action_url']
		);

		$row_class = 'gateway_' . $this->data['gateway']->id;
		if ( ! $this->data['gateway']->is_live_mode() ) {
			$row_class .= ' sandbox-mode';
		}

		ob_start();
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<td class="ms-buy-now-column" colspan="2" >
				<form action="<?php echo esc_url( $action_url ); ?>" method="post">
					<?php
					foreach ( $fields as $field ) {
						MS_Helper_Html::html_element( $field );
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

		if ( 0 === $membership->price ) {
			return;
		}

		$gateway = $this->data['gateway'];
		$invoice = MS_Model_Invoice::get_current_invoice( $ms_relationship );
		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );

		$fields = array(
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
			'bn' => array(
				'id' => 'bn',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => 'incsub_SP',
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
				'value' => add_query_arg(
					array( 'ms_relationship_id' => $ms_relationship->id ),
					$ms_pages->get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE, false )
				),
			),
			'cancel_return' => array(
				'id' => 'cancel_return',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $ms_pages->get_page_url( MS_Model_Pages::MS_PAGE_REGISTER ),
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

		// Don't send to paypal if free
		if ( 0 === $invoice->total ) {
			$fields = array(
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
					'value' => MS_Controller_Frontend::STEP_PROCESS_PURCHASE,
				),
				'_wpnonce' => array(
					'id' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce(
						$this->data['gateway']->id . '_' .$this->data['ms_relationship']->id
					),
				),
			);
			$this->data['action_url'] = null;
		}
		else {
			if ( $gateway->is_live_mode() ) {
				$this->data['action_url'] = 'https://www.paypal.com/cgi-bin/webscr';
			}
			else {
				$this->data['action_url'] = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
			}
		}

		$fields['submit'] = array(
			'id' => 'submit',
			'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
			'value' => 'https://www.paypalobjects.com/en_US/i/btn/x-click-but06.gif',
			'alt' => __( 'PayPal - The safer, easier way to pay online', MS_TEXT_DOMAIN ),
		);

		// custom pay button defined in gateway settings
		if ( ! empty( $gateway->pay_button_url ) ) {
			if ( strpos( $gateway->pay_button_url, 'http' ) !== 0 ) {
				$fields['submit']['value'] = $gateway->pay_button_url;
			}
			else {
				$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => $gateway->pay_button_url
						? $gateway->pay_button_url
						: __( 'PayPal', MS_TEXT_DOMAIN ),
				);
			}
		}

		return apply_filters(
			'ms_view_gateway_paypal_single_prepare_fields',
			$fields
		);
	}
}