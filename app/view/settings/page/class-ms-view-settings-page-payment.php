<?php

class MS_View_Settings_Page_Payment extends MS_View_Settings_Edit {

	/**
	 * Overrides parent's to_html() method.
	 *
	 * Creates an output buffer, outputs the HTML and grabs the buffer content before releasing it.
	 * HTML contains the list of available payment gateways.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$gateway_list = MS_Factory::create( 'MS_Helper_ListTable_Gateway' );
		$gateway_list->prepare_items();
		$fields = $this->get_global_payment_fields();

		$gateways = MS_Model_Gateway::get_gateways();

		ob_start();
		?>
		<div id="ms-payment-settings-wrapper">
		<div class="ms-global-payment-wrapper">
			<div class="ms-list-table-wrapper">
				<?php
				MS_Helper_Html::settings_tab_header(
					array(
						'title' => __( 'Global Payment Settings', MS_TEXT_DOMAIN ),
						'desc' => __( 'These are shared across all memberships.', MS_TEXT_DOMAIN ),
					)
				);
				?>
				<div class="ms-half space">
					<?php MS_Helper_Html::html_element( $fields['currency'] ); ?>
				</div>
				<div class="ms-half">
					<?php MS_Helper_Html::html_element( $fields['invoice_sender_name'] ); ?>
				</div>

				<div class="ms-group-head">
					<div class="ms-bold"><?php _e( 'Payment Gateways:', MS_TEXT_DOMAIN ); ?></div>
					<div class="ms-description"><?php _e( 'You need to set-up at least one Payment Gateway to be able to process payments.', MS_TEXT_DOMAIN ); ?></div>
				</div>

				<?php $gateway_list->display(); ?>
			</div>

			<?php MS_Helper_Html::settings_footer( null, false ); ?>
		</div>
		</div>
		<?php
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Prepares a list with field definitions that are required to render the
	 * payment list/global options (i.e. currency and sender name)
	 *
	 * @since  1.0.0
	 *
	 * @return array
	 */
	private function get_global_payment_fields() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_SETTING;
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'currency' => array(
				'id' => 'currency',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Select payment currency', MS_TEXT_DOMAIN ),
				'value' => $settings->currency,
				'field_options' => $settings->get_currencies(),
				'class' => '',
				'class' => 'chosen-select',
				'data_ms' => array(
					'field' => 'currency',
				),
			),

			'invoice_sender_name' => array(
				'id' => 'invoice_sender_name',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Invoice sender name', MS_TEXT_DOMAIN ),
				'value' => $settings->invoice_sender_name,
				'data_ms' => array(
					'field' => 'invoice_sender_name',
				),
			),
		);

		foreach ( $fields as $key => $field ) {
			if ( is_array( $field['data_ms'] ) ) {
				$fields[ $key ]['data_ms']['_wpnonce'] = $nonce;
				$fields[ $key ]['data_ms']['action'] = $action;
			}
		}

		return apply_filters( 'ms_gateway_view_get_global_payment_fields', $fields );
	}

}