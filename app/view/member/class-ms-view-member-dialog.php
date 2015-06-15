<?php

/**
 * Dialog: Member Profile
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.1.0
 * @package Membership2
 * @subpackage View
 */
class MS_View_Member_Dialog extends MS_Dialog {

	const ACTION_SAVE = 'ms_save_member';

	/**
	 * Generate/Prepare the dialog attributes.
	 *
	 * @since 1.1.0
	 */
	public function prepare() {
		$member_id = $_POST['member_id'];
		$member = MS_Factory::load( 'MS_Model_Member', $member_id );

		$data = array(
			'model' => $member,
		);

		$data = apply_filters( 'ms_view_member_dialog_data', $data );

		// Dialog Title
		$this->title = sprintf(
			__( 'Profile: %1$s %2$s', MS_TEXT_DOMAIN ),
			esc_html( $member->first_name ),
			esc_html( $member->last_name )
		);

		// Dialog Size
		$this->height = 390;

		// Contents
		$this->content = $this->get_contents( $data );

		// Make the dialog modal
		$this->modal = true;
	}

	/**
	 * Save the gateway details.
	 *
	 * @since  1.1.0
	 * @return string
	 */
	public function submit() {
		$data = $_POST;
		$res = MS_Helper_Member::MEMBER_MSG_NOT_UPDATED;

		unset( $data['action'] );
		unset( $data['dialog'] );

		// Update the memberships
		if ( isset( $_POST['dialog_action'] )
			&& $this->verify_nonce( $_POST['dialog_action'] )
			&& isset( $_POST['member_id'] )
		) {
			// No input fields, so we cannot save anything...
			$res = MS_Helper_Member::MEMBER_MSG_UPDATED;
		}

		return $res;
	}

	/**
	 * Returns the contens of the dialog
	 *
	 * @since 1.1.0
	 *
	 * @return object
	 */
	public function get_contents( $data ) {
		$member = $data['model'];

		$currency = MS_Plugin::instance()->settings->currency;
		$show_trial = MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_TRIAL );

		$all_subscriptions = MS_Model_Relationship::get_subscriptions(
			array(
				'user_id' => $member->id,
				'status' => 'all',
				'meta_key' => 'expire_date',
				'orderby' => 'meta_value',
				'order' => 'DESC',
			)
		);

		// Prepare the form fields.
		$inp_dialog = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'dialog',
			'value' => 'View_Member_Dialog',
		);

		$inp_id = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'member_id',
			'value' => $member->id,
		);

		$inp_nonce = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => '_wpnonce',
			'value' => wp_create_nonce( self::ACTION_SAVE ),
		);

		$inp_action = array(
			'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
			'name' => 'dialog_action',
			'value' => self::ACTION_SAVE,
		);

		$inp_save = array(
			'type' => MS_Helper_Html::INPUT_TYPE_SUBMIT,
			'value' => __( 'Save', MS_TEXT_DOMAIN ),
			'class' => 'ms-submit-form',
			'data' => array(
				'form' => 'ms-edit-member',
			)
		);

		$inp_cancel = array(
			'type' => MS_Helper_Html::INPUT_TYPE_BUTTON,
			'value' => __( 'Close', MS_TEXT_DOMAIN ),
			'class' => 'close',
		);

		ob_start();
		?>
		<div>
			<form class="ms-form wpmui-ajax-update ms-edit-member" data-ajax="<?php echo esc_attr( 'save' ); ?>">
				<div class="ms-form wpmui-form wpmui-grid-8">
					<table class="widefat">
					<thead>
						<tr>
							<th class="column-membership">
								<?php _e( 'Membership', MS_TEXT_DOMAIN ); ?>
							</th>
							<th class="column-status">
								<?php _e( 'Status', MS_TEXT_DOMAIN ); ?>
							</th>
							<th class="column-start">
								<?php _e( 'Subscribed on', MS_TEXT_DOMAIN ); ?>
							</th>
							<th class="column-expire">
								<?php _e( 'Expires on', MS_TEXT_DOMAIN ); ?>
							</th>
							<?php if ( $show_trial ) : ?>
							<th class="column-trialexpire">
								<?php _e( 'Trial until', MS_TEXT_DOMAIN ); ?>
							</th>
							<?php endif; ?>
							<th class="column-payments">
								<?php _e( 'Payments', MS_TEXT_DOMAIN ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $all_subscriptions as $subscription ) :
						$membership = $subscription->get_membership();
						$payments = $subscription->payments;

						$num_payments = count( $payments );
						$amount_payments = 0;
						foreach ( $payments as $payment ) {
							if ( ! empty( $payment['amount'] ) ) {
								$amount_payments += floatval( $payment['amount'] );
							}
						}

						$subscription_info = array(
							'subscription_id' => $subscription->id,
						);
						$update_info = array(
							'subscription_id' => $subscription->id,
							'statuscheck' => 'yes',
						);
						?>
						<tr>
							<td class="column-membership">
								<?php $membership->name_tag(); ?>
							</td>
							<td class="column-status">
								<?php
								printf(
									'<a href="#" data-ms-dialog="View_Member_Subscription" data-ms-data="%2$s">%1$s</a>
									<a href="#" data-ms-dialog="View_Member_Subscription" data-ms-data="%3$s" title="%5$s">%4$s</a>',
									$subscription->status,
									esc_attr( json_encode( $subscription_info ) ),
									esc_attr( json_encode( $update_info ) ),
									'<i class="dashicons dashicons-update"></i>',
									__( 'Check and update subscription status', MS_TEXT_DOMAIN )
								);
								?>
							</td>
							<td class="column-start">
								<?php echo '' . $subscription->start_date; ?>
							</td>
							<td class="column-expire">
								<?php echo '' . $subscription->expire_date; ?>
							</td>
							<?php if ( $show_trial ) : ?>
							<td class="column-trialexpire">
								<?php
								if ( $subscription->start_date == $subscription->trial_expire_date ) {
									echo '-';
								} else {
									echo '' . $subscription->trial_expire_date;
								}
								?>
							</td>
							<?php endif; ?>
							<td class="column-payments">
								<?php
								$total = sprintf(
									'<b>%1$s</b> (%3$s %2$s)',
									$num_payments,
									MS_Helper_Billing::format_price( $amount_payments ),
									$currency
								);

								printf(
									'<a href="#" data-ms-dialog="View_Member_Payment" data-ms-data="%1$s">%2$s</a>',
									esc_attr( json_encode( $subscription_info ) ),
									$total
								);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
					</table>
				</div>
				<?php
				MS_Helper_Html::html_element( $inp_id );
				MS_Helper_Html::html_element( $inp_dialog );
				MS_Helper_Html::html_element( $inp_nonce );
				MS_Helper_Html::html_element( $inp_action );
				?>
			</form>
			<div class="buttons">
				<?php
				MS_Helper_Html::html_element( $inp_cancel );
				// MS_Helper_Html::html_element( $inp_save );
				?>
			</div>
		</div>
		<?php
		$html = ob_get_clean();
		return apply_filters( 'ms_view_member_dialog_to_html', $html );
	}

};