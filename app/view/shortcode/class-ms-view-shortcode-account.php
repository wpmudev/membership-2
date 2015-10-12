<?php

class MS_View_Shortcode_Account extends MS_View {

	public function to_html() {
		global $post;

		/**
		 * Provide a customized account page.
		 *
		 * @since  1.0.0
		 */
		$html = apply_filters(
			'ms_shortcode_custom_account',
			'',
			$this->data
		);

		if ( ! empty( $html ) ) {
			return $html;
		} else {
			$html = '';
		}

		$member = MS_Model_Member::get_current_member();
		$fields = $this->prepare_fields();

		// Extract shortcode options.
		extract( $this->data );

		ob_start();
		?>
		<div class="ms-account-wrapper">
			<?php if ( MS_Model_Member::is_logged_in() ) : ?>

				<?php
				// ================================================= MEMBERSHIPS
				if ( $show_membership ) : ?>
				<div id="account-membership">
				<h2>
					<?php
					echo $membership_title;

					if ( $show_membership_change ) {
						$signup_url = MS_Model_Pages::get_page_url( MS_Model_Pages::MS_PAGE_REGISTER );

						printf(
							'<a href="%s" class="ms-edit-profile">%s</a>',
							$signup_url,
							$membership_change_label
						);
					}
					?>
				</h2>
				<?php
				/**
				 * Add custom content right before the memberships list.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ms_view_account_memberships_top', $member, $this );

				if ( MS_Model_Member::is_admin_user() ) {
					_e( 'You are an admin user and have access to all memberships', 'membership2' );
				} else {
					if ( ! empty( $this->data['subscription'] ) ) {
						?>
						<table>
							<tr>
								<th class="ms-col-membership"><?php
									_e( 'Membership name', 'membership2' );
								?></th>
								<th class="ms-col-status"><?php
									_e( 'Status', 'membership2' );
								?></th>
								<th class="ms-col-expire-date"><?php
									_e( 'Expire date', 'membership2' );
								?></th>
							</tr>
							<?php
							$empty = true;

							// These subscriptions have no expire date
							$no_expire_list = array(
								MS_Model_Relationship::STATUS_PENDING,
								MS_Model_Relationship::STATUS_WAITING,
								MS_Model_Relationship::STATUS_DEACTIVATED,
							);

							// These subscriptions display the trial-expire date
							$trial_expire_list = array(
								MS_Model_Relationship::STATUS_TRIAL,
								MS_Model_Relationship::STATUS_TRIAL_EXPIRED,
							);

							foreach ( $this->data['subscription'] as $subscription ) :
								$empty = false;
								$membership = $subscription->get_membership();
								$subs_classes = array(
									'ms-subscription-' . $subscription->id,
									'ms-status-' . $subscription->status,
									'ms-type-' . $membership->type,
									'ms-payment-' . $membership->payment_type,
									'ms-gateway-' . $subscription->gateway_id,
									'ms-membership-' . $subscription->membership_id,
									$subscription->has_trial() ? 'ms-with-trial' : 'ms-no-trial',
								);
								?>
								<tr class="<?php echo esc_attr( implode( ' ', $subs_classes ) ); ?>">
									<td class="ms-col-membership"><?php echo esc_html( $membership->name ); ?></td>
									<td class="ms-col-status">
									<?php
									if ( MS_Model_Relationship::STATUS_PENDING == $subscription->status ) {
										// Display a "Purchase" link when status is Pending
										$code = sprintf(
											'[%s id="%s" label="%s"]',
											MS_Helper_Shortcode::SCODE_MS_BUY,
											$membership->id,
											__( 'Pending', 'membership2' )
										);
										echo do_shortcode( $code );
									} else {
										echo esc_html( $subscription->status_text() );
									}
									?>
									</td>
									<td class="ms-col-expire-date"><?php
									if ( in_array( $subscription->status, $no_expire_list ) ) {
										echo '&nbsp;';
									} elseif ( in_array( $subscription->status, $trial_expire_list ) ) {
										echo esc_html(
											MS_Helper_Period::format_date( $subscription->trial_expire_date )
										);
									} elseif ( $subscription->expire_date ) {
										echo esc_html(
											MS_Helper_Period::format_date( $subscription->expire_date )
										);
									} else {
										_e( 'Never', 'membership2' );
									}
									?></td>
								</tr>
							<?php
							endforeach;

							if ( $empty ) {
								$cols = 3;
								if ( MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL ) ) {
									$cols += 1;
								}

								printf(
									'<tr><td colspan="%1$s">%2$s</td></tr>',
									$cols,
									__( '(No Membership)', 'membership2' )
								);
							}
							?>
						</table>
					<?php
					} else {
						_e( 'No memberships', 'membership2' );
					}
				}
				/**
				 * Add custom content right after the memberships list.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ms_view_account_memberships_bottom', $member, $this );
				?>
				</div>
				<?php
				endif;
				// END: if ( $show_membership )
				// =============================================================
				?>

				<?php
				// ===================================================== PROFILE
				if ( $show_profile ) : ?>
				<div id="account-profile">
				<h2>
					<?php
					echo $profile_title;

					if ( $show_profile_change ) {
						$edit_url = esc_url_raw(
							add_query_arg(
								array( 'action' => MS_Controller_Frontend::ACTION_EDIT_PROFILE )
							)
						);

						printf(
							'<a href="%s" class="ms-edit-profile">%s</a>',
							$edit_url,
							$profile_change_label
						);
					}
					?>
				</h2>
				<?php
				/**
				 * Add custom content right before the profile overview.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ms_view_account_profile_top', $member, $this );
				?>
				<table>
					<?php foreach ( $fields['personal_info'] as $field => $title ) : ?>
						<tr>
							<th class="ms-label-title"><?php echo esc_html( $title ); ?>: </th>
							<td class="ms-label-field"><?php echo esc_html( $this->data['member']->$field ); ?></td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php
				do_action( 'ms_view_shortcode_account_card_info', $this->data );

				/**
				 * Add custom content right after the profile overview.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ms_view_account_profile_bottom', $member, $this );
				?>
				</div>
				<?php
				endif;
				// END: if ( $show_profile )
				// =============================================================
				?>

				<?php
				// ==================================================== INVOICES
				if ( $show_invoices ) : ?>
				<div id="account-invoices">
				<h2>
					<?php
					echo $invoices_title;

					if ( $show_all_invoices ) {
						$detail_url = esc_url_raw(
							add_query_arg(
								array( 'action' => MS_Controller_Frontend::ACTION_VIEW_INVOICES )
							)
						);

						printf(
							'<a href="%s" class="ms-all-invoices">%s</a>',
							$detail_url,
							$invoices_details_label
						);
					}
					?>
				</h2>
				<?php
				/**
				 * Add custom content right before the invoice overview list.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ms_view_account_invoices_top', $member, $this );
				?>
				<table>
					<thead>
						<tr>
							<th class="ms-col-invoice-no"><?php
								_e( 'Invoice #', 'membership2' );
							?></th>
							<th class="ms-col-invoice-status"><?php
								_e( 'Status', 'membership2' );
							?></th>
							<th class="ms-col-invoice-total"><?php
							printf(
								'%s (%s)',
								__( 'Total', 'membership2' ),
								MS_Plugin::instance()->settings->currency
							);
							?></th>
							<th class="ms-col-invoice-title"><?php
								_e( 'Membership', 'membership2' );
							?></th>
							<th class="ms-col-invoice-due"><?php
								_e( 'Due date', 'membership2' );
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
										__( 'F j', 'membership2' )
									)
								);
							?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				/**
				 * Add custom content right after the invoices overview list.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ms_view_account_invoices_bottom', $member, $this );
				?>
				</div>
				<?php
				endif;
				// END: if ( $show_invoices )
				// =============================================================
				?>

				<?php
				// ==================================================== ACTIVITY
				if ( $show_activity ) : ?>
				<div id="account-activity">
				<h2>
					<?php
					echo $activity_title;

					if ( $show_all_activities ) {
						$detail_url = esc_url_raw(
							add_query_arg(
								array( 'action' => MS_Controller_Frontend::ACTION_VIEW_ACTIVITIES )
							)
						);

						printf(
							'<a href="%s" class="ms-all-activities">%s</a>',
							$detail_url,
							$activity_details_label
						);
					}
					?>
				</h2>
				<?php
				/**
				 * Add custom content right before the activities overview list.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ms_view_account_activity_top', $member, $this );
				?>
				<table>
					<thead>
						<tr>
							<th class="ms-col-activity-date"><?php
								_e( 'Date', 'membership2' );
							?></th>
							<th class="ms-col-activity-title"><?php
								_e( 'Activity', 'membership2' );
							?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ( $this->data['events'] as $event ) :
						$ev_classes = array(
							'ms-activity-topic-' . $event->topic,
							'ms-activity-type-' . $event->type,
							'ms-membership-' . $event->membership_id,
						);
						?>
						<tr class="<?php echo esc_attr( implode( ' ', $ev_classes ) ); ?>">
							<td class="ms-col-activity-date"><?php
								echo esc_html(
									MS_Helper_Period::format_date(
										$event->post_modified
									)
								);
							?></td>
							<td class="ms-col-activity-title"><?php
								echo esc_html( $event->description );
							?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<?php
				/**
				 * Add custom content right after the activities overview list.
				 *
				 * @since  1.0.0
				 */
				do_action( 'ms_view_account_activity_bottom', $member, $this );
				?>
				</div>
				<?php
				endif;
				// END: if ( $show_activity )
				// =============================================================
				?>

			<?php else :
				$has_login_form = MS_Helper_Shortcode::has_shortcode(
					MS_Helper_Shortcode::SCODE_LOGIN,
					$post->post_content
				);

				if ( ! $has_login_form ) {
					$redirect = esc_url_raw( add_query_arg( array() ) );
					$title = __( 'Your account', 'membership2' );
					$scode = sprintf(
						'[%1$s redirect="%2$s" title="%3$s"]',
						MS_Helper_Shortcode::SCODE_LOGIN,
						esc_url( $redirect ),
						esc_attr( $title )
					);
					echo do_shortcode( $scode );
				}
			endif; ?>
		</div>
		<?php
		$html = ob_get_clean();
		$html = apply_filters( 'ms_compact_code', $html );

		return apply_filters(
			'ms_shortcode_account',
			$html,
			$this->data
		);
	}

	/**
	 * Prepare some fields that are displayed in the account overview.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function prepare_fields() {
		$fields = array(
			'personal_info' => array(
				'first_name' => __( 'First name', 'membership2' ),
				'last_name' => __( 'Last name', 'membership2' ),
				'username' => __( 'Username', 'membership2' ),
				'email' => __( 'Email', 'membership2' ),
			)
		);

		$fields = apply_filters(
			'ms_shortcode_account_fields',
			$fields,
			$this
		);

		return $fields;
	}

}