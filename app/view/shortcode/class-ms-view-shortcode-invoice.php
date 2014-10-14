<?php

class MS_View_Shortcode_Invoice extends MS_View {

	protected $data;

	public function to_html() {
		$invoice = $this->data['invoice'];
		$member = $this->data['member'];
		$ms_relationship = $this->data['ms_relationship'];
		$membership = $this->data['membership'];
		$gateway = $this->data['gateway'];

		$inv_title = __( 'Invoice #', MS_TEXT_DOMAIN ) . esc_html( $invoice->id );

		if ( $invoice->amount > 0 ) {
			$inv_amount = sprintf(
				'%1$s %2$s',
				$invoice->currency,
				number_format( $invoice->amount, 2 )
			);
		}
		else {
			$inv_amount = __( 'Free', MS_TEXT_DOMAIN );
		}

$invoice->discount = 2;
$invoice->pro_rate = 14;
		if ( $invoice->discount ) {
			$inv_discount = sprintf(
				'%s -%s',
				$invoice->currency,
				number_format( $invoice->discount, 2 )
			);
		}
		else {
			$inv_discount = '';
		}

		if ( $invoice->pro_rate ) {
			$inv_pro_rate = sprintf(
				'%s -%s',
				$invoice->currency,
				number_format( $invoice->pro_rate, 2 )
			);
		}
		else {
			$inv_pro_rate = '';
		}

		$inv_total = sprintf(
			'%s %s',
			$invoice->currency,
			number_format( $invoice->total, 2 )
		);

		$inv_title = apply_filters( 'ms_invoice_title', $inv_title, $invoice );
		$inv_from = apply_filters( 'ms_invoice_sender', MS_Plugin::instance()->settings->invoice_sender_name, $invoice );
		$inv_to = apply_filters( 'ms_invoice_recipient', $member->username, $invoice, $member );
		$inv_due_date = apply_filters( 'ms_invoice_due_date', $invoice->due_date, $invoice );
		$inv_status = apply_filters( 'ms_invoice_status', $invoice->status, $invoice );
		$inv_item_name = apply_filters( 'ms_invoice_item_name', $membership->name, $invoice, $membership );
		$inv_details = apply_filters( 'ms_invoice_description', $invoice->description, $invoice );
		$inv_amount = apply_filters( 'my_invoice_amount', $inv_amount, $invoice );
		$inv_discount = apply_filters( 'my_invoice_discount', $inv_discount, $invoice );
		$inv_pro_rate = apply_filters( 'my_invoice_pro_rate', $inv_pro_rate, $invoice );
		$inv_total = apply_filters( 'my_invoice_total', $inv_total, $invoice );

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
			</style>
			<h2>
			<?php
			printf(
				'<a href="%s">%s</a>',
				get_permalink( $invoice->id ),
				$inv_title
			);
			?>
			</h2>

			<div class="ms-invoice-details ms-status-<?php echo esc_attr( $invoice->status ); ?>">
				<table class="ms-purchase-table">
					<?php if ( ! empty( $inv_from ) ) : ?>
					<tr class="ms-inv-from">
						<th><?php _e( 'Sender', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-text"><?php echo esc_html( $inv_from ); ?></td>
					</tr>
					<?php endif; ?>

					<tr class="ms-inv-to">
						<th><?php _e( 'Invoice to', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-text"><?php echo $inv_to; ?></td>
					</tr>
					<tr class="ms-inv-due-date">
						<th><?php _e( 'Due date', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-date"><?php echo $inv_due_date; ?></td>
					</tr>
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
							<th><?php _e( 'Discount', MS_TEXT_DOMAIN ); ?></th>
							<td class="ms-inv-price"><?php echo $inv_discount; ?></td>
						</tr>
					<?php endif; ?>

					<?php if ( ! empty( $inv_pro_rate ) ) : ?>
						<tr class="ms-inv-pro-rate <?php echo esc_attr( $sep ); $sep = ''; ?>">
							<th><?php _e( 'Pro rate discount', MS_TEXT_DOMAIN ); ?></th>
							<td class="ms-inv-price"><?php echo $inv_pro_rate; ?></td>
						</tr>
					<?php endif; ?>

					<tr class="ms-inv-total <?php echo esc_attr( $sep ); $sep = ''; ?>">
						<th><?php _e( 'Total', MS_TEXT_DOMAIN ); ?></th>
						<td class="ms-inv-price"><?php echo $inv_total; ?></td>
					</tr>

					<tr class="ms-inv-sep sep"><td colspan="2"></td></tr>

					<?php
					$inv_buy_now = '';
					if ( $gateway->manual_payment && '1' == $this->data['pay_button'] && $invoice->status != MS_Model_Invoice::STATUS_PAID ) {
						ob_start();
						do_action( 'ms_view_shortcode_invoice_purchase_button', $ms_relationship );
						$inv_buy_now = ob_get_clean();
					}

					if ( ! empty( $inv_buy_now ) ) {
						echo $inv_buy_now;
					}
					?>
				</table>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return $html;
	}
}