<?php

/**
 * The members tax settings editor
 */
class MS_Addon_Taxamo_Userprofile extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();

		$classes = array();
		if ( $fields['vat_number']['valid_country'] ) {
			$classes[] = 'ms-no-manual';
		} elseif ( $fields['declare_manually']['value'] ) {
			$classes[] = 'ms-tax-manual';
		}

		ob_start();
		?>
		<div class="ms-wrap <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<div class="modal-header">
				<button type="button" class="close">&times;</button>
				<h4 class="modal-title"><?php _e( 'Tax Settings', MS_TEXT_DOMAIN ); ?></h4>
			</div>
			<div class="modal-body">

			<?php
			foreach ( $fields as $field ) {
				MS_Helper_Html::html_element( $field );
			}
			?>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default close"><?php _e( 'Close', MS_TEXT_DOMAIN ); ?></button>
				<button type="button" class="btn btn-primary save"><?php _e( 'Save', MS_TEXT_DOMAIN ); ?></button>
			</div>
			<div class="loading-message">
				<?php _e( 'Saving data, please wait...', MS_TEXT_DOMAIN ); ?>
			</div>
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

		$profile = MS_Addon_Taxamo_Api::get_user_profile();
		$countries = MS_Addon_Taxamo_Api::get_country_codes();
		$use_manually = false;
		$action = MS_Addon_Taxamo::AJAX_SAVE_USERPROFILE;
		$nonce = wp_create_nonce( $action );

		if ( $profile->billing_country->code ) {
			$use_manually = $profile->billing_country->code != $profile->detected_country->code;
		}

		$vat_details = '';
		if ( $profile->vat_country->tax_supported ) {
			$vat_details = sprintf(
				__( 'Taxes are calculated for %s. Remove your VAT Number to manually declare your country of residence', MS_TEXT_DOMAIN ),
				'<strong>' . $profile->vat_country->name . '</strong>'
			);
		}

		$fields['detected_country_label'] = array(
			'type' => MS_Helper_Html::TYPE_HTML_TEXT,
			'title' => sprintf(
				__( 'We have detected that your computer is located in %s', MS_TEXT_DOMAIN ),
				'<strong>' . $profile->detected_country->name . '</strong>'
			),
		);
		$fields['detected_country'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'id' => 'detected_country',
			'value' => $profile->detected_country->code,
		);
		$fields['declare_manually'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_CHECKBOX,
			'id' => 'declare_manually',
			'title' => __( 'I want to manually declare my country of residence', MS_TEXT_DOMAIN ),
			'value' => $use_manually,
		);
		$fields['billing_country_code'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
			'id' => 'billing_country',
			'title' => __( 'My country of residence', MS_TEXT_DOMAIN ),
			'value' => $profile->billing_country->code,
			'field_options' => $countries,
			'wrapper_class' => 'is-manual manual-country',
		);
		$fields['vat_number'] = array(
			'type' => MS_Helper_Html::INPUT_TYPE_TEXT,
			'id' => 'vat_number',
			'title' => __( 'VAT Number', MS_TEXT_DOMAIN ),
			'desc' => __( 'Fill this field if you are representing EU VAT payer', MS_TEXT_DOMAIN ),
			'wrapper_class' => 'vat_number_field',
			'value' => $profile->vat_number,
			'valid_country' => $profile->vat_country->tax_supported,
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