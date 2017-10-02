<?php

/**
 * Hustle View
 *
 * @since 1.1.2
 */
class MS_Addon_Hustle_View extends MS_View {

	/**
	 * Returns the HTML code of the Settings form.
	 *
	 * @since  1.1.2
	 * @return string
	 */
	public function render_tab() {
		$settings 	= $this->data['settings'];
		$provider 	= $settings->get_custom_setting( 'hustle', 'hustle_provider' );
		$fields 	= $this->prepare_fields( $provider );
		ob_start();
		?>
		<div class="ms-addon-wrap">
			<?php
			MS_Helper_Html::settings_tab_header(
				array( 'title' => __( 'Hustle Integration', 'membership2' ) )
			);

			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>
			<div class="ms-hustle-provider-details">
				<div class="ms-hustle-response notice notice-error is-dismissible" style="display:none">
					<p></p>
				</div>
				<?php
					if ( $provider && !empty( $provider ) ) {
						$provider_details = $settings->get_custom_setting( 'hustle', $provider );
						$this->render_provider_details( $settings, $provider, $provider_details );
						?>
							<div id="optin-provider-account-options" class="wpmudev-provider-block ms-hustle-provider-list-details">
								<div class="ms-bold"><?php echo __( 'Press the Fetch Lists button to update value.', Opt_In::TEXT_DOMAIN ); ?></div>
								<?php
								$separator = MS_Helper_Html::html_element( array(
												'type' => MS_Helper_Html::TYPE_HTML_SEPARATOR,
											), true );
								if ( $provider_details && is_array( $provider_details ) && is_array( $provider_details['lists'] ) ) {
									$subscription_options 		= MS_Addon_Hustle::subscription_list_types();
									$provider_details_list 		= $provider_details['lists'];
									$output = "";
									foreach ( $subscription_options as $key => $subscription_option ) {
										if ( isset( $provider_details_list[$key] ) ) {
											foreach ( $provider_details_list[$key] as $provider_detail ) {
												$output .= $subscription_option . " : " . $provider_detail['text'] ;
												$output .= $separator;
											}
										}
									}
									echo $output;
									
								} 
								?>
							</div>
						<?php
					} 
					?>
			</div>
			
		</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}

	/**
	 * Prepare fields that are displayed in the form.
	 *
	 * @since  1.1.2
	 *
	 * @param string $provider - the hustle provider
	 *
	 * @return array
	 */
	protected function prepare_fields( $provider ) {
		$action = MS_Controller_Settings::AJAX_ACTION_UPDATE_CUSTOM_SETTING;
		$fields = array(
			'hustle_providers' => array(
				'id' 			=> 'hustle_provider',
				'name' 			=> 'custom[hustle][hustle_provider]',
				'type' 			=> MS_Helper_Html::INPUT_TYPE_SELECT,
				'class' 		=> 'ms-text-large',
				'title' 		=> __( 'Available Hustle Integrations', 'membership2' ),
				'field_options' => MS_Addon_Hustle::hustle_providers(),
				'value' 		=> $provider,
				'ajax_data' 	=> array(
					'group' 		=> 'hustle',
					'field' 		=> 'hustle_provider',
					'action' 		=> $action,
				),
			),
		);

		return $fields;
	}

	/**
	 * Render provider details
	 *
	 * @since  1.1.2
	 *
	 * @param object $settings
	 * @param string $current_provider - the hustle provider
	 *
	 * @return string
	 */
	protected function render_provider_details( $settings, $current_provider, $provider_details ) {
		global $hustle;
		if ( $current_provider && !empty( $current_provider ) ) {
			$provider = Opt_In::get_provider_by_id( $current_provider );

			if ( $provider ) { 
				$provider_instance 	= Opt_In::provider_instance( $provider );
				$options 			= $provider_instance->get_account_options( false );
				foreach ( $options as $key =>  $option ) {
					
					if ( $option['type'] === 'text') {
						$option['class'] 				= "wpmui-field-input wpmui-text";
						$option['attributes']['style'] 	= "width:80%";
						$name = $option['name'];
						if ( $provider_details && is_array( $provider_details ) ) {
							if ( isset( $provider_details[ $name ] ) ) {
								$option['value'] = $provider_details[ $name ];
							}
						}
					}
					if ( isset ( $option['elements'] ) && is_array( $option['elements'] ) ) {
						foreach ( $option['elements'] as  &$element ) {
							if ( $element['type'] === 'text') {
								$element['class'] 				= "wpmui-field-input wpmui-text";
								$element['attributes']['style'] = "width:80%";
								$name = $element['name'];
								if ( $provider_details && is_array( $provider_details ) ) {
									if ( isset( $provider_details[ $name ] ) ) {
										$element['value'] = $provider_details[ $name ];
									}
								}
								
							} else if ( $element['type'] === 'ajax_button') {
								if ( preg_match( "/<[^<]+>/",$element['value'], $m ) != 0 ) {
									$element['value'] = __( "Fetch Lists", "membership2" );
								}
								$element['type'] 						= "button";
								$element['class'] 						= "button-primary ms_optin_refresh_provider_details button";
								$element['attributes']['style'] 		= "margin-top:6px;";
								$element['attributes']['data-nonce'] 	= wp_create_nonce('refresh_provider_details');
								$element['attributes']['data-provider'] = $current_provider;
							}
						}
					}
					$option = apply_filters( "wpoi_optin_filter_optin_options", $option, $optin );
					$hustle->render( "general/option", array_merge( $option, array( "key" => $key ) ));
				}
			}
		}
	}
}
?>