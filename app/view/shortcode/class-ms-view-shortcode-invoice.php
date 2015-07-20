<?php

class MS_View_Shortcode_Invoice extends MS_View {

	public function to_html() {
		/**
		 * Provide a customized invoice.
		 *
		 * @since  1.0.0
		 */
		$html = apply_filters(
			'ms_shortcode_custom_invoice',
			'',
			$this->data
		);

		if ( ! empty( $html ) ) {
			return $html;
		} else {
			$html = '';
		}

		$invoice = $this->data['invoice'];
		$member = $this->data['member'];
		$subscription = $this->data['ms_relationship'];
		$membership = $this->data['membership'];
		$gateway = $this->data['gateway'];
		$is_free = false;

		$invoice_number = $invoice->get_invoice_number();

		$inv_title = sprintf(
			'<a href="%s">%s</a>',
			get_permalink( $invoice->id ),
			esc_html( __( 'Invoice ', MS_TEXT_DOMAIN ) . $invoice_number )
		);

		if ( $invoice->amount > 0 ) {
			$inv_amount = sprintf(
				'%1$s %2$s',
				$invoice->currency,
				MS_Helper_Billing::format_price( $invoice->amount )
			);
		} else {
			$inv_amount = __( 'Free', MS_TEXT_DOMAIN );
			$is_free = true;
		}

		if ( $invoice->tax ) {
			$inv_taxes = sprintf(
				'%s %s',
				$invoice->currency,
				MS_Helper_Billing::format_price( $invoice->tax )
			);
		} else {
			$inv_taxes = '';
		}

		if ( $invoice->discount ) {
			$inv_discount = sprintf(
				'%s -%s',
				$invoice->currency,
				MS_Helper_Billing::format_price( $invoice->discount )
			);
		} else {
			$inv_discount = '';
		}

		if ( $invoice->pro_rate ) {
			$inv_pro_rate = sprintf(
				'%s -%s',
				$invoice->currency,
				MS_Helper_Billing::format_price( $invoice->pro_rate )
			);
		} else {
			$inv_pro_rate = '';
		}

		$inv_total = sprintf(
			'%s %s',
			$invoice->currency,
			MS_Helper_Billing::format_price( $invoice->total )
		);

		$inv_title = apply_filters( 'ms_invoice_title', $inv_title, $invoice );
		$inv_from = apply_filters( 'ms_invoice_sender', MS_Plugin::instance()->settings->invoice_sender_name, $invoice );
		$inv_to = apply_filters( 'ms_invoice_recipient', $member->username, $invoice, $member );
		$inv_status = apply_filters( 'ms_invoice_status', $invoice->status_text(), $invoice );
		$inv_item_name = apply_filters( 'ms_invoice_item_name', $membership->name, $invoice, $membership );
		$inv_amount = apply_filters( 'ms_invoice_amount', $inv_amount, $invoice );
		$inv_taxes = apply_filters( 'ms_invoice_taxes', $inv_taxes, $invoice );
		$inv_discount = apply_filters( 'ms_invoice_discount', $inv_discount, $invoice );
		$inv_pro_rate = apply_filters( 'ms_invoice_pro_rate', $inv_pro_rate, $invoice );
		$inv_total = apply_filters( 'ms_invoice_total', $inv_total, $invoice );

		$inv_details = apply_filters( 'ms_invoice_description', $invoice->description, $invoice, null );
		$inv_date = apply_filters(
			'ms_invoice_date',
			MS_Helper_Period::format_date( $invoice->invoice_date ),
			$invoice,
			null
		);
		$inv_due_date = apply_filters(
			'ms_invoice_due_date',
			MS_Helper_Period::format_date( $invoice->due_date ),
			$invoice,
			null
		);

		if ( $invoice->uses_trial ) {
			$trial_date = apply_filters(
				'ms_invoice_trial_date',
				MS_Helper_Period::get_period_desc( $membership->trial_period, true ),
				$trial_invoice,
				$invoice
			);
			$trial_date .= sprintf(
				' <small>(%s %s)</small>',
				__( 'ends on', MS_TEXT_DOMAIN ),
				MS_Helper_Period::format_date( $invoice->trial_ends )
			);
		} else {
			$trial_date = '';
		}

		ob_start();
		?>
		<div class="entry-content ms-invoice" id="invoice">
			<?php
			/**
			 * We hardcode the CSS styles into this file, because the shortcode
			 * is also used in Emails, which usually do not load remote CSS
			 * files by default...
			 */
			?>
			<style>
			#invoice table, th, td { margin: 0; font-size: 14px; }
			#invoice table { padding: 0; width: 520px; border: 1px solid #DDD; background-color: #FFF; box-shadow: 0 1px 8px #F0F0F0; }
			#invoice th, td { border: 0; padding: 8px; }
			#invoice th { font-weight: bold; text-align: left; text-transform: none; font-size: 13px; }
			#invoice tr.alt { background-color: #F9F9F9; }
			#invoice tr.sep th,
			#invoice tr.sep td { border-top: 1px solid #DDD; padding-top: 16px; }
			#invoice tr.space th,
			#invoice tr.space td { padding-bottom: 16px; }
			#invoice tr.ms-inv-sep th,
			#invoice tr.ms-inv-sep td { line-height: 1px; height: 1px; padding: 0; border-bottom: 1px solid #DDD; background-color: #F9F9F9; }
			#invoice .ms-inv-total .ms-inv-price { font-weight: bold; font-size: 18px; text-align: right; }
			#invoice h2 { text-align: right; padding: 10px 10px 0 0; }
			#invoice h2 a { color: #000; }
			<?php do_action( 'ms_invoice_css' ) ?>
			</style>

			<div class="ms-invoice-details ms-status-<?php echo esc_attr( $invoice->status ); ?>">
				<table class="ms-purchase-table">
					<tr class="ms-inv-title">
						<td colspan="2"><h2><?php echo $inv_title; ?></h2></td>
					</tr>

					<?php if ( ! empty( $inv_from ) ) : ?>
						<tr class="ms-inv-from">
							<th><?php _e( 'Sender', MS_TEXT_DOMAIN ); ?></th>
							<td class="ms-inv-text"><?php echo $inv_from; ?></td>
						</tr>
					<?php endif; ?>

					<tr class="ms-inv-to">
						<th><?php _e( 'Invoice to', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-text"><?php echo $inv_to; ?></td>
					</tr>
					<tr class="ms-inv-invoice-date">
						<th><?php _e( 'Invoice date', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-date"><?php echo $inv_date; ?></td>
					</tr>
					<?php if ( ! empty( $trial_date ) ) : ?>
						<tr class="ms-inv-trial-end-date">
							<th><?php _e( 'Trial period', MS_TEXT_DOMAIN ); ?></th>
							<td class="ms-inv-date"><?php echo $trial_date; ?></td>
						</tr>
					<?php endif; ?>
					<tr class="ms-inv-status space">
						<th><?php _e( 'Status', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-text"><?php echo $inv_status; ?></td>
					</tr>
					<tr class="ms-inv-item-name alt sep">
						<th><?php _e( 'Name', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-text"><?php echo $inv_item_name; ?></td>
					</tr>

					<?php if ( ! empty( $inv_details ) ) : ?>
					<tr class="ms-inv-description alt">
						<th><?php _e( 'Description', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-text"><?php echo $inv_details; ?></td>
					</tr>
					<?php endif; ?>

					<tr class="ms-inv-amount alt space">
						<th><?php _e( 'Amount', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-price"><?php echo $inv_amount; ?></td>
					</tr>

					<?php $sep = 'sep'; ?>

					<?php if ( ! empty( $inv_discount ) ) : ?>
						<tr class="ms-inv-discount <?php echo esc_attr( $sep ); $sep = ''; ?>">
							<th><?php _e( 'Coupon discount', MS_TEXT_DOMAIN ); ?></th>
							<td class="ms-inv-price"><?php echo $inv_discount; ?></td>
						</tr>
					<?php endif; ?>

					<?php if ( ! empty( $inv_pro_rate ) ) : ?>
						<tr class="ms-inv-pro-rate <?php echo esc_attr( $sep ); $sep = ''; ?>">
							<th><?php _e( 'Pro rate discount', MS_TEXT_DOMAIN ); ?></th>
							<td class="ms-inv-price"><?php echo $inv_pro_rate; ?></td>
						</tr>
					<?php endif; ?>

					<?php if ( ! empty( $inv_taxes ) ) : ?>
						<tr class="ms-inv-tax <?php echo esc_attr( $sep ); $sep = ''; ?>">
							<th><?php
							printf(
								__( 'Taxes %s', MS_TEXT_DOMAIN ),
								'<small>(' . $invoice->tax_name . ')</small>'
							);
							?></th>
							<td class="ms-inv-price"><?php echo $inv_taxes; ?></td>
						</tr>
					<?php endif; ?>

					<?php if ( ! $is_free ) : ?>
						<tr class="ms-inv-due-date <?php echo esc_attr( $sep ); $sep = ''; ?>">
							<th><?php _e( 'Payment due', MS_TEXT_DOMAIN ); ?></th>
							<td class="ms-inv-date"><?php echo $inv_due_date; ?></td>
						</tr>
					<?php endif; ?>
					<tr class="ms-inv-total <?php echo esc_attr( $sep ); $sep = ''; ?>">
						<th><?php _e( 'Total', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-price"><?php echo $inv_total; ?></td>
					</tr>

					<?php
					$show_button = lib2()->is_true( $this->data['pay_button'] );

					if ( $invoice->is_paid() ) {
						// Invoice is already paid. We don't need a payment
						// button...
						$show_button = false;
					}

					if ( $show_button ) {
						?>
						<tr class="ms-inv-sep sep"><td colspan="2"></td></tr>
						<?php
						do_action(
							'ms_view_shortcode_invoice_purchase_button',
							$subscription,
							$invoice
						);
					}
					?>
				</table>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return apply_filters(
			'ms_shortcode_invoice',
			$html,
			$this->data
		);
	}
}