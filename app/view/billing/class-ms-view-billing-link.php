<?php
/**
 * Render Link-Transaction View.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since  1.0.1.0
 *
 * @package Membership2
 * @subpackage View
 */
class MS_View_Billing_Link extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since  1.0.1.0
	 *
	 * @return string
	 */
	public function to_html() {
		$groups = $this->prepare_fields();

		ob_start();
		// Render tabbed interface.
		?>
		<div>
			<div class="wpmui-grid-8 ms-transaction-window">
				<div class="the-details col-3">
				<?php
				foreach ( $groups['info'] as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				unset( $groups['info'] );
				?>
				</div>
				<div class="the-form col-5">
				<?php foreach ( $groups as $key => $fields ) : ?>
				<div class="link-block link-<?php echo esc_attr( $key . ' ' . $key ); ?>">
				<?php
				foreach ( $fields as $field ) {
					MS_Helper_Html::html_element( $field );
				}
				?>
				</div>
				<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters( 'ms_view_billing_link_to_html', $html, $this );
	}

	/**
	 * Prepare html fields.
	 *
	 * @since  1.0.1.0
	 *
	 * @return array
	 */
	function prepare_fields() {
		$member = $this->data['member'];
		$userlist = MS_Model_Member::get_usernames( null, MS_Model_Member::SEARCH_ALL_USERS );
		$log = $this->data['log'];
		$data_action = MS_Controller_Billing::AJAX_ACTION_TRANSACTION_LINK_DATA;
		$update_action = MS_Controller_Billing::AJAX_ACTION_TRANSACTION_UPDATE;
		$fields = array();

		$member_id = 0;
		if ( $member && $member->id ) {
			$member_id = $member->id;
		}

		$fields['info'] = array(
			'id' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Transaction ID', 'membership2' ),
				'value' => $log->id,
			),
			'gateway' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Payment Gateway', 'membership2' ),
				'value' => MS_Model_Gateway::get_name( $log->gateway_id, true ),
			),
			'amount' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Transaction Amount', 'membership2' ),
				'value' => MS_Helper_Billing::format_price( $log->amount ),
			),
			'details' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Transaction Details', 'membership2' ),
				'value' => $log->description,
			),
			'sep' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
				'value' => 'vertical',
			),
		);

		$fields['member'] = array(
			'user_id' => array(
				'id' => 'user_id',
				'title' => __( '1. Payment by user', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $member_id,
				'field_options' => $userlist,
			)
		);

		$fields['subscription'] = array(
			'subscription_id' => array(
				'id' => 'subscription_id',
				'title' => __( '2. Payment for subscription', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => 0,
				'field_options' => array(),
			)
		);

		$fields['invoice'] = array(
			'invoice_id' => array(
				'id' => 'invoice_id',
				'title' => __( '3. Link payment with invoice', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => 0,
				'field_options' => array(),
				'after' => __( 'The selected Invoice will be marked as "paid"', 'membership2' ),
			)
		);

		$fields['buttons'] = array(
			'nonce_link_data' => array(
				'id' => 'nonce_link_data',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $data_action ),
			),
			'nonce_update' => array(
				'id' => 'nonce_update',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $update_action ),
			),
			'log_id' => array(
				'id' => 'log_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $log->id,
			),
			'cancel' => array(
				'id' => 'cancel',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Cancel', 'membership2' ),
				'class' => 'wpmui-field-button button close',
			),
			'submit' => array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Transaction', 'membership2' ),
			),
		);

		return apply_filters(
			'ms_view_billing_link_prepare_fields',
			$fields,
			$this
		);
	}
}