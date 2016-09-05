<?php

/**
 * The Settings-Form
 */
class MS_Addon_Taxamo_View extends MS_View {

	public function render_tab() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Taxamo Settings', 'membership2' ) )
			);

			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	public function prepare_fields() {
		$model = MS_Addon_Taxamo::model();

		$action = MS_Addon_Taxamo::AJAX_SAVE_SETTING;
		$domain_name = $_SERVER['SERVER_NAME'];

		$fields = array(
			'info' => array(
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Setup Taxamo', 'membership2' ),
				'desc' => sprintf(
					__( 'Before you can use the <strong>Taxamo API</strong> you must <a href="%1$s">get an Taxamo account</a> here.<br />After you login to Taxamo you can <a href="%2$s">find your API keys here</a>.<br />Also remember to add your domain "<code>%3$s</code>" in <a href="%4$s">your taxamo javascript settings</a>!', 'membership2' ),
					'http://www.taxamo.com/" target="_blank',
					'https://dashboard.taxamo.com/merchant/app.html#/account/api" target="_blank',
					esc_html( $domain_name ),
					'https://dashboard.taxamo.com/merchant/app.html#/account/api/javascript" target="_blank'
				),
				'label_class' => 'no-click',
			),

			'sep0' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'is_live' => array(
				'id' => 'is_live',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'before' => __( 'I\'m testing', 'membership2' ),
				'after' => __( 'Live mode', 'membership2' ),
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
				'title' => __( 'Test mode', 'membership2' ),
				'desc' => __( 'Public Token', 'membership2' ),
				'placeholder' => __( 'public_test_...', 'membership2' ),
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
				'desc' => __( 'Private Token', 'membership2' ),
				'placeholder' => __( 'priv_test_...', 'membership2' ),
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
				'title' => __( 'Live mode', 'membership2' ),
				'desc' => __( 'Public Token', 'membership2' ),
				'placeholder' => __( 'public_...', 'membership2' ),
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
				'desc' => __( 'Private Token', 'membership2' ),
				'placeholder' => __( 'priv_...', 'membership2' ),
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