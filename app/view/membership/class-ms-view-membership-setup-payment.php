<?php

class MS_View_Membership_Setup_Payment extends MS_View {

	protected $data;
	
	public function to_html() {		
		$fields = $this->get_fields();

		$desc = MS_Helper_Html::html_input( $fields['free'], true );
		
		ob_start();
		?>
		
		<div class="wrap ms-wrap">
			<?php 
				MS_Helper_Html::settings_header( array(
					'title' => __( 'Payment', MS_TEXT_DOMAIN ),
					'desc' => "$desc" 
				) ); 
			?>
			<div class="clear"></div>
			<hr />
			<?php $this->global_payments_html(); ?>
			<div class="clear"></div>
			<?php MS_Helper_Html::settings_footer( array( 'fields' => $this->fields['control_fields'] ) ); ?>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function get_fields() {
		$membership = $this->data['membership'];
	
		$fields = array(
				'free' => array(
						'id' => 'free',
						'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
						'value' => $this->data['membership']->free,
						'desc' => __( 'Do you want to accept payments for this membership?', MS_TEXT_DOMAIN ),
						'class' => 'ms-payments-choice',
						'field_options' => array(
								'1' => __( 'Yes', MS_TEXT_DOMAIN ),
								'0' => __( 'No', MS_TEXT_DOMAIN ),
						),
				),
				'control_fields' => array(
						'membership_id' => array(
								'id' => 'membership_id',
								'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'value' => $membership->id,
						),
						'step' => array(
								'id' => 'step',
								'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'value' => $this->data['step'],
						),
						'action' => array(
								'id' => 'action',
								'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'value' => $this->data['action'],
						),
						'_wpnonce' => array(
								'id' => '_wpnonce',
								'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
								'value' => wp_create_nonce( $this->data['action'] ),
						),
				),
		);
		
		return apply_filters( 'ms_view_memebrship_setup_payment_get_fields', $fields );
	}
	
	public function global_payments_html() {
		$gateway_list = new MS_Helper_List_Table_Gateway();
		$gateway_list->prepare_items();
		$fields = $this->get_global_fields();
		
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
		</div>
		<?php 
	}
	
	public function get_global_fields() {
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