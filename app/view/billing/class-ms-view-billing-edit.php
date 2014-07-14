<?php

class MS_View_Billing_Edit extends MS_View {

	const BILLING_SECTION = 'billing_section';
	const BILLING_NONCE = 'billing_nonce';
	
	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<h2 class="ms-settings-title"><i class="fa fa-pencil-square"></i> <?php echo empty( $this->data['invoice']->id ) ? __( 'Add', MS_TEXT_DOMAIN ) : __( 'Edit', MS_TEXT_DOMAIN ) ; _e( ' Billing', MS_TEXT_DOMAIN ); ?></h2>
				<form action="<?php echo remove_query_arg( array( 'action', 'invoice_id' ) ); ?>" method="post" class="ms-form">
					<?php wp_nonce_field( $this->fields['action']['value'] ); ?>
					<table class="form-table">
						<tbody>
							<?php foreach( $this->fields as $field ): ?>
								<tr>
									<td>
										<?php MS_Helper_Html::html_input( $field ); ?>
									</td>
								</tr>
								<?php endforeach; ?>
						</tbody>
					</table>
					<?php MS_Helper_Html::html_submit(); ?>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	function prepare_fields() {
		$invoice = $this->data['invoice'];
		$currency = MS_Plugin::instance()->settings->currency;
		$this->fields = array(
			'execute' => array(
					'id' => 'execute',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Execute status change actions (add/remove membership).', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
					'value' => true,
			),
			'status' => array(
					'id' => 'status',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Status', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => MS_Model_Invoice::get_status(),
					'value' => $invoice->status,
			),
			'user_id' => array(
					'id' => 'user_id',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Username', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $invoice->user_id,
					'field_options' => $this->data['users'],
			),
			'membership_id' => array(
				'id' => 'membership_id',
				'section' => self::BILLING_SECTION,
				'title' => __( 'Membership', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $invoice->membership_id,
				'field_options' => $this->data['memberships'],
			),
			'description' => array(
					'id' => 'description',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Description', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $invoice->description,
			),
			'amount' => array(
					'id' => 'amount',
					'section' => self::BILLING_SECTION,
					'title' => sprintf( __( 'Amount (%s)', MS_TEXT_DOMAIN ), $currency ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $invoice->amount,
			),
			'discount' => array(
					'id' => 'discount',
					'section' => self::BILLING_SECTION,
					'title' => sprintf( __( 'Discount (%s)', MS_TEXT_DOMAIN ), $currency ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $invoice->discount,
			),				
// 			'tax_name' => array(
// 					'id' => 'tax_name',
// 					'section' => self::BILLING_SECTION,
// 					'title' => __( 'Tax name', MS_TEXT_DOMAIN ),
// 					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
// 					'value' => $invoice->tax_name,
// 			),
// 			'tax_rate' => array(
// 					'id' => 'tax_rate',
// 					'section' => self::BILLING_SECTION,
// 					'title' => __( 'Tax rate', MS_TEXT_DOMAIN ),
// 					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
// 					'value' => $invoice->tax_rate,
// 			),
			'due_date' => array(
					'id' => 'due_date',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Due date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $invoice->due_date,
					'class' => 'ms-date',
			),
			'notes' => array(
					'id' => 'notes',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Notes', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
					'value' => $invoice->notes,
			),
			'gateway_id' => array(
					'id' => 'gateway_id',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Gateway', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $invoice->gateway_id,
					'field_options' => $this->data['gateways'],
			),
			'invoice_id' => array(
					'id' => 'invoice_id',
					'section' => self::BILLING_SECTION,
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $invoice->id,
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
		);
		if( $invoice->id > 0 ) {
			unset( $this->fields['user_id'] );
			unset( $this->fields['membership_id'] );
		}
	}
}