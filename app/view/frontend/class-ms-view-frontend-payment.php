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
			'Please check the details of the membership below and click on the relevant button to complete the signup.', 'membership2'
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

		$membership_wrapper_class = esc_attr( implode( ' ', $classes ) );
		$alert_box_class = esc_attr( $class );
		$membership_name = esc_html( $membership->name );
		$is_membership_description = $membership->description;
		$membership_description = $membership->get_description();
		$is_membership_free = $membership->is_free();
		$invoice_discount = $invoice->discount;
		$invoice_pro_rate = $invoice->pro_rate;
		$invoice_tax_rate = $invoice->tax_rate;
		$invoice_discount = $invoice->discount;
		$invoice_formatted_discount = sprintf(
			'%s -%s',
			$invoice->currency,
			MS_Helper_Billing::format_price( $invoice->discount )
		);
		$invoice_formatted_pro_rate = sprintf(
			'%s -%s',
			$invoice->currency,
			MS_Helper_Billing::format_price( $invoice->pro_rate )
		);
		$invoice_tax_name = sprintf(
			__( 'Taxes %s', 'membership2' ),
			'<a href="#" class="ms-tax-editor"><small>(' . $invoice->tax_name . ')</small></a>'
		);
		$invoice_formatted_tax = sprintf(
			'%s %s',
			$invoice->currency,
			MS_Helper_Billing::format_price( $invoice->tax )
		);
		$invoice_total = $invoice->total;
		$is_ms_admin_user = MS_Model_Member::is_admin_user();
		$invoice_formatted_total_for_admin = sprintf(
			'<span class="price">%s %s</span>',
			$invoice->currency,
			MS_Helper_Billing::format_price( $membership->price - $invoice->discount + $invoice->pro_rate + $invoice->tax )
		);
		$invoice_formatted_total = sprintf(
			'<span class="price">%s %s</span>',
			$invoice->currency,
			MS_Helper_Billing::format_price( $invoice->total )
		);
		$membership_price = $membership->price;
		$membership_formatted_price = sprintf(
			'<span class="price">%s %s</span>',
			$invoice->currency,
			MS_Helper_Billing::format_price( $membership->price )
		);

		$invoice_formatted_due_date = MS_Helper_Period::format_date( $invoice->due_date );
		$invoice_trial_price = $invoice->trial_price;
		$invoice_formatted_trial_price = sprintf(
			'<span class="price">%s %s</span>',
			$invoice->currency,
			MS_Helper_Billing::format_price( $invoice->trial_price )
		);
		$invoice_payment_description = $subscription->get_payment_description( $invoice );

		$template_data = array(
			'membership_wrapper_class' => $membership_wrapper_class,
			'alert_box_class' => $alert_box_class,
			'msg' => $msg,
			'membership_name' => $membership_name,
			'is_membership_description' => $is_membership_description,
			'membership_description' => $membership_description,
			'is_membership_free' => $is_membership_free,
			'invoice_discount' => $invoice_discount,
			'invoice_pro_rate' => $invoice_pro_rate,
			'invoice_tax_rate' => $invoice_tax_rate,
			'membership_price' => $membership_price,
			'membership_formatted_price' => $membership_formatted_price,
			'invoice_formatted_discount' => $invoice_formatted_discount,
			'invoice_formatted_pro_rate' => $invoice_formatted_pro_rate,
			'show_tax' => $show_tax,
			'invoice_tax_name' => $invoice_tax_name,
			'invoice_formatted_tax' => $invoice_formatted_tax,
			'invoice_total' => $invoice_total,
			'is_ms_admin_user' => $is_ms_admin_user,
			'invoice_formatted_total_for_admin' => $invoice_formatted_total_for_admin,
			'invoice_formatted_total' => $invoice_formatted_total,
			'is_trial' => $is_trial,
			'invoice_formatted_due_date' => $invoice_formatted_due_date,
			'invoice_trial_price' => $invoice_trial_price,
			'invoice_formatted_trial_price' => $invoice_formatted_trial_price,
			'invoice_payment_description' => $invoice_payment_description,
			'cancel_warning' => $cancel_warning,
			'm2_payment_obj' => $this,
			'subscription' => $subscription,
			'invoice' => $invoice,
		);

		MS_Helper_Template::$ms_front_payment = $template_data;
		if ( $path = MS_Helper_Template::template_exists( 'membership_frontend_payment.php' ) ) {
			require $path;
		}

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
