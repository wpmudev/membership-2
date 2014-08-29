<?php

class MS_View_Membership_Setup_Payment extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {		
		$this->prepare_fields();
		$gateway_list = new MS_Helper_List_Table_Gateway();
		$gateway_list->prepare_items();

		$create_new_button = array(
			'id' => 'create_new_ms_button',
			'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value' => __( 'Create New Membership', MS_TEXT_DOMAIN ),
		);
		$desc = MS_Helper_Html::html_input( $this->fields['free'], true );
		
		ob_start();
		?>
		
		<div class="wrap ms-wrap">
			<?php 
				MS_Helper_Html::settings_header( array(
					'title' => __( 'Payment', MS_TEXT_DOMAIN ),
					'desc' => $desc 
				) ); 
			?>
			<div class="clear"></div>
			<hr />
			<div class="ms-payment-wrapper">
				<div class="ms-list-table-wrapper ms-list-table-half">
					<?php $gateway_list->display(); ?>
				</div>
			</div>
			<div class="clear"></div>
			<?php MS_Helper_Html::settings_footer( array( 'fields' => $this->fields['control_fields'] ) ); ?>
		</div>
		
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function prepare_fields() {
		$membership = $this->data['membership'];
	
		$this->fields = array(
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
	}
}