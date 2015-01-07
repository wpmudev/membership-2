<?php

class MS_View_Mailchimp_General extends MS_View {

	protected $fields = array();

	protected $data;

	public function render_tab() {
		$this->prepare_fields();
		ob_start();
		?>
		<div class='ms-wrap'>
			<div class='ms-settings'>
				<?php
				MS_Helper_Html::settings_tab_header(
					array( 'title' => __( 'MailChimp Settings', MS_TEXT_DOMAIN ) )
				);
				?>

				<form action="" method="post">
					<?php MS_Helper_Html::settings_box( $this->fields ); ?>
				</form>
				<?php MS_Helper_Html::settings_footer( null, false ); ?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		echo '' . $html;
	}

	public function prepare_fields() {
		$api_status = MS_Integration_Mailchimp::get_api_status();
		$settings = $this->data['settings'];

		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;
		$nonce = wp_create_nonce( $action );

		$this->fields = array(
				'mailchimp_api_test' => array(
						'id' => 'mailchimp_api_test',
						'type' => MS_Helper_Html::TYPE_HTML_TEXT,
						'title' => __( 'MailChimp API test status: ', MS_TEXT_DOMAIN ),
						'value' => ( $api_status ) ? __( 'Verified', MS_TEXT_DOMAIN ) : __( 'Failed', MS_TEXT_DOMAIN ),
						'class' => ( $api_status ) ? 'ms-ok' : 'ms-nok',
				),
				'mailchimp_api_key' => array(
						'id' => 'mailchimp_api_key',
						'name' => 'custom[mailchimp][api_key]',
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
						'title' => __( 'MailChimp API Key', MS_TEXT_DOMAIN ),
						'desc' => '<div>Visit your <a target="_blank" href="http://admin.mailchimp.com/account/api">your API dashboard</a> to create an API Key.</div>', //i18n here please!
						'value' => $settings->get_custom_setting( 'mailchimp', 'api_key' ),
						'class' => 'ms-text-medium',
						'data_ms' => array(
								'group' => 'mailchimp',
								'field' => 'api_key',
								'_wpnonce' => $nonce,
								'action' => $action,
						),
				),
				'separator' => array(
						'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
				),
				'auto_opt_in' => array(
						'id' => 'auto_opt_in',
						'name' => 'custom[mailchimp][auto_opt_in]',
						'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
						'title' => __( 'Automatically opt-in new users to the mailing list.', MS_TEXT_DOMAIN ),
						'desc' => __( 'Users will not receive an email confirmation. Use at your own risk.', MS_TEXT_DOMAIN ),
						'value' => $settings->get_custom_setting( 'mailchimp', 'auto_opt_in' ),
						'data_ms' => array(
								'group' => 'mailchimp',
								'field' => 'auto_opt_in',
								'_wpnonce' => $nonce,
								'action' => $action,
						),
				),
				'separator1' => array(
						'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
				),
				'mail_list_registered' => array(
						'id' => 'mail_list_registered',
						'name' => 'custom[mailchimp][mail_list_registered]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Registered users mailing list (not members)', MS_TEXT_DOMAIN ),
						'field_options' => MS_Integration_Mailchimp::get_mail_lists(),
						'value' => $settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ),
						'data_ms' => array(
								'group' => 'mailchimp',
								'field' => 'mail_list_registered',
								'_wpnonce' => $nonce,
								'action' => $action,
						),

				),
				'mail_list_members' => array(
						'id' => 'mail_list_members',
						'name' => 'custom[mailchimp][mail_list_members]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Members mailing list', MS_TEXT_DOMAIN ),
						'field_options' => MS_Integration_Mailchimp::get_mail_lists(),
						'value' => $settings->get_custom_setting( 'mailchimp', 'mail_list_members' ),
						'data_ms' => array(
								'group' => 'mailchimp',
								'field' => 'mail_list_members',
								'_wpnonce' => $nonce,
								'action' => $action,
						),
				),
				'mail_list_deactivated' => array(
						'id' => 'mail_list_deactivated',
						'name' => 'custom[mailchimp][mail_list_deactivated]',
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'title' => __( 'Deactivated memberships mailing list', MS_TEXT_DOMAIN ),
						'field_options' => MS_Integration_Mailchimp::get_mail_lists(),
						'value' => $settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' ),
						'data_ms' => array(
								'group' => 'mailchimp',
								'field' => 'mail_list_deactivated',
								'_wpnonce' => $nonce,
								'action' => $action,
						),

				),
		);
	}
}