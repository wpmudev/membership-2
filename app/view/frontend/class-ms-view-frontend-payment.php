<?php

class MS_View_Frontend_Payment extends MS_View {

	/**
	 * Returns the HTML code for the Purchase-Membership form.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function to_html() {
		$membership = $this->data['membership'];
		$invoice = $this->data['invoice'];
		$ms_relationship = $this->data['ms_relationship'];

		$class = 'ms-alert-success';
		$msg = __(
			'Please check the details of the membership below and click ' .
			'on the relevant button to complete the signup.', MS_TEXT_DOMAIN
		);

		if ( ! empty( $this->data['error'] ) ) {
			$class = 'ms-alert-error';
			$msg = $this->data['error'];
		}

		$cancel_warning = false;
		if ( ! MS_Model_Member::is_admin_user()
			&& ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS )
		) {
			// Member can only sign up to one membership.
			$valid_status = array(
				MS_Model_Relationship::STATUS_TRIAL,
				MS_Model_Relationship::STATUS_ACTIVE,
				MS_Model_Relationship::STATUS_PENDING,
			);

			foreach ( $this->data['member']->subscriptions as $tmp_relationship ) {
				if ( $tmp_relationship->is_system() ) { continue; }
				if ( in_array( $tmp_relationship->status, $valid_status ) ) {
					$cancel_warning = true;
					break;
				}
			}
		}

		if ( ! MS_Model_Member::is_admin_user()
			&& ! $cancel_warning
			&& $membership->is_free()
		) {
			// No confirmation required. Simply register for this membership!

			$args = array();
			$args['ms_relationship_id'] = $ms_relationship->id;
			$args['gateway'] = MS_Gateway_Free::ID;
			$args['step'] = MS_Controller_Frontend::STEP_PROCESS_PURCHASE;
			$args['_wpnonce'] = wp_create_nonce( $args['gateway'] . '_' . $args['ms_relationship_id'] );
			$url = add_query_arg( $args );

			/*
			 * Very likely the html output has already began.
			 * So we redirect by using javascript.
			 */
			?>
			<script>window.location.href = '<?php echo '' . $url; ?>';</script>
			<?php
			exit;
		}

		ob_start();
		?>
		<div class="ms-membership-form-wrapper">
			<legend><?php _e( 'Join Membership', MS_TEXT_DOMAIN ) ?></legend>
			<p class="ms-alert-box <?php echo esc_attr( $class ); ?>">
				<?php echo '' . $msg; ?>
			</p>
			<table class="ms-purchase-table">
				<tr>
					<td class="ms-title-column">
						<?php _e( 'Name', MS_TEXT_DOMAIN ); ?>
					</td>
					<td class="ms-details-column">
						<?php echo esc_html( $membership->name ); ?>
					</td>
				</tr>

				<?php if ( $membership->description ) : ?>
					<tr>
						<td class="ms-title-column">
							<?php _e( 'Description', MS_TEXT_DOMAIN ); ?>
						</td>
						<td class="ms-desc-column">
							<span class="ms-membership-description"><?php
								echo '' . $membership->description;
							?></span>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( ! $membership->is_free() ) : ?>
					<?php if ( $invoice->discount || $invoice->pro_rate || $invoice->tax_rate ) : ?>
					<tr>
						<td class="ms-title-column">
							<?php _e( 'Price', MS_TEXT_DOMAIN ); ?>
						</td>
						<td class="ms-details-column">
							<?php
							if ( $membership->price > 0 ) {
								printf(
									'<span class="price">%s %s</span>',
									$invoice->currency,
									MS_Helper_Billing::format_price( $membership->price )
								);
							} else {
								_e( 'Free', MS_TEXT_DOMAIN );
							}
							?>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( $invoice->discount ) : ?>
						<tr>
							<td class="ms-title-column">
								<?php _e( 'Coupon discount', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class="ms-price-column">
								<?php printf( '%s -%s', $invoice->currency, MS_Helper_Billing::format_price( $invoice->discount ) ); ?>
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( $invoice->pro_rate ) : ?>
						<tr>
							<td class="ms-title-column">
								<?php _e( 'Pro rate discount', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class="ms-price-column">
								<?php printf( '%s -%s', $invoice->currency, MS_Helper_Billing::format_price( $invoice->pro_rate ) ); ?>
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( $invoice->tax_rate ) : ?>
						<tr>
							<td class="ms-title-column">
								<?php printf(
									__( 'Taxes %s', MS_TEXT_DOMAIN ),
									'<small>(' . $invoice->tax_name . ')</small>'
								); ?>
							</td>
							<td class="ms-price-column">
								<?php printf(
									'%s %s',
									$invoice->currency,
									MS_Helper_Billing::format_price( $invoice->tax )
								); ?>
							</td>
						</tr>
					<?php endif; ?>

					<tr>
						<td class="ms-title-column">
							<?php _e( 'Total', MS_TEXT_DOMAIN ); ?>
						</td>
						<td class="ms-price-column ms-total">
							<?php
							if ( $invoice->total > 0 ) {
								printf(
									'<span class="price">%s %s</span>',
									$invoice->currency,
									MS_Helper_Billing::format_price( $invoice->total )
								);
							} else {
								_e( 'Free', MS_TEXT_DOMAIN );
							}
							?>
						</td>
					</tr>

					<?php if ( $membership->trial_period_enabled && $invoice->trial_period ) : ?>
						<tr>
							<td class="ms-title-column">
								<?php _e( 'Trial until', MS_TEXT_DOMAIN ); ?>
							</td>
							<td class="ms-desc-column"><?php
								echo '' . $ms_relationship->calc_trial_expire_date(
									MS_Helper_Period::current_date()
								);
							?></td>
						</tr>
					<?php endif; ?>
					<tr>
						<td class="ms-desc-column" colspan="2">
							<span class="ms-membership-description"><?php
								echo '' . $ms_relationship->get_payment_description( $invoice );
							?></span>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( $cancel_warning ) : ?>
					<tr>
						<td class="ms-desc-warning" colspan="2">
							<span class="ms-cancel-other-memberships"><?php
								_e(
									'Your other Memberships will be cancelled when you complete this payment.',
									MS_TEXT_DOMAIN
								);
							?></span>
						</td>
					</tr>
				<?php endif;

				if ( MS_Model_Member::is_admin_user() ) : ?>
					<tr>
						<td class="ms-desc-adminnote" colspan="2">
							<em><?php
							_e( 'As admin user you already have access to this membership', MS_TEXT_DOMAIN );
							?></em>
						</td>
					</tr>
				<?php else :
					do_action(
						'ms_view_frontend_payment_purchase_button',
						$ms_relationship,
						$invoice
					);
				endif;
				?>
			</table>
		</div>
		<?php
		do_action( 'ms_view_frontend_payment_after', $this->data, $this );
		do_action( 'ms_show_prices' );
		?>
		<div style="clear:both;"></div>
		<?php

		return ob_get_clean();
	}

}