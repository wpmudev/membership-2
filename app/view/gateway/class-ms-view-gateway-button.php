<?php

class MS_View_Gateway_Button extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();

		$action_url = apply_filters( 'ms_view_gateway_button_form_action_url', MS_Factory::load( 'MS_Model_Pages')->get_ms_page_url( MS_Model_Pages::MS_PAGE_REGISTER ) );
		ob_start();
		?>
			<tr>
				<td class='ms-buy-now-column' colspan='2' >
					<form action="<?php echo $action_url; ?>" method="post">
						<?php 
							foreach( $this->fields as $field ) {
								MS_Helper_Html::html_element( $field ); 
							}
						?>
					</form>
				</td>
			</tr>
		<?php
		$html = ob_get_clean();
		return $html;
	}
	
	private function prepare_fields() {
	
		$gateway = $this->data['gateway'];
		
		$this->fields = array(
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
		if( strpos( $this->pay_button_url, 'http' ) === 0 ) {
			$this->fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
					'value' =>  $gateway->pay_button_url,
			);
		}
		else {
			$this->fields['submit'] = array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' =>  $gateway->pay_button_url ? $gateway->pay_button_url : __( 'Signup', MS_TEXT_DOMAIN ),
			);
		}
	}
}