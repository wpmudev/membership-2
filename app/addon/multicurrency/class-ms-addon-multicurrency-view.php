<?php

class MS_Addon_MultiCurrency_View extends MS_View {
    
        const AJAX_ACTION_SAVE_CURRENCIES = 'ms_ajax_save_currencies';
        const AJAX_ACTION_GET_RATE_CHANGER = 'ms_ajax_get_rate_changer';
        const AJAX_ACTION_SAVE_RATE_CHANGER = 'ms_ajax_save_rate_changer';

	/**
	 * Returns the HTML code of the Settings form.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function render_tab() {
		$fields = $this->prepare_fields();
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Multi Currency Settings', 'membership2' ) )
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
        
        public function get_currencies() {
            //return $settings->get_currencies();
        }

	/**
	 * Prepare fields that are displayed in the form.
	 *
	 * @since  1.0.1.0
	 * @return array
	 */
	protected function prepare_fields() {
		$settings = $this->data['settings'];
                
                $action = MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;
		$enable = $settings->get_custom_setting( 'multicurrency', 'enable' );
                $currencies = $settings->get_custom_setting( 'multicurrency', 'currencies' );
                
		$enable = lib3()->is_true( $enable );

		$fields = array(
                        
                        'enable' => array(
				'id' => 'enable',
				'name' => 'custom[multicurrency][enable]',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Enable Multi Currency Addon', 'membership2' ),
				'value' => $enable,
				'class' => 'inp-before',
				'ajax_data' => array(
					'group' => 'multicurrency',
					'field' => 'enable',
					'action' => $action,
				),
			),
                        
                        'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
                        
                        'currency' => array(
                                'id' => 'ms_custom_multi_currency',
                                'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
                                'title' => __( 'Select currencies that you want to enable', 'membership2' ),
                                'value' => $currencies,
                                'field_options' => $settings->get_currencies(),
                                'multiple' => true,
                                'class' => 'ms-memberships',
                                'ajax_data' => array(
                                        'action' => self::AJAX_ACTION_SAVE_CURRENCIES
                                ),
                        ),
                        
                        'separator2' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),
                        
                        'set_rate' => array(
                                'type' => MS_Helper_Html::TYPE_HTML_TEXT,
                                'title' => __( 'Set currency covertion rate', 'membership2' ),
                                'desc' => __( 'This rate should be respected to your default currency.', 'membership2' ),
                        ),
                        
                        'set_rate_html' => array(
                                'type' => MS_Helper_Html::TYPE_HTML_TEXT,
                                'title' => __( '<div class="ms_currency_rate">&nbsp;</div>', 'membership2' )
                        ),
                        
                        
                        
                        
                        
                        
                        
                        
                        
                        
			/*'mailchimp_api_test' => array(
				'id' => 'mailchimp_api_test',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'MailChimp API test status: ', 'membership2' ),
				'value' => ( $api_status ) ? __( 'Verified', 'membership2' ) : __( 'Failed', 'membership2' ),
				'class' => ( $api_status ) ? 'ms-ok' : 'ms-nok',
			),

			'mailchimp_api_key' => array(
				'id' => 'mailchimp_api_key',
				'name' => 'custom[mailchimp][api_key]',
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'title' => __( 'MailChimp API Key', 'membership2' ),
				'desc' => sprintf(
					'<div>' . __( 'Visit <a href="%1$s">your API dashboard</a> to create an API Key.', 'membership2' ) . '</div>',
					'http://admin.mailchimp.com/account/api" target="_blank'
				),
				'value' => $settings->get_custom_setting( 'mailchimp', 'api_key' ),
				'class' => 'ms-text-medium',
				'ajax_data' => array(
					'group' => 'mailchimp',
					'field' => 'api_key',
					'action' => $action,
				),
			),

			'separator' => array(
				'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
			),

			'auto_opt_in' => array(
				'id' => 'auto_opt_in',
				'name' => 'custom[mailchimp][auto_opt_in]',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO_SLIDER,
				'title' => __( 'Automatically opt-in new users to the mailing list.', 'membership2' ),
				'desc' => __( 'Users will not receive an email confirmation. You are responsible to inform your users.', 'membership2' ),
				'value' => $auto_opt_in,
				'class' => 'inp-before',
				'ajax_data' => array(
					'group' => 'mailchimp',
					'field' => 'auto_opt_in',
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
				'title' => __( 'Registered users mailing list (not members)', 'membership2' ),
				'field_options' => MS_Addon_Mailchimp::get_mail_lists(),
				'value' => $settings->get_custom_setting( 'mailchimp', 'mail_list_registered' ),
				'ajax_data' => array(
					'group' => 'mailchimp',
					'field' => 'mail_list_registered',
					'action' => $action,
				),
			),

			'mail_list_members' => array(
				'id' => 'mail_list_members',
				'name' => 'custom[mailchimp][mail_list_members]',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Members mailing list', 'membership2' ),
				'field_options' => MS_Addon_Mailchimp::get_mail_lists(),
				'value' => $settings->get_custom_setting( 'mailchimp', 'mail_list_members' ),
				'ajax_data' => array(
					'group' => 'mailchimp',
					'field' => 'mail_list_members',
					'action' => $action,
				),
			),

			'mail_list_deactivated' => array(
				'id' => 'mail_list_deactivated',
				'name' => 'custom[mailchimp][mail_list_deactivated]',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Deactivated memberships mailing list', 'membership2' ),
				'field_options' => MS_Addon_Mailchimp::get_mail_lists(),
				'value' => $settings->get_custom_setting( 'mailchimp', 'mail_list_deactivated' ),
				'ajax_data' => array(
					'group' => 'mailchimp',
					'field' => 'mail_list_deactivated',
					'action' => $action,
				),
			),*/
		);

		return $fields;
	}
}