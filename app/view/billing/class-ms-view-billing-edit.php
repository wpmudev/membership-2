<?php

/**
 * Render Invoice add/edit view.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 *
 * @package Membership
 * @subpackage View
 */
class MS_View_Billing_Edit extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$this->check_simulation();

		$fields = $this->prepare_fields();
		$form_url = remove_query_arg( array( 'action', 'invoice_id' ) );

		if ( $this->data['invoice']->is_valid() ) {
			$title = __( 'Edit Billing', MS_TEXT_DOMAIN );
		} else {
			$title = __( 'Add Billing', MS_TEXT_DOMAIN );
		}

		ob_start();
		// Render tabbed interface.
		?>
			<div class="ms-wrap ms-billing">
				<?php
				MS_Helper_Html::settings_header(
					array(
					'title' => $title,
					'title_icon_class' => 'wpmui-fa wpmui-fa-pencil-square',
					)
				);
				?>
				<form action="<?php echo $form_url; ?>" method="post" class="ms-form">
					<?php MS_Helper_Html::settings_box( $fields, '', '', 'static', 'ms-small-form' ); ?>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_billing_edit_to_html', $html, $this );
	}

	/**
	 * Prepare html fields.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	function prepare_fields() {
		$invoice = $this->data['invoice'];
		$currency = MS_Plugin::instance()->settings->currency;
		$fields = array(
			'txt_user' => array(
				'id' => 'txt_user',
				'title' => __( 'Username', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => $this->data['users'][ $invoice->user_id ],
			),
			'txt_membership' => array(
				'id' => 'txt_membership',
				'title' => __( 'Membership', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => $this->data['memberships'][ $invoice->membership_id ],
			),
			'txt_separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
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
				'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'value' => $invoice->due_date,
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
			'execute' => array(
				'id' => 'execute',
				'title' => __( 'Execute status change actions on Save (add/remove membership)', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
				'value' => true,
			),
			'cancel' => array(
				'id' => 'cancel',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'title' => __( 'Cancel', MS_TEXT_DOMAIN ),
				'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
				'url' => remove_query_arg( array( 'action', 'invoice_id' ) ),
				'class' => 'wpmui-field-button button',
			),
			'submit' => array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);

		if ( $invoice->id > 0 ) {
			$fields['user_id']['type'] = MS_Helper_Html::INPUT_TYPE_HIDDEN;
			$fields['membership_id']['type'] = MS_Helper_Html::INPUT_TYPE_HIDDEN;
		} else {
			unset( $fields['txt_user'] );
			unset( $fields['txt_membership'] );
			unset( $fields['txt_separator'] );
		}

		return apply_filters(
			'ms_view_billing_edit_prepare_fields',
			$fields,
			$this
		);
	}
}