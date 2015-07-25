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
		$fields = $this->prepare_fields();

		ob_start();
		// Render tabbed interface.
		?>
		<div>
			<div class="link-member">
			<?php
			foreach ( $fields['member'] as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
			</div>
			<div class="link-membership">
			<?php
			foreach ( $fields['membership'] as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
			</div>
			<div class="link-invoice">
			<?php
			foreach ( $fields['invoice'] as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
			</div>
			<div class="buttons">
			<?php
			foreach ( $fields['buttons'] as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
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
		$userlist = $this->data['users'];
		$action = $this->data['action'];
		$fields = array();

		$member_id = 0;
		if ( $member && $member->id ) {
			$member_id = $member->id;
		}

		$fields['member'] = array(
			'user_id' => array(
				'id' => 'user_id',
				'title' => __( '1. Payment by user', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => $member_id,
				'field_options' => $userlist,
			)
		);

		$fields['membership'] = array(
			'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'membership_id' => array(
				'id' => 'user_id',
				'title' => __( '2. Payment for Subscription', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => 0,
				'field_options' => array(),
			)
		);

		$fields['invoice'] = array(
			'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
			'invoice_id' => array(
				'id' => 'user_id',
				'title' => __( '3. Payment for Invoice', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'value' => 0,
				'field_options' => array(),
			)
		);

		$fields['buttons'] = array(
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( $action ),
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $action,
			),
			'cancel' => array(
				'id' => 'cancel',
				'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
				'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
				'class' => 'wpmui-field-button button close',
			),
			'submit' => array(
				'id' => 'submit',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Transaction', MS_TEXT_DOMAIN ),
			),
		);

		return apply_filters(
			'ms_view_billing_link_prepare_fields',
			$fields,
			$this
		);
	}
}