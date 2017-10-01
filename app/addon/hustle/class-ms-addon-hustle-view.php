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

			$this->render_provider_details( $settings, $provider );
			?>
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
	 */
	protected function render_provider_details( $settings, $current_provider ) {
		global $hustle;
		if ( $current_provider && !empty( $current_provider ) ) {
			$provider = Opt_In::get_provider_by_id( $current_provider );

			if ( $provider ) { 
				$provider_instance 	= Opt_In::provider_instance( $provider );
				$options 			= $provider_instance->get_account_options( false );
				foreach ( $options as $key =>  $option ) {
					if ( $option['type'] === 'wrapper'  ){ $option['apikey'] = ''; }
					
					if ( $option['type'] === 'text') {
						$option['class'] 				= "wpmui-field-input wpmui-text";
						$option['attributes']['style'] 	= "width:80%";
					}
					if ( isset ( $option['elements'] ) && is_array( $option['elements'] ) ) {
						foreach ( $option['elements'] as  &$element ) {
							if ( $element['type'] === 'text') {
								$element['class'] 				= "wpmui-field-input wpmui-text";
								$element['attributes']['style'] = "width:80%";
								
							} else if ( $element['type'] === 'ajax_button') {
								if ( preg_match( "/<[^<]+>/",$element['value'], $m ) != 0 ) {
									$element['value'] = __( "Fetch Lists", "membership2" );
								}
								$element['type'] 				= "button";
								$element['class'] 				= "button-primary " . $element['class'];
								$element['attributes']['style'] = "margin-top:6px;";
							}
						}
					}
					$option = apply_filters( "wpoi_optin_filter_optin_options", $option, $optin );
					$hustle->render( "general/option", array_merge( $option, array( "key" => $key ) ));
				}
				?>
				<div id="optin-provider-account-options" class="wpmudev-provider-block">
                                                    
					<div id="optin-provider-account-selected-list" class="wpmudev-provider-block" data-nonce="<?php echo wp_create_nonce('optin_provider_current_settings') ?>" >

						<label class="wpmudev-label--notice">
							
							<span><?php echo __('Selected list (campaign), Press the Fetch Lists button to update value.', "membership2" ); ?></span>
							
						</label>
						
					</div>
				
				</div>
				<?php
			}
		}
	}
}
?>