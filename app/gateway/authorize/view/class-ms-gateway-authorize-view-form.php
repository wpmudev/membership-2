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
					<div class="ms-validation-error"><p><?php echo $this->data['auth_error']; ?></p></div>
				<?php endif; ?>

				<?php $this->render_cim_profiles( $fields ); ?>

				<form id="ms-authorize-extra-form" method="post" class="ms-form">
					<?php foreach ( $fields['hidden'] as $field ): ?>
						<?php MS_Helper_Html::html_element( $field ); ?>
					<?php endforeach;?>

					<div id="ms-authorize-card-wrapper">
						<table class="form-table ms-form-table">
							<tr>
								<td class="ms-title-row" colspan="2">
									<?php _e( 'Credit Card Information', 'membership2' ); ?>
								</td>
							</tr>
							<tr>
								<td class="ms-card-info" colspan="2">
									<table border="0" width="100%" cellspacing="0" cellpadding="0">
										<tr>
										<td class="ms-col-card_num">
											<?php MS_Helper_Html::html_element( $fields['card']['card_num'] ); ?>
										</td>
										<td class="ms-col-card_code">
											<?php MS_Helper_Html::html_element( $fields['card']['card_code'] ); ?>
										</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td class="ms-col-expire" colspan="2">
									<?php MS_Helper_Html::html_element( $fields['card']['exp_month'] ); ?>
									<?php MS_Helper_Html::html_element( $fields['card']['exp_year'] ); ?>
								</td>
							</tr>
							<tr>
								<td class="ms-title-row" colspan="2">
									<?php _e( 'Billing Information', 'membership2' ); ?>
								</td>
							</tr>
							<tr>
								<td class="ms-col-first_name">
									<?php MS_Helper_Html::html_element( $fields['billing']['first_name'] ); ?>
								</td>
								<td class="ms-col-last_name">
									<?php MS_Helper_Html::html_element( $fields['billing']['last_name'] ); ?>
								</td>
							</tr>
							<tr>
								<td class="ms-col-country" colspan="2">
									<?php MS_Helper_Html::html_element( $fields['billing']['country'] ); ?>
								</td>
							</tr>
							<?php if ( ! empty( $fields['extra'] ) ) : ?>
							<?php foreach ( $fields['extra'] as $field ) : ?>
							<tr>
								<td class="ms-col-<?php echo esc_attr( $field['id'] ); ?>" colspan="2">
									<?php MS_Helper_Html::html_element( $field ); ?>
								</td>
							</tr>
							<?php endforeach; ?>
							<?php endif; ?>
							<tr>
								<td class="ms-col-submit" colspan="2">
									<?php MS_Helper_Html::html_element( $fields['submit'] ); ?>
								</td>
							</tr>
						</table>
					</div>
				</form>
				<div class="clear"></div>
			</div>
		<?php
		$html = ob_get_clean();

		echo $html;
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

		$months = array( '' => __( 'Month', 'membership2' ) );
		for ( $i = 1, $date = new DateTime( '01-01-1970' ); $i <= 12; $date->setDate( 2013, ++$i, 1 ) ) {
			$months[ $i ] = $date->format( 'm - M' );
		}

		$years = array( '' => __( 'Year', 'membership2' ) );
		for ( $i = gmdate( 'Y' ), $maxYear = $i + 15; $i <= $maxYear; $i++ ) {
			$years[ $i ] = $i;
		}

		$fields['card'] = array(
			'card_num' => array(
				'id' => 'card_num',
				'title' => __( 'Card Number', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'placeholder' => '•••• •••• •••• ••••',
				'maxlength' => 24, // 20 digits + 4 spaces
			),
			'card_code' => array(
				'id' => 'card_code',
				'title' => __( 'Card Code', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'placeholder' => 'CVC',
				'maxlength' => 4,
			),
			'exp_month' => array(
				'id' => 'exp_month',
				'title' => __( 'Expires', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $months,
				'class' => 'ms-select',
			),
			'exp_year' => array(
				'id' => 'exp_year',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $years,
				'class' => 'ms-select',
			),
		);
		$fields['billing'] = array(
			'first_name' => array(
				'id' => 'first_name',
				'title' => __( 'First Name', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'placeholder' => __( 'First Name', 'membership2' ),
			),
			'last_name' => array(
				'id' => 'last_name',
				'title' => __( 'Last Name', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
				'placeholder' => __( 'Last Name', 'membership2' ),
			),
			'country' => array(
				'id' => 'country',
				'title' => __( 'Country', 'membership2' ),
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $this->data['countries'],
				'class' => 'ms-select',
			),
		);
		$fields['submit'] = array(
			'id' => 'submit',
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Pay now', 'membership2' ),
		);

		// Can be populated via the filter to add extra fields to the form.
		$fields['extra'] = array();

		if ( 'update_card' == $this->data['action'] ) {
			$fields['submit']['value'] = __( 'Change card', 'membership2' );
		}

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
	protected function render_cim_profiles( $fields ) {
		// if profile is empty, then return
		if ( empty( $this->data['cim_profiles'] ) ) {
			return;
		}

		$gateway = MS_Model_Gateway::factory( MS_Gateway_Authorize::ID );
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
		$options[ 0 ] = __( 'Enter a new credit card', 'membership2' );
		$cim = array(
			'id' => 'profile',
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
			'field_options' => $options,
			'value' => $first_key,
		);
		if ( $this->data['cim_payment_profile_id'] ) {
			$cim['value'] = $this->data['cim_payment_profile_id'];
		}
		$card_cvc = array(
			'id' => 'card_code',
			'title' => __( 'Enter the credit cards CVC code to verify the payment', 'membership2' ),
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			'placeholder' => 'CVC',
			'maxlength' => 4,
		);
		?>
		<form id="ms-authorize-extra-form" method="post" class="ms-form">
			<?php foreach ( $fields['hidden'] as $field ): ?>
				<?php MS_Helper_Html::html_element( $field ); ?>
			<?php endforeach;?>

			<div id="ms-authorize-cim-profiles-wrapper" class="authorize-form-block">
				<table>
					<tr>
						<td class="ms-title-row"><?php _e( 'Stored Credit Cards', 'membership2' ); ?></td>
					</tr>
					<tr>
						<td class="ms-col-cim_profiles">
						<?php MS_Helper_Html::html_element( $cim );?>
						</td>
					</tr>
					<?php if ( lib3()->is_true( $gateway->secure_cc ) ) : ?>
					<tr class="ms-row-card_cvc">
						<td>
						<?php MS_Helper_Html::html_element( $card_cvc ); ?>
						</td>
					</tr>
					<?php endif; ?>
					<tr class="ms-row-submit">
						<td class="ms-col-submit">
							<?php MS_Helper_Html::html_element( $fields['submit'] ); ?>
						</td>
					</tr>
				</table>
			</div>
		</form>
		<?php
	}
}