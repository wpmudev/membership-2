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
		$subscription = $this->data['ms_relationship'];

		$class = 'ms-alert-success';
		$msg = __(
			'Please check the details of the membership below and click ' .
			'on the relevant button to complete the signup.', 'membership2'
		);

		if ( ! empty( $this->data['error'] ) ) {
			$class = 'ms-alert-error';
			$msg = $this->data['error'];
		}

		/**
		 * Log the users IP and current timestamp inside the invoice.
		 *
		 * @since 1.0.2.0
		 */
		$invoice->checkout_ip = lib3()->net->current_ip()->ip;
		$invoice->checkout_date = date( 'Y-m-d H:i:s' );
		$invoice->save();

		$cancel_warning = false;
		if ( ! MS_Model_Member::is_admin_user() ) {
			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
				// Member can only sign up to one membership.
				$valid_status = array(
					MS_Model_Relationship::STATUS_TRIAL,
					MS_Model_Relationship::STATUS_ACTIVE,
					MS_Model_Relationship::STATUS_PENDING,
				);

				foreach ( $this->data['member']->subscriptions as $tmp_subscription ) {
					if ( $tmp_subscription->is_system() ) { continue; }
					if ( in_array( $tmp_subscription->status, $valid_status ) ) {
						$cancel_warning = __(
							'Your other Memberships will be cancelled when you complete this payment.',
							'membership2'
						);
						break;
					}
				}
			} elseif ( $subscription->move_from_id ) {
				$move_from_ids = explode( ',', $subscription->move_from_id );
				$names = array();
				foreach ( $move_from_ids as $id ) {
					$ms = MS_Factory::load( 'MS_Model_Membership', $id );
					if ( $ms->is_system() ) { continue; }
					$names[] = $ms->name;
				}

				if ( 1 == count( $names ) ) {
					$cancel_warning = sprintf(
						__( 'When you complete this payment your Membership "%s" will be cancelled.', 'membership2' ),
						$names[0]
					);
				} elseif ( 1 < count( $names ) ) {
					$cancel_warning = sprintf(
						__( 'When you complete this payment the following Memberships will be cancelled: %s.', 'membership2' ),
						implode( ', ', $names )
					);
				}
			}
		}

		// Check if the user goes through a trial period before first payment.
		$is_trial = $invoice->uses_trial;

		$skip_form = ! MS_Model_Member::is_admin_user()
			&& ! $cancel_warning
			&& $membership->is_free();

		/**
		 * Filter the flag to allow Add-ons like "Invitation codes" to override
		 * the state and force the form to display.
		 *
		 * @var bool
		 */
		$skip_form = apply_filters(
			'ms_view_frontend_payment_skip_form',
			$skip_form,
			$invoice,
			$this
		);

		if ( $skip_form ) {
			// No confirmation required. Simply register for this membership!
			$args = array();
			$args['ms_relationship_id'] = $subscription->id;
			$args['gateway'] = MS_Gateway_Free::ID;
			$args['step'] = MS_Controller_Frontend::STEP_PROCESS_PURCHASE;
			$args['_wpnonce'] = wp_create_nonce( $args['gateway'] . '_' . $args['ms_relationship_id'] );
			$url = esc_url_raw( add_query_arg( $args ) );

			/*
			 * Very likely the html output has already began.
			 * So we redirect by using javascript.
			 */
			?>
			<script>window.location.href = '<?php echo $url; ?>';</script>
			<?php
			exit;
		}

		$show_tax = MS_Model_Addon::is_enabled( MS_Addon_Taxamo::ID );

		/**
		 * Trigger an action before the payment form is displayed. This hook
		 * can be used by Add-ons or plugins to initialize payment settings or
		 * add custom code.
		 */
		do_action( 'ms_view_frontend_payment_form_start', $invoice, $this );

		$classes = array(
			'ms-membership-form-wrapper',
			'ms-subscription-' . $subscription->id,
			'ms-invoice-' . $invoice->id,
		);

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
			<legend><?php _e( 'Join Membership', 'membership2' ) ?></legend>
			<p class="ms-alert-box <?php echo esc_attr( $class ); ?>">
				<?php echo $msg; ?>
			</p>
			<table class="ms-purchase-table">
				<tr>
					<td class="ms-title-column">
						<?php _e( 'Name', 'membership2' ); ?>
					</td>
					<td class="ms-details-column">
						<?php echo esc_html( $membership->name ); ?>
					</td>
				</tr>

				<?php if ( $membership->description ) : ?>
					<tr>
						<td class="ms-title-column">
							<?php _e( 'Description', 'membership2' ); ?>
						</td>
						<td class="ms-desc-column">
							<span class="ms-membership-description"><?php
								echo $membership->get_description();
							?></span>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( ! $membership->is_free() ) : ?>
					<?php if ( $invoice->discount || $invoice->pro_rate || $invoice->tax_rate ) : ?>
					<tr>
						<td class="ms-title-column">
							<?php _e( 'Price', 'membership2' ); ?>
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
								_e( 'Free', 'membership2' );
							}
							?>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( $invoice->discount ) : ?>
						<tr>
							<td class="ms-title-column">
								<?php _e( 'Coupon Discount', 'membership2' ); ?>
							</td>
							<td class="ms-price-column">
								<?php
								printf(
									'%s -%s',
									$invoice->currency,
									MS_Helper_Billing::format_price( $invoice->discount )
								);
								?>
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( $invoice->pro_rate ) : ?>
						<tr>
							<td class="ms-title-column">
								<?php _e( 'Pro-Rate Discount', 'membership2' ); ?>
							</td>
							<td class="ms-price-column">
								<?php
								printf(
									'%s -%s',
									$invoice->currency,
									MS_Helper_Billing::format_price( $invoice->pro_rate )
								);
								?>
							</td>
						</tr>
					<?php endif; ?>

					<?php if ( $show_tax ) : ?>
						<tr>
							<td class="ms-title-column">
								<?php
								printf(
									__( 'Taxes %s', 'membership2' ),
									'<a href="#" class="ms-tax-editor"><small>(' . $invoice->tax_name . ')</small></a>'
								);
								?>
							</td>
							<td class="ms-price-column">
								<?php
								printf(
									'%s %s',
									$invoice->currency,
									MS_Helper_Billing::format_price( $invoice->tax )
								);
								?>
							</td>
						</tr>
					<?php endif; ?>

					<tr>
						<td class="ms-title-column">
							<?php _e( 'Total', 'membership2' ); ?>
						</td>
						<td class="ms-price-column ms-total">
							<?php
							if ( $invoice->total > 0 ) {
                                                            if ( MS_Model_Member::is_admin_user() ) {
                                                                printf(
									'<span class="price">%s %s</span>',
									$invoice->currency,
									MS_Helper_Billing::format_price( $membership->price - $invoice->discount + $invoice->pro_rate + $invoice->tax )
								);
                                                            }else{
								printf(
									'<span class="price">%s %s</span>',
									$invoice->currency,
									MS_Helper_Billing::format_price( $invoice->total )
								);
                                                            }
							} else {
								_e( 'Free', 'membership2' );
							}
							?>
						</td>
					</tr>

					<?php if ( $is_trial ) : ?>
						<tr>
							<td class="ms-title-column">
								<?php _e( 'Payment due', 'membership2' ); ?>
							</td>
							<td class="ms-desc-column"><?php
								echo MS_Helper_Period::format_date( $invoice->due_date );
							?></td>
						</tr>
						<tr>
							<td class="ms-title-column">
								<?php _e( 'Trial price', 'membership2' ); ?>
							</td>
							<td class="ms-desc-column">
							<?php
							if ( $invoice->trial_price > 0 ) {
								printf(
									'<span class="price">%s %s</span>',
									$invoice->currency,
									MS_Helper_Billing::format_price( $invoice->trial_price )
								);
							} else {
								_e( 'Free', 'membership2' );
							}
							?>
							</td>
						</tr>
					<?php endif; ?>

					<?php
					do_action(
						'ms_view_frontend_payment_after_total_row',
						$subscription,
						$invoice,
						$this
					);
					?>

					<tr>
						<td class="ms-desc-column" colspan="2">
							<span class="ms-membership-description"><?php
								echo $subscription->get_payment_description( $invoice );
							?></span>
						</td>
					</tr>
				<?php endif; ?>

				<?php if ( $cancel_warning ) : ?>
					<tr>
						<td class="ms-desc-warning" colspan="2">
							<span class="ms-cancel-other-memberships"><?php
								echo $cancel_warning;
							?></span>
						</td>
					</tr>
				<?php endif;

				if ( MS_Model_Member::is_admin_user() ) : ?>
					<tr>
						<td class="ms-desc-adminnote" colspan="2">
							<em><?php
							_e( 'As admin user you already have access to this membership', 'membership2' );
							?></em>
						</td>
					</tr>
				<?php else :
					do_action(
						'ms_view_frontend_payment_purchase_button',
						$subscription,
						$invoice,
						$this
					);
				endif;
				?>
			</table>
		</div>
		<?php
		do_action( 'ms_view_frontend_payment_after', $this->data, $this );
		do_action( 'ms_show_prices' );

		if ( $show_tax ) {
			do_action( 'ms_tax_editor', $invoice );
		}
		?>
		<div style="clear:both;"></div>
		<?php

		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		$html = apply_filters(
			'ms_view_frontend_payment_form',
			$html,
			$this
		);
		return $html;
	}

}