<?php

class MS_Gateway_Authorize_View_Form extends MS_View {

	public function to_html() {
		// let 3rd party themes/plugins use their own form
		if ( ! apply_filters( 'ms_gateway_authorize_view_form_to_html', true, $this ) ) {
			return;
		}

		$fields = $this->prepare_fields();
		ob_start();
		// Render tabbed interface.
		?>
			<div class="ms-wrap">
				<?php if ( $this->data['auth_error'] ): ?>
					<div class="ms-validation-error"><p><?php echo '' . $this->data['auth_error']; ?></p></div>
				<?php endif; ?>
				<form id="ms-authorize-extra-form" method="post" class="ms-form">
					<?php foreach ( $fields['hidden'] as $field ): ?>
						<?php MS_Helper_Html::html_element( $field ); ?>
					<?php endforeach;?>

					<?php $this->render_cim_profiles() ?>
					<div id="ms-authorize-card-wrapper">
						<?php _e( 'Credit Card Information', MS_TEXT_DOMAIN ); ?>
						<table class="form-table ms-form-table">
							<tbody>
								<tr>
									<td>
										<?php MS_Helper_Html::html_element( $fields['card']['card_num'] ); ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php MS_Helper_Html::html_element( $fields['card']['card_code'] ); ?>
									</td>
								</tr>
								<tr>
									<td>
										<?php MS_Helper_Html::html_element( $fields['card']['exp_month'] ); ?>
										<?php MS_Helper_Html::html_element( $fields['card']['exp_year'] ); ?>
									</td>
								</tr>
							</tbody>
						</table>
						<?php _e( 'Billing Information', MS_TEXT_DOMAIN ); ?>
						<table class="form-table ms-form-table">
							<tbody>
								<?php foreach ( $fields['billing'] as $field ): ?>
									<tr>
										<td>
											<?php MS_Helper_Html::html_element( $field ); ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<?php
						MS_Helper_Html::html_submit(
							array(
								'value' => ( 'update_card' == $this->data['action'] )
									? __( 'Change card', MS_TEXT_DOMAIN )
									: __( 'Pay now', MS_TEXT_DOMAIN ),
							)
						);
					?>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();

		echo '' . $html;
	}

	public function prepare_fields() {
		$currency = MS_Plugin::instance()->settings->currency;
		$fields['hidden'] = array(
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
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce( "{$this->data['gateway']}_{$this->data['ms_relationship_id']}" ),
			),
			'action' => array(
				'id' => 'action',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['action'],
			),
			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => MS_Controller_Frontend::STEP_PROCESS_PURCHASE,
			),
		);

		$months = array( '' => __( 'Month', MS_TEXT_DOMAIN ) );
		for ( $i = 1, $date = new DateTime( '01-01-1970' ); $i <= 12; $date->setDate( 2013, ++$i, 1 ) ) {
			$months[ $i ] = $date->format( 'm - M' );
		}

		$years = array( '' => __( 'Year', MS_TEXT_DOMAIN ) );
		for ( $i = gmdate( 'Y' ), $maxYear = $i + 15; $i <= $maxYear; $i++ ) {
			$years[ $i ] = $i;
		}

		$fields['card'] = array(
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
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $years,
			),
		);
		$fields['billing'] = array(
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
			'country' => array(
				'id' => 'country',
				'title' => __( 'Country', MS_TEXT_DOMAIN ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $this->data['countries'],
				'class' => 'ms-select',
			),
		);

		return apply_filters(
			'ms_gateway_authorize_view_form_prepare_fields',
			$fields
		);
	}

	/**
	 * Renders Authorize.net CIM profiles.
	 *
	 * @since  1.0.0
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
				$options[ $profile['customerPaymentProfileId'] ] = esc_html(
					sprintf(
						"%s %s's - **** **** **** %s ",
						$profile['billTo']['firstName'],
						$profile['billTo']['lastName'],
						str_replace( 'XXXX', '', $profile['payment']['creditCard']['cardNumber'] )
					)
				);
				if ( ! $first_key ) {
					$first_key = $profile['customerPaymentProfileId'];
				}
			}
		}
		$options[ 0 ] = __( 'Enter a new credit card', MS_TEXT_DOMAIN );
		$cim = array(
			'id' => 'profile',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
			'field_options' => $options,
			'value' => ( $this->data['cim_payment_profile_id'] ) ? $this->data['cim_payment_profile_id'] : $first_key,
		);
		?>
		<div id="ms-authorize-cim-profiles-wrapper" class="authorize-form-block">
			<div class="authorize-form-block-title"><?php _e( 'Credit card:', MS_TEXT_DOMAIN ); ?></div>
			<?php MS_Helper_Html::html_element( $cim );?>
		</div>
		<?php
	}
}