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
				<h2>Billing edit</h2>
				<form action="<?php echo remove_query_arg( array( 'action', 'gateway_id' ) ); ?>" method="post" class="ms-form">
					<?php wp_nonce_field( self::BILLING_NONCE, self::BILLING_NONCE ); ?>
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
		$transaction = $this->data['transaction'];
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
					'field_options' => MS_Model_Transaction::get_status(),
					'value' => $transaction->status,
			),
			'name' => array(
					'id' => 'name',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Name', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $transaction->name,
			),
			'amount' => array(
					'id' => 'amount',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Amount', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $transaction->amount,
			),
			'tax_name' => array(
					'id' => 'tax_name',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Tax name', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $transaction->tax_name,
			),
			'tax_rate' => array(
					'id' => 'tax_rate',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Tax rate', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $transaction->tax_rate,
			),
			'due_date' => array(
					'id' => 'due_date',
					'section' => self::BILLING_SECTION,
					'title' => __( 'Due date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $transaction->due_date,
					'class' => 'ms-date',
			),
			'transaction_id' => array(
					'id' => 'transaction_id',
					'section' => self::BILLING_SECTION,
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $transaction->id,
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
		);
	}
}