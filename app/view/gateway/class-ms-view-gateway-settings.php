<?php

class MS_View_Gateway_Settings extends MS_View {

	protected $fields = array();

	protected $data;

	public function to_html() {
		$this->prepare_fields();

		/** Render tabbed interface. */
		?>
		<div class='ms-wrap'>
			<div class='ms-settings'>
				<h2><?php echo esc_html( $this->data['model']->name ); ?> settings</h2>
				<form action="<?php echo esc_url( remove_query_arg( array( 'action', 'gateway_id' ) ) ); ?>" method="post" class="ms-form">
					<?php MS_Helper_Html::settings_box( $this->fields ); ?>
				</form>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	function prepare_fields() {
		$model = $this->data['model'];
		$this->fields = array(
			'pay_button_url' => array(
				'id' => 'pay_button_url',
				'title' => __( 'Payment button label or url', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'value' => $model->pay_button_url,
				'label_element' => 'h3',
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
			'gateway_id' => array(
				'id' => 'gateway_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $model->id,
			),
			'cancel' => array(
				'id' => 'cancel',
				'type' => MS_Helper_Html::TYPE_HTML_LINK,
				'title' => __( 'Cancel', MS_TEXT_DOMAIN ),
				'value' => __( 'Cancel', MS_TEXT_DOMAIN ),
				'url' => remove_query_arg( array( 'action', 'gateway_id' ) ),
				'class' => 'ms-link-button button',
				'label_element' => 'h3',
			),
			'submit_gateway' => array(
				'id' => 'submit_gateway',
				'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
				'value' => __( 'Save Changes', MS_TEXT_DOMAIN ),
			),
		);
	}
}