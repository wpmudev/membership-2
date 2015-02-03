<?php

/**
 * The Settings-Form
 */
class MS_Addon_Taxamo_View extends MS_View {

	public function render_tab() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-wrap">
			<div class="ms-settings">
				<?php
				MS_Helper_Html::settings_tab_header(
					array( 'title' => __( 'Taxamo Settings', MS_TEXT_DOMAIN ) )
				);
				?>

				<form action="" method="post">
					<?php MS_Helper_Html::settings_box( $fields ); ?>
				</form>
				<?php MS_Helper_Html::settings_footer(); ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		echo '' . $html;
	}

	public function prepare_fields() {
		$model = MS_Addon_Taxamo::model();

		$action = MS_Addon_Taxamo::AJAX_SAVE_SETTING;
		$domain_name = $_SERVER['SERVER_NAME'];

		$fields = array(
			'info' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Setup Taxamo', MS_TEXT_DOMAIN ),
				'desc' => sprintf(
					__( 'Before you can use the <strong>Taxamo API</strong> you must <a href="%1$s">get an Taxamo account</a> here.<br />After you login to Taxamo you can <a href="%2$s">find your API keys here</a>.<br />Also remember to add your domain "<code>%3$s</code>" in <a href="%4$s">your taxamo javascript settings</a>!', MS_TEXT_DOMAIN ),
					'http://www.taxamo.com/" target="_blank',
					'https://dashboard.taxamo.com/merchant/app.html#/account/api" target="_blank',
					esc_html( $domain_name ),
					'https://dashboard.taxamo.com/merchant/app.html#/account/api/javascript" target="_blank'
				),
				'label_class' => 'no-click',
			),

			'gateway_setup' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Getting paid', MS_TEXT_DOMAIN ),
				'desc' => sprintf(
					__( 'Taxamo supports the gateways PayPal and Stripe - you can simply activate the gateway in your Taxamo dashboard:<br /><a href="%1$s">Setup PayPal</a><br /><a href="%2$s">Setup Stripe</a>', MS_TEXT_DOMAIN ),
					'https://dashboard.taxamo.com/merchant/app.html#/account/payment-gateways/paypal" target="_blank',
					'https://dashboard.taxamo.com/merchant/app.html#/account/payment-gateways/stripe" target="_blank'
				),
				'label_class' => 'no-click',
			),

			'sep0' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'is_live' => array(
				'id' => 'is_live',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'before' => __( 'I\'m testing', MS_TEXT_DOMAIN ),
				'after' => __( 'Live mode', MS_TEXT_DOMAIN ),
				'value' => $model->get( 'is_live' ),
				'ajax_data' => array(
					'field' => 'is_live',
					'action' => $action,
				),
			),

			'sep1' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'test_public_key' => array(
				'id' => 'test_public_key',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Test mode', MS_TEXT_DOMAIN ),
				'desc' => __( 'Public Token', MS_TEXT_DOMAIN ),
				'placeholder' => __( 'public_test_...', MS_TEXT_DOMAIN ),
				'value' => $model->get( 'test_public_key' ),
				'class' => 'ms-text-large',
				'ajax_data' => array(
					'field' => 'test_public_key',
					'action' => $action,
				),
			),

			'test_private_key' => array(
				'id' => 'test_private_key',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'desc' => __( 'Private Token', MS_TEXT_DOMAIN ),
				'placeholder' => __( 'priv_test_...', MS_TEXT_DOMAIN ),
				'value' => $model->get( 'test_private_key' ),
				'class' => 'ms-text-large',
				'ajax_data' => array(
					'field' => 'test_private_key',
					'action' => $action,
				),
			),

			'sep2' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'live_public_key' => array(
				'id' => 'live_public_key',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'Live mode', MS_TEXT_DOMAIN ),
				'desc' => __( 'Public Token', MS_TEXT_DOMAIN ),
				'placeholder' => __( 'public_...', MS_TEXT_DOMAIN ),
				'value' => $model->get( 'live_public_key' ),
				'class' => 'ms-text-large',
				'ajax_data' => array(
					'field' => 'live_public_key',
					'action' => $action,
				),
			),

			'live_private_key' => array(
				'id' => 'live_private_key',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'desc' => __( 'Private Token', MS_TEXT_DOMAIN ),
				'placeholder' => __( 'priv_...', MS_TEXT_DOMAIN ),
				'value' => $model->get( 'live_private_key' ),
				'class' => 'ms-text-large',
				'ajax_data' => array(
					'field' => 'live_private_key',
					'action' => $action,
				),
			),
		);

		return $fields;
	}
}