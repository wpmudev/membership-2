<?php

class MS_View_Settings_Payment extends MS_View {

	protected $data;

	public function to_html() {

		$gateway_list = new MS_Helper_List_Table_Gateway();
		$gateway_list->prepare_items();
		$fields = $this->get_global_payment_fields();

		$gateways = MS_Model_Gateway::get_gateways();

		?>
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

			<?php foreach ( $gateways as $gateway ) : ?>
				<div class="ms-gateway-settings-wrapper ms-half" id="ms-gateway-settings-<?php echo esc_attr( $gateway->id ); ?>">
					<?php do_action( 'ms_controller_gateway_settings_render_view', $gateway->id ); ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

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
					'class' => 'chosen-select ms-ajax-update',
					'data_ms' => array(
							'field' => 'currency',
							'_wpnonce' => $nonce,
							'action' => $action,
					),
			),
			'invoice_sender_name' => array(
					'id' => 'invoice_sender_name',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' => __( 'Invoice sender name', MS_TEXT_DOMAIN ),
					'value' => $settings->invoice_sender_name,
					'class' => 'ms-ajax-update',
					'data_ms' => array(
							'field' => 'invoice_sender_name',
							'_wpnonce' => $nonce,
							'action' => $action,
					),
			),
		);

		return apply_filters( 'ms_view_gateway_get_global_payment_fields', $fields );
	}

}