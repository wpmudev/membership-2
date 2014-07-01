<?php

class MS_View_Gateway_Authorize extends MS_View {

	protected $fields = array();
	
	protected $data;
	
	public function to_html() {
		// let 3rd party themes/plugins use their own form
		if ( ! apply_filters( 'ms_view_gateway_authorize_form_to_html', true, $this ) ) {
			return;
		}
		
		$this->prepare_fields();
		ob_start();
		/** Render tabbed interface. */
		?>
			<div class='ms-wrap'>
				<h2><?php echo __( 'Signup ', MS_TEXT_DOMAIN ); ?> </h2>
				<form id="ms-authorize-extra-form" method="post" class="ms-form">
					<?php foreach( $this->fields['hidden'] as $field ): ?>
						<?php MS_Helper_Html::html_input( $field ); ?>
					<?php endforeach;?>
					<?php $this->render_cim_profiles() ?>
					<div id="ms-authorize-card-wrapper">
						<?php _e( 'Credit Card Information', MS_TEXT_DOMAIN ); ?>
						<table class="form-table">
							<tbody>
								<tr>
									<td>
										<?php MS_Helper_Html::html_input( $this->fields['card']['card_num'] ); ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php MS_Helper_Html::html_input( $this->fields['card']['card_code'] ); ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php MS_Helper_Html::html_input( $this->fields['card']['exp_month'] ); ?>
										<?php MS_Helper_Html::html_input( $this->fields['card']['exp_year'] ); ?>
									</td>
								</tr>
							</tbody>
						</table>
						<?php _e( 'Billing Information', MS_TEXT_DOMAIN ); ?>
						<table class="form-table">
							<tbody>
								<?php foreach( $this->fields['billing'] as $field ): ?>
									<tr>
										<td>
											<?php MS_Helper_Html::html_input( $field ); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php MS_Helper_Html::html_submit(); ?>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();
		echo $html;
	}
	
	public function prepare_fields() {
		$currency = MS_Plugin::instance()->settings->currency;
		$this->fields['hidden'] = array(
				'gateway' => array(
						'id' => 'gateway',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['gateway'],
				),
				'ms_relationship_id' => array(
						'id' => 'ms_relationship_id',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => $this->data['ms_relationship_id'],
				),
				'step' => array(
						'id' => 'step',
						'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
						'value' => 'process_purchase',
				),
		);
		
		$months = array( '' => __( 'Month', MS_TEXT_DOMAIN ) );
		for( $i = 1, $date = new DateTime( '01-01-1970' ); $i <= 12; $date->setDate( 2013, ++$i, 1 ) ) {
			$months[ $i ] = $date->format( 'm - M' ); 
		}
		$years = array( '' => __( 'Year', MS_TEXT_DOMAIN ) );
		for( $i = date( 'Y' ), $maxYear = $i + 15; $i <= $maxYear; $i++ ) {
			$years[ $i ] = $i;
		}
		$this->fields['card'] = array(
				'card_num' => array(
						'id' => 'card_num',
						'title' => __( 'Card Number', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				),
				'card_code' => array(
						'id' => 'card_code',
						'title' => __( 'Security Code', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				),
				'exp_month' => array(
						'id' => 'exp_month',
						'title' => __( 'Expiration Date', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'field_options' => $months,
				),
				'exp_year' => array(
						'id' => 'exp_year',
						'title' => __( '', MS_TEXT_DOMAIN ),
						'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
						'field_options' => $years,
				),
		);
		$this->fields['billing'] = array(
			'first_name' => array(
					'id' => 'first_name',
					'title' => __( 'First Name', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			),
			'last_name' => array(
					'id' => 'last_name',
					'title' => __( 'Last Name', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			),
			'company' => array(
					'id' => 'company',
					'title' => __( 'Company', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			),
			'address' => array(
					'id' => 'address',
					'title' => __( 'Address', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			),
			'city' => array(
					'id' => 'city',
					'title' => __( 'City', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			),
			'state' => array(
					'id' => 'state',
					'title' => __( 'State', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			),
			'zip' => array(
					'id' => 'zip',
					'title' => __( 'Zip code', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			),
			'country' => array(
					'id' => 'country',
					'title' => __( 'Country', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
					'field_options' => $this->data['countries'],
					'class' => 'chosen-select',
			),
			'phone' => array(
					'id' => 'phone',
					'title' => __( 'Phone', MS_TEXT_DOMAIN ),
					'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			),

		);
	}
	
	/**
	 * Renders Authorize.net CIM profiles.
	 *
	 * @since 3.5
	 *
	 * @access protected
	 */
	protected function render_cim_profiles() {
		// if profile is empty, then return
		if ( empty( $this->data['cim_profiles'] ) ) {
			return;
		}
		$cim_profiles = $this->data['cim_profiles'];
			
		// if we have one record in profile, then wrap it into array to make it
		// compatible with case when we have more then one payment methods added
		if ( isset( $cim_profiles['billTo'] ) ) {
			$cim_profiles = array( $cim_profiles );
		}
	
		$first_key = null; 
		foreach ( $cim_profiles as $index => $profile ) {
			if ( is_array( $profile ) && ! empty( $profile['customerPaymentProfileId'] ) ) {
				$options[ $profile['customerPaymentProfileId'] ] =	esc_html( sprintf(
						"%s %s's - XXXXXXX%s - %s, %s, %s",
						$profile['billTo']['firstName'],
						$profile['billTo']['lastName'],
						$profile['payment']['creditCard']['cardNumber'],
						! empty( $profile['billTo']['address'] ) ? $profile['billTo']['address'] : '',
						! empty( $profile['billTo']['city'] ) ? $profile['billTo']['city'] : '',
						! empty( $profile['billTo']['country'] ) ? $profile['billTo']['country'] : ''
				) );
				if( ! $first_key ) {
					$first_key = $profile['customerPaymentProfileId'];
				}
			}
		}
		$options[ 0 ] = __( 'Enter a new credit card', MS_TEXT_DOMAIN );
		$cim = array(
			'id' => 'profile',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
			'field_options' => $options,
			'value' => $first_key,
		); 
		?>
			<div id="ms-authorize-cim-profiles-wrapper" class="authorize-form-block">
				<div class="authorize-form-block-title"><?php _e( 'Payment Profile', MS_TEXT_DOMAIN ); ?></div>
				<?php MS_Helper_Html::html_input( $cim );?>
			</div>
		<?php
	}
}