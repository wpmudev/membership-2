<?php

class MS_Gateway_2checkout_View_Settings extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();
		$gateway = $this->data['model'];

		ob_start();
		// Render tabbed interface.
		?>
		<form class="ms-gateway-settings-form ms-form">
			<?php
			$description = sprintf(
				'%1$s<br />&nbsp;<br />%2$s <strong>%3$s</strong><br />%6$s <strong>%7$s</strong><br /><a href="%4$s" target="_blank">%5$s</a>',
				__( 'In order for Membership 2 to function correctly you must setup an INS (Instant Notification Service) URL with 2Checkout. Make sure you add the following URLs to your 2Checkout "Notifications" section as well as the "Approved URL" in the Site Management section. The domain must be the same as the one registered with your Live account for production sites.', MS_TEXT_DOMAIN ),
				__( 'Your Global Notifications URL is:', MS_TEXT_DOMAIN ),
				$this->data['model']->get_return_url(),
				'https://www.2checkout.com/documentation/notifications/',
				__( 'Instructions &raquo;', MS_TEXT_DOMAIN ),
				__( 'Your "Approved URL" is:', MS_TEXT_DOMAIN ),
				MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REG_COMPLETE )
			);

			MS_Helper_Html::settings_box_header( '', $description );
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			MS_Helper_Html::settings_box_footer();
			?>
		</form>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	protected function prepare_fields() {
		$gateway = $this->data['model'];
		$action = MS_Controller_Gateway::AJAX_ACTION_UPDATE_GATEWAY;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'seller_id' => array(
				'id' => 'seller_id',
				'title' => __( '2Checkout Account Number', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->seller_id,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'secret_word' => array(
				'id' => 'secret_word',
				'title' => __( '2Checkout Secret Word', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->secret_word,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'mode' => array(
				'id' => 'mode',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Payment Mode', MS_TEXT_DOMAIN ),
				'value' => $gateway->mode,
				'field_options' => $gateway->get_mode_types(),
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),

			'pay_button_url' => array(
				'id' => 'pay_button_url',
				'title' => __( 'Payment button label or URL', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $gateway->pay_button_url,
				'class' => 'ms-text-large',
				'ajax_data' => array( 1 ),
			),
		);

		// Process the fields and add missing default attributes.
		foreach ( $fields as $key => $field ) {
			if ( ! empty( $field['ajax_data'] ) ) {
				$fields[ $key ]['ajax_data']['field'] = $fields[ $key ]['id'];
				$fields[ $key ]['ajax_data']['_wpnonce'] = $nonce;
				$fields[ $key ]['ajax_data']['action'] = $action;
				$fields[ $key ]['ajax_data']['gateway_id'] = $gateway->id;
			}
		}

		return apply_filters(
			'ms_gateway_2checkout_view_settings_prepare_fields',
			$fields
		);
	}
}