<?php

class MS_View_Frontend_Invoices extends MS_View {

	public function to_html() {
		ob_start();
		?>
		<div class="ms-account-wrapper">
			<?php if ( MS_Model_Member::is_logged_in() ): ?>
				<h2>
					<?php _e( 'Invoice', MS_TEXT_DOMAIN ); ?>
				</h2>
				<table>
					<thead>
						<tr>
							<th class="ms-col-invoice-no"><?php
								_e( 'Invoice #', MS_TEXT_DOMAIN );
							?></th>
							<th class="ms-col-invoice-status"><?php
								_e( 'Status', MS_TEXT_DOMAIN );
							?></th>
							<th class="ms-col-invoice-total"><?php
							printf(
								'%s (%s)',
								__( 'Total', MS_TEXT_DOMAIN ),
								MS_Plugin::instance()->settings->currency
							);
							?></th>
							<th class="ms-col-invoice-title"><?php
								_e( 'Membership', MS_TEXT_DOMAIN );
							?></th>
							<th class="ms-col-invoice-due"><?php
								_e( 'Due date', MS_TEXT_DOMAIN );
							?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $this->data['invoices'] as $invoice ) :
						$inv_membership = MS_Factory::load( 'MS_Model_Membership', $invoice->membership_id );
						$inv_classes = array(
							'ms-invoice-' . $invoice->id,
							'ms-subscription-' . $invoice->ms_relationship_id,
							'ms-invoice-' . $invoice->status,
							'ms-gateway-' . $invoice->gateway_id,
							'ms-membership-' . $invoice->membership_id,
							'ms-type-' . $inv_membership->type,
							'ms-payment-' . $inv_membership->payment_type,
						);
						?>
						<tr class="<?php echo esc_attr( implode( ' ', $inv_classes ) ); ?>">
							<td class="ms-col-invoice-no"><?php
							printf(
								'<a href="%s">%s</a>',
								get_permalink( $invoice->id ),
								$invoice->get_invoice_number()
							);
							?></td>
							<td class="ms-col-invoice-status"><?php
								echo esc_html( $invoice->status_text() );
							?></td>
							<td class="ms-col-invoice-total"><?php
								echo esc_html( MS_Helper_Billing::format_price( $invoice->total ) );
							?></td>
							<td class="ms-col-invoice-title"><?php
								echo esc_html( $inv_membership->name );
							?></td>
							<td class="ms-col-invoice-due"><?php
								echo esc_html(
									MS_Helper_Period::format_date(
										$invoice->due_date,
										__( 'F j', MS_TEXT_DOMAIN )
									)
								);
							?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<?php
				$redirect = esc_url_raw( add_query_arg( array() ) );
				$title = __( 'Your account', MS_TEXT_DOMAIN );
				echo do_shortcode( "[ms-membership-login redirect='$redirect' title='$title']" );
				?>
			<?php endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return $html;
	}

}