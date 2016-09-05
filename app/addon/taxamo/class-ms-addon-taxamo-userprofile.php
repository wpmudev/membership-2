<?php

/**
 * The members tax settings editor
 */
class MS_Addon_Taxamo_Userprofile extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();

		$classes = array();
		$classes[] = 'ms-tax-' . $fields['country_choice']['value'];

		ob_start();
		?>
		<div class="ms-wrap <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<div class="modal-header">
				<button type="button" class="close">&times;</button>
				<h4 class="modal-title"><?php _e( 'Tax Settings', 'membership2' ); ?></h4>
			</div>
			<div class="modal-body">

			<?php
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default close"><?php _e( 'Close', 'membership2' ); ?></button>
				<button type="button" class="btn btn-primary save"><?php _e( 'Save', 'membership2' ); ?></button>
			</div>
			<div class="loading-message">
				<?php _e( 'Saving data, please wait...', 'membership2' ); ?>
			</div>
		</div>
		<div class="body-messages">
			<div class="ms-tax-loading-overlay"></div>
			<div class="ms-tax-loading-message"><?php _e( 'Refreshing page, please wait...', 'membership2' ); ?></div>
		</div>
		<?php
		$html = ob_get_clean();

		return apply_filters(
			'ms_addon_taxamo_userprofile',
			$html
		);
	}

	public function prepare_fields() {
		$fields = array();
		$invoice_id = false;

		if ( isset( $this->data['invoice'] ) ) {
			$invoice = $this->data['invoice'];
			$invoice_id = $invoice->id;
		}

		$profile = MS_Addon_Taxamo_Api::get_tax_profile();
		$countries = MS_Addon_Taxamo_Api::get_country_codes();
		$action = MS_Addon_Taxamo::AJAX_SAVE_USERPROFILE;
		$nonce = wp_create_nonce( $action );

		$country_options = array(
			'auto' => sprintf(
				__( 'The detected country %s is correct.', 'membership2' ),
				'<strong>' . $profile->detected_country->name . '</strong>'
			),
			'vat' => __( 'I have an EU VAT number and want to use it for tax declaration.', 'membership2' ),
			'declared' => __( 'Manually declare my country of residence.', 'membership2' ),
		);

		$vat_details = '';
		if ( ! empty( $profile->vat_number ) && $profile->vat_valid ) {
			$vat_details = sprintf(
				__( 'This is a valid VAT number of %s. By using this you are are now exempt of VAT.', 'membership2' ),
				'<strong>' . $profile->vat_country->name . '</strong>'
			);
		} else {
			$vat_details = __( 'VAT Number is invalid.', 'membership2' );
		}
		if ( $profile->use_vat_number ) {
			$tax_message = __( 'Valid EU VAT Number provided: You are exempt of VAT', 'membership2' );
		} else {
			$tax_message = __( 'The country used for tax calculation is %s', 'membership2' );
		}

		$fields['tax_country_label'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TEXT,
			'title' => sprintf(
				$tax_message,
				'<strong>' . $profile->tax_country->name . '</strong>'
			),
			'wrapper_class' => 'effective_tax_country',
		);
		$fields['detected_country_label'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TEXT,
			'title' => sprintf(
				__( 'We have detected that your computer is located in %s', 'membership2' ),
				'<strong>' . $profile->detected_country->name . '</strong>'
			),
		);
		$fields['detected_country'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'id' => 'detected_country',
			'value' => $profile->detected_country->code,
		);
		$fields['country_choice'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
			'id' => 'country_choice',
			'class' => 'country_choice',
			'value' => $profile->country_choice,
			'field_options' => $country_options,
		);
		$fields['declared_country_code'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'id' => 'declared_country',
			'title' => __( 'My country of residence', 'membership2' ),
			'desc' => __( 'I confirm that I am established, have my permanent address, or usually reside in the following country', 'membership2' ),
			'value' => $profile->declared_country->code,
			'field_options' => $countries,
			'wrapper_class' => 'manual_country_field',
		);
		$fields['vat_number'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			'id' => 'vat_number',
			'title' => __( 'EU VAT Number', 'membership2' ),
			'desc' => __( 'Fill this field if you are representing EU VAT payer', 'membership2' ),
			'wrapper_class' => 'vat_number_field',
			'value' => $profile->vat_number,
			'valid_country' => $profile->vat_country->vat_valid,
			'after' => $vat_details,
		);
		$fields['invoice_id'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'id' => 'invoice_id',
			'value' => $invoice_id,
		);
		$fields['action'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'id' => 'action',
			'value' => $action,
		);
		$fields['_wpnonce'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'id' => '_wpnonce',
			'value' => $nonce,
		);

		/*
		 * 1. Checkbox "I confirm that the country of my main residence is in <country>" (in the payment table!)
		 * 4. When VAT is entered the checkbox is disabled and VAT country is used. Checkbox 1 is hidden.
		 */

		return apply_filters(
			'ms_addon_taxamo_userprofile_fields',
			$fields
		);
	}
}