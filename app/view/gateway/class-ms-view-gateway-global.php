<?php

class MS_View_Gateway_Global extends MS_View {

	protected $data;
	
	public function to_html() {		

		$gateway_list = new MS_Helper_List_Table_Gateway();
		$gateway_list->prepare_items();
		$fields = $this->get_global_payment_fields();
		
		$gateways = MS_Model_Gateway::get_gateways();
		
		?>
		<div class="ms-global-payment-wrapper">
			<div class="ms-list-table-wrapper ms-list-table-half">
				<div class="ms-field-input-label"><?php _e( 'Global Payment Settings', MS_TEXT_DOMAIN );?></div>
				<div class="ms-description"><?php _e( 'These are shared across all memberships', MS_TEXT_DOMAIN );?></div>
				<div class="ms-setup-half-width">
					<?php MS_Helper_Html::html_input( $fields['currency'] ); ?>
				</div>
				<div class="ms-setup-half-width">
					<?php MS_Helper_Html::html_input( $fields['invoice_sender_name'] ); ?>
				</div>
				<?php $gateway_list->display(); ?>
			</div>
			<?php foreach( $gateways as $gateway ) :?>
				<div class="ms-gateway-settings-wrapper ms-setup-half-width" id="ms-gateway-settings-<?php echo $gateway->id; ?>">
					<?php do_action( 'ms_controller_gateway_settings_render_view', $gateway->id );?>
				</div>
			<?php endforeach;?>
		</div>
		<?php 
	}
	
	private function get_global_payment_fields() {
		$settings = MS_Factory::load( 'MS_Model_Settings' );
// 		$action = $this->data['action'];
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
							'_wpnonce' => $nonce,
							'action' => $action,
					),
			),
			'invoice_sender_name' => array(
					'id' => 'invoice_sender_name',
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'title' => __( 'Invoice sender name', MS_TEXT_DOMAIN ),
					'value' => $settings->invoice_sender_name,
					'class' => '',
					'data_ms' => array(
							'field' => 'invoice_sender_name',
							'_wpnonce' => $nonce,
							'action' => $action,
					),
			),
		);

		return apply_filters( 'ms_view_memebrship_setup_payment_get_global_fields', $fields );
	}
	
}