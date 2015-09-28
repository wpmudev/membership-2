<?php

/**
 * Render Invoice add/edit view.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.0
 *
 * @package Membership2
 * @subpackage View
 */
class MS_View_Billing_Edit extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.0
	 *
	 * @return string
	 */
	public function to_html() {
		$this->check_simulation();

		$fields = $this->prepare_fields();
		$form_url = esc_url_raw(
			remove_query_arg( array( 'action', 'invoice_id' ) )
		);

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
	 * @since  1.0.0
	 *
	 * @return array
	 */
	function prepare_fields() {
		$invoice = $this->data['invoice'];
		$currency = MS_Plugin::instance()->settings->currency;
		$user_name = '';
		$transaction_link = '';
		$user_id = 0;
		$user_list = array();

		if ( $invoice->id ) {
			$member = $invoice->get_member();
			$user_id = $member->id;
			$user_name = $member->name;

			$transaction_link = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				MS_Controller_Plugin::get_admin_url(
					'billing',
					array( 'show' => 'logs', 'invoice' => $invoice->id )
				),
				__( 'Show Transactions', MS_TEXT_DOMAIN )
			);
		} else {
			$user_list = MS_Model_Member::get_usernames( null, MS_Model_Member::SEARCH_ALL_USERS );
		}

		$fields = array(
			'link_transactions' => array(
				'id' => 'link_transactions',
				'title' => $transaction_link,
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'wrapper_class' => 'ms-transactions-link',
			),
			'txt_user' => array(
				'id' => 'txt_user',
				'title' => __( 'Invoice for member', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'value' => sprintf(
					'<a href="%s">%s</a>',
					MS_Controller_Plugin::get_admin_url(
						'add-member',
						array( 'user_id' => $user_id )
					),
					$user_name
				),
			),
			'txt_membership' => array(
				'id' => 'txt_membership',
				'title' => __( 'Payment for membership', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
			),
			'txt_created' => array(
				'id' => 'txt_created',
				'title' => __( 'Invoice created on', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
			),
			'txt_separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'status' => array(
				'id' => 'status',
				'title' => __( 'Invoice status', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => MS_Model_Invoice::get_status_types( true ),
				'value' => $invoice->status,
			),
			'user_id' => array(
				'id' => 'user_id',
				'title' => __( 'Invoice for member', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $invoice->user_id,
				'field_options' => $user_list,
			),
			'membership_id' => array(
				'id' => 'membership_id',
				'title' => __( 'Payment for membership', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $invoice->membership_id,
				'field_options' => $this->data['memberships'],
			),
			'amount' => array(
				'id' => 'amount',
				'title' => sprintf( __( 'Amount (%s)', MS_TEXT_DOMAIN ), $currency ),
				'type' => MS_Helper_Html::INPUT_TYPE_NUMBER,
				'value' => MS_Helper_Billing::format_price( $invoice->amount ),
				'config' => array(
					'step' => 'any',
					'min' => 0,
				),
			),
			'discount' => array(
				'id' => 'discount',
				'title' => sprintf( __( 'Discount (%s)', MS_TEXT_DOMAIN ), $currency ),
				'type' => MS_Helper_Html::INPUT_TYPE_NUMBER,
				'value' => MS_Helper_Billing::format_price( $invoice->discount ),
				'config' => array(
					'step' => 'any',
					'min' => 0,
				),
			),
			'due_date' => array(
				'id' => 'due_date',
				'title' => __( 'Due date', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_DATEPICKER,
				'value' => $invoice->due_date,
			),
			'description' => array(
				'id' => 'description',
				'title' => __( 'Description', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'class' => 'widefat',
				'value' => $invoice->description,
			),
			'notes' => array(
				'id' => 'notes',
				'title' => __( 'Notes', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT_AREA,
				'class' => 'widefat',
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
				'url' => esc_url_raw( remove_query_arg( array( 'action', 'invoice_id' ) ) ),
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
			$fields['txt_membership']['value'] = $this->data['memberships'][ $invoice->membership_id ];
			$fields['txt_created']['value'] = MS_Helper_Period::format_date( $invoice->invoice_date );
		} else {
			unset( $fields['txt_user'] );
			unset( $fields['txt_membership'] );
			unset( $fields['txt_created'] );
			unset( $fields['txt_separator'] );
		}

		return apply_filters(
			'ms_view_billing_edit_prepare_fields',
			$fields,
			$this
		);
	}
}