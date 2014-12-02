<?php

class MS_View_Gateway_Button extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();

		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
		$action_url = $ms_pages->get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );
		$action_url = apply_filters(
			'ms_view_gateway_button_form_action_url',
			$action_url
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

		if ( strpos( $this->pay_button_url, 'http' ) === 0 ) {
			$fields['submit'] = array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_IMAGE,
				'value' => $gateway->pay_button_url,
			);
		} else {
			$fields['submit'] = array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => $gateway->pay_button_url
					? $gateway->pay_button_url
					: __( 'Signup', MS_TEXT_DOMAIN ),
			);
		}

		return $fields;
	}
}