<?php

class MS_View_Gateway_Authorize_Button extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$fields = $this->prepare_fields();
		/** force ssl url */
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$action_url = apply_filters( 'ms_view_gateway_authorize_button_form_action_url',
				$settings->get_special_page_url( MS_Model_Settings::SPECIAL_PAGE_SIGNUP, true )
		);
		ob_start();
		?>
			<tr>
				<td class='ms-buy-now-column' colspan='2' >
					<form action="<?php echo $action_url; ?>" method="post">
						<?php MS_Helper_Html::html_element( $fields['_wpnonce'] ); ?>
						<?php MS_Helper_Html::html_element( $fields['gateway'] ); ?>
						<?php MS_Helper_Html::html_element( $fields['ms_relationship_id'] ); ?>
						<?php MS_Helper_Html::html_element( $fields['step'] ); ?>
						<?php MS_Helper_Html::html_element( $fields['submit'] ); ?>
					</form>
				</td>
			</tr>
		<?php 
		$html = ob_get_clean();
		return $html;
	}
	
	private function prepare_fields() {
	
		$gateway = $this->data['gateway'];
		
		$fields = array(
				'_wpnonce' => array(
						'id' => '_wpnonce',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => wp_create_nonce( "{$this->data['gateway']->id}_{$this->data['ms_relationship']->id}" ),
				),
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $gateway->id,
				),
				'ms_relationship_id' => array(
						'id' => 'ms_relationship_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['ms_relationship']->id,
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['step'],
				),
		);
		if( strpos( $gateway->pay_button_url, 'http' ) === 0 ) {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
					'value' =>  $gateway->pay_button_url,
			);
		}
		else {
			$fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' =>  $gateway->pay_button_url ? $gateway->pay_button_url : __( 'Signup', MS_TEXT_DOMAIN ),
			);
		}
		
		return apply_filters( 'ms_view_gateway_authorize_button_prepare_fields', $fields ); 
	}
}