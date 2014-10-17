<?php

class MS_View_Billing_Edit extends MS_View {

	const BILLING_SECTION = 'billing_section';
	const BILLING_NONCE = 'billing_nonce';
	
	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		$fields = $this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<?php 
					$text = $this->data['invoice']->is_valid() ? __( 'Add', MS_TEXT_DOMAIN ) : __( 'Edit', MS_TEXT_DOMAIN );
					MS_Helper_Html::settings_header( array(
						'title' => sprintf( __( ' %s Billing', MS_TEXT_DOMAIN ), $text ),
						'title_icon_class' => 'fa fa-pencil-square',
					) ); 
				?>
				<form action="<?php echo remove_query_arg( array( 'action', 'invoice_id' ) ); ?>" method="post" class="ms-form">
					<?php MS_Helper_Html::settings_box( $fields ); ?>
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
		$fields = array(
			'execute' => array(
					'id' => 'execute',
					'title' => __( 'Execute status change actions (add/remove membership).', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
					'value' => true,
			),
			'status' => array(
					'id' => 'status',
					'title' => __( 'Status', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => MS_Model_Invoice::get_status_types(),
					'value' => $invoice->status,
			),
			'user_id' => array(
					'id' => 'user_id',
					'title' => __( 'Username', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'value' => $invoice->user_id,
					'field_options' => $this->data['users'],
			),
			'membership_id' => array(
				'id' => 'membership_id',
				'title' => __( 'Membership', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $invoice->membership_id,
				'field_options' => $this->data['memberships'],
			),
			'description' => array(
					'id' => 'description',
					'title' => __( 'Description', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $invoice->description,
			),
			'amount' => array(
					'id' => 'amount',
					'title' => sprintf( __( 'Amount (%s)', MS_TEXT_DOMAIN ), $currency ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $invoice->amount,
			),
			'discount' => array(
					'id' => 'discount',
					'title' => sprintf( __( 'Discount (%s)', MS_TEXT_DOMAIN ), $currency ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $invoice->discount,
			),				
			'due_date' => array(
					'id' => 'due_date',
					'title' => __( 'Due date', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
					'value' => $invoice->due_date,
					'class' => 'ms-date',
			),
			'notes' => array(
					'id' => 'notes',
					'title' => __( 'Notes', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
					'value' => $invoice->get_notes_desc(),
			),
			'invoice_id' => array(
					'id' => 'invoice_id',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $invoice->id,
			),
			'_wpnonce' => array(
					'id' => '_wpnonce',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => wp_create_nonce( $this->data['action'] ),
			),
			'action' => array(
					'id' => 'action',
					'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
					'value' => $this->data['action'],
			),
			'separator' => array(
					'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'cancel' => array(
					'id' => 'cancel',
					'type' => MS_Helper_Html::TYPE_HTML_LINK,
					'title' => __( 'Cancel', MS_TEXT_DOMAIN ),
					'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
					'url' => remove_query_arg( array( 'action', 'invoice_id' ) ),
					'class' => 'ms-link-button button',
			),
			'submit' => array(
					'id' => 'submit',
					'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
					'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);
		if( $invoice->id > 0 ) {
			$fields['user_id']['type'] = MS_Helper_Html::INPUT_TYPE_HIDDEN ;
			$fields['membership_id']['type'] = MS_Helper_Html::INPUT_TYPE_HIDDEN;
		}
		
		return apply_filters( 'ms_view_billing_edit_prepare_fields', $fields );
	}
}